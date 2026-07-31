<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
require_once dirname(__DIR__) . '/api/core/user_sync.php';
$pdo = db();
ensure_user_schema($pdo);
$msg = '';
$allowedStatuses = array_keys(order_status_options());
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = (int)($_POST['order_id'] ?? 0);
    $newStatus = preg_replace('/[^a-z_]/', '', (string)($_POST['status'] ?? ''));
    if ($id > 0 && in_array($newStatus, $allowedStatuses, true)) {
        $extra = '';
        if ($newStatus === 'shipping') $extra = ',shipped_at=NOW()';
        if ($newStatus === 'completed') $extra = ',completed_at=NOW()';
        if ($newStatus === 'pending_ship') $extra = ',paid_at=IF(paid_at IS NULL,NOW(),paid_at)';
        $pdo->prepare("UPDATE orders SET status=?$extra WHERE id=?")->execute([$newStatus, $id]);
        admin_audit('修改', '订单管理', '订单#' . $id . ' 状态调整为「' . order_status_label($newStatus) . '」');
        $msg = '订单状态已更新';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_apply'])) {
    $ids = array_values(array_filter(array_map('intval', (array)($_POST['order_ids'] ?? []))));
    $newStatus = preg_replace('/[^a-z_]/', '', (string)($_POST['batch_status'] ?? ''));
    if ($ids && in_array($newStatus, $allowedStatuses, true)) {
        $extra = '';
        if ($newStatus === 'shipping') $extra = ',shipped_at=NOW()';
        if ($newStatus === 'completed') $extra = ',completed_at=NOW()';
        if ($newStatus === 'pending_ship') $extra = ',paid_at=IF(paid_at IS NULL,NOW(),paid_at)';
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE orders SET status=?$extra WHERE id IN ($ph)")->execute(array_merge([$newStatus], $ids));
        admin_audit('修改', '订单管理', '批量调整 ' . count($ids) . ' 个订单为「' . order_status_label($newStatus) . '」');
        $msg = '已批量更新 ' . count($ids) . ' 个订单';
    }
}
$status = trim($_GET['status'] ?? '');
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = max(10, min(100, (int)($_GET['ps'] ?? 20)));
$where = ['1=1'];
$params = [];
if ($status !== '') { $where[] = 'o.status=?'; $params[] = preg_replace('/[^a-z_]/', '', $status); }
if ($q !== '') {
    $where[] = '(o.order_no LIKE ? OR u.phone LIKE ? OR u.nickname LIKE ? OR o.address_name LIKE ? OR o.address_phone LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}
$whereSql = implode(' AND ', $where);
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportStmt = $pdo->prepare("SELECT o.order_no,u.nickname,u.phone,o.pay_amount,o.pay_type,o.status,o.address_name,o.address_phone,o.address_detail,o.created_at FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE $whereSql ORDER BY o.id DESC");
    $exportStmt->execute($params);
    $exportRows = $exportStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($exportRows as &$er) { $er['status'] = order_status_label((string)$er['status']); }
    unset($er);
    admin_csv_download('orders.csv',
        ['订单号','用户','手机','金额','支付','状态','收货人','收货电话','收货地址','下单时间'],
        ['order_no','nickname','phone','pay_amount','pay_type','status','address_name','address_phone','address_detail','created_at'],
        $exportRows);
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$offset = ($page - 1) * $pageSize;
$listStmt = $pdo->prepare("SELECT o.*,u.nickname,u.phone FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE $whereSql ORDER BY o.id DESC LIMIT $pageSize OFFSET $offset");
$listStmt->execute($params);
$rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);
$exportQs = http_build_query(array_filter(['status' => $status, 'q' => $q, 'export' => 'csv']));
admin_layout_start('订单管理', 'orders.php');
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="get" class="form-grid" style="margin-bottom:12px"><div class="form-row"><label>搜索</label><div class="field"><input name="q" value="' . htmlspecialchars($q) . '" placeholder="订单号 / 用户 / 收货人 / 手机"><input type="hidden" name="status" value="' . htmlspecialchars($status) . '"></div></div>';
echo '<p style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn btn-sm" type="submit">查询</button> <a class="btn btn-sm btn-secondary" href="orders.php">重置</a> <a class="btn btn-sm" href="?' . htmlspecialchars($exportQs) . '">导出 CSV（全部符合条件）</a></p></form><p>';
foreach (array_merge(['' => '全部'], order_status_options()) as $k => $v) {
    $sel = ($status===$k) ? 'btn' : 'btn btn-secondary';
    echo '<a class="' . $sel . ' btn-sm" style="margin-right:6px" href="?status=' . urlencode($k) . '&q=' . urlencode($q) . '">' . $v . '</a> ';
}
echo '</p><form method="post" id="orders-batch-form"><div class="batch-bar" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px">';
echo '<select name="batch_status" class="field" style="padding:6px 10px">';
foreach (order_status_options() as $sk => $sl) {
    echo '<option value="' . htmlspecialchars($sk) . '">' . htmlspecialchars($sl) . '</option>';
}
echo '</select><button class="btn btn-sm" type="submit" name="batch_apply" value="1" onclick="return confirm(\'确认批量调整选中订单？\')">批量调整状态</button></div>';
echo '<table><tr><th class="col-check"><input type="checkbox" onclick="document.querySelectorAll(\'.order-chk\').forEach(function(c){c.checked=this.checked;}.bind(this))"></th><th>订单号</th><th>用户</th><th>用户手机</th><th>收货人</th><th>收货电话</th><th>收货地址</th><th>金额</th><th>支付</th><th>状态</th><th>时间</th><th>操作</th></tr>';
foreach ($rows as $r) {
    echo '<tr><td class="col-check"><input class="order-chk" type="checkbox" name="order_ids[]" value="' . (int)$r['id'] . '"></td>';
    echo '<td>' . htmlspecialchars($r['order_no']) . '</td>';
    echo '<td>' . htmlspecialchars($r['nickname']??'') . '</td><td>' . htmlspecialchars($r['phone']??'') . '</td>';
    echo '<td>' . htmlspecialchars($r['address_name']??'') . '</td><td>' . htmlspecialchars($r['address_phone']??'') . '</td>';
    echo '<td style="max-width:180px;font-size:12px">' . htmlspecialchars($r['address_detail']??'') . '</td>';
    echo '<td>¥' . $r['pay_amount'] . '</td><td>' . htmlspecialchars($r['pay_type']??'') . '</td>';
    echo '<td>' . htmlspecialchars(order_status_label($r['status'])) . '</td><td>' . htmlspecialchars($r['created_at']) . '</td><td>';
    echo '<select form="order-status-' . (int)$r['id'] . '" name="status" style="font-size:12px;padding:2px 4px">';
    foreach (order_status_options() as $sk => $sl) {
        $sel = ($r['status'] === $sk) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($sk) . '"' . $sel . '>' . htmlspecialchars($sl) . '</option>';
    }
    echo '</select> <button class="btn btn-sm" type="submit" form="order-status-' . (int)$r['id'] . '">保存</button>';
    echo '</td></tr>';
}
echo '</table></form>';
foreach ($rows as $r) {
    echo '<form method="post" id="order-status-' . (int)$r['id'] . '" style="display:none"><input type="hidden" name="update_status" value="1"><input type="hidden" name="order_id" value="' . (int)$r['id'] . '"></form>';
}
admin_pagination($total, $page, $pageSize, 'orders.php?status=' . urlencode($status) . '&q=' . urlencode($q));
echo '</div>';
admin_layout_end();

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
$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = max(10, min(100, (int)($_GET['ps'] ?? 20)));
$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(u.phone LIKE ? OR u.nickname LIKE ? OR c.name LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($status === '0' || $status === '1') { $where[] = 'uc.status=?'; $params[] = (int)$status; }
$whereSql = implode(' AND ', $where);
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportStmt = $pdo->prepare("SELECT uc.id,u.nickname,u.phone,c.name AS coupon_name,c.value,uc.status,uc.created_at FROM user_coupons uc JOIN users u ON uc.user_id=u.id JOIN coupons c ON uc.coupon_id=c.id WHERE $whereSql ORDER BY uc.id DESC");
    $exportStmt->execute($params);
    $exportRows = $exportStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($exportRows as &$er) { $er['status'] = ((int)$er['status']===0?'未使用':'已使用'); }
    unset($er);
    admin_csv_download('user_coupons.csv', ['ID','用户','手机','优惠券','面额','状态','领取时间'], ['id','nickname','phone','coupon_name','value','status','created_at'], $exportRows);
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_coupons uc JOIN users u ON uc.user_id=u.id JOIN coupons c ON uc.coupon_id=c.id WHERE $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$offset = ($page - 1) * $pageSize;
$listStmt = $pdo->prepare("SELECT uc.*,u.nickname,u.phone,c.name AS coupon_name,c.value,c.min_amount FROM user_coupons uc JOIN users u ON uc.user_id=u.id JOIN coupons c ON uc.coupon_id=c.id WHERE $whereSql ORDER BY uc.id DESC LIMIT $pageSize OFFSET $offset");
$listStmt->execute($params);
$rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);
admin_layout_start('用户领券记录', 'user_coupons.php');
echo '<div class="card"><form method="get" class="form-grid"><div class="form-row"><label>搜索</label><div class="field"><input name="q" value="' . htmlspecialchars($q) . '" placeholder="用户手机 / 昵称 / 券名"></div></div>';
echo '<div class="form-row"><label>状态</label><div class="field"><select name="status"><option value="">全部</option><option value="0"' . ($status==='0'?' selected':'') . '>未使用</option><option value="1"' . ($status==='1'?' selected':'') . '>已使用</option></select></div></div>';
echo '<p><button class="btn btn-sm" type="submit">查询</button> <a class="btn btn-sm" href="?' . htmlspecialchars(http_build_query(['q'=>$q,'status'=>$status,'export'=>'csv'])) . '">导出 CSV（全部符合条件）</a></p></form>';
echo '<table><tr><th>ID</th><th>用户</th><th>手机</th><th>优惠券</th><th>面额</th><th>状态</th><th>领取时间</th></tr>';
foreach ($rows as $r) {
    echo '<tr><td>' . (int)$r['id'] . '</td><td>' . htmlspecialchars($r['nickname']??'') . '</td><td>' . htmlspecialchars($r['phone']??'') . '</td>';
    echo '<td>' . htmlspecialchars($r['coupon_name']) . '</td><td>¥' . $r['value'] . '</td>';
    echo '<td>' . ((int)$r['status']===0?'未使用':'已使用') . '</td><td>' . htmlspecialchars($r['created_at']) . '</td></tr>';
}
echo '</table>';
admin_pagination($total, $page, $pageSize, 'user_coupons.php?q=' . urlencode($q) . '&status=' . urlencode($status));
echo '</div>';
admin_layout_end();

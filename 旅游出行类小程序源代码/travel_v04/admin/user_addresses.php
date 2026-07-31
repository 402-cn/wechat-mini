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
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = max(10, min(100, (int)($_GET['ps'] ?? 20)));
$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(u.phone LIKE ? OR u.nickname LIKE ? OR a.name LIKE ? OR a.phone LIKE ? OR a.detail LIKE ?)';
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like,$like,$like,$like,$like]);
}
$whereSql = implode(' AND ', $where);
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportStmt = $pdo->prepare("SELECT u.nickname,u.phone AS user_phone,a.name,a.phone,a.detail,a.is_default,a.created_at FROM user_addresses a JOIN users u ON a.user_id=u.id WHERE $whereSql ORDER BY a.id DESC");
    $exportStmt->execute($params);
    $exportRows = $exportStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($exportRows as &$er) { $er['is_default'] = ((int)$er['is_default']===1?'是':'否'); }
    unset($er);
    admin_csv_download('user_addresses.csv', ['用户','用户手机','收货人','收货电话','地址','默认','创建时间'], ['nickname','user_phone','name','phone','detail','is_default','created_at'], $exportRows);
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_addresses a JOIN users u ON a.user_id=u.id WHERE $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$offset = ($page - 1) * $pageSize;
$listStmt = $pdo->prepare("SELECT a.*,u.nickname,u.phone AS user_phone FROM user_addresses a JOIN users u ON a.user_id=u.id WHERE $whereSql ORDER BY a.id DESC LIMIT $pageSize OFFSET $offset");
$listStmt->execute($params);
$rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);
admin_layout_start('收货地址', 'user_addresses.php');
echo '<div class="card"><form method="get"><input name="q" value="' . htmlspecialchars($q) . '" placeholder="搜索用户/收货人/地址" style="width:280px;padding:6px 10px"> <button class="btn btn-sm" type="submit">查询</button> <a class="btn btn-sm" href="?' . htmlspecialchars(http_build_query(['q'=>$q,'export'=>'csv'])) . '">导出 CSV</a></form>';
echo '<table style="margin-top:12px"><tr><th>用户</th><th>手机</th><th>收货人</th><th>联系电话</th><th>地址</th><th>默认</th><th>时间</th></tr>';
foreach ($rows as $r) {
    echo '<tr><td>' . htmlspecialchars($r['nickname']??'') . '</td><td>' . htmlspecialchars($r['user_phone']??'') . '</td>';
    echo '<td>' . htmlspecialchars($r['name']) . '</td><td>' . htmlspecialchars($r['phone']) . '</td>';
    echo '<td>' . htmlspecialchars($r['detail']) . '</td><td>' . ((int)$r['is_default']?'是':'') . '</td><td>' . htmlspecialchars($r['created_at']) . '</td></tr>';
}
echo '</table>';
admin_pagination($total, $page, $pageSize, 'user_addresses.php?q=' . urlencode($q));
echo '</div>';
admin_layout_end();

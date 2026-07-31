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
    $where[] = '(inv.nickname LIKE ? OR inv.phone LIKE ? OR ie.nickname LIKE ? OR ie.phone LIKE ? OR ui.invite_code LIKE ?)';
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like,$like,$like,$like,$like]);
}
$whereSql = implode(' AND ', $where);
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportStmt = $pdo->prepare("SELECT inv.nickname AS inviter_name,inv.phone AS inviter_phone,ie.nickname AS invitee_name,ie.phone AS invitee_phone,ui.invite_code,ui.points_reward,ui.created_at FROM user_invites ui JOIN users inv ON ui.inviter_id=inv.id JOIN users ie ON ui.invitee_id=ie.id WHERE $whereSql ORDER BY ui.id DESC");
    $exportStmt->execute($params);
    admin_csv_download('user_invites.csv', ['邀请人','邀请人手机','被邀请人','被邀请人手机','邀请码','奖励积分','时间'], ['inviter_name','inviter_phone','invitee_name','invitee_phone','invite_code','points_reward','created_at'], $exportStmt->fetchAll(PDO::FETCH_ASSOC));
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_invites ui JOIN users inv ON ui.inviter_id=inv.id JOIN users ie ON ui.invitee_id=ie.id WHERE $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$offset = ($page - 1) * $pageSize;
$listStmt = $pdo->prepare("SELECT ui.*,inv.nickname AS inviter_name,inv.phone AS inviter_phone,ie.nickname AS invitee_name,ie.phone AS invitee_phone FROM user_invites ui JOIN users inv ON ui.inviter_id=inv.id JOIN users ie ON ui.invitee_id=ie.id WHERE $whereSql ORDER BY ui.id DESC LIMIT $pageSize OFFSET $offset");
$listStmt->execute($params);
$rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);
admin_layout_start('邀请记录', 'user_invites.php');
echo '<div class="card"><form method="get"><input name="q" value="' . htmlspecialchars($q) . '" placeholder="邀请人/被邀请人/邀请码" style="width:280px;padding:6px 10px"> <button class="btn btn-sm" type="submit">查询</button> <a class="btn btn-sm" href="?' . htmlspecialchars(http_build_query(['q'=>$q,'export'=>'csv'])) . '">导出 CSV</a></form>';
echo '<table style="margin-top:12px"><tr><th>邀请人</th><th>被邀请人</th><th>邀请码</th><th>奖励积分</th><th>时间</th></tr>';
foreach ($rows as $r) {
    echo '<tr><td>' . htmlspecialchars($r['inviter_name']??'') . ' ' . htmlspecialchars($r['inviter_phone']??'') . '</td>';
    echo '<td>' . htmlspecialchars($r['invitee_name']??'') . ' ' . htmlspecialchars($r['invitee_phone']??'') . '</td>';
    echo '<td>' . htmlspecialchars($r['invite_code']) . '</td><td>' . (int)$r['points_reward'] . '</td><td>' . htmlspecialchars($r['created_at']) . '</td></tr>';
}
echo '</table>';
admin_pagination($total, $page, $pageSize, 'user_invites.php?q=' . urlencode($q));
echo '</div>';
admin_layout_end();

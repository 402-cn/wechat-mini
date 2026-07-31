<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$pdo = db();
$user = try_user($pdo);
if (!$user) json_ok(['logged_in' => false, 'invite_code' => '', 'invite_count' => 0, 'invite_points' => 0, 'records' => [], 'reward_inviter' => 50, 'reward_invitee' => 20]);
$uid = (int)$user['id'];
$code = ensure_user_invite_code($pdo, $uid);
$cntStmt = $pdo->prepare('SELECT COUNT(*) FROM user_invites WHERE inviter_id=?');
$cntStmt->execute([$uid]);
$count = (int)$cntStmt->fetchColumn();
$ptsStmt = $pdo->prepare('SELECT COALESCE(SUM(points_reward),0) FROM user_invites WHERE inviter_id=?');
$ptsStmt->execute([$uid]);
$points = (int)$ptsStmt->fetchColumn();
$recStmt = $pdo->prepare('SELECT ui.created_at,ui.points_reward,u.nickname,u.phone FROM user_invites ui LEFT JOIN users u ON ui.invitee_id=u.id WHERE ui.inviter_id=? ORDER BY ui.id DESC LIMIT 20');
$recStmt->execute([$uid]);
$records = $recStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
json_ok([
    'logged_in' => true,
    'invite_code' => $code,
    'invite_count' => $count,
    'invite_points' => $points,
    'records' => $records,
    'reward_inviter' => 50,
    'reward_invitee' => 20,
]);

<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$pdo = db();
user_session_start();
$uid = user_current_id();
if ($uid <= 0) json_ok(['logged_in' => false]);
$stmt = $pdo->prepare('SELECT id,username,openid,nickname,avatar,phone,balance,points,deposit,member_level,login_type,status FROM users WHERE id=? AND status=1 LIMIT 1');
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) json_ok(['logged_in' => false]);
json_ok(['logged_in' => true, 'user' => user_public($user)]);

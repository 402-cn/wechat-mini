<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    user_session_start();
    $uid = user_current_id();
    if ($uid <= 0) json_ok(['logged_in' => false]);
    $stmt = $pdo->prepare('SELECT id,username,openid,nickname,avatar,phone,balance,points,deposit,member_level,login_type FROM users WHERE id=? AND status=1 LIMIT 1');
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) json_ok(['logged_in' => false]);
    json_ok(['logged_in' => true, 'user' => user_public($user)]);
}
$user = require_user($pdo);
$data = get_json_input();
$nickname = trim((string)($data['nickname'] ?? ''));
$phone = trim((string)($data['phone'] ?? ''));
$avatar = trim((string)($data['avatar'] ?? ''));
$curNick = (string)($user['nickname'] ?? '');
$curPhone = (string)($user['phone'] ?? '');
$curAvatar = (string)($user['avatar'] ?? '');
if ($nickname !== '') $curNick = $nickname;
if ($phone !== '') $curPhone = $phone;
if ($avatar !== '') $curAvatar = $avatar;
if ($curNick === '') json_error('昵称不能为空');
$pdo->prepare('UPDATE users SET nickname=?, phone=?, avatar=? WHERE id=?')->execute([$curNick, $curPhone, $curAvatar, (int)$user['id']]);
$stmt = $pdo->prepare('SELECT id,username,openid,nickname,avatar,phone,balance,points,deposit,member_level,login_type FROM users WHERE id=? LIMIT 1');
$stmt->execute([(int)$user['id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
json_ok(['message' => '已保存', 'user' => user_public($row ?: [])]);

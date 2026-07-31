<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$data = get_json_input();
$phone = trim((string)($data['phone'] ?? ''));
$password = (string)($data['password'] ?? '');
$stmt = db()->prepare('SELECT * FROM users WHERE username = ? AND status = 1');
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || !password_verify($password, $user['password'])) json_error('账号或密码错误');
unset($user['password']);
user_set_session((int)$user['id']);
db()->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([(int)$user['id']]);
json_ok(['user' => user_public($user), 'session_id' => session_id()], '登录成功');

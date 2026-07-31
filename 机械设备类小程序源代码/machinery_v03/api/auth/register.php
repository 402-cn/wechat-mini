<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
user_session_start();
$data = get_json_input();
$captcha = trim((string)($data['captcha'] ?? ''));
if ($captcha === '' || empty($_SESSION['captcha']) || strcasecmp($captcha, $_SESSION['captcha']) !== 0) {
    json_error('验证码错误');
}
unset($_SESSION['captcha']);
$phone = trim((string)($data['phone'] ?? ''));
$password = (string)($data['password'] ?? '');
$nickname = trim((string)($data['nickname'] ?? ''));
if ($phone === '' || $password === '') json_error('手机号和密码不能为空');
$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = db()->prepare('INSERT INTO users (username, password, phone, nickname, login_type) VALUES (?, ?, ?, ?, 1)');
try {
    $stmt->execute([$phone, $hash, $phone, $nickname ?: $phone]);
    $uid = (int)db()->lastInsertId();
    user_set_session($uid);
    ensure_user_schema(db());
    gift_register_coupon(db(), $uid);
    bind_invite_code(db(), $uid, trim((string)($data['invite_code'] ?? '')));
    $u = db()->prepare('SELECT id,username,openid,nickname,avatar,phone,balance,points,deposit,member_level,login_type FROM users WHERE id=?');
    $u->execute([$uid]);
    $user = $u->fetch(PDO::FETCH_ASSOC);
    json_ok(['id' => $uid, 'user' => user_public($user ?: [])], '注册成功，新人券已发放');
} catch (Throwable $e) { json_error('注册失败，手机号可能已存在'); }

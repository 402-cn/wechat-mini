<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$data = get_json_input();
$code = trim((string)($data['code'] ?? ''));
$profileNick = trim((string)($data['nickname'] ?? ''));
$profileAvatar = trim((string)($data['avatar'] ?? ''));
if ($code === '') json_error('code 不能为空');
$cfg = $GLOBALS['app_config']['wechat'] ?? [];
$appId = trim((string)($cfg['app_id'] ?? ''));
$secret = trim((string)($cfg['app_secret'] ?? ''));
if ($appId === '' || $secret === '') {
    json_error('未配置小程序 AppID/AppSecret，请在 install.php 填写或编辑 config/config.inc.php 中 wechat.app_id / wechat.app_secret', 400);
}
$url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . urlencode($appId) . '&secret=' . urlencode($secret) . '&js_code=' . urlencode($code) . '&grant_type=authorization_code';
$resp = json_decode((string)@file_get_contents($url), true);
if (!is_array($resp)) json_error('微信接口无响应，请检查服务器能否访问 api.weixin.qq.com');
if (empty($resp['openid'])) {
    $msg = (string)($resp['errmsg'] ?? '微信登录失败');
    if (!empty($resp['errcode'])) $msg .= ' (errcode:' . $resp['errcode'] . ')';
    json_error($msg, 400);
}
$openid = $resp['openid'];
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM users WHERE openid = ?');
$stmt->execute([$openid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    $nick = $profileNick !== '' ? $profileNick : '微信用户';
    $pdo->prepare('INSERT INTO users (openid, nickname, avatar, login_type) VALUES (?, ?, ?, 2)')->execute([$openid, $nick, $profileAvatar]);
    $newId = (int)$pdo->lastInsertId();
    ensure_user_schema($pdo);
    gift_register_coupon($pdo, $newId);
    $stmt->execute([$openid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($profileNick !== '' || $profileAvatar !== '') {
    $nick = $profileNick !== '' ? $profileNick : (string)($user['nickname'] ?? '微信用户');
    $av = $profileAvatar !== '' ? $profileAvatar : (string)($user['avatar'] ?? '');
    $pdo->prepare('UPDATE users SET nickname=?, avatar=? WHERE id=?')->execute([$nick, $av, (int)$user['id']]);
    $stmt->execute([$openid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
unset($user['password']);
user_set_session((int)$user['id']);
$pdo->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([(int)$user['id']]);
json_ok(['user' => user_public($user), 'session_id' => session_id()], '登录成功');

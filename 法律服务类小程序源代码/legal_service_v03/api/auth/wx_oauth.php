<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$cfg = $GLOBALS['app_config']['wechat'] ?? [];
$appId = trim((string)($cfg['app_id'] ?? ''));
$secret = trim((string)($cfg['app_secret'] ?? ''));
if ($appId === '' || $secret === '') json_error('未配置微信公众号 AppID');
$action = (string)($_GET['action'] ?? '');
if ($action === 'url') {
    $redirect = trim((string)($_GET['redirect'] ?? ''));
    if ($redirect === '') $redirect = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/#mine';
    $cb = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . dirname($_SERVER['SCRIPT_NAME']) . '/wx_oauth.php';
    $state = rtrim(strtr(base64_encode($redirect), '+/', '-_'), '=');
    $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . urlencode($appId)
        . '&redirect_uri=' . urlencode($cb)
        . '&response_type=code&scope=snsapi_userinfo&state=' . urlencode($state) . '#wechat_redirect';
    json_ok(['url' => $url]);
}
$code = trim((string)($_GET['code'] ?? ''));
if ($code === '') json_error('缺少 code');
$stateRaw = (string)($_GET['state'] ?? '');
$stateRaw = strtr($stateRaw, '-_', '+/');
$pad = strlen($stateRaw) % 4;
if ($pad) $stateRaw .= str_repeat('=', 4 - $pad);
$back = base64_decode($stateRaw) ?: ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/#mine');
$tokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . urlencode($appId)
    . '&secret=' . urlencode($secret) . '&code=' . urlencode($code) . '&grant_type=authorization_code';
$tokenResp = json_decode((string)@file_get_contents($tokenUrl), true);
if (empty($tokenResp['openid'])) json_error('微信授权失败');
$openid = (string)$tokenResp['openid'];
$access = (string)($tokenResp['access_token'] ?? '');
$nickname = '微信用户';
$avatar = '';
if ($access !== '') {
    $info = json_decode((string)@file_get_contents('https://api.weixin.qq.com/sns/userinfo?access_token=' . urlencode($access) . '&openid=' . urlencode($openid) . '&lang=zh_CN'), true);
    if (!empty($info['nickname'])) $nickname = (string)$info['nickname'];
    if (!empty($info['headimgurl'])) $avatar = (string)$info['headimgurl'];
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM users WHERE openid = ? LIMIT 1');
$stmt->execute([$openid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    $pdo->prepare('INSERT INTO users (openid, nickname, avatar, login_type) VALUES (?, ?, ?, 2)')->execute([$openid, $nickname, $avatar]);
    $newId = (int)$pdo->lastInsertId();
    ensure_user_schema($pdo);
    gift_register_coupon($pdo, $newId);
    $stmt->execute([$openid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $pdo->prepare('UPDATE users SET nickname=?, avatar=?, last_login_at=NOW() WHERE id=?')->execute([$nickname, $avatar, (int)$user['id']]);
}
user_set_session((int)$user['id']);
header('Location: ' . $back);
exit;

<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';

if (is_file(__DIR__ . '/../core/user_sync.php')) require_once __DIR__ . '/../core/user_sync.php';
function interaction_user_id(): int {
    return function_exists('user_current_id') ? user_current_id() : 0;
}
function interaction_visitor_key(): string {
    $body = json_decode(file_get_contents('php://input'), true);
    if (is_array($body) && !empty($body['visitor_key'])) return trim((string)$body['visitor_key']);
    $vk = trim((string)($_GET['visitor_key'] ?? ''));
    if ($vk !== '') return $vk;
    return 'v_' . substr(md5(($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 16);
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$type = trim((string)($body['target_type'] ?? ''));
$id = (int)($body['target_id'] ?? 0);
if (!in_array($type, ['product', 'article'], true) || $id <= 0) json_error('参数错误');
$uid = interaction_user_id();
$vk = interaction_visitor_key();
$pdo = db();
$pdo->prepare('INSERT INTO user_footprints (user_id,visitor_key,target_type,target_id,viewed_at) VALUES (?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE viewed_at=NOW()')->execute([$uid, $vk, $type, $id]);
json_ok(['recorded' => true]);

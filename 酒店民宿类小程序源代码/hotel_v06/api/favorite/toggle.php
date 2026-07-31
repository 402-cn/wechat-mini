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
$stmt = $pdo->prepare('SELECT id FROM user_favorites WHERE user_id=? AND visitor_key=? AND target_type=? AND target_id=? LIMIT 1');
$stmt->execute([$uid, $vk, $type, $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $pdo->prepare('DELETE FROM user_favorites WHERE id=?')->execute([(int)$row['id']]);
    json_ok(['favorited' => false]);
}
$pdo->prepare('INSERT INTO user_favorites (user_id,visitor_key,target_type,target_id) VALUES (?,?,?,?)')->execute([$uid, $vk, $type, $id]);
json_ok(['favorited' => true]);

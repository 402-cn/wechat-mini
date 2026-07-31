<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';

if (is_file(__DIR__ . '/../core/user_sync.php')) require_once __DIR__ . '/../core/user_sync.php';
function widget_user_id(): int {
    return function_exists('user_current_id') ? user_current_id() : 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('请使用 POST');
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;
$instanceId = trim($data['instance_id'] ?? '');
$fields = $data['fields'] ?? [];
if ($instanceId === '' || !is_array($fields)) json_error('参数错误');
$pdo = db();
$stmt = $pdo->prepare('SELECT props_json FROM widget_instances WHERE instance_id = ? AND component_type = ? AND status = 1 LIMIT 1');
$stmt->execute([$instanceId, 'messageBoard']);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) json_error('留言组件不存在', 404);
$props = json_decode($row['props_json'] ?? '{}', true) ?: [];
$userId = 0;
$nickname = trim($fields['nickname'] ?? $fields['name'] ?? '访客');
if (!empty($props['requireLogin'])) {
    $uid = widget_user_id();
    if ($uid <= 0) json_error('请先登录', 401);
    $userId = $uid;
}
$ins = $pdo->prepare('INSERT INTO message_submissions (instance_id,user_id,nickname,fields_json) VALUES (?,?,?,?)');
$ins->execute([$instanceId, $userId, $nickname, json_encode($fields, JSON_UNESCAPED_UNICODE)]);
json_ok(['message' => '提交成功，我们会尽快处理']);

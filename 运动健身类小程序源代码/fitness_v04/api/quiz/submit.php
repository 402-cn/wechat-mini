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
$answers = $data['answers'] ?? [];
if ($instanceId === '' || !is_array($answers)) json_error('参数错误');
$pdo = db();
$stmt = $pdo->prepare('SELECT props_json FROM widget_instances WHERE instance_id = ? AND component_type = ? AND status = 1 LIMIT 1');
$stmt->execute([$instanceId, 'quiz']);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) json_error('答题组件不存在', 404);
$props = json_decode($row['props_json'] ?? '{}', true) ?: [];
$uid = widget_user_id();
if (!empty($props['requireLogin']) && $uid <= 0) json_error('请先登录', 401);
$maxAttempts = (int)($props['maxAttempts'] ?? 0);
if ($maxAttempts > 0 && $uid > 0) {
    $c = $pdo->prepare('SELECT COUNT(*) FROM quiz_submissions WHERE instance_id = ? AND user_id = ?');
    $c->execute([$instanceId, $uid]);
    if ((int)$c->fetchColumn() >= $maxAttempts) json_error('已达最大答题次数');
}
$items = $pdo->prepare('SELECT item_json FROM widget_items WHERE instance_id = ? AND status = 1 ORDER BY sort_order,id');
$items->execute([$instanceId]);
$questions = [];
foreach ($items->fetchAll(PDO::FETCH_ASSOC) as $it) {
    $questions[] = json_decode($it['item_json'] ?? '{}', true) ?: [];
}
$score = 0;
$total = count($questions);
foreach ($questions as $i => $q) {
    $ans = trim($answers[$i] ?? $answers[(string)$i] ?? '');
    $correct = trim($q['answer'] ?? '');
    if ($correct !== '' && strcasecmp(str_replace(' ', '', $ans), str_replace(' ', '', $correct)) === 0) $score++;
}
$ins = $pdo->prepare('INSERT INTO quiz_submissions (instance_id,user_id,score,total,answers_json) VALUES (?,?,?,?,?)');
$ins->execute([$instanceId, $uid, $score, $total, json_encode($answers, JSON_UNESCAPED_UNICODE)]);
$pass = (int)($props['passScore'] ?? 60);
$passed = $total > 0 && ($score * 100 / $total) >= $pass;
json_ok(['score' => $score, 'total' => $total, 'passed' => $passed]);

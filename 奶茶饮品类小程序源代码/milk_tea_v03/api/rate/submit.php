<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';

if (is_file(__DIR__ . '/../core/user_sync.php')) require_once __DIR__ . '/../core/user_sync.php';
function widget_user_id(): int {
    return function_exists('user_current_id') ? user_current_id() : 0;
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$instanceId = trim((string)($body['instance_id'] ?? ''));
$score = max(1, min(5, (int)($body['score'] ?? 0)));
$visitorKey = trim((string)($body['visitor_key'] ?? ''));
if ($instanceId === '' || $score <= 0) json_error('参数错误');
$pdo = db();
$stmt = $pdo->prepare('SELECT props_json FROM widget_instances WHERE instance_id = ? AND component_type = ? AND status = 1 LIMIT 1');
$stmt->execute([$instanceId, 'rate']);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) json_error('评分组件不存在', 404);
$props = json_decode($row['props_json'] ?? '{}', true) ?: [];
$uid = widget_user_id();
if ($visitorKey === '') {
    $visitorKey = 'v_' . substr(md5(($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($instanceId)), 0, 16);
}
$oldScore = 0;
$voteStmt = $pdo->prepare('SELECT score FROM rate_votes WHERE instance_id=? AND user_id=? AND visitor_key=? LIMIT 1');
$voteStmt->execute([$instanceId, $uid, $visitorKey]);
$oldRow = $voteStmt->fetch(PDO::FETCH_ASSOC);
$count = (int)($props['count'] ?? 0);
$totalScore = (float)($props['totalScore'] ?? 0);
if ($oldRow) {
    $oldScore = (int)$oldRow['score'];
    $totalScore = max(0, $totalScore - $oldScore + $score);
    $pdo->prepare('UPDATE rate_votes SET score=? WHERE instance_id=? AND user_id=? AND visitor_key=?')->execute([$score, $instanceId, $uid, $visitorKey]);
} else {
    $count++;
    $totalScore += $score;
    $pdo->prepare('INSERT INTO rate_votes (instance_id,user_id,visitor_key,score) VALUES (?,?,?,?)')->execute([$instanceId, $uid, $visitorKey, $score]);
}
$avg = $count > 0 ? round($totalScore / $count, 1) : 0;
$props['count'] = $count;
$props['totalScore'] = $totalScore;
$props['score'] = $avg;
$pdo->prepare('UPDATE widget_instances SET props_json=? WHERE instance_id=?')->execute([json_encode($props, JSON_UNESCAPED_UNICODE), $instanceId]);
$targetType = trim((string)($body['target_type'] ?? ($props['targetType'] ?? '')));
$targetId = (int)($body['target_id'] ?? ($props['targetId'] ?? 0));
if ($targetType === '') $targetType = trim((string)($props['targetType'] ?? ''));
if ($targetId <= 0) $targetId = (int)($props['targetId'] ?? 0);
if ($uid > 0 && in_array($targetType, ['product', 'article'], true) && $targetId > 0) {
    if ($score >= 5) {
        $pdo->prepare('INSERT INTO user_hobbies (user_id,visitor_key,target_type,target_id,score) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE score=VALUES(score)')->execute([$uid, $visitorKey, $targetType, $targetId, $score]);
    } else {
        $pdo->prepare('DELETE FROM user_hobbies WHERE user_id=? AND visitor_key=? AND target_type=? AND target_id=?')->execute([$uid, $visitorKey, $targetType, $targetId]);
    }
}
json_ok(['score' => $avg, 'count' => $count, 'showCount' => $props['showCount'] ?? true, 'maxScore' => $props['maxScore'] ?? 5, 'allowUserRate' => $props['allowUserRate'] ?? true]);

<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';

if (is_file(__DIR__ . '/../core/user_sync.php')) require_once __DIR__ . '/../core/user_sync.php';
function widget_user_id(): int {
    return function_exists('user_current_id') ? user_current_id() : 0;
}

$instanceId = trim($_GET['instance_id'] ?? '');
if ($instanceId === '') json_error('instance_id 不能为空');
$pdo = db();
$stmt = $pdo->prepare('SELECT props_json FROM widget_instances WHERE instance_id = ? AND component_type = ? AND status = 1 LIMIT 1');
$stmt->execute([$instanceId, 'checkinActivity']);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) json_error('打卡活动不存在', 404);
$props = json_decode($row['props_json'] ?? '{}', true) ?: [];
$uid = widget_user_id();
$today = date('Y-m-d');
$checkedToday = false;
$streak = 0;
$total = 0;
if ($uid > 0) {
    $s = $pdo->prepare('SELECT COUNT(*) FROM checkin_records WHERE instance_id = ? AND user_id = ?');
    $s->execute([$instanceId, $uid]);
    $total = (int)$s->fetchColumn();
    $s = $pdo->prepare('SELECT streak FROM checkin_records WHERE instance_id = ? AND user_id = ? AND checkin_date = ? LIMIT 1');
    $s->execute([$instanceId, $uid, $today]);
    $r = $s->fetch(PDO::FETCH_ASSOC);
    if ($r) { $checkedToday = true; $streak = (int)$r['streak']; }
    else {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $s = $pdo->prepare('SELECT streak FROM checkin_records WHERE instance_id = ? AND user_id = ? AND checkin_date = ? LIMIT 1');
        $s->execute([$instanceId, $uid, $yesterday]);
        $yr = $s->fetch(PDO::FETCH_ASSOC);
        if ($yr) $streak = (int)$yr['streak'];
    }
}
json_ok(['checkedToday' => $checkedToday, 'streak' => $streak, 'total' => $total, 'props' => $props, 'userId' => $uid]);

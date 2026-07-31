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
if ($instanceId === '') json_error('instance_id 不能为空');
$pdo = db();
$stmt = $pdo->prepare('SELECT props_json FROM widget_instances WHERE instance_id = ? AND component_type = ? AND status = 1 LIMIT 1');
$stmt->execute([$instanceId, 'checkinActivity']);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) json_error('打卡活动不存在', 404);
$props = json_decode($row['props_json'] ?? '{}', true) ?: [];
$uid = widget_user_id();
$rewardPoints = (int)($props['rewardPoints'] ?? 0);
$rewardCoupon = !empty($props['rewardCoupon']);
if ($uid <= 0 && !empty($props['requireLogin'])) json_error('请先登录', 401);
if ($uid <= 0) $uid = 0;
$today = date('Y-m-d');
$chk = $pdo->prepare('SELECT id FROM checkin_records WHERE instance_id = ? AND user_id = ? AND checkin_date = ? LIMIT 1');
$chk->execute([$instanceId, $uid, $today]);
if ($chk->fetch()) json_error('今日已打卡');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$streak = 1;
if ($uid > 0) {
    $s = $pdo->prepare('SELECT streak FROM checkin_records WHERE instance_id = ? AND user_id = ? AND checkin_date = ? LIMIT 1');
    $s->execute([$instanceId, $uid, $yesterday]);
    if ($yr = $s->fetch(PDO::FETCH_ASSOC)) $streak = (int)$yr['streak'] + 1;
}
$ins = $pdo->prepare('INSERT INTO checkin_records (instance_id,user_id,checkin_date,streak) VALUES (?,?,?,?)');
$ins->execute([$instanceId, $uid, $today, $streak]);
$msg = '打卡成功';
$bonus = 0;
if ($uid > 0 && $rewardPoints > 0 && function_exists('points_change')) {
    points_change($pdo, $uid, $rewardPoints, 'checkin', 'instance_id', 0, '每日打卡奖励');
    $msg .= '，获得' . $rewardPoints . '积分';
}
$bonus7 = (int)($props['streakBonus7'] ?? 0);
$bonus30 = (int)($props['streakBonus30'] ?? 0);
if ($uid > 0 && $streak === 7 && $bonus7 > 0 && function_exists('points_change')) {
    points_change($pdo, $uid, $bonus7, 'checkin_bonus', 'streak', 7, '连续7天奖励');
    $bonus += $bonus7;
}
if ($uid > 0 && $streak === 30 && $bonus30 > 0 && function_exists('points_change')) {
    points_change($pdo, $uid, $bonus30, 'checkin_bonus', 'streak', 30, '连续30天奖励');
    $bonus += $bonus30;
}
if ($bonus > 0) $msg .= '，里程碑奖励+' . $bonus . '积分';
if ($uid > 0 && $rewardCoupon) {
    $cp = $pdo->query('SELECT id FROM coupons WHERE status=1 ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if ($cp) {
        $exist = $pdo->prepare('SELECT id FROM user_coupons WHERE user_id=? AND coupon_id=? LIMIT 1');
        $exist->execute([$uid, (int)$cp['id']]);
        if (!$exist->fetch()) {
            $pdo->prepare('INSERT INTO user_coupons (user_id,coupon_id) VALUES (?,?)')->execute([$uid, (int)$cp['id']]);
            $msg .= '，优惠券已发放';
        }
    }
}
json_ok(['message' => $msg, 'streak' => $streak]);

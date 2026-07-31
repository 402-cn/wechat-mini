<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$pdo = db();
$user = require_user($pdo);
$data = get_json_input();
$levelId = (int)($data['level_id'] ?? 6);
$payType = trim((string)($data['pay_type'] ?? 'balance'));
$deductAmount = (float)($data['deduct_amount'] ?? 99);
$deductPoints = (int)($data['deduct_points'] ?? 999);
$stmt = $pdo->prepare('SELECT id,name,min_points,discount FROM member_levels WHERE id=? AND status=1 LIMIT 1');
$stmt->execute([$levelId]);
$level = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$level) json_error('会员等级不存在');
$curLevel = (int)($user['member_level'] ?? 0);
if ($curLevel >= $levelId) json_error('您已是该等级或更高等级会员');
$points = (int)($user['points'] ?? 0);
$minPoints = (int)$level['min_points'];
if ($minPoints > 0 && $points >= $minPoints && $payType !== 'points') {
    $pdo->prepare('UPDATE users SET member_level=? WHERE id=?')->execute([$levelId, (int)$user['id']]);
    json_ok(['message' => '积分达标，已升级为' . $level['name']]);
}
try {
    if ($payType === 'points') {
        if ($deductPoints <= 0) $deductPoints = max(1, $minPoints);
        points_change($pdo, (int)$user['id'], -$deductPoints, 'vip_open', 'member_level', $levelId, '开通' . $level['name']);
    } else {
        if ($deductAmount <= 0) $deductAmount = 99.0;
        wallet_change($pdo, (int)$user['id'], -$deductAmount, 'vip_open', 'member_level', $levelId, '开通' . $level['name']);
    }
    $pdo->prepare('UPDATE users SET member_level=? WHERE id=?')->execute([$levelId, (int)$user['id']]);
    json_ok(['message' => '开通成功，已升级为' . $level['name']]);
} catch (Throwable $e) {
    json_error($e->getMessage());
}

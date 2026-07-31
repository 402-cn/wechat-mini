<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$pdo = db();
$user = require_user($pdo);
$data = get_json_input();
$couponId = (int)($data['coupon_id'] ?? 0);
if ($couponId <= 0) json_error('参数错误');
$c = $pdo->prepare('SELECT * FROM coupons WHERE id=? AND status=1 LIMIT 1');
$c->execute([$couponId]);
$cp = $c->fetch(PDO::FETCH_ASSOC);
if (!$cp) json_error('优惠券不存在');
if (!empty($cp['start_at']) && strtotime((string)$cp['start_at']) > time()) json_error('领取尚未开始');
if (!empty($cp['end_at']) && strtotime((string)$cp['end_at']) < time()) json_error('领取已截止');
if ((int)$cp['total_count'] > 0 && (int)$cp['used_count'] >= (int)$cp['total_count']) json_error('已领完');
$claimType = (string)($cp['claim_type'] ?? 'all');
if ($claimType === 'new_user') {
    $createdAt = strtotime((string)($user['created_at'] ?? ''));
    if ($createdAt <= 0 || (time() - $createdAt) > 7 * 86400) json_error('仅新注册用户可领取');
} elseif ($claimType === 'spend') {
    $minSpend = (float)($cp['claim_min_spend'] ?? 0);
    $spentStmt = $pdo->prepare("SELECT COALESCE(SUM(pay_amount),0) FROM orders WHERE user_id=? AND status IN ('pending_ship','shipping','completed','pending_review')");
    $spentStmt->execute([(int)$user['id']]);
    $spent = (float)$spentStmt->fetchColumn();
    if ($spent < $minSpend) json_error('累计消费满 ¥' . $minSpend . ' 才可领取');
}
$exist = $pdo->prepare('SELECT id FROM user_coupons WHERE user_id=? AND coupon_id=? LIMIT 1');
$exist->execute([(int)$user['id'], $couponId]);
if ($exist->fetch()) json_error('已领取过');
$pdo->prepare('INSERT INTO user_coupons (user_id,coupon_id) VALUES (?,?)')->execute([(int)$user['id'], $couponId]);
$pdo->prepare('UPDATE coupons SET used_count=used_count+1 WHERE id=?')->execute([$couponId]);
json_ok(['message' => '领取成功']);

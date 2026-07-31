<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$pdo = db();
user_session_start();
$uid = user_current_id();
$demo = [
    'user' => ['id'=>0,'nickname'=>'','avatar'=>'','phone'=>'','openid'=>'','balance'=>0,'points'=>0,'deposit'=>0,'member_level'=>0,'member_level_name'=>'普通会员','login_type'=>2],
    'order_counts' => ['pending_pay'=>0,'pending_ship'=>0,'shipping'=>0,'completed'=>0,'pending_review'=>0],
    'coupon_count' => 0, 'logged_in' => false,
    'member_levels' => [], 'benefits' => ['专属折扣','新品上市','满减券','果蔬礼盒','生日礼包'],
];
if ($uid <= 0) {
    $demo['member_levels'] = $pdo->query('SELECT id,name,min_points,discount,benefits FROM member_levels WHERE status=1 ORDER BY sort_order ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    json_ok($demo);
}
$stmt = $pdo->prepare('SELECT id,username,openid,nickname,avatar,phone,balance,points,deposit,member_level,login_type,status FROM users WHERE id=? AND status=1 LIMIT 1');
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    unset($_SESSION['user_id']);
    $demo['member_levels'] = $pdo->query('SELECT id,name,min_points,discount,benefits FROM member_levels WHERE status=1 ORDER BY sort_order ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    json_ok($demo);
}
$levels = $pdo->query('SELECT id,name,min_points,discount,benefits FROM member_levels WHERE status=1 ORDER BY sort_order ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
json_ok([
    'logged_in' => true,
    'user' => user_public($user),
    'order_counts' => order_counts($pdo, (int)$user['id']),
    'coupon_count' => user_coupon_count($pdo, (int)$user['id']),
    'member_levels' => $levels,
    'benefits' => ['专属折扣','新品上市','满减券','果蔬礼盒','生日礼包'],
]);

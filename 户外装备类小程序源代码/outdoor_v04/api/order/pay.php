<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
require_once __DIR__ . '/../core/product_sync.php';
$pdo = db();
$user = require_user($pdo);
$data = get_json_input();
$orderId = (int)($data['order_id'] ?? 0);
$payType = trim((string)($data['pay_type'] ?? 'balance'));
if ($orderId <= 0) json_error('参数错误');
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id=? AND user_id=? LIMIT 1');
$stmt->execute([$orderId, (int)$user['id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) json_error('订单不存在');
if ($order['status'] !== 'pending_pay') json_error('订单状态不可支付');
$payAmount = (float)$order['pay_amount'];
if ($payType === 'balance') {
    $pdo->beginTransaction();
    try {
        $stockErr = product_deduct_order_items($pdo, $orderId);
        if ($stockErr) throw new RuntimeException($stockErr);
        wallet_change($pdo, (int)$user['id'], -$payAmount, 'pay', 'order', $orderId, '余额支付订单' . $order['order_no']);
        $pdo->prepare("UPDATE orders SET status='pending_ship',pay_type='balance',paid_at=NOW() WHERE id=?")->execute([$orderId]);
        points_change($pdo, (int)$user['id'], (int)floor($payAmount), 'order_reward', 'order', $orderId, '下单赠送积分');
        if ((int)$order['coupon_id'] > 0) {
            $pdo->prepare('UPDATE user_coupons SET status=1,used_at=NOW(),order_id=? WHERE id=?')->execute([$orderId, (int)$order['coupon_id']]);
        }
        $pdo->commit();
        json_ok(['message' => '支付成功', 'status' => 'pending_ship']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        json_error($e->getMessage());
    }
}
if ($payType === 'wechat') {
    if (empty($user['openid'])) json_error('微信用户才能使用微信支付');
    $wx = wx_unified_order($order['order_no'], $payAmount, $user['openid'], '商城订单');
    if (!empty($wx['error'])) json_error($wx['error']);
    $pdo->prepare('INSERT INTO wx_pay_orders (order_id,order_no,prepay_id,pay_amount,status) VALUES (?,?,?,?,0)')->execute([$orderId, $order['order_no'], $wx['prepay_id'], $payAmount]);
    $pdo->prepare("UPDATE orders SET pay_type='wechat' WHERE id=?")->execute([$orderId]);
    json_ok(['payment' => $wx['payment'], 'order_id' => $orderId]);
}
json_error('不支持的支付方式');

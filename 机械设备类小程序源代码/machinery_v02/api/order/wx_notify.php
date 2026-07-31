<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
require_once __DIR__ . '/../core/product_sync.php';
$raw = file_get_contents('php://input');
$x = @simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA);
if (!$x || (string)$x->return_code !== 'SUCCESS' || (string)$x->result_code !== 'SUCCESS') {
    echo '<xml><return_code><![CDATA[FAIL]]></return_code></xml>'; exit;
}
$orderNo = (string)$x->out_trade_no;
$transId = (string)$x->transaction_id;
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE order_no=? LIMIT 1');
$stmt->execute([$orderNo]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if ($order && $order['status'] === 'pending_pay') {
    $pdo->beginTransaction();
    try {
        $stockErr = product_deduct_order_items($pdo, (int)$order['id']);
        if ($stockErr) throw new RuntimeException($stockErr);
        $pdo->prepare("UPDATE orders SET status='pending_ship',paid_at=NOW() WHERE id=?")->execute([(int)$order['id']]);
        $pdo->prepare('UPDATE wx_pay_orders SET status=1,transaction_id=?,paid_at=NOW() WHERE order_id=?')->execute([$transId, (int)$order['id']]);
        points_change($pdo, (int)$order['user_id'], (int)floor((float)$order['pay_amount']), 'order_reward', 'order', (int)$order['id'], '微信下单赠送积分');
        if ((int)$order['coupon_id'] > 0) {
            $pdo->prepare('UPDATE user_coupons SET status=1,used_at=NOW(),order_id=? WHERE id=?')->execute([(int)$order['id'], (int)$order['coupon_id']]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
}
echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';

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
if ($orderId <= 0) json_error('参数错误');
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id=? AND user_id=? LIMIT 1');
$stmt->execute([$orderId, (int)$user['id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) json_error('订单不存在');
if (!in_array($order['status'], ['pending_pay','pending_ship'], true)) json_error('当前状态不可取消');
$pdo->beginTransaction();
try {
    if ($order['status'] === 'pending_ship') {
        if ($order['pay_type'] === 'balance') {
            wallet_change($pdo, (int)$user['id'], (float)$order['pay_amount'], 'refund', 'order', $orderId, '取消订单退款');
        }
        product_restore_order_items($pdo, $orderId);
    }
    $pdo->prepare("UPDATE orders SET status='cancelled' WHERE id=?")->execute([$orderId]);
    $pdo->commit();
    json_ok(['message' => '已取消']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_error($e->getMessage());
}

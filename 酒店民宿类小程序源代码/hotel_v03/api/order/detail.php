<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$pdo = db();
$user = require_user($pdo);
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) json_error('参数错误');
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id=? AND user_id=? LIMIT 1');
$stmt->execute([$id, (int)$user['id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) json_error('订单不存在', 404);
$order['status_label'] = order_status_label($order['status']);
$order['coupon_name'] = '';
$order['coupon_value'] = 0;
if ((int)$order['coupon_id'] > 0) {
    $cs = $pdo->prepare('SELECT c.name,c.value FROM user_coupons uc JOIN coupons c ON uc.coupon_id=c.id WHERE uc.id=? LIMIT 1');
    $cs->execute([(int)$order['coupon_id']]);
    if ($cp = $cs->fetch(PDO::FETCH_ASSOC)) {
        $order['coupon_name'] = (string)$cp['name'];
        $order['coupon_value'] = (float)$cp['value'];
    }
}
$is = $pdo->prepare('SELECT * FROM order_items WHERE order_id=?');
$is->execute([$id]);
$order['items'] = $is->fetchAll(PDO::FETCH_ASSOC);
if (trim((string)($order['address_name'] ?? '')) === '' && trim((string)($order['address_phone'] ?? '')) === '' && trim((string)($order['address_detail'] ?? '')) === '') {
    $addrStmt = $pdo->prepare('SELECT name, phone, detail FROM user_addresses WHERE user_id=? ORDER BY is_default DESC, id DESC LIMIT 1');
    $addrStmt->execute([(int)$user['id']]);
    if ($addr = $addrStmt->fetch(PDO::FETCH_ASSOC)) {
        if (trim((string)$order['address_name']) === '') $order['address_name'] = (string)$addr['name'];
        if (trim((string)$order['address_phone']) === '') $order['address_phone'] = (string)$addr['phone'];
        if (trim((string)$order['address_detail']) === '') $order['address_detail'] = (string)$addr['detail'];
    }
}
json_ok($order);

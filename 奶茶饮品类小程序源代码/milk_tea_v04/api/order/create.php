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
$fromCart = (int)($data['from_cart'] ?? 1) === 1;
$couponId = (int)($data['user_coupon_id'] ?? 0);
$addrName = trim((string)($data['address_name'] ?? ''));
$addrPhone = trim((string)($data['address_phone'] ?? ''));
$addrDetail = trim((string)($data['address_detail'] ?? ''));
$remark = trim((string)($data['remark'] ?? ''));
$items = [];
if ($fromCart) {
    $stmt = $pdo->prepare('SELECT c.product_id,c.quantity,p.name,p.image,p.price,p.stock FROM cart_items c JOIN products p ON c.product_id=p.id WHERE c.user_id=? AND c.selected=1 AND p.status=1');
    $stmt->execute([(int)$user['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $pid = (int)($data['product_id'] ?? 0);
    $qty = max(1, (int)($data['quantity'] ?? 1));
    if ($pid > 0) {
        $s = $pdo->prepare('SELECT id AS product_id,name,image,price,stock FROM products WHERE id=? AND status=1 LIMIT 1');
        $s->execute([$pid]);
        $p = $s->fetch(PDO::FETCH_ASSOC);
        if ($p) { $p['quantity'] = $qty; $items[] = $p; }
    }
}
if (!$items) json_error('请选择商品');
$stockErr = product_validate_order_items($pdo, $items);
if ($stockErr) json_error($stockErr);
$total = 0;
foreach ($items as $it) $total += (float)$it['price'] * (int)$it['quantity'];
$discount = 0;
$couponRef = 0;
$couponName = '';
if ($couponId > 0) {
    $cs = $pdo->prepare('SELECT uc.id,uc.coupon_id,c.value,c.min_amount,c.name FROM user_coupons uc JOIN coupons c ON uc.coupon_id=c.id WHERE uc.id=? AND uc.user_id=? AND uc.status=0 AND c.status=1 LIMIT 1');
    $cs->execute([$couponId, (int)$user['id']]);
    $cp = $cs->fetch(PDO::FETCH_ASSOC);
    if ($cp && $total >= (float)$cp['min_amount']) {
        $discount = min((float)$cp['value'], $total);
        $couponRef = (int)$cp['id'];
        $couponName = (string)$cp['name'];
    }
} else {
    $cs = $pdo->prepare('SELECT uc.id,uc.coupon_id,c.value,c.min_amount,c.name FROM user_coupons uc JOIN coupons c ON uc.coupon_id=c.id WHERE uc.user_id=? AND uc.status=0 AND c.status=1 AND c.min_amount <= ? ORDER BY c.value DESC LIMIT 1');
    $cs->execute([(int)$user['id'], $total]);
    $cp = $cs->fetch(PDO::FETCH_ASSOC);
    if ($cp) {
        $discount = min((float)$cp['value'], $total);
        $couponRef = (int)$cp['id'];
        $couponName = (string)$cp['name'];
    }
}
$payAmount = round(max(0, $total - $discount), 2);
$orderNo = order_no_new();
$pdo->beginTransaction();
try {
    $pdo->prepare('INSERT INTO orders (order_no,user_id,status,total_amount,discount_amount,pay_amount,coupon_id,address_name,address_phone,address_detail,remark) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([$orderNo, (int)$user['id'], 'pending_pay', $total, $discount, $payAmount, $couponRef, $addrName, $addrPhone, $addrDetail, $remark]);
    $orderId = (int)$pdo->lastInsertId();
    $ins = $pdo->prepare('INSERT INTO order_items (order_id,product_id,product_name,product_image,price,quantity) VALUES (?,?,?,?,?,?)');
    foreach ($items as $it) {
        $ins->execute([$orderId, (int)$it['product_id'], $it['name'], $it['image'], (float)$it['price'], (int)$it['quantity']]);
    }
    if ($fromCart) {
        $pdo->prepare('DELETE c FROM cart_items c JOIN products p ON c.product_id=p.id WHERE c.user_id=? AND c.selected=1')->execute([(int)$user['id']]);
    }
    $pdo->commit();
    json_ok(['order_id' => $orderId, 'order_no' => $orderNo, 'pay_amount' => $payAmount, 'total_amount' => $total, 'discount_amount' => $discount, 'coupon_name' => $couponName]);
} catch (Throwable $e) {
    $pdo->rollBack();
    json_error('创建订单失败');
}

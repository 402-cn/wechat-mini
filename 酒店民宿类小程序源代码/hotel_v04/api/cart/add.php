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
$pid = (int)($data['product_id'] ?? 0);
$qty = max(1, (int)($data['quantity'] ?? 1));
if ($pid <= 0) json_error('参数错误');
$chk = $pdo->prepare('SELECT id, stock FROM products WHERE id=? AND status=1 LIMIT 1');
$chk->execute([$pid]);
$prod = $chk->fetch(PDO::FETCH_ASSOC);
if (!$prod) json_error('商品不存在或已下架');
$exist = $pdo->prepare('SELECT id,quantity FROM cart_items WHERE user_id=? AND product_id=? LIMIT 1');
$exist->execute([(int)$user['id'], $pid]);
$row = $exist->fetch(PDO::FETCH_ASSOC);
$newQty = $row ? (int)$row['quantity'] + $qty : $qty;
if (!product_stock_available((int)$prod['stock'], $newQty)) json_error('库存不足');
if ($row) {
    $pdo->prepare('UPDATE cart_items SET quantity=?,selected=1 WHERE id=?')->execute([$newQty, (int)$row['id']]);
} else {
    $pdo->prepare('INSERT INTO cart_items (user_id,product_id,quantity,selected) VALUES (?,?,?,1)')->execute([(int)$user['id'], $pid, $qty]);
}
json_ok([], '加入购物车成功');

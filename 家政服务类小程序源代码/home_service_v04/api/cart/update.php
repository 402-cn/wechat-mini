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
$id = (int)($data['id'] ?? 0);
$qty = max(1, (int)($data['quantity'] ?? 1));
$selected = isset($data['selected']) ? ((int)$data['selected'] ? 1 : 0) : null;
if ($id <= 0) json_error('参数错误');
$row = $pdo->prepare('SELECT c.product_id, p.stock FROM cart_items c JOIN products p ON c.product_id=p.id WHERE c.id=? AND c.user_id=? LIMIT 1');
$row->execute([$id, (int)$user['id']]);
$cartRow = $row->fetch(PDO::FETCH_ASSOC);
if (!$cartRow) json_error('购物车条目不存在');
if (!product_stock_available((int)$cartRow['stock'], $qty)) json_error('库存不足');
$sql = 'UPDATE cart_items SET quantity=?';
$params = [$qty];
if ($selected !== null) { $sql .= ',selected=?'; $params[] = $selected; }
$sql .= ' WHERE id=? AND user_id=?';
$params[] = $id; $params[] = (int)$user['id'];
$pdo->prepare($sql)->execute($params);
json_ok(['message' => '已更新']);

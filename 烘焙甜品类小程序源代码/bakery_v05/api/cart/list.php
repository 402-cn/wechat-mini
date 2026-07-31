<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$pdo = db();
$user = try_user($pdo);
if (!$user) json_ok(['logged_in' => false, 'list' => [], 'selected_total' => 0]);
$stmt = $pdo->prepare('SELECT c.id,c.product_id,c.quantity,c.selected,p.name,p.image,p.price,p.status FROM cart_items c JOIN products p ON c.product_id=p.id WHERE c.user_id=? ORDER BY c.id DESC');
$stmt->execute([(int)$user['id']]);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = 0;
foreach ($list as &$row) {
    $row['id'] = (int)$row['id'];
    $row['product_id'] = (int)$row['product_id'];
    $row['quantity'] = (int)$row['quantity'];
    $row['selected'] = (int)$row['selected'];
    $row['price'] = (float)$row['price'];
    if ((int)$row['selected'] === 1 && (int)$row['status'] === 1) $total += $row['price'] * $row['quantity'];
}
json_ok(['logged_in' => true, 'list' => $list, 'selected_total' => round($total, 2)]);

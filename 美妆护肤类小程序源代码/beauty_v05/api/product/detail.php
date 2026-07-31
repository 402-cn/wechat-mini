<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/product_sync.php';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) json_error('参数错误');
$pdo = db();
ensure_demo_products($pdo);
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND status = 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) json_error('商品不存在', 404);
$pdo->prepare('UPDATE products SET view_count = view_count + 1 WHERE id = ?')->execute([$id]);
$row['view_count'] = (int)($row['view_count'] ?? 0) + 1;
if (isset($row['image'])) $row['image'] = product_frontend_image((string)$row['image']);
json_ok($row);

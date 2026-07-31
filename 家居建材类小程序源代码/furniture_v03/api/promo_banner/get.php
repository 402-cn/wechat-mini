<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/product_sync.php';
$pdo = db();
ensure_demo_products($pdo);
$id = trim((string)($_GET['id'] ?? ''));
if ($id === '') json_error('id 不能为空');
ensure_promo_banner_widget_row($pdo, $id);
$stmt = $pdo->prepare('SELECT * FROM promo_banner_widgets WHERE instance_id = ? AND status = 1 LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) json_error('组件不存在', 404);
$props = [
    'title' => $row['title'] ?? '',
    'subtitle' => $row['subtitle'] ?? '',
    'bannerImage' => $row['banner_image'] ?? '',
    'bannerBgColor' => $row['banner_bg_color'] ?? '#e8f5e9',
    'columns' => (int)($row['columns'] ?? 2),
    'rows' => (int)($row['row_count'] ?? 2),
];
$orderIds = [];
$raw = $row['product_ids'] ?? '';
if ($raw !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        foreach ($decoded as $oid) {
            if (is_numeric($oid)) $orderIds[] = (int)$oid;
        }
    }
}
$layout = $props['layout'] ?? 'grid';
$rows = max(1, (int)($props['rows'] ?? 3));
if ($layout === 'list' || $layout === 'row') {
    $limit = $rows;
} else {
    $limit = max(1, (int)($props['columns'] ?? 2) * $rows);
}
$list = [];
if ($orderIds) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $sql = "SELECT id, category_id, name, image, price, description, is_demo, stock, is_flash_sale, flash_stock, flash_end_at FROM products WHERE status = 1 AND id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($orderIds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $r) { $map[(string)$r['id']] = $r; }
    foreach ($orderIds as $oid) {
        if (isset($map[(string)$oid])) $list[] = $map[(string)$oid];
    }
    $list = array_slice($list, 0, $limit);
} else {
    $stmt = $pdo->prepare('SELECT id, category_id, name, image, price, description, is_demo, stock, is_flash_sale, flash_stock, flash_end_at FROM products WHERE status = 1 ORDER BY RAND() LIMIT ?');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
product_normalize_row_images($list);
json_ok(['props' => $props, 'products' => $list]);

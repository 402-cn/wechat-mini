<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/product_sync.php';
$pdo = db();
ensure_demo_products($pdo);
$componentId = trim((string)($_GET['component_id'] ?? ''));
$limit = max(1, min(30, (int)($_GET['limit'] ?? 6)));
$itemCount = $limit;
$orderIds = [];
if ($componentId !== '') {
    ensure_product_scroll_widget_row($pdo, $componentId);
    backfill_widget_product_ids($pdo, 'product_scroll_widgets', $componentId, 6);
    $w = $pdo->prepare('SELECT product_ids, item_count FROM product_scroll_widgets WHERE instance_id = ? AND status = 1 LIMIT 1');
    $w->execute([$componentId]);
    $widget = $w->fetch(PDO::FETCH_ASSOC);
    if ($widget) {
        $itemCount = max(1, min(30, (int)$widget['item_count']));
        $raw = $widget['product_ids'];
        if ($raw !== null && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $id) {
                    if (is_numeric($id)) $orderIds[] = (int)$id;
                }
            }
        }
    }
}
$list = [];
if ($orderIds) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $sql = "SELECT id, category_id, name, image, price, description, is_demo, stock, is_flash_sale, flash_stock, flash_end_at FROM products WHERE status = 1 AND id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($orderIds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $row) { $map[(string)$row['id']] = $row; }
    foreach ($orderIds as $oid) {
        if (isset($map[(string)$oid])) $list[] = $map[(string)$oid];
    }
    $list = array_slice($list, 0, $itemCount);
} elseif ($componentId !== '') {
    $list = [];
} else {
    $stmt = $pdo->prepare('SELECT id, category_id, name, image, price, description, is_demo, stock, is_flash_sale, flash_stock, flash_end_at FROM products WHERE status = 1 ORDER BY RAND() LIMIT ?');
    $stmt->bindValue(1, $itemCount, PDO::PARAM_INT);
    $stmt->execute();
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
product_normalize_row_images($list);
json_ok(['list' => $list, 'item_count' => $itemCount]);

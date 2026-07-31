<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/product_sync.php';
$pdo = db();
$demoIds = ensure_demo_products($pdo);
sync_product_widgets_featured_ids($pdo, $demoIds);
$keyword = trim((string)($_GET['keyword'] ?? ''));
$categoryId = (int)($_GET['category_id'] ?? 0);
$componentId = trim((string)($_GET['component_id'] ?? ''));
$featuredOnly = (int)($_GET['featured'] ?? 0);
$limit = max(1, min(50, (int)($_GET['limit'] ?? 50)));
$page = (int)($_GET['page'] ?? 0);
$pageSize = max(1, min(20, (int)($_GET['page_size'] ?? 20)));
$paging = $page > 0;
$useFeaturedFilter = false;
$orderIds = [];
$showLimit = 0;
if ($componentId !== '') {
    ensure_product_widget_row($pdo, $componentId);
    backfill_widget_product_ids($pdo, 'product_widgets', $componentId, 6);
    $w = $pdo->prepare('SELECT product_ids, featured_ids, show_limit FROM product_widgets WHERE instance_id = ? AND status = 1 LIMIT 1');
    $w->execute([$componentId]);
    $widget = $w->fetch(PDO::FETCH_ASSOC);
    if ($widget) {
        $showLimit = max(1, (int)$widget['show_limit']);
        $pidRaw = $widget['product_ids'];
        if ($pidRaw === null || $pidRaw === '' || $pidRaw === '[]') {
            $pidRaw = $widget['featured_ids'];
        }
        if ($pidRaw !== null && $pidRaw !== '') {
            $decoded = json_decode($pidRaw, true);
            if (is_array($decoded)) {
                $useFeaturedFilter = true;
                foreach ($decoded as $id) {
                    if (is_numeric($id)) $orderIds[] = (int)$id;
                }
            }
        }
        if (!$useFeaturedFilter) $featuredOnly = 0;
    }
}
if ($useFeaturedFilter && empty($orderIds)) {
    $useFeaturedFilter = false;
    $featuredOnly = 0;
}
if ($useFeaturedFilter) {
    $list = [];
    if ($orderIds) {
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $sql = "SELECT id, category_id, name, image, price, description, is_demo, stock, is_flash_sale, flash_stock, flash_end_at FROM products WHERE status = 1 AND id IN ($placeholders)";
        $params = $orderIds;
        if ($keyword !== '') { $sql .= ' AND name LIKE ?'; $params[] = '%' . $keyword . '%'; }
        if ($categoryId > 0) { $sql .= ' AND category_id = ?'; $params[] = $categoryId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $row) { $map[(string)$row['id']] = $row; }
        foreach ($orderIds as $oid) {
            if (isset($map[(string)$oid])) $list[] = $map[(string)$oid];
        }
    }
    if ($paging) {
        $total = count($list);
        $offset = ($page - 1) * $pageSize;
        $list = array_slice($list, $offset, $pageSize);
        $hasMore = ($offset + count($list)) < $total;
    } else {
        $list = array_slice($list, 0, $limit);
        $total = count($list);
        $hasMore = false;
    }
} else {
    $where = 'WHERE status = 1';
    $params = [];
    if ($featuredOnly === 1) { $where .= ' AND is_featured = 1'; }
    if ($keyword !== '') { $where .= ' AND name LIKE ?'; $params[] = '%' . $keyword . '%'; }
    if ($categoryId > 0) { $where .= ' AND category_id = ?'; $params[] = $categoryId; }
    if ($paging) {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $offset = ($page - 1) * $pageSize;
        $sql = "SELECT id, category_id, name, image, price, description, is_demo, stock, is_flash_sale, flash_stock, flash_end_at FROM products $where ORDER BY sort_order DESC, id DESC LIMIT $pageSize OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hasMore = ($offset + count($list)) < $total;
    } else {
        $sql = "SELECT id, category_id, name, image, price, description, is_demo, stock, is_flash_sale, flash_stock, flash_end_at FROM products $where ORDER BY sort_order DESC, id DESC LIMIT " . $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = count($list);
        $hasMore = false;
    }
}
product_normalize_row_images($list);
$resp = ['list' => $list, 'demo_hint' => '演示商品可在后台编辑或删除'];
if ($paging) {
    $resp['page'] = $page;
    $resp['page_size'] = $pageSize;
    $resp['total'] = $total;
    $resp['has_more'] = $hasMore;
}
if ($showLimit > 0 && !$paging) $resp['show_limit'] = $showLimit;
json_ok($resp);

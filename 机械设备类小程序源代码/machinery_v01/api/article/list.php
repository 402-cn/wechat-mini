<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/article_sync.php';
$pdo = db();
$demoIds = ensure_demo_articles($pdo);
sync_article_widgets_featured_ids($pdo, $demoIds);
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
$widgetLabel = '';
$widgetShowMore = 1;
if ($componentId !== '') {
    ensure_article_widget_row($pdo, $componentId);
    $w = $pdo->prepare('SELECT featured_ids, show_limit, label, show_more FROM article_widgets WHERE instance_id = ? AND status = 1 LIMIT 1');
    $w->execute([$componentId]);
    $widget = $w->fetch(PDO::FETCH_ASSOC);
    if ($widget) {
        $showLimit = max(1, (int)$widget['show_limit']);
        $widgetLabel = (string)($widget['label'] ?? '');
        $widgetShowMore = (int)($widget['show_more'] ?? 1);
        $featuredRaw = $widget['featured_ids'];
        if ($featuredRaw !== null && $featuredRaw !== '') {
            $decoded = json_decode($featuredRaw, true);
            if (is_array($decoded)) {
                $useFeaturedFilter = true;
                foreach ($decoded as $id) {
                    if (is_numeric($id)) $orderIds[] = (int)$id;
                }
            }
        }
        if (!$useFeaturedFilter) $featuredOnly = 1;
    }
}
if ($useFeaturedFilter) {
    $list = [];
    if ($orderIds) {
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $sql = "SELECT id, category_id, title, summary, cover, view_count, created_at, is_demo FROM articles WHERE status = 1 AND is_featured = 1 AND id IN ($placeholders)";
        $params = $orderIds;
        if ($keyword !== '') { $sql .= ' AND (title LIKE ? OR summary LIKE ?)'; $params[] = '%' . $keyword . '%'; $params[] = '%' . $keyword . '%'; }
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
    if ($keyword !== '') { $where .= ' AND (title LIKE ? OR summary LIKE ?)'; $params[] = '%' . $keyword . '%'; $params[] = '%' . $keyword . '%'; }
    if ($categoryId > 0) { $where .= ' AND category_id = ?'; $params[] = $categoryId; }
    if ($paging) {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM articles $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $offset = ($page - 1) * $pageSize;
        $sql = "SELECT id, category_id, title, summary, cover, view_count, created_at, is_demo FROM articles $where ORDER BY sort_order DESC, id DESC LIMIT $pageSize OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hasMore = ($offset + count($list)) < $total;
    } else {
        $sql = "SELECT id, category_id, title, summary, cover, view_count, created_at, is_demo FROM articles $where ORDER BY sort_order DESC, id DESC LIMIT " . $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = count($list);
        $hasMore = false;
    }
}
$resp = ['list' => $list, 'demo_hint' => '演示文章可在后台编辑或删除'];
if ($paging) {
    $resp['page'] = $page;
    $resp['page_size'] = $pageSize;
    $resp['total'] = $total;
    $resp['has_more'] = $hasMore;
}
if ($showLimit > 0 && !$paging) $resp['show_limit'] = $showLimit;
if ($componentId !== '' && $widgetLabel !== '') $resp['label'] = $widgetLabel;
if ($componentId !== '') $resp['show_more'] = $widgetShowMore;
json_ok($resp);

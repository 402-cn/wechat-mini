<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
$id = trim($_GET['id'] ?? '');
if ($id === '') json_error('id 不能为空');
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM widget_instances WHERE instance_id = ? AND status = 1 LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) json_error('组件不存在', 404);
$props = json_decode($row['props_json'] ?? '{}', true) ?: [];
$itemsStmt = $pdo->prepare('SELECT item_key, item_json, sort_order FROM widget_items WHERE instance_id = ? AND status = 1 ORDER BY sort_order ASC, id ASC');
$itemsStmt->execute([$id]);
$itemsRaw = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
$items = [];
$children = [];
foreach ($itemsRaw as $it) {
    $data = json_decode($it['item_json'] ?? '{}', true) ?: [];
    if (($it['item_key'] ?? '') === 'child') {
        $children[] = $data;
    } else {
        $items[] = $data;
    }
}
if ($children) $props['children'] = $children;
if ($items) {
    if (($row['component_type'] ?? '') === 'filterBar') {
        $tags = [];
        $dropdowns = [];
        foreach ($items as $it) {
            if (isset($it['text']) && !isset($it['options'])) $tags[] = $it['text'];
            else $dropdowns[] = $it;
        }
        if ($tags) $props['tags'] = $tags;
        if ($dropdowns) $props['dropdowns'] = $dropdowns;
    } else {
        // DB widget_items 优先（PHP 后台编辑后 props_json 内 items 可能仍是 Build 快照）
        $props['items'] = $items;
    }
}
if (($row['component_type'] ?? '') === 'quiz') {
    $props['questions'] = $items;
}
if (($row['component_type'] ?? '') === 'map') {
    $props['markers'] = $items;
}
json_ok([
  'instance_id' => $row['instance_id'],
  'component_type' => $row['component_type'],
  'props' => $props,
  'items' => $items,
]);

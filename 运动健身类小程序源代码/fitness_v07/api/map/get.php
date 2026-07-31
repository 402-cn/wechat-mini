<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
$id = trim($_GET['id'] ?? '');
if ($id === '') json_error('id 不能为空');
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM widget_instances WHERE instance_id = ? AND component_type = ? AND status = 1 LIMIT 1');
$stmt->execute([$id, 'map']);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) json_error('地图不存在', 404);
$props = json_decode($row['props_json'] ?? '{}', true) ?: [];
$items = $pdo->prepare('SELECT item_json FROM widget_items WHERE instance_id = ? AND status = 1 ORDER BY sort_order,id');
$items->execute([$id]);
$markers = [];
foreach ($items->fetchAll(PDO::FETCH_ASSOC) as $it) {
    $markers[] = json_decode($it['item_json'] ?? '{}', true) ?: [];
}
$props['markers'] = $markers;
$cfg = $GLOBALS['app_config'] ?? [];
$props['tencentMapKey'] = $cfg['tencent_map_key'] ?? '';
json_ok(['instance_id' => $id, 'props' => $props, 'markers' => $markers]);

<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
$id = trim($_GET['instance_id'] ?? '');
$pdo = db();
if ($id !== '') {
    $stmt = $pdo->prepare("SELECT * FROM widget_instances WHERE instance_id=? AND component_type='flashSale' AND status=1 LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $row = $pdo->query("SELECT * FROM widget_instances WHERE component_type='flashSale' AND status=1 ORDER BY updated_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}
if (!$row) json_error('秒杀组件不存在', 404);
$props = json_decode($row['props_json'] ?? '{}', true) ?: [];
$ids = $props['productIds'] ?? [];
if (!is_array($ids)) $ids = [];
$clean = [];
foreach ($ids as $v) {
    $v = (int)$v;
    if ($v > 0 && !in_array($v, $clean, true)) $clean[] = $v;
}
$products = [];
if ($clean && $pdo->query("SHOW TABLES LIKE 'products'")->fetch()) {
    $place = implode(',', array_fill(0, count($clean), '?'));
    $s = $pdo->prepare("SELECT id,name,price,image FROM products WHERE status=1 AND id IN ($place)");
    $s->execute($clean);
    $map = [];
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $p) $map[(int)$p['id']] = $p;
    foreach ($clean as $pid) {
        if (isset($map[$pid])) $products[] = $map[$pid];
    }
}
json_ok([
    'instance_id' => $row['instance_id'],
    'title' => $props['title'] ?? '限时秒杀',
    'subtitle' => $props['subtitle'] ?? '',
    'showCountdown' => $props['showCountdown'] ?? true,
    'countdownEnd' => $props['countdownEnd'] ?? '',
    'products' => $products,
]);

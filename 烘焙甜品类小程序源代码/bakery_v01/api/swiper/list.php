<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
$id = trim($_GET['id'] ?? '');
if ($id === '') json_error('id 不能为空');
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM swiper_instances WHERE instance_id = ? AND status = 1 LIMIT 1');
$stmt->execute([$id]);
$inst = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$inst) json_error('轮播不存在', 404);
$items = $pdo->prepare('SELECT image, link, title FROM swiper_items WHERE instance_id = ? AND status = 1 ORDER BY sort_order ASC, id ASC');
$items->execute([$id]);
$list = $items->fetchAll(PDO::FETCH_ASSOC);
foreach ($list as &$row) {
    $raw = trim($row['link'] ?? '');
    if ($raw === '') {
        $row['link'] = ['type' => 'none'];
    } else {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && isset($decoded['type'])) {
            $row['link'] = $decoded;
        } elseif (preg_match('#^https?://#i', $raw)) {
            $row['link'] = ['type' => 'external', 'url' => $raw];
        } else {
            $row['link'] = ['type' => 'internal', 'module' => 'page', 'pageKey' => $raw];
        }
    }
}
unset($row);
json_ok([
  'instance_id' => $inst['instance_id'],
  'height' => (int)$inst['height'],
  'autoplay' => (int)$inst['autoplay'] === 1,
  'interval' => (int)$inst['interval_ms'],
  'items' => $list,
]);

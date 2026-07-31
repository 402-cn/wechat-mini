<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
$id = trim($_GET['id'] ?? '');
if ($id === '') json_error('id 不能为空');
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM notice_instances WHERE instance_id = ? AND status = 1 LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) json_error('公告不存在', 404);
json_ok([
  'instance_id' => $row['instance_id'],
  'content' => $row['content'],
  'textColor' => $row['text_color'],
  'bgColor' => $row['bg_color'],
  'fontSize' => (int)$row['font_size'],
  'scrollDirection' => $row['scroll_direction'],
  'scrollSpeed' => (int)$row['scroll_speed'],
]);

<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
$id = trim($_GET['id'] ?? '');
if ($id === '') json_error('id 不能为空');
$stmt = db()->prepare('SELECT instance_id, content FROM rich_text_blocks WHERE instance_id = ? AND status = 1 LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) json_error('内容不存在', 404);
json_ok(['instance_id' => $row['instance_id'], 'content' => $row['content']]);

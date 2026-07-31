<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/article_sync.php';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) json_error('参数错误');
$pdo = db();
ensure_demo_articles($pdo);
$stmt = $pdo->prepare('SELECT * FROM articles WHERE id = ? AND status = 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) json_error('文章不存在', 404);
$pdo->prepare('UPDATE articles SET view_count = view_count + 1 WHERE id = ?')->execute([$id]);
$row['view_count'] = (int)$row['view_count'] + 1;
json_ok($row);

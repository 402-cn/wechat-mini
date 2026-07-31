<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
$kind = trim($_GET['kind'] ?? '');
$q = trim($_GET['q'] ?? '');
$pdo = db();
$out = [];
if ($kind === 'article' && $pdo->query("SHOW TABLES LIKE 'articles'")->fetch()) {
    $s = $pdo->prepare('SELECT id,title FROM articles WHERE status=1 AND title LIKE ? ORDER BY id DESC LIMIT 20');
    $s->execute(['%' . $q . '%']);
    $out = $s->fetchAll(PDO::FETCH_ASSOC) ?: [];
} elseif ($kind === 'product' && $pdo->query("SHOW TABLES LIKE 'products'")->fetch()) {
    $s = $pdo->prepare('SELECT id,name FROM products WHERE status=1 AND name LIKE ? ORDER BY id DESC LIMIT 20');
    $s->execute(['%' . $q . '%']);
    $out = $s->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
echo json_encode(['code' => 0, 'data' => $out], JSON_UNESCAPED_UNICODE);

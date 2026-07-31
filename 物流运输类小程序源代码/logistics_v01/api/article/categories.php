<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
$pdo = db();
$rows = $pdo->query('SELECT id, name FROM article_categories WHERE status=1 ORDER BY sort_order DESC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
json_ok(['list' => $rows]);

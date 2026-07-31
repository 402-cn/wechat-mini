<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../../../core/bootstrap.php';
require_admin();

$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

$pdo = db();
$total = (int)$pdo->query('SELECT COUNT(*) FROM `software_it_v04_home_contact`')->fetchColumn();
$stmt = $pdo->prepare('SELECT id, `name`, `phone`, `message`, created_at FROM `software_it_v04_home_contact` ORDER BY id DESC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $pageSize, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

json_ok(['list' => $rows, 'total' => $total, 'page' => $page]);

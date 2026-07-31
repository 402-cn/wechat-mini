<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
$pdo = db();
$stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'global_config' LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$cfg = $row ? json_decode($row['setting_value'], true) : [];
json_ok($cfg ?: new stdClass());

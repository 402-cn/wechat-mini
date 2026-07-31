<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/form_display.php';
$formId = trim($_GET['form_id'] ?? '');
if ($formId === '') json_error('参数错误');
$pdo = db();
$placeholders = form_load_placeholders($pdo, $formId);
json_ok(['placeholders' => $placeholders]);

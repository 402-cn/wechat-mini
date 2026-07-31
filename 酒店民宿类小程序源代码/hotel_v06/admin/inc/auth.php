<?php
/**
 * 筑码引擎 www.402.cn
 */

session_start();
require_once dirname(__DIR__, 2) . '/api/core/install_guard.php';
app_require_installed();
require_once __DIR__ . '/rbac.php';
admin_rbac_bootstrap();
if (empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
admin_require_permission();

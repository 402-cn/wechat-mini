<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
user_session_start();
$_SESSION = [];
if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
json_ok(['message' => '已退出登录']);

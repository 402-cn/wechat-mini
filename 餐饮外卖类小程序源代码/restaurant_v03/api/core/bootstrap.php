<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once dirname(__DIR__, 2) . '/config/config.inc.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/Auth.php';

function db(): PDO {
    return Database::getInstance()->getConnection();
}

function get_json_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

if (is_file(__DIR__ . '/install_guard.php')) {
    require_once __DIR__ . '/install_guard.php';
}
if (is_file(__DIR__ . '/widget_sync.php')) {
    require_once __DIR__ . '/widget_sync.php';
    if (function_exists('widget_instances_maybe_sync')) {
        widget_instances_maybe_sync();
    }
}

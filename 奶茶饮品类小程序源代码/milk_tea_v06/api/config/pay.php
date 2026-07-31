<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
json_ok([
    'wx_pay_enabled' => wx_pay_enabled(),
    'balance_enabled' => true,
]);

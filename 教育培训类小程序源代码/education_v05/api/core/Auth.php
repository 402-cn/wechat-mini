<?php
/**
 * 筑码引擎 www.402.cn
 */

function require_admin(): void {
    session_start();
    if (empty($_SESSION['admin_id'])) {
        json_error('未登录', 401);
    }
}

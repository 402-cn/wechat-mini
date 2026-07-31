<?php
/**
 * 筑码引擎 www.402.cn
 */

function app_root_dir(): string {
    return dirname(__DIR__, 2);
}

function app_is_installed(): bool {
    $root = app_root_dir();
    return is_file($root . '/install.lock') && is_file($root . '/config/config.inc.php');
}

function app_install_status(): array {
    $root = app_root_dir();
    return [
        'lock' => is_file($root . '/install.lock'),
        'config' => is_file($root . '/config/config.inc.php'),
        'config_writable' => is_dir($root . '/config') ? is_writable($root . '/config') : is_writable($root),
    ];
}

function app_require_installed(string $redirect = '/install.php?from=admin'): void {
    if (app_is_installed()) {
        return;
    }
    $st = app_install_status();
    if (!empty($_GET['debug_install'])) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>安装状态</title></head><body style="font-family:sans-serif;padding:32px">';
        echo '<h2>站点尚未安装完成</h2><ul>';
        echo '<li>install.lock：' . ($st['lock'] ? '✔ 存在' : '✘ 不存在') . '</li>';
        echo '<li>config/config.inc.php：' . ($st['config'] ? '✔ 存在' : '✘ 不存在') . '</li>';
        echo '<li>config 可写：' . ($st['config_writable'] ? '是' : '否') . '</li>';
        echo '</ul><p><a href="/install.php">前往安装向导</a></p></body></html>';
        exit;
    }
    header('Location: ' . $redirect);
    exit;
}

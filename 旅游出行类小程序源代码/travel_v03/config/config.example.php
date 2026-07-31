<?php
/**
 * 筑码引擎 www.402.cn
 */

// 安装完成后由安装器生成 config.inc.php
// 升级部署时安装器会读取已有配置作为默认值
$app_config = [
    'api_base_url' => 'https://your-domain.com',
    'database' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'your_db',
        'username' => 'root',
        'password' => '',
    ],
    'wechat' => [
        'app_id' => '',
        'app_secret' => '',
    ],
];

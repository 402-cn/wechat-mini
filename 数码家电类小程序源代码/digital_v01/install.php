<?php
/**
 * 筑码引擎 www.402.cn
 */

if (function_exists('opcache_invalidate')) {
    opcache_invalidate(__FILE__, true);
}
// ── 根目录检测：install.php 必须在网站根目录运行 ──
$installReqUri = $_SERVER['REQUEST_URI'] ?? '';
$installReqUri = preg_replace('/\?.*$/', '', $installReqUri);
if ($installReqUri !== '/install.php' && $installReqUri !== '/install.php/') {
    $installBaseUrl = install_detect_base_url_raw();
    $installCorrectUrl = $installBaseUrl . '/install.php';
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>安装错误 - 非根目录</title>';
    echo '<style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f0f2f5;margin:0;padding:24px;color:#333}';
    echo '.wrap{max-width:640px;margin:0 auto;background:#fff;padding:28px 32px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,.06)}';
    echo 'h1{color:#c0392b;font-size:22px;margin:0 0 12px}.tip{color:#666;font-size:14px;line-height:1.8}';
    echo 'code{background:#f4f4f4;padding:2px 6px;border-radius:4px;font-size:13px}';
    echo '.box{padding:14px 16px;border-radius:8px;margin:12px 0;background:#fef0f0;border:1px solid #fbc4c4;color:#c0392b;line-height:1.7;font-size:14px}';
    echo '.box-ok{background:#edfbf3;border:1px solid #b8ebd0;color:#1e8449;padding:14px 16px;border-radius:8px;margin:12px 0;font-size:14px;line-height:1.7}';
    echo 'a{color:#2ecc71;font-weight:600}</style></head><body><div class="wrap">';
    echo '<h1>❌ 安装程序必须在网站根目录运行</h1>';
    echo '<div class="box">当前访问路径：<code>' . htmlspecialchars($installReqUri) . '</code><br>';
    echo '安装程序要求放在网站根目录，即通过 <code>' . htmlspecialchars($installCorrectUrl) . '</code> 访问。</div>';
    echo '<div class="box-ok"><strong>请按以下步骤操作：</strong><br>';
    echo '1. 将整个代码目录的内容移动到网站根目录（不要放在子目录）<br>';
    echo '2. 确保通过 <code>' . htmlspecialchars($installCorrectUrl) . '</code> 访问安装页面<br>';
    echo '3. 如果仍有问题，请检查 Nginx/Apache 的网站根目录配置是否指向代码所在目录</div>';
    echo '<p class="tip">当前路径 <code>' . htmlspecialchars($installReqUri) . '</code> 不在根目录，';
    echo '请将代码移到网站根目录后再访问 <a href="' . htmlspecialchars($installCorrectUrl) . '">' . htmlspecialchars($installCorrectUrl) . '</a></p>';
    echo '</div></body></html>';
    exit;
}
function install_detect_base_url_raw(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}
$configPath = __DIR__ . '/config/config.inc.php';
$lockPath = __DIR__ . '/install.lock';
$configDir = __DIR__ . '/config';
$migrationDir = __DIR__ . '/migrations';
$defaults = file_exists($configPath) ? (require $configPath) : null;

function install_detect_base_url(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function install_val(string $key, $defaults, string $section, string $field, string $fallback = ''): string {
    if (isset($_POST[$key])) return trim((string)$_POST[$key]);
    if (is_array($defaults) && isset($defaults[$section][$field])) return (string)$defaults[$section][$field];
    if (is_array($defaults) && $section === '' && isset($defaults[$field])) return (string)$defaults[$field];
    return $fallback;
}

function install_can_write_config(): bool {
    return install_ensure_config_dir();
}

function install_ensure_config_dir(): bool {
    global $configDir;
    if (is_dir($configDir)) {
        if (is_writable($configDir)) return true;
        @chmod($configDir, 0775);
        return is_writable($configDir);
    }
    if (!@mkdir($configDir, 0755, true)) {
        return false;
    }
    if (!is_writable($configDir)) {
        @chmod($configDir, 0775);
    }
    return is_writable($configDir);
}

function install_config_dir_error(): string {
    global $configDir;
    if (is_dir($configDir)) {
        return 'config 目录已存在，但 PHP 进程无写入权限。请在网站根目录执行：chmod 775 config（仍失败则 chown -R www:www config，www 换成 PHP 运行用户，如 nginx/apache）';
    }
    return '无法创建 config 目录。请在网站根目录执行：mkdir -p config && chmod 775 config';
}

function install_runtime_dir_list(): array {
    global $configDir;
    $root = __DIR__;
    return [
        $configDir,
        $root . '/frontend',
        $root . '/frontend/mp-weixin',
        $root . '/frontend/mp-weixin/utils',
        $root . '/assets/uploads',
        $root . '/assets/uploads/videos',
    ];
}

function install_ensure_dir_writable(string $dir): bool {
    if (is_dir($dir)) {
        if (is_writable($dir)) return true;
        @chmod($dir, 0775);
        return is_writable($dir);
    }
    if (!@mkdir($dir, 0775, true)) {
        return false;
    }
    if (!is_writable($dir)) {
        @chmod($dir, 0775);
    }
    return is_writable($dir);
}

function install_dir_error(string $dir): string {
    $rel = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $dir);
    $rel = str_replace(__DIR__ . '/', '', $rel);
    if ($rel === 'config') {
        return install_config_dir_error();
    }
    if (strpos($rel, 'frontend/mp-weixin') === 0) {
        return $rel . ' 目录不可写。请在网站根目录执行：chmod -R 775 frontend/mp-weixin';
    }
    if (strpos($rel, 'assets/uploads') === 0) {
        return $rel . ' 目录不可写。请在网站根目录执行：mkdir -p ' . $rel . ' && chmod -R 775 assets/uploads';
    }
    return $rel . ' 目录不可写。请执行：chmod -R 775 ' . $rel;
}

function install_ensure_runtime_dirs(): array {
    $errors = [];
    foreach (install_runtime_dir_list() as $dir) {
        if (!install_ensure_dir_writable($dir)) {
            $errors[] = install_dir_error($dir);
        }
    }
    return $errors;
}

/** 轮播去重索引（避免在 SQL 迁移里用 PREPARE，防止 PDO 未缓冲查询报错） */
function install_ensure_swiper_dedup(PDO $pdo): void {
    if (!$pdo->query("SHOW TABLES LIKE 'swiper_items'")->fetch()) return;
    $pdo->exec("DELETE t1 FROM swiper_items t1 INNER JOIN swiper_items t2 ON t1.instance_id=t2.instance_id AND t1.image=t2.image AND t1.id>t2.id");
    $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='swiper_items' AND index_name='uk_instance_image'");
    $cnt = (int)$stmt->fetchColumn();
    if ($cnt === 0) {
        $pdo->exec("ALTER TABLE swiper_items ADD UNIQUE KEY uk_instance_image (instance_id, image(191))");
    }
}

/** widget 条目去重（二次 install 迁移重复 INSERT 时清理） */
function install_ensure_widget_items_dedup(PDO $pdo): void {
    if (!$pdo->query("SHOW TABLES LIKE 'widget_items'")->fetch()) return;
    $pdo->exec("DELETE t1 FROM widget_items t1 INNER JOIN widget_items t2 ON t1.instance_id=t2.instance_id AND t1.item_json=t2.item_json AND t1.id>t2.id");
}

function install_upload_dirs(): array {
    return [
        __DIR__ . '/assets/uploads',
        __DIR__ . '/assets/uploads/videos',
    ];
}

function install_ensure_upload_dirs(): bool {
    foreach (install_upload_dirs() as $dir) {
        if (!install_ensure_dir_writable($dir)) {
            return false;
        }
    }
    return true;
}

function install_preflight(): array {
    global $configPath, $lockPath, $migrationDir;
    $checks = [];
    $checks[] = [
        'name' => 'PHP 版本 ≥ 7.4',
        'ok' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'tip' => '当前版本 ' . PHP_VERSION . '，请联系主机商升级 PHP',
    ];
    $checks[] = [
        'name' => 'PDO MySQL 扩展',
        'ok' => extension_loaded('pdo_mysql'),
        'tip' => '请在 php.ini 中启用 pdo_mysql 扩展后重启 PHP（本项目使用 PDO 连接 MySQL，无需 mysqli）',
    ];
    $checks[] = [
        'name' => 'GD 图像扩展',
        'ok' => extension_loaded('gd') && function_exists('imagecreatetruecolor'),
        'tip' => '请在 php.ini 中启用 gd 扩展后重启 PHP（注册验证码与图片上传处理需要）',
    ];
    $checks[] = [
        'name' => 'config 目录可写',
        'ok' => install_can_write_config(),
        'tip' => install_config_dir_error(),
    ];
    $checks[] = [
        'name' => '根目录可写（用于 install.lock）',
        'ok' => is_writable(__DIR__),
        'tip' => '请给网站根目录开启写入权限，否则无法标记“已安装”',
    ];
    $checks[] = [
        'name' => '上传目录 assets/uploads 可写',
        'ok' => install_ensure_upload_dirs(),
        'tip' => '请确保 assets/uploads 与 assets/uploads/videos 存在且 Web 进程可写（chmod 775）',
    ];
    $checks[] = [
        'name' => '小程序目录 frontend/mp-weixin 可写',
        'ok' => install_ensure_dir_writable(__DIR__ . '/frontend/mp-weixin/utils'),
        'tip' => '安装需写入 frontend/mp-weixin。若目录不存在会自动创建；若已存在请执行 chmod -R 775 frontend/mp-weixin',
        'optional' => !is_dir(__DIR__ . '/frontend/mp-weixin'),
    ];
    $checks[] = [
        'name' => '数据库迁移文件 migrations/',
        'ok' => is_dir($migrationDir) && count(glob($migrationDir . '/*.sql') ?: []) > 0,
        'tip' => '请确认 ZIP 已完整解压，migrations 目录存在且内含 .sql 文件',
    ];
    $checks[] = [
        'name' => '配置文件 config/config.inc.php',
        'ok' => file_exists($configPath),
        'tip' => file_exists($configPath) ? '已存在，本次为升级安装' : '尚未生成，首次安装后会自动创建',
        'optional' => !file_exists($configPath),
    ];
    $checks[] = [
        'name' => '安装锁 install.lock',
        'ok' => file_exists($lockPath),
        'tip' => file_exists($lockPath) ? '已安装，可重新提交表单进行升级' : '安装成功后会自动创建',
        'optional' => !file_exists($lockPath),
    ];
    return $checks;
}

function install_preflight_ok(): bool {
    foreach (install_preflight() as $c) {
        if (!empty($c['optional'])) continue;
        if (empty($c['ok'])) return false;
    }
    return true;
}

function install_friendly_db_error(Throwable $e): array {
    $msg = $e->getMessage();
    if (stripos($msg, 'Access denied') !== false) {
        return ['数据库账号或密码错误', '请核对数据库用户名、密码是否正确；如忘记密码请在主机面板重置 MySQL 密码。'];
    }
    if (stripos($msg, 'Connection refused') !== false || stripos($msg, 'timed out') !== false) {
        return ['无法连接数据库服务器', '请检查「数据库主机」和「端口」是否正确。常见主机地址为 127.0.0.1 或 localhost，端口为 3306。'];
    }
    if (stripos($msg, 'Unknown database') !== false) {
        return ['数据库不存在且无权限创建', '请先在主机面板创建数据库，或确认数据库账号有 CREATE DATABASE 权限。'];
    }
    if (stripos($msg, 'could not find driver') !== false) {
        return ['缺少 MySQL 驱动', '服务器未安装 pdo_mysql，请联系主机商开启。'];
    }
    if (stripos($msg, 'unbuffered queries are active') !== false || stripos($msg, '2014') !== false) {
        return ['数据库迁移执行异常', '迁移 SQL 与当前 PHP/PDO 不兼容。请重新下载最新生成的 ZIP 包后再安装；若仍失败请联系技术支持。'];
    }
    if (stripos($msg, '3144') !== false || (stripos($msg, 'CHARACTER SET') !== false && stripos($msg, 'binary') !== false)) {
        return ['数据库迁移 JSON 写入失败', '迁移 SQL 向 JSON 字段写入了不兼容的数据格式。请用平台重新「生成并部署」最新代码包后再安装。'];
    }
    if (stripos($msg, '1064') !== false && stripos($msg, 'JSON') !== false) {
        return ['数据库迁移 JSON 语法不兼容', '当前数据库（MariaDB / MySQL）与迁移 SQL 不兼容。请重新生成并下载最新 ZIP 包后再安装。'];
    }
    return ['数据库操作失败', $msg];
}

/** 按语句拆分 SQL，忽略引号与 0x 十六进制字面量内的分号 */
function install_split_sql(string $sql): array {
    $statements = [];
    $current = '';
    $len = strlen($sql);
    $inSingle = false;
    $inDouble = false;
    $i = 0;
    while ($i < $len) {
        $ch = $sql[$i];
        if (!$inSingle && !$inDouble && $ch === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
            while ($i < $len && $sql[$i] !== "\n") {
                $current .= $sql[$i];
                $i++;
            }
            continue;
        }
        if (!$inSingle && !$inDouble && $ch === '0' && $i + 1 < $len && ($sql[$i + 1] === 'x' || $sql[$i + 1] === 'X')) {
            $current .= '0x';
            $i += 2;
            while ($i < $len && ctype_xdigit($sql[$i])) {
                $current .= $sql[$i];
                $i++;
            }
            continue;
        }
        if ($ch === "'" && !$inDouble) {
            if ($inSingle && $i + 1 < $len && $sql[$i + 1] === "'") {
                $current .= "''";
                $i += 2;
                continue;
            }
            $inSingle = !$inSingle;
            $current .= $ch;
            $i++;
            continue;
        }
        if ($ch === '"' && !$inSingle) {
            $inDouble = !$inDouble;
            $current .= $ch;
            $i++;
            continue;
        }
        if ($ch === ';' && !$inSingle && !$inDouble) {
            $trimmed = trim($current);
            if ($trimmed !== '') {
                $statements[] = $trimmed;
            }
            $current = '';
            $i++;
            continue;
        }
        $current .= $ch;
        $i++;
    }
    $trimmed = trim($current);
    if ($trimmed !== '') {
        $statements[] = $trimmed;
    }
    return $statements;
}

function install_page_start(string $title): void {
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . htmlspecialchars($title) . '</title>';
    echo '<style>
      body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f0f2f5;margin:0;padding:24px;color:#333}
      .wrap{max-width:720px;margin:0 auto;background:#fff;padding:28px 32px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,.06)}
      h1{margin:0 0 8px;font-size:22px} h2{margin:24px 0 12px;font-size:17px}
      .tip{color:#666;font-size:14px;line-height:1.6}
      .ok{color:#27ae60}.bad{color:#e74c3c}.warn{color:#e67e22}
      .box{padding:14px 16px;border-radius:8px;margin:12px 0;line-height:1.7;font-size:14px}
      .box-error{background:#fef0f0;border:1px solid #fbc4c4;color:#c0392b}
      .box-warn{background:#fff8e6;border:1px solid #ffe0a3;color:#b7791f}
      .box-ok{background:#edfbf3;border:1px solid #b8ebd0;color:#1e8449}
      .box-info{background:#eef6ff;border:1px solid #c5ddff;color:#2c5282}
      ul.hints{margin:8px 0 0;padding-left:20px} ul.hints li{margin:6px 0}
      .checks{list-style:none;padding:0;margin:12px 0}
      .checks li{padding:8px 0;border-bottom:1px solid #f0f0f0;font-size:14px;display:flex;gap:8px;align-items:flex-start}
      .checks li:last-child{border:none}
      .badge{font-size:12px;padding:2px 8px;border-radius:4px;white-space:nowrap}
      .badge-ok{background:#d5f5e3;color:#1e8449}.badge-bad{background:#fde8e8;color:#c0392b}
      label{display:block;margin-top:12px;font-weight:600;font-size:14px}
      input{width:100%;padding:10px;margin-top:6px;box-sizing:border-box;border:1px solid #ddd;border-radius:6px;font-size:14px}
      .install-field{margin-top:14px}
      .install-field-row{display:flex;align-items:center;gap:12px}
      .install-field-row>label{width:148px;flex-shrink:0;margin:0;font-weight:600;font-size:14px;display:block}
      .install-field-row>input{width:420px;flex-shrink:0;margin:0;padding:10px;box-sizing:border-box;border:1px solid #ddd;border-radius:6px;font-size:14px}
      .install-field-hint{margin:6px 0 0 160px;font-size:13px;color:#666;line-height:1.55;max-width:720px}
      .install-field-hint a{color:#409eff;text-decoration:none}
      .install-field-hint a:hover{text-decoration:underline}
      button,.btn{display:inline-block;margin-top:20px;padding:12px 28px;background:#2ecc71;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:15px;text-decoration:none}
      button:disabled{background:#95a5a6;cursor:not-allowed}
      .btn-secondary{background:#3498db}.btn-link{background:transparent;color:#2ecc71;padding:0;margin-top:12px}
      code{background:#f4f4f4;padding:2px 6px;border-radius:4px;font-size:13px}
    </style></head><body><div class="wrap">';
}

function install_page_end(): void {
    echo '</div></body></html>';
}

function install_show_error(string $title, string $message, array $hints = []): void {
    install_page_start('安装失败');
    echo '<h1 class="bad">❌ ' . htmlspecialchars($title) . '</h1>';
    echo '<div class="box box-error">' . nl2br(htmlspecialchars($message)) . '</div>';
    if ($hints) {
        echo '<h2>您可以尝试</h2><ul class="hints">';
        foreach ($hints as $h) echo '<li>' . htmlspecialchars($h) . '</li>';
        echo '</ul>';
    }
    echo '<p><a class="btn" href="install.php">← 返回重新安装</a></p>';
    install_page_end();
}

function install_show_success(string $apiBase, string $adminUser): void {
    install_page_start('安装成功');
    echo '<h1 class="ok">✅ 安装成功！</h1>';
    echo '<div class="box box-ok">';
    echo '<p>恭喜，您的站点已安装完成。以下文件已校验通过：</p><ul class="hints">';
    echo '<li>✔ <code>config/config.inc.php</code> 配置文件</li>';
    echo '<li>✔ <code>install.lock</code> 安装锁</li>';
    echo '<li>✔ 数据库连接与管理员账号</li>';
    echo '</ul></div>';
    echo '<div class="box box-info">';
    echo '<p><strong>API 域名：</strong>' . htmlspecialchars($apiBase) . '</p>';
    echo '<p><strong>后台账号：</strong>' . htmlspecialchars($adminUser) . '（密码为您刚才设置的密码）</p>';
    echo '</div>';
    echo '<h2>下一步</h2>';
    echo '<p><a class="btn" href="/index.php">打开 H5 首页</a> ';
    echo '<a class="btn btn-secondary" href="/admin/">进入管理后台</a></p>';
    echo '<p class="tip">微信小程序请用开发者工具导入 <code>frontend/mp-weixin</code>，并在公众平台配置 request 合法域名。</p>';
    if (file_exists(__FILE__)) {
        echo '<p class="tip warn">安全提示：建议删除或保留 <code>install.php.bak</code>，勿将安装入口长期暴露公网。</p>';
    }
    install_page_end();
}

function install_verify_result(): array {
    global $configPath, $lockPath;
    $errors = [];
    if (!file_exists($configPath)) {
        $errors[] = '配置文件 config/config.inc.php 未生成，通常是目录没有写入权限';
    } elseif (!is_readable($configPath)) {
        $errors[] = '配置文件已生成但无法读取，请检查文件权限';
    }
    if (!file_exists($lockPath)) {
        $errors[] = '安装锁 install.lock 未生成，网站根目录可能没有写入权限';
    }
    if (file_exists($configPath)) {
        try {
            $cfg = require $configPath;
            if (!is_array($cfg) || empty($cfg['database'])) {
                $errors[] = '配置文件内容不完整，请重新安装';
            }
        } catch (Throwable $e) {
            $errors[] = '配置文件存在但无法加载：' . $e->getMessage();
        }
    }
    return $errors;
}

function install_render_checks(): void {
    echo '<h2>环境自检</h2><ul class="checks">';
    foreach (install_preflight() as $c) {
        $ok = !empty($c['ok']);
        $badge = $ok ? '<span class="badge badge-ok">通过</span>' : '<span class="badge badge-bad">未通过</span>';
        if (!empty($c['optional']) && !$ok) $badge = '<span class="badge" style="background:#eee;color:#666">待安装</span>';
        echo '<li>' . $badge . '<div><strong>' . htmlspecialchars($c['name']) . '</strong>';
        if (!$ok || !empty($c['optional'])) echo '<br><span class="tip">' . htmlspecialchars($c['tip']) . '</span>';
        echo '</div></li>';
    }
    echo '</ul>';
}

// ── POST 安装 ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dirErrors = install_ensure_runtime_dirs();
    if (!empty($dirErrors)) {
        install_show_error(
            '目录权限不足',
            implode('；', $dirErrors),
            [
                '在网站根目录手动创建 config、assets/uploads、assets/uploads/videos',
                '执行 chmod 775 并将所有者设为 PHP 运行用户（如 www、nginx、apache）',
                '若使用宝塔/cPanel，在文件管理中对该目录点「权限」修改',
            ]
        );
        exit;
    }
    if (!install_preflight_ok()) {
        install_show_error(
            '环境检测未通过',
            '您的服务器环境尚未满足安装条件，请先解决下方「环境自检」中的红色项，再重新提交。',
            [
                '在主机面板将网站根目录权限设为 755 或 775',
                '确认 PHP 已启用 pdo_mysql 与 gd 扩展',
                '确认 ZIP 包已完整解压到网站根目录',
            ]
        );
        exit;
    }

    $dbHost = install_val('db_host', $defaults, 'database', 'host', '127.0.0.1');
    $dbPort = install_val('db_port', $defaults, 'database', 'port', '3306');
    $dbName = install_val('db_name', $defaults, 'database', 'database', 'wechat');
    $dbUser = install_val('db_user', $defaults, 'database', 'username', 'root');
    $dbPass = install_val('db_pass', $defaults, 'database', 'password', '123456');
    $apiBase = install_val('api_base_url', $defaults, '', 'api_base_url', install_detect_base_url());
    require_once __DIR__ . '/api/core/mp_sync.php';
    $apiBase = site_base_url_normalize($apiBase);
    $wxAppId = install_val('wx_app_id', $defaults, 'wechat', 'app_id');
    $wxSecret = install_val('wx_app_secret', $defaults, 'wechat', 'app_secret');
    $wxMchId = install_val('wx_mch_id', $defaults, 'wechat', 'mch_id');
    $wxMchKey = install_val('wx_mch_key', $defaults, 'wechat', 'mch_key');

    
    $adminUser = 'admin';
    $adminPass = trim((string)($_POST['admin_pass'] ?? 'admin'));
    if ($adminPass === '') $adminPass = 'admin';

    if ($dbName === '') {
        install_show_error('请填写数据库名', '「数据库名」不能为空。请先在主机面板查看或创建一个数据库，将名称填入此处。');
        exit;
    }
    if ($adminUser === '' || $adminPass === '') {
        install_show_error('请设置管理员账号', '「后台用户名」和「后台密码」都必须填写，这将用于登录 /admin/ 管理后台。');
        exit;
    }
    if (strlen($adminPass) < 4) {
        install_show_error('密码太短', '后台密码至少 4 位，请设置一个您能记住的密码。');
        exit;
    }

    try {
        // 1. 连接数据库
        $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ]);
        $q = chr(96);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS {$q}{$dbName}{$q} DEFAULT CHARSET utf8mb4");
        $pdo->exec("USE {$q}{$dbName}{$q}");

        // 2. 执行迁移
        $sqlFiles = glob(__DIR__ . '/migrations/*.sql');
        if (!$sqlFiles) {
            throw new RuntimeException('migrations 目录下没有 SQL 文件，请确认 ZIP 已完整解压');
        }
        sort($sqlFiles);
        foreach ($sqlFiles as $file) {
            $sql = file_get_contents($file);
            if ($sql === false) continue;
            foreach (install_split_sql($sql) as $stmt) {
                $pdo->exec($stmt);
            }
        }
        install_ensure_swiper_dedup($pdo);
        install_ensure_widget_items_dedup($pdo);
        
        require_once __DIR__ . '/api/core/article_sync.php';
        $demoIds = ensure_demo_articles($pdo, true);
        sync_article_widgets_featured_ids($pdo, $demoIds);
        
        require_once __DIR__ . '/api/core/product_sync.php';
        $demoIds = ensure_demo_products($pdo, true);
        sync_product_widgets_featured_ids($pdo, $demoIds);

        // 3. 管理员
        $hash = password_hash($adminPass, PASSWORD_BCRYPT);
        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            nickname VARCHAR(50) NOT NULL DEFAULT '',
            status TINYINT NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id), UNIQUE KEY uk_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash, nickname) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)');
        $stmt->execute([$adminUser, $hash, $adminUser]);

        require_once __DIR__ . '/api/core/install_rbac.php';
        install_init_rbac($pdo, $adminUser);
        install_patch_demo_admin_guides($pdo, $apiBase, $adminUser);

        // 4. 写配置文件
        $configData = [
            'api_base_url' => $apiBase,
            'database' => ['host'=>$dbHost,'port'=>$dbPort,'database'=>$dbName,'username'=>$dbUser,'password'=>$dbPass],
            'wechat' => ['app_id'=>$wxAppId,'app_secret'=>$wxSecret,'mch_id'=>$wxMchId,'mch_key'=>$wxMchKey,'notify_url'=>$apiBase.'/api/order/wx_notify.php'],
            'admin_user' => $adminUser,
            
        ];
        $configContent = "<?php\n\$app_config = " . var_export($configData, true) . ";\nreturn \$app_config;\n";
        if (!is_dir($configDir) && !@mkdir($configDir, 0755, true)) {
            throw new RuntimeException('无法创建 config 目录。请手动在网站根目录新建 config 文件夹，并赋予写入权限（chmod 755）。');
        }
        if (@file_put_contents($configPath, $configContent) === false) {
            throw new RuntimeException('无法写入 config/config.inc.php。请检查 config 目录是否可写：在 FTP/面板中对该目录执行 chmod 755 或 775。');
        }

        // 5. 同步小程序配置（project.config.json + utils/mp_config.js）
        if (!sync_mp_weixin_config($wxAppId, $apiBase)) {
            throw new RuntimeException('无法写入小程序配置 frontend/mp-weixin。请先在网站根目录执行：chmod -R 775 frontend/mp-weixin（目录不存在则 mkdir -p frontend/mp-weixin/utils && chmod -R 775 frontend）');
        }

        if (!install_ensure_upload_dirs()) {
            throw new RuntimeException('无法创建或写入上传目录 assets/uploads。请检查 assets 目录权限（chmod 755 或 775）。');
        }

        // 6. 写安装锁
        if (@file_put_contents($lockPath, json_encode(['installed_at'=>date('c'),'api_base_url'=>$apiBase], JSON_UNESCAPED_UNICODE)) === false) {
            throw new RuntimeException('无法写入 install.lock。请检查网站根目录是否可写（chmod 755 或 775）。');
        }

        // 7. 安装后校验（全部通过才算成功）
        $verifyErrors = install_verify_result();
        if ($verifyErrors) {
            throw new RuntimeException("安装后校验失败：\n" . implode("\n", $verifyErrors));
        }

        // 8. 验证数据库可读
        $cfg = require $configPath;
        $testDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $cfg['database']['host'], $cfg['database']['port'], $cfg['database']['database']);
        $testPdo = new PDO($testDsn, $cfg['database']['username'], $cfg['database']['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $testPdo->query('SELECT 1');

        // 9. 验证后台/API 能加载配置（防止 bootstrap 路径错误）
        require_once __DIR__ . '/api/core/bootstrap.php';
        db()->query('SELECT 1');

        // 10. 备份并删除安装入口
        $bak = __FILE__ . '.bak';
        if (!@rename(__FILE__, $bak)) {
            throw new RuntimeException('安装文件 install.php 无法重命名，请手动删除或改名为 install.php.bak');
        }
        @unlink($bak);

        install_show_success($apiBase, $adminUser);
    } catch (Throwable $e) {
        [$title, $detail] = install_friendly_db_error($e);
        if ($title === '数据库操作失败' && stripos($e->getMessage(), 'config') !== false) {
            $title = '写入配置文件失败';
            $detail = $e->getMessage();
        }
        if (stripos($e->getMessage(), '校验失败') !== false || stripos($e->getMessage(), '无法写入') !== false || stripos($e->getMessage(), '无法创建') !== false) {
            $title = '文件写入失败';
            $detail = $e->getMessage();
        }
        install_show_error($title, $detail, [
            '检查网站根目录和 config 目录是否有写入权限',
            '可尝试在面板将目录权限设为 755 或 775',
            '如仍失败，请联系主机商并告知：PHP 需要对网站根目录有写入权限',
            '解决问题后点击「返回重新安装」再次提交即可',
        ]);
    }
    exit;
}

// ── GET 安装表单 ──
function install_field(string $label, string $name, string $value, string $hintHtml = '', string $extraAttrs = ''): void {
    echo '<div class="install-field">';
    echo '<div class="install-field-row"><label for="install_' . htmlspecialchars($name) . '">' . htmlspecialchars($label) . '</label>';
    echo '<input id="install_' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" ' . $extraAttrs . '></div>';
    if ($hintHtml !== '') {
        echo '<div class="install-field-hint">' . $hintHtml . '</div>';
    }
    echo '</div>';
}

$apiDefault = install_detect_base_url();
$dbHost = is_array($defaults) ? ($defaults['database']['host'] ?? '127.0.0.1') : '127.0.0.1';
$dbPort = is_array($defaults) ? ($defaults['database']['port'] ?? '3306') : '3306';
$dbName = is_array($defaults) ? ($defaults['database']['database'] ?? 'wechat') : 'wechat';
$dbUser = is_array($defaults) ? ($defaults['database']['username'] ?? 'root') : 'root';
$dbPass = is_array($defaults) ? ($defaults['database']['password'] ?? '123456') : '123456';
$apiBase = is_array($defaults) ? ($defaults['api_base_url'] ?? $apiDefault) : $apiDefault;
$wxAppId = is_array($defaults) ? ($defaults['wechat']['app_id'] ?? '') : '';
$wxSecret = is_array($defaults) ? ($defaults['wechat']['app_secret'] ?? '') : '';
$wxMchId = is_array($defaults) ? ($defaults['wechat']['mch_id'] ?? '') : '';
$wxMchKey = is_array($defaults) ? ($defaults['wechat']['mch_key'] ?? '') : '';
$adminUser = is_array($defaults) ? ($defaults['admin_user'] ?? 'admin') : 'admin';
$adminPass = (is_array($defaults) && isset($defaults['database'])) ? '' : 'admin';
$installDirErrors = install_ensure_runtime_dirs();
$envOk = install_preflight_ok();
$alreadyInstalled = file_exists($lockPath) && file_exists($configPath);

install_page_start('应用安装向导');
echo '<h1>应用安装向导</h1>';
echo '<p class="tip">只需填写数据库和后台密码，其余保持默认即可。全程约 1 分钟。</p>';

if (!empty($installDirErrors)) {
    echo '<div class="box box-error"><strong>目录初始化失败</strong><ul>';
    foreach ($installDirErrors as $de) {
        echo '<li>' . htmlspecialchars($de) . '</li>';
    }
    echo '</ul></div>';
}

if (!empty($_GET['from']) && $_GET['from'] === 'admin') {
    echo '<div class="box box-warn">您访问了管理后台，但检测到<strong>尚未安装完成</strong>。请先完成下方安装，再登录 <a href="/admin/">/admin/</a>。</div>';
} elseif (!empty($_GET['from']) && $_GET['from'] === 'h5') {
    echo '<div class="box box-warn">您访问了 H5 首页，但检测到<strong>尚未安装完成</strong>。请先完成下方安装。</div>';
}
if ($alreadyInstalled) {
    echo '<div class="box box-ok">✔ 检测到站点<strong>已安装</strong>。如需升级可修改下方配置后重新提交；日常请访问 <a href="/admin/">管理后台</a> 或 <a href="/index.php">H5 首页</a>。</div>';
}
if (!$envOk) {
    echo '<div class="box box-error"><strong>环境检测未通过</strong>：请先解决下方红色项，否则安装会失败。无需懂技术，按提示操作或联系主机商即可。</div>';
}

install_render_checks();

echo '<form method="post" action="install.php">';
echo '<h2>数据库配置</h2>';
echo '<p class="tip">在主机面板（宝塔/cPanel 等）的「数据库」页面可找到以下信息。</p>';
echo '<label>数据库主机</label><input name="db_host" value="' . htmlspecialchars($dbHost) . '" required placeholder="一般为 127.0.0.1">';
echo '<label>端口</label><input name="db_port" value="' . htmlspecialchars($dbPort) . '" required placeholder="一般为 3306">';
echo '<label>数据库名</label><input name="db_name" value="' . htmlspecialchars($dbName) . '" required placeholder="默认 wechat">';
echo '<label>数据库用户名</label><input name="db_user" value="' . htmlspecialchars($dbUser) . '" required>';
echo '<label>数据库密码</label><input name="db_pass" value="' . htmlspecialchars($dbPass) . '" placeholder="无密码可留空">';

echo '<h2>站点配置</h2>';
install_field('API 域名', 'api_base_url', $apiBase, '站点根域名，如 https://www.example.com（<strong>不要带 /api</strong>）。安装后同步到 <code>utils/mp_config.js</code>（自动加 <code>/api</code>）与 <code>project.config.json</code>。', 'required placeholder="https://www.example.com"');
install_field('AppID', 'wx_app_id', $wxAppId, '<a href="https://mp.weixin.qq.com/" target="_blank" rel="noopener">微信公众平台</a> → 开发 → 开发管理 → 开发设置 → 开发者 ID → AppID（没有可先留空）');
install_field('AppSecret', 'wx_app_secret', $wxSecret, '<a href="https://mp.weixin.qq.com/" target="_blank" rel="noopener">微信公众平台</a> → 开发 → 开发管理 → 开发设置 → AppSecret（点击重置获取，仅显示一次）');
echo '<h2>微信支付（可选）</h2>';
echo '<p class="tip">项目含商品/购物功能，如需微信支付请填写商户号与 API 密钥；暂不需要可留空。</p>';
install_field('商户号 mch_id', 'wx_mch_id', $wxMchId, '<a href="https://pay.weixin.qq.com/" target="_blank" rel="noopener">微信商户平台</a> → 账户中心 → 商户信息 → 商户号');
install_field('API 密钥 mch_key', 'wx_mch_key', $wxMchKey, '<a href="https://pay.weixin.qq.com/" target="_blank" rel="noopener">微信商户平台</a> → 账户中心 → API 安全 → 设置 API v2 密钥');


echo '<h2>管理后台账号</h2>';
echo '<p class="tip">安装时固定使用超级管理员账号 <code>admin</code>，请设置后台密码。</p>';
echo '<label>后台用户名</label><input name="admin_user" value="admin" readonly required>';
echo '<label>后台密码</label><input name="admin_pass" type="password" value="' . htmlspecialchars($adminPass) . '" required placeholder="默认 admin" autocomplete="new-password">';

if ($envOk) {
    echo '<button type="submit">开始安装</button>';
} else {
    echo '<button type="submit" disabled title="请先解决环境检测中的红色项">环境未就绪，暂无法安装</button>';
    echo '<p class="tip bad">按钮灰色表示环境检测未通过，请按上方红色提示处理后再试。</p>';
}
echo '</form>';
install_page_end();

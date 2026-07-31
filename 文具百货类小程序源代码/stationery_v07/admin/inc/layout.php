<?php
/**
 * 筑码引擎 www.402.cn
 */

function site_asset_version(): string {
    static $v = null;
    if ($v !== null) return $v;
    $root = dirname(__DIR__, 2);
    $f = $root . '/asset_version.txt';
    $v = is_file($f) ? trim((string)file_get_contents($f)) : gmdate('YmdHis');
    return $v;
}

function asset_url(string $path): string {
    if ($path === '') return '';
    if (preg_match('#^(https?:)?//#i', $path) || strpos($path, 'data:') === 0) return $path;
    $sep = strpos($path, '?') !== false ? '&' : '?';
    return $path . $sep . 'v=' . rawurlencode(site_asset_version());
}

function admin_asset_url(string $url): string {
    if ($url === '') return '';
    if (preg_match('#^https?://#i', $url) || strpos($url, 'data:') === 0) return $url;
    $path = $url;
    if (strpos($url, './') === 0) $path = '..' . substr($url, 1);
    elseif (strpos($url, 'assets/') === 0) $path = '../' . $url;
    elseif (isset($url[0]) && $url[0] === '/') $path = $url;
    return asset_url($path);
}

function admin_project_root(): string {
    return dirname(__DIR__, 2);
}

function admin_image_preview_src(string $url): string {
    if ($url === '') return '';
    if (preg_match('#(\./)?assets/uploads/(.+)$#', $url, $m)) {
        $base = $m[2];
        $thumbName = preg_replace('/(\.[^.]+)$/', '_thumb$1', $base);
        $thumbDisk = admin_project_root() . '/assets/uploads/' . $thumbName;
        if (is_file($thumbDisk)) return '../assets/uploads/' . $thumbName;
    }
    return admin_asset_url($url);
}

function admin_media_storage_used(): int {
    $root = admin_media_uploads_root();
    if (!is_dir($root)) return 0;
    return admin_media_dir_size($root);
}

function admin_media_uploads_root(): string {
    return admin_project_root() . '/assets/uploads';
}

function admin_media_safe_rel(string $rel): string {
    $rel = str_replace('\\', '/', trim($rel, '/'));
    if ($rel === '') return '';
    foreach (explode('/', $rel) as $seg) {
        if ($seg === '' || $seg === '.' || $seg === '..') return '';
        if (!preg_match('/^[a-zA-Z0-9_\-\x{4e00}-\x{9fa5}()（）]+$/u', $seg)) return '';
    }
    return $rel;
}

function admin_media_abs_dir(string $rel): string {
    $rel = admin_media_safe_rel($rel);
    $root = admin_media_uploads_root();
    return $rel === '' ? $root : $root . '/' . $rel;
}

function admin_media_file_url(string $rel, string $filename): string {
    $rel = admin_media_safe_rel($rel);
    $path = $rel === '' ? $filename : $rel . '/' . $filename;
    return './assets/uploads/' . $path;
}

function admin_media_breadcrumb(string $rel): array {
    $out = [['id' => '', 'name' => '全部文件']];
    $rel = admin_media_safe_rel($rel);
    if ($rel === '') return $out;
    $acc = '';
    foreach (explode('/', $rel) as $seg) {
        $acc = $acc === '' ? $seg : $acc . '/' . $seg;
        $out[] = ['id' => $acc, 'name' => $seg];
    }
    return $out;
}

function admin_media_dir_size(string $dir): int {
    if (!is_dir($dir)) return 0;
    $used = 0;
    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') continue;
        $path = $dir . '/' . $name;
        if (is_dir($path)) $used += admin_media_dir_size($path);
        elseif (is_file($path)) $used += (int)filesize($path);
    }
    return $used;
}

function admin_media_unique_folder_name(string $parentRel, string $base = '新建文件夹'): string {
    $parent = admin_media_abs_dir($parentRel);
    if (!is_dir($parent)) mkdir($parent, 0755, true);
    $name = $base;
    $n = 1;
    while (is_dir($parent . '/' . $name)) {
        $name = $base . '(' . $n . ')';
        $n++;
    }
    return $name;
}

function admin_media_delete_recursive(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') continue;
        $path = $dir . '/' . $name;
        if (is_dir($path)) admin_media_delete_recursive($path);
        elseif (is_file($path)) @unlink($path);
    }
    @rmdir($dir);
}

function admin_media_delete_file_by_url(string $url): bool {
    if (!preg_match('#(\./)?assets/uploads/(.+)$#', $url, $m)) return false;
    $rel = admin_media_safe_file_rel(str_replace('\\', '/', $m[2]));
    if ($rel === '') return false;
    $path = admin_media_uploads_root() . '/' . $rel;
    if (!is_file($path)) return false;
    $thumb = preg_replace('/(\.[^.]+)$/', '_thumb$1', $path);
    @unlink($path);
    if (is_file($thumb)) @unlink($thumb);
    return true;
}

function admin_media_safe_file_rel(string $rel): string {
    $rel = str_replace('\\', '/', trim($rel, '/'));
    if ($rel === '') return '';
    $parts = explode('/', $rel);
    $n = count($parts);
    foreach ($parts as $i => $seg) {
        if ($seg === '' || $seg === '.' || $seg === '..') return '';
        $pat = ($i === $n - 1)
            ? '/^[a-zA-Z0-9_\-\.\x{4e00}-\x{9fa5}()（）]+$/u'
            : '/^[a-zA-Z0-9_\-\x{4e00}-\x{9fa5}()（）]+$/u';
        if (!preg_match($pat, $seg)) return '';
    }
    return $rel;
}

function admin_video_uploads_root(): string {
    return admin_project_root() . '/assets/uploads/videos';
}

function admin_video_safe_rel(string $rel): string {
    return admin_media_safe_rel($rel);
}

function admin_video_abs_dir(string $rel): string {
    $rel = admin_video_safe_rel($rel);
    $root = admin_video_uploads_root();
    return $rel === '' ? $root : $root . '/' . $rel;
}

function admin_video_file_url(string $rel, string $filename): string {
    $rel = admin_video_safe_rel($rel);
    $path = $rel === '' ? $filename : $rel . '/' . $filename;
    return './assets/uploads/videos/' . $path;
}

function admin_video_breadcrumb(string $rel): array {
    $out = [['id' => '', 'name' => '全部视频']];
    $rel = admin_video_safe_rel($rel);
    if ($rel === '') return $out;
    $acc = '';
    foreach (explode('/', $rel) as $seg) {
        $acc = $acc === '' ? $seg : $acc . '/' . $seg;
        $out[] = ['id' => $acc, 'name' => $seg];
    }
    return $out;
}

function admin_video_storage_used(): int {
    $root = admin_video_uploads_root();
    if (!is_dir($root)) return 0;
    return admin_media_dir_size($root);
}

function admin_video_unique_folder_name(string $parentRel, string $base = '视频文件夹'): string {
    $parent = admin_video_abs_dir($parentRel);
    if (!is_dir($parent)) mkdir($parent, 0755, true);
    $name = $base;
    $n = 1;
    while (is_dir($parent . '/' . $name)) {
        $name = $base . '(' . $n . ')';
        $n++;
    }
    return $name;
}

function admin_video_delete_recursive(string $dir): void {
    admin_media_delete_recursive($dir);
}

function admin_video_delete_file_by_url(string $url): bool {
    if (!preg_match('#(\./)?assets/uploads/videos/(.+)$#', $url, $m)) return false;
    $rel = admin_media_safe_file_rel(str_replace('\\', '/', $m[2]));
    if ($rel === '') return false;
    $path = admin_video_uploads_root() . '/' . $rel;
    if (!is_file($path)) return false;
    @unlink($path);
    return true;
}

function admin_norm_color(string $v, string $def = '#333333'): string {
    $v = trim($v);
    if ($v !== '' && preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $v)) return $v;
    return $def;
}

function admin_datetime_to_input(string $value): string {
    $value = admin_format_datetime($value);
    if ($value === '') return '';
    return substr($value, 0, 10) . 'T' . substr($value, 11);
}

function admin_datetime_from_input(string $value): string {
    return admin_format_datetime($value);
}

function admin_format_datetime(string $value): string {
    $value = trim(str_replace('/', '-', str_replace('T', ' ', $value)));
    if ($value === '') return '';
    if (preg_match('/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})(:\d{2})?$/', $value, $m)) {
        return $m[1] . ' ' . $m[2] . ($m[3] ?? ':00');
    }
    if (preg_match('/^(\d{4}-\d{2}-\d{2})$/', $value, $m)) {
        return $m[1] . ' 00:00:00';
    }
    return $value;
}

function admin_date_to_input(string $value): string {
    return admin_format_date($value);
}

function admin_date_from_input(string $value): string {
    return admin_format_date($value);
}

function admin_format_date(string $value): string {
    $value = trim(str_replace('/', '-', str_replace('T', ' ', $value)));
    if ($value === '') return '';
    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $value, $m)) return $m[1];
    return $value;
}

function admin_project_flags(): array {
    static $flags = null;
    if ($flags === null) {
        $raw = @file_get_contents(dirname(__DIR__) . '/_flags.json');
        $flags = json_decode($raw ?: '{}', true) ?: [];
    }
    return $flags;
}

function admin_flag(string $key): bool {
    $flags = admin_project_flags();
    return !empty($flags[$key]);
}

function admin_site_root(): string {
    return dirname(__DIR__, 2);
}

function admin_system_link_options(): array {
    $all = [
        'favorites-list' => '我的收藏',
        'footprint-list' => '我的足迹',
        'hobbies-list' => '我的爱好',
        'wallet-recharge' => '余额充值',
        'wallet-logs' => '余额明细',
        'points-logs' => '积分明细',
        'order-list' => '我的订单',
        'coupon-list' => '优惠券',
        'member-center' => '会员中心',
        'address-list' => '收货地址',
        'flash-sale' => '限时秒杀',
        'group-buy' => '拼团专区',
        'live-room' => '直播间',
        'check-in' => '每日签到',
        'product-list' => '商品列表',
        'search-article' => '资讯列表',
    ];
    $out = [];
    foreach ($all as $route => $label) {
        if (strpos($route, 'product') === 0 || $route === 'flash-sale' || $route === 'group-buy') {
            if (!admin_flag('has_product')) continue;
        }
        if ($route === 'flash-sale' && !admin_flag('has_flash_sale')) continue;
        if ($route === 'group-buy' && !admin_flag('has_group_buy')) continue;
        if ($route === 'live-room' && !admin_flag('has_live_entry')) continue;
        if ($route === 'check-in' && !admin_flag('has_check_in')) continue;
        if (strpos($route, 'search-article') === 0 || $route === 'article-list') {
            if (!admin_flag('has_article')) continue;
        }
        if (in_array($route, ['order-list', 'coupon-list', 'member-center', 'address-list', 'favorites-list', 'footprint-list', 'hobbies-list', 'wallet-recharge', 'wallet-logs', 'points-logs'], true)) {
            if (!admin_flag('has_user')) continue;
        }
        if (in_array($route, ['order-list', 'wallet-recharge', 'wallet-logs'], true) && !admin_flag('has_commerce')) continue;
        $out[$route] = $label;
    }
    return $out;
}

function admin_nav_map_sys_pages(): array {
    $all = [
        ['key'=>'cart','name'=>'购物车','admin'=>'','desc'=>'系统内置，无需配置','need'=>'commerce'],
        ['key'=>'checkout','name'=>'确认订单','admin'=>'','desc'=>'系统内置，无需配置','need'=>'commerce'],
        ['key'=>'product-detail','name'=>'商品详情','admin'=>'products.php','desc'=>'商品数据(图片/价格/库存等)','need'=>'product'],
        ['key'=>'article-detail','name'=>'文章详情','admin'=>'articles.php','desc'=>'文章数据(标题/封面/内容等)','need'=>'article'],
        ['key'=>'product-list','name'=>'商品列表','admin'=>'product_categories.php','desc'=>'分类排序+商品管理','need'=>'product'],
        ['key'=>'article-list','name'=>'文章列表','admin'=>'article_categories.php','desc'=>'分类排序+文章管理','need'=>'article'],
        ['key'=>'search-product','name'=>'搜索商品','admin'=>'products.php','desc'=>'商品数据','need'=>'product'],
        ['key'=>'search-article','name'=>'搜索文章','admin'=>'articles.php','desc'=>'文章数据','need'=>'article'],
        ['key'=>'order-list','name'=>'我的订单','admin'=>'orders.php','desc'=>'订单管理(发货/完成)','need'=>'commerce'],
        ['key'=>'coupon-list','name'=>'优惠券','admin'=>'coupons.php','desc'=>'优惠券模板(名称/面额/条件)','need'=>'user'],
        ['key'=>'member-center','name'=>'会员中心','admin'=>'member_levels.php','desc'=>'会员等级(名称/折扣/权益)','need'=>'user'],
        ['key'=>'address-list','name'=>'收货地址','admin'=>'user_addresses.php','desc'=>'用户地址记录','need'=>'user'],
        ['key'=>'settings','name'=>'个人设置','admin'=>'users.php','desc'=>'用户数据管理','need'=>'user'],
        ['key'=>'invite','name'=>'邀请好友','admin'=>'user_invites.php','desc'=>'邀请记录','need'=>'user'],
        ['key'=>'wallet-recharge','name'=>'余额充值','admin'=>'users.php','desc'=>'用户余额调整','need'=>'commerce'],
        ['key'=>'wallet-logs','name'=>'余额明细','admin'=>'users.php','desc'=>'用户余额记录','need'=>'commerce'],
        ['key'=>'points-logs','name'=>'积分明细','admin'=>'users.php','desc'=>'用户积分记录','need'=>'user'],
        ['key'=>'favorites-list','name'=>'我的收藏','admin'=>'','desc'=>'用户行为数据，无需配置','need'=>'user'],
        ['key'=>'footprint-list','name'=>'我的足迹','admin'=>'','desc'=>'用户行为数据，无需配置','need'=>'user'],
        ['key'=>'login','name'=>'登录','admin'=>'users.php','desc'=>'用户管理','need'=>'user'],
        ['key'=>'register','name'=>'注册','admin'=>'users.php','desc'=>'用户管理','need'=>'user'],
        ['key'=>'flash-sale','name'=>'限时特惠页','admin'=>'products.php','desc'=>'商品截止时间/库存','need'=>'flash_sale'],
        ['key'=>'group-buy','name'=>'拼团专区','admin'=>'products.php','desc'=>'商品数据','need'=>'group_buy'],
    ];
    $out = [];
    foreach ($all as $sp) {
        $need = $sp['need'] ?? '';
        if ($need === 'product' && !admin_flag('has_product')) continue;
        if ($need === 'article' && !admin_flag('has_article')) continue;
        if ($need === 'user' && !admin_flag('has_user')) continue;
        if ($need === 'commerce' && !admin_flag('has_commerce')) continue;
        if ($need === 'flash_sale' && (!admin_flag('has_product') || !admin_flag('has_flash_sale'))) continue;
        if ($need === 'group_buy' && (!admin_flag('has_product') || !admin_flag('has_group_buy'))) continue;
        unset($sp['need']);
        $out[] = $sp;
    }
    return $out;
}

function admin_nav_map_globals(): array {
    $all = [
        ['name'=>'主题颜色','admin'=>'settings.php','desc'=>'全局主色、背景色','need'=>'always'],
        ['name'=>'底部导航栏','admin'=>'settings.php','desc'=>'TabBar 开关/页面/文字','need'=>'always'],
        ['name'=>'商品管理','admin'=>'products.php','desc'=>'所有商品 CRUD','need'=>'product'],
        ['name'=>'商品分类','admin'=>'product_categories.php','desc'=>'分类名称/排序','need'=>'product'],
        ['name'=>'内容管理','admin'=>'articles.php','desc'=>'所有文章 CRUD','need'=>'article'],
        ['name'=>'文章分类','admin'=>'article_categories.php','desc'=>'分类名称/排序','need'=>'article'],
        ['name'=>'用户管理','admin'=>'users.php','desc'=>'用户搜索/状态/余额/积分','need'=>'user'],
        ['name'=>'订单管理','admin'=>'orders.php','desc'=>'发货/完成操作','need'=>'commerce'],
        ['name'=>'优惠券管理','admin'=>'coupons.php','desc'=>'优惠券模板 CRUD','need'=>'user'],
        ['name'=>'会员等级','admin'=>'member_levels.php','desc'=>'等级配置/折扣/权益','need'=>'user'],
        ['name'=>'系统设置','admin'=>'settings.php','desc'=>'全局配置','need'=>'always'],
    ];
    $out = [];
    foreach ($all as $g) {
        $need = $g['need'] ?? 'always';
        if ($need === 'product' && !admin_flag('has_product')) continue;
        if ($need === 'article' && !admin_flag('has_article')) continue;
        if ($need === 'user' && !admin_flag('has_user')) continue;
        if ($need === 'commerce' && !admin_flag('has_commerce')) continue;
        unset($g['need']);
        $out[] = $g;
    }
    return $out;
}

function admin_product_options(PDO $pdo): array {
    if (!$pdo->query("SHOW TABLES LIKE 'products'")->fetch()) {
        return [];
    }
    return $pdo->query('SELECT id, name FROM products WHERE status = 1 ORDER BY sort_order DESC, id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function admin_article_options(PDO $pdo): array {
    if (!$pdo->query("SHOW TABLES LIKE 'articles'")->fetch()) {
        return [];
    }
    return $pdo->query('SELECT id, title FROM articles WHERE status = 1 ORDER BY sort_order DESC, id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function admin_field_text(string $label, string $name, string $value = '', string $type = 'text', string $extraAttrs = ''): void {
    echo '<div class="form-row"><label>' . htmlspecialchars($label) . '</label><div class="field">';
    echo '<input name="' . htmlspecialchars($name) . '" type="' . htmlspecialchars($type) . '" value="' . htmlspecialchars($value) . '" ' . $extraAttrs . '>';
    echo '</div></div>';
}

function admin_field_image(string $label, string $name, string $id, string $value = ''): void {
    $preview = admin_image_preview_src($value);
    $text = $value !== '' ? '更换图片' : '添加图片';
    echo '<div class="form-row"><label>' . htmlspecialchars($label) . '</label><div class="field image-field">';
    echo '<input type="hidden" name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($id) . '" value="' . htmlspecialchars($value) . '">';
    echo '<div class="image-picker-trigger" onclick="adminPickImage(\'' . htmlspecialchars($id) . '\')" role="button" tabindex="0">';
    if ($preview !== '') {
        echo '<img src="' . htmlspecialchars($preview) . '" alt="" class="image-picker-thumb" id="' . htmlspecialchars($id) . '_thumb">';
    } else {
        echo '<div class="image-picker-placeholder" id="' . htmlspecialchars($id) . '_thumb">+</div>';
    }
    echo '<span class="image-picker-text">' . htmlspecialchars($text) . '</span>';
    echo '</div></div></div>';
}

function admin_field_video(string $label, string $name, string $id, string $value = ''): void {
    echo '<div class="form-row"><label>' . htmlspecialchars($label) . '</label><div class="field video-field">';
    echo '<input type="text" name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($id) . '" value="' . htmlspecialchars($value) . '" placeholder="https:// CDN 外链，或 ./assets/uploads/videos/...">';
    echo '<button type="button" class="btn btn-sm btn-secondary" onclick="adminPickVideo(\'' . htmlspecialchars($id) . '\')">从视频库选择</button>';
    if ($value !== '') {
        $hint = preg_match('#^https?://#i', $value) ? '当前为外部/CDN 链接' : '当前为本地视频库路径';
        echo '<p class="tip" style="margin:4px 0 0">' . htmlspecialchars($hint) . '</p>';
    } else {
        echo '<p class="tip" style="margin:4px 0 0">视频较大时可只填 CDN 地址；也可上传至视频库。</p>';
    }
    echo '</div></div>';
}

function admin_field_datetime(string $label, string $name, string $value = ''): void {
    $display = admin_format_datetime($value);
    $fieldId = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
    echo '<div class="form-row"><label>' . htmlspecialchars($label) . '</label><div class="field datetime-field">';
    echo '<div class="datetime-input-row">';
    echo '<input type="text" name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($fieldId) . '" class="admin-datetime-input" value="' . htmlspecialchars($display) . '" placeholder="2022-11-11 11:11:11" maxlength="19" autocomplete="off" readonly>';
    echo '<button type="button" class="btn btn-sm btn-secondary admin-datetime-btn" data-mode="datetime" data-target="' . htmlspecialchars($fieldId) . '">选择</button>';
    echo '</div></div></div>';
}

function admin_field_date(string $label, string $name, string $value = ''): void {
    $display = admin_format_date($value);
    $fieldId = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
    echo '<div class="form-row"><label>' . htmlspecialchars($label) . '</label><div class="field datetime-field">';
    echo '<div class="datetime-input-row">';
    echo '<input type="text" name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($fieldId) . '" class="admin-date-input" value="' . htmlspecialchars($display) . '" placeholder="2025-11-11" maxlength="10" autocomplete="off" readonly>';
    echo '<button type="button" class="btn btn-sm btn-secondary admin-datetime-btn" data-mode="date" data-target="' . htmlspecialchars($fieldId) . '">选择</button>';
    echo '</div></div></div>';
}

function admin_field_color(string $label, string $name, string $value, string $id = ''): void {
    $value = admin_norm_color($value);
    if ($id === '') $id = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
    echo '<div class="form-row"><label>' . htmlspecialchars($label) . '</label><div class="field color-field">';
    echo '<input type="color" id="' . htmlspecialchars($id) . '_picker" value="' . htmlspecialchars($value) . '" oninput="adminSyncColor(this,\'' . htmlspecialchars($id) . '\')">';
    echo '<input name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($id) . '" type="text" value="' . htmlspecialchars($value) . '" oninput="adminSyncColorText(this,\'' . htmlspecialchars($id) . '_picker\')">';
    echo '<span class="color-swatch" id="' . htmlspecialchars($id) . '_swatch" style="background:' . htmlspecialchars($value) . '"></span>';
    echo '</div></div>';
}

function admin_field_textarea(string $label, string $name, string $value, int $rows = 5): void {
    echo '<div class="form-row form-row-top"><label>' . htmlspecialchars($label) . '</label><div class="field">';
    echo '<textarea name="' . htmlspecialchars($name) . '" rows="' . $rows . '">' . htmlspecialchars($value) . '</textarea>';
    echo '</div></div>';
}

function admin_field_select(string $label, string $name, array $options, string $selected): void {
    echo '<div class="form-row"><label>' . htmlspecialchars($label) . '</label><div class="field"><select name="' . htmlspecialchars($name) . '">';
    foreach ($options as $val => $text) {
        $sel = ((string)$val === (string)$selected) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars((string)$val) . '"' . $sel . '>' . htmlspecialchars((string)$text) . '</option>';
    }
    echo '</select></div></div>';
}

function admin_field_checkbox(string $label, string $name, bool $checked, string $text): void {
    echo '<div class="form-row"><label>' . htmlspecialchars($label) . '</label><div class="field">';
    echo '<label class="chk-inline"><input type="checkbox" name="' . htmlspecialchars($name) . '" value="1"' . ($checked ? ' checked' : '') . '> ' . htmlspecialchars($text) . '</label>';
    echo '</div></div>';
}

function admin_field_text_hint(string $label, string $name, string $value, string $hint, string $type = 'text', string $extraAttrs = '', bool $hintHtml = false): void {
    echo '<div class="form-field-hint">';
    echo '<div class="form-row"><label>' . htmlspecialchars($label) . '</label><div class="field">';
    echo '<input name="' . htmlspecialchars($name) . '" type="' . htmlspecialchars($type) . '" value="' . htmlspecialchars($value) . '" ' . $extraAttrs . '>';
    echo '</div></div>';
    echo '<div class="form-hint">';
    echo $hintHtml ? $hint : htmlspecialchars($hint);
    echo '</div></div>';
}

function admin_field_checkbox_hint(string $label, string $name, bool $checked, string $hint): void {
    echo '<div class="form-field-checkbox-inline">';
    echo '<div class="form-row form-row-inline"><label>' . htmlspecialchars($label) . '</label>';
    echo '<div class="field checkbox-inline-field"><label class="chk-inline"><input type="checkbox" name="' . htmlspecialchars($name) . '" value="1"' . ($checked ? ' checked' : '') . '></label></div>';
    echo '<span class="form-hint-inline">' . htmlspecialchars($hint) . '</span>';
    echo '</div></div>';
}

function admin_field_readonly_hint(string $label, string $value, string $hint): void {
    echo '<div class="form-field-hint">';
    echo '<div class="form-row"><label>' . htmlspecialchars($label) . '</label><div class="field">';
    echo '<input type="text" readonly onclick="this.select()" value="' . htmlspecialchars($value) . '" class="input-readonly-mono">';
    echo '</div></div>';
    echo '<div class="form-hint">' . $hint . '</div></div>';
}

function admin_save_app_config(array $topPatch, array $wechatPatch = []): bool {
    $path = admin_site_root() . '/config/config.inc.php';
    if (!is_file($path)) {
        return false;
    }
    if (!is_writable($path)) {
        @chmod($path, 0666);
    }
    if (!is_writable($path)) {
        return false;
    }
    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($path, true);
    }
    $cfg = require $path;
    if (!is_array($cfg)) {
        $cfg = [];
    }
    foreach ($topPatch as $k => $v) {
        $cfg[$k] = $v;
    }
    if ($wechatPatch) {
        $wechat = is_array($cfg['wechat'] ?? null) ? $cfg['wechat'] : [];
        foreach ($wechatPatch as $k => $v) {
            $wechat[$k] = $v;
        }
        $cfg['wechat'] = $wechat;
    }
    $content = "<?php\n\$app_config = " . var_export($cfg, true) . ";\nreturn \$app_config;\n";
    $tmp = $path . '.tmp';
    if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
        return false;
    }
    return @rename($tmp, $path);
}

/** @deprecated use admin_save_app_config */
function admin_save_wechat_config(array $patch): bool {
    return admin_save_app_config([], $patch);
}

function admin_link_parse(string $raw): array {
    $raw = trim($raw);
    if ($raw === '') return ['type' => 'none'];
    $decoded = json_decode($raw, true);
    if (is_array($decoded) && isset($decoded['type'])) return $decoded;
    if (preg_match('#^https?://#i', $raw)) return ['type' => 'external', 'url' => $raw];
    return ['type' => 'internal', 'module' => 'page', 'pageKey' => $raw];
}

function admin_link_format(string $raw, array $pageNameMap = [], ?PDO $pdo = null): string {
    $link = admin_link_parse($raw);
    $type = $link['type'] ?? 'none';
    if ($type === 'none') return '无';
    if ($type === 'external') {
        $url = trim((string)($link['url'] ?? ''));
        return $url === '' ? '外链（未填写）' : ('外链：' . $url);
    }
    $module = $link['module'] ?? 'page';
    if ($module === 'article') {
        $aid = $link['articleId'] ?? '';
        if ($aid === '' || $aid === null) return '内链：资讯列表';
        if ($pdo) {
            $stmt = $pdo->prepare('SELECT title FROM articles WHERE id = ? LIMIT 1');
            $stmt->execute([(int)$aid]);
            $title = $stmt->fetchColumn();
            if ($title) return '内链：' . $title . '（ID=' . (int)$aid . '）';
        }
        return '内链：文章ID=' . $aid;
    }
    if ($module === 'product') {
        $pid = $link['productId'] ?? '';
        if ($pid === '' || $pid === null) return '内链：商品列表';
        if ($pdo && $pdo->query("SHOW TABLES LIKE 'products'")->fetch()) {
            $stmt = $pdo->prepare('SELECT name FROM products WHERE id = ? LIMIT 1');
            $stmt->execute([(int)$pid]);
            $name = $stmt->fetchColumn();
            if ($name) return '内链：' . $name . '（ID=' . (int)$pid . '）';
        }
        return '内链：商品ID=' . $pid;
    }
    if ($module === 'system') {
        $route = (string)($link['systemRoute'] ?? '');
        $params = is_array($link['systemParams'] ?? null) ? $link['systemParams'] : [];
        if ($route === 'product-detail' && !empty($params['id'])) {
            $pid = (int)$params['id'];
            if ($pdo && $pdo->query("SHOW TABLES LIKE 'products'")->fetch()) {
                $stmt = $pdo->prepare('SELECT name FROM products WHERE id = ? LIMIT 1');
                $stmt->execute([$pid]);
                $name = $stmt->fetchColumn();
                if ($name) return '内链：' . $name . '（ID=' . $pid . '）';
            }
            return '内链：商品ID=' . $pid;
        }
        if ($route === 'article-detail' && !empty($params['id'])) {
            $aid = (int)$params['id'];
            if ($pdo) {
                $stmt = $pdo->prepare('SELECT title FROM articles WHERE id = ? LIMIT 1');
                $stmt->execute([$aid]);
                $title = $stmt->fetchColumn();
                if ($title) return '内链：' . $title . '（ID=' . $aid . '）';
            }
            return '内链：文章ID=' . $aid;
        }
        $opts = admin_system_link_options();
        return '系统：' . ($opts[$route] ?? $route);
    }
    $pk = (string)($link['pageKey'] ?? 'home');
    $name = $pageNameMap[$pk] ?? $pk;
    return '内链：' . $name;
}

function admin_link_build_from_post(array $post, string $prefix = ''): string {
    $type = (string)($post[$prefix . 'link_type'] ?? 'none');
    if ($type === 'none') return json_encode(['type' => 'none'], JSON_UNESCAPED_UNICODE);
    if ($type === 'external') {
        $url = trim((string)($post[$prefix . 'link_external_url'] ?? ''));
        if ($url === '') return json_encode(['type' => 'none'], JSON_UNESCAPED_UNICODE);
        if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
        return json_encode(['type' => 'external', 'url' => $url], JSON_UNESCAPED_UNICODE);
    }
    $target = (string)($post[$prefix . 'link_internal_target'] ?? 'page:home');
    if (strpos($target, 'system:') === 0) {
        $route = substr($target, 7);
        if ($route === 'product-list') {
            $pid = trim((string)($post[$prefix . 'link_product_id'] ?? ''));
            $catId = trim((string)($post[$prefix . 'link_product_category_id'] ?? ''));
            $params = [];
            if ($catId !== '') $params['category_id'] = $catId;
            if ($pid !== '') {
                return json_encode(['type' => 'internal', 'module' => 'system', 'systemRoute' => 'product-detail', 'systemParams' => ['id' => $pid]], JSON_UNESCAPED_UNICODE);
            }
            $out = ['type' => 'internal', 'module' => 'system', 'systemRoute' => 'product-list'];
            if ($params) $out['systemParams'] = $params;
            return json_encode($out, JSON_UNESCAPED_UNICODE);
        }
        if ($route === 'search-article') {
            $aid = trim((string)($post[$prefix . 'link_article_id'] ?? ''));
            $catId = trim((string)($post[$prefix . 'link_article_category_id'] ?? ''));
            $params = [];
            if ($catId !== '') $params['category_id'] = $catId;
            if ($aid !== '') {
                return json_encode(['type' => 'internal', 'module' => 'system', 'systemRoute' => 'article-detail', 'systemParams' => ['id' => $aid]], JSON_UNESCAPED_UNICODE);
            }
            $out = ['type' => 'internal', 'module' => 'system', 'systemRoute' => 'search-article'];
            if ($params) $out['systemParams'] = $params;
            return json_encode($out, JSON_UNESCAPED_UNICODE);
        }
        return json_encode(['type' => 'internal', 'module' => 'system', 'systemRoute' => $route], JSON_UNESCAPED_UNICODE);
    }
    if ($target === 'article') {
        $aid = trim((string)($post[$prefix . 'link_article_id'] ?? ''));
        if ($aid !== '') {
            return json_encode(['type' => 'internal', 'module' => 'system', 'systemRoute' => 'article-detail', 'systemParams' => ['id' => $aid]], JSON_UNESCAPED_UNICODE);
        }
        return json_encode(['type' => 'internal', 'module' => 'system', 'systemRoute' => 'search-article'], JSON_UNESCAPED_UNICODE);
    }
    if ($target === 'product') {
        $pid = trim((string)($post[$prefix . 'link_product_id'] ?? ''));
        if ($pid !== '') {
            return json_encode(['type' => 'internal', 'module' => 'system', 'systemRoute' => 'product-detail', 'systemParams' => ['id' => $pid]], JSON_UNESCAPED_UNICODE);
        }
        return json_encode(['type' => 'internal', 'module' => 'system', 'systemRoute' => 'product-list'], JSON_UNESCAPED_UNICODE);
    }
    if (strpos($target, 'page:') === 0) {
        $pk = substr($target, 5) ?: 'home';
        return json_encode(['type' => 'internal', 'module' => 'page', 'pageKey' => $pk], JSON_UNESCAPED_UNICODE);
    }
    return json_encode(['type' => 'none'], JSON_UNESCAPED_UNICODE);
}

function admin_field_link(string $label, array $pages, array $articles, array $products, string $raw = '', string $prefix = ''): void {
    $link = admin_link_parse($raw);
    $type = $link['type'] ?? 'none';
    $module = $link['module'] ?? 'page';
    $pageKey = (string)($link['pageKey'] ?? 'home');
    $articleId = (string)($link['articleId'] ?? '');
    $productId = (string)($link['productId'] ?? '');
    $extUrl = (string)($link['url'] ?? '');
    $internalTarget = 'page:' . $pageKey;
    if ($type === 'internal') {
        if ($module === 'article') {
            $internalTarget = 'system:search-article';
        } elseif ($module === 'product') {
            $internalTarget = 'system:product-list';
        } elseif ($module === 'system') {
            $route = (string)($link['systemRoute'] ?? '');
            $params = is_array($link['systemParams'] ?? null) ? $link['systemParams'] : [];
            if ($route === 'product-detail' && !empty($params['id'])) {
                $internalTarget = 'system:product-list';
                $productId = (string)$params['id'];
            } elseif ($route === 'article-detail' && !empty($params['id'])) {
                $internalTarget = 'system:search-article';
                $articleId = (string)$params['id'];
            } else {
                $internalTarget = 'system:' . $route;
            }
        } else {
            $internalTarget = 'page:' . ($pageKey !== '' ? $pageKey : 'home');
        }
    }
    $productCatId = '';
    $articleCatId = '';
    if ($type === 'internal' && $module === 'system') {
        $route = (string)($link['systemRoute'] ?? '');
        $params = is_array($link['systemParams'] ?? null) ? $link['systemParams'] : [];
        if ($route === 'product-list' && !empty($params['category_id'])) {
            $productCatId = (string)$params['category_id'];
        }
        if ($route === 'search-article' && !empty($params['category_id'])) {
            $articleCatId = (string)$params['category_id'];
        }
    }
    global $pdo;
    $productCats = [];
    $articleCats = [];
    if ($pdo) {
        if ($pdo->query("SHOW TABLES LIKE 'product_categories'")->fetch()) {
            $productCats = $pdo->query('SELECT id,name FROM product_categories WHERE status=1 ORDER BY sort_order DESC,id ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        if ($pdo->query("SHOW TABLES LIKE 'article_categories'")->fetch()) {
            $articleCats = $pdo->query('SELECT id,name FROM article_categories WHERE status=1 ORDER BY sort_order DESC,id ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    }
    echo '<div class="form-row form-row-top"><label>' . htmlspecialchars($label) . '</label><div class="field link-field">';
    echo '<select name="' . htmlspecialchars($prefix . 'link_type') . '" class="link-type-select" onchange="adminLinkTypeChange(this)">';
    foreach (['none' => '无链接', 'internal' => '内链', 'external' => '外链'] as $val => $text) {
        $sel = ($type === $val) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($val) . '"' . $sel . '>' . htmlspecialchars($text) . '</option>';
    }
    echo '</select>';
    $internalDisplay = $type === 'internal' ? 'block' : 'none';
    echo '<div class="link-internal-box" style="margin-top:8px;display:' . $internalDisplay . '">';
    echo '<select name="' . htmlspecialchars($prefix . 'link_internal_target') . '" class="link-internal-target" onchange="adminLinkInternalChange(this)">';
    foreach ($pages as $p) {
        $key = (string)($p['page_key'] ?? '');
        $val = 'page:' . $key;
        $sel = ($internalTarget === $val) ? ' selected' : '';
        $labelText = '页面 · ' . (string)($p['page_name'] ?? $key);
        echo '<option value="' . htmlspecialchars($val) . '"' . $sel . '>' . htmlspecialchars($labelText) . '</option>';
    }
    foreach (admin_system_link_options() as $route => $labelText) {
        $val = 'system:' . $route;
        $sel = ($internalTarget === $val) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($val) . '"' . $sel . '>系统 · ' . htmlspecialchars($labelText) . '</option>';
    }
    echo '</select>';
    $articleDisplay = ($type === 'internal' && $internalTarget === 'system:search-article') ? 'block' : 'none';
    echo '<div class="link-article-search" style="margin-top:8px;display:' . $articleDisplay . '">';
    echo '<input type="hidden" name="' . htmlspecialchars($prefix . 'link_article_id') . '" class="link-article-id" value="' . htmlspecialchars($articleId) . '">';
    echo '<input type="text" class="link-article-q" placeholder="输入关键词搜索文章（最多20条）" style="width:100%;margin-bottom:6px" value="">';
    echo '<div class="link-article-picked" style="font-size:12px;color:#666;margin-bottom:6px">' . ($articleId !== '' ? '已选文章 ID: ' . htmlspecialchars($articleId) : '未选择具体文章（留空=资讯列表）') . '</div>';
    echo '<div class="link-article-results" style="max-height:180px;overflow:auto;border:1px solid #eee;border-radius:6px"></div>';
    if ($articleCats) {
        echo '<div class="link-article-category" style="margin-top:8px"><label style="font-size:12px;color:#666">或指定新闻分类</label><select name="' . htmlspecialchars($prefix . 'link_article_category_id') . '" style="width:100%;margin-top:4px;padding:6px;border:1px solid #ddd;border-radius:6px"><option value="">全部分类</option>';
        foreach ($articleCats as $c) {
            $sel = ((string)$c['id'] === $articleCatId) ? ' selected' : '';
            echo '<option value="' . (int)$c['id'] . '"' . $sel . '>' . htmlspecialchars($c['name']) . '</option>';
        }
        echo '</select></div>';
    }
    echo '</div>';
    $productDisplay = ($type === 'internal' && $internalTarget === 'system:product-list') ? 'block' : 'none';
    echo '<div class="link-product-search" style="margin-top:8px;display:' . $productDisplay . '">';
    echo '<input type="hidden" name="' . htmlspecialchars($prefix . 'link_product_id') . '" class="link-product-id" value="' . htmlspecialchars($productId) . '">';
    echo '<input type="text" class="link-product-q" placeholder="输入关键词搜索商品（最多20条）" style="width:100%;margin-bottom:6px" value="">';
    echo '<div class="link-product-picked" style="font-size:12px;color:#666;margin-bottom:6px">' . ($productId !== '' ? '已选商品 ID: ' . htmlspecialchars($productId) : '未选择具体商品（留空=商品列表）') . '</div>';
    echo '<div class="link-product-results" style="max-height:180px;overflow:auto;border:1px solid #eee;border-radius:6px"></div>';
    if ($productCats) {
        echo '<div class="link-product-category" style="margin-top:8px"><label style="font-size:12px;color:#666">或指定商品分类</label><select name="' . htmlspecialchars($prefix . 'link_product_category_id') . '" style="width:100%;margin-top:4px;padding:6px;border:1px solid #ddd;border-radius:6px"><option value="">全部分类</option>';
        foreach ($productCats as $c) {
            $sel = ((string)$c['id'] === $productCatId) ? ' selected' : '';
            echo '<option value="' . (int)$c['id'] . '"' . $sel . '>' . htmlspecialchars($c['name']) . '</option>';
        }
        echo '</select></div>';
    }
    echo '</div></div>';
    $externalDisplay = $type === 'external' ? 'block' : 'none';
    echo '<input name="' . htmlspecialchars($prefix . 'link_external_url') . '" class="link-external-input" placeholder="www.example.com 或 https://..." value="' . htmlspecialchars($extUrl) . '" style="margin-top:8px;display:' . $externalDisplay . '">';
    echo '</div></div>';
}

function admin_menu_items(): array {
    static $menu = null;
    if ($menu === null) {
        $raw = @file_get_contents(__DIR__ . '/../_menu.json');
        $menu = json_decode($raw ?: '[]', true) ?: [];
        if (function_exists('admin_filter_menu')) {
            $menu = admin_filter_menu($menu);
        }
    }
    return $menu;
}

function admin_menu_href_active(string $href, string $activeHref): bool {
    if ($href === '' || $activeHref === '') return false;
    return $href === $activeHref || strpos($activeHref, $href) === 0 || strpos($href, $activeHref) === 0;
}

function admin_menu_branch_has_active(array $node, string $activeHref): bool {
    $href = (string)($node['href'] ?? '');
    if ($href !== '' && admin_menu_href_active($href, $activeHref)) return true;
    foreach ($node['children'] ?? [] as $child) {
        if (admin_menu_branch_has_active($child, $activeHref)) return true;
    }
    return false;
}

function admin_render_sidebar_menu(array $nodes, string $activeHref, int $depth = 0): void {
    foreach ($nodes as $node) {
        $children = $node['children'] ?? [];
        $label = (string)($node['label'] ?? '');
        $href = (string)($node['href'] ?? '');
        $icon = (string)($node['icon'] ?? '');
        $disabled = !empty($node['disabled']) || ($href === '' && empty($children));
        if (!empty($children)) {
            $open = admin_menu_branch_has_active($node, $activeHref);
            $cls = 'side-group collapsed';
            if ($open) $cls = 'side-group';
            echo '<div class="' . $cls . '" data-depth="' . $depth . '">';
            echo '<div class="side-group-head" role="button" tabindex="0" aria-expanded="' . ($open ? 'true' : 'false') . '">';
            if ($icon !== '') echo '<span class="side-ico">' . htmlspecialchars($icon) . '</span>';
            echo '<span class="side-group-title">' . htmlspecialchars($label) . '</span>';
            echo '<span class="side-arrow">▾</span></div>';
            echo '<div class="side-group-body">';
            admin_render_sidebar_menu($children, $activeHref, $depth + 1);
            echo '</div></div>';
            continue;
        }
        $active = admin_menu_href_active($href, $activeHref) ? ' active' : '';
        if ($disabled) {
            echo '<span class="side-disabled side-depth-' . $depth . $active . '" title="该组件无需后台配置">' . htmlspecialchars($label) . '</span>';
            continue;
        }
        echo '<a class="side-depth-' . $depth . $active . '" href="' . htmlspecialchars($href) . '">' . htmlspecialchars($label) . '</a>';
    }
}

function admin_guide_path(string $guideKey): string {
    if ($guideKey === '') return '';
    $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $guideKey);
    // layout.php 在 admin/inc/，引导图在 admin/assets/guides/
    $file = dirname(__DIR__) . '/assets/guides/' . $safe . '.png';
    return is_file($file) ? asset_url('assets/guides/' . $safe . '.png') : '';
}

/** 非首页 Tab 页组件：flow 引导图键名与 instance_id 一致（构建时生成 flow_{id}.png） */
function admin_guide_flow_key(string $pageKey, string $instanceId): string {
    if ($pageKey === '' || $pageKey === 'home' || $instanceId === '') return '';
    return preg_replace('/[^a-zA-Z0-9_-]/', '_', $instanceId);
}

function admin_batch_list_open(string $confirmMsg = '确定删除选中的记录？删除后不可恢复'): void {
    echo '<form method="post" class="batch-list-form" data-confirm="' . htmlspecialchars($confirmMsg) . '" onsubmit="return adminBatchDeleteConfirm(this)">';
    echo '<input type="hidden" name="batch_del" value="1">';
    echo '<div class="batch-bar"><button type="submit" class="btn btn-sm btn-danger">批量删除</button></div>';
}

function admin_batch_list_close(): void {
    echo '</form>';
}

function admin_batch_list_th(): void {
    echo '<th class="col-check"><input type="checkbox" class="batch-check-all" onclick="adminBatchToggleAll(this)"></th>';
}

function admin_batch_list_td(int $id): void {
    echo '<td class="col-check"><input type="checkbox" name="ids[]" value="' . $id . '" class="batch-row-check"></td>';
}

function admin_guide_tip(string $guideKey, string $caption = ''): void {
    $path = admin_guide_path($guideKey);
    if ($caption === '' && $path === '') return;
    if ($caption === '') {
        $caption = '发布时根据真实页面截图生成，红圈标注即当前正在编辑的组件位置。';
    }
    echo '<span class="guide-wrap guide-inline" tabindex="0" title="悬停查看位置示意">';
    echo '<span class="guide-tip">?</span>';
    echo '<span class="guide-preview">';
    if ($path !== '') {
        echo '<img src="' . htmlspecialchars($path) . '" alt="位置示意">';
        echo '<div class="guide-cap">' . htmlspecialchars($caption) . '</div>';
    } else {
        echo '<div class="guide-cap guide-cap-only" style="padding:16px 12px;margin:0">' . htmlspecialchars($caption) . '（示意图尚未生成，请重新 Build 部署后再看。）</div>';
    }
    echo '</span></span>';
}

/** 多步新手引导：一张合成图含 ①点「我」②点目标入口，构建时生成 flow_{key}.png */
function admin_guide_flow(string $flowKey, string $caption = ''): void {
    $path = admin_guide_path('flow_' . $flowKey);
    if ($path === '') return;
    if ($caption === '') {
        $caption = '上图两步为前台操作路径：先点底部「我」，再点对应功能入口（红圈标注）。';
    }
    echo '<span class="guide-wrap guide-inline guide-flow-wrap" tabindex="0" title="悬停查看新手引导">';
    echo '<span class="guide-tip guide-tip-flow">导</span>';
    echo '<span class="guide-preview guide-flow"><img src="' . htmlspecialchars($path) . '" alt="新手引导">';
    echo '<div class="guide-cap">' . htmlspecialchars($caption) . '</div></span></span>';
}

function admin_layout_start(string $title, string $activeHref = '', string $guideInstanceId = '', string $guideCaption = '', string $guideFlowKey = '', string $pageActions = ''): void {
    $user = htmlspecialchars($_SESSION['admin_username'] ?? 'admin');
    $menu = admin_menu_items();
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . htmlspecialchars($title) . ' - 管理后台</title>';
    echo '<style>
      :root,[data-admin-theme="dark"]{--adm-top-bg:linear-gradient(135deg,#1a2332 0%,#243447 100%);--adm-top-text:rgba(255,255,255,.75);--adm-top-muted:rgba(255,255,255,.65);--adm-side-bg:linear-gradient(180deg,#1e2a3a 0%,#162030 100%);--adm-side-head:rgba(255,255,255,.92);--adm-side-link:rgba(255,255,255,.72);--adm-side-hover-bg:rgba(46,204,113,.15);--adm-side-active-bg:linear-gradient(90deg,rgba(46,204,113,.25),rgba(46,204,113,.08));--adm-side-active-color:#6ee7a0;--adm-side-active-border:#2ecc71;--adm-body-bg:#eef1f6}
      [data-admin-theme="light"]{--adm-top-bg:#fff;--adm-top-text:#333;--adm-top-muted:#666;--adm-side-bg:#f8f9fb;--adm-side-head:#222;--adm-side-link:#555;--adm-side-hover-bg:rgba(52,152,219,.12);--adm-side-active-bg:linear-gradient(90deg,rgba(52,152,219,.18),rgba(52,152,219,.06));--adm-side-active-color:#2980b9;--adm-side-active-border:#3498db;--adm-body-bg:#f0f2f5}
      [data-admin-theme="blue"]{--adm-top-bg:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);--adm-top-text:rgba(255,255,255,.85);--adm-top-muted:rgba(255,255,255,.7);--adm-side-bg:linear-gradient(180deg,#1a365d 0%,#152a45 100%);--adm-side-head:rgba(255,255,255,.95);--adm-side-link:rgba(255,255,255,.78);--adm-side-hover-bg:rgba(96,165,250,.2);--adm-side-active-bg:linear-gradient(90deg,rgba(59,130,246,.35),rgba(59,130,246,.1));--adm-side-active-color:#93c5fd;--adm-side-active-border:#3b82f6;--adm-body-bg:#e8eef5}
      [data-admin-theme="green"]{--adm-top-bg:linear-gradient(135deg,#14532d 0%,#16a34a 100%);--adm-top-text:rgba(255,255,255,.85);--adm-top-muted:rgba(255,255,255,.7);--adm-side-bg:linear-gradient(180deg,#166534 0%,#14532d 100%);--adm-side-head:rgba(255,255,255,.95);--adm-side-link:rgba(255,255,255,.78);--adm-side-hover-bg:rgba(74,222,128,.18);--adm-side-active-bg:linear-gradient(90deg,rgba(34,197,94,.35),rgba(34,197,94,.1));--adm-side-active-color:#86efac;--adm-side-active-border:#22c55e;--adm-body-bg:#ecfdf3}
      *{box-sizing:border-box} body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","PingFang SC","Microsoft YaHei",sans-serif;background:var(--adm-body-bg);color:#333}
      .top{position:fixed;top:0;left:0;right:0;height:56px;background:var(--adm-top-bg);border-bottom:none;display:flex;align-items:center;padding:0 24px;z-index:200;box-shadow:0 2px 12px rgba(0,0,0,.12)}
      .top .logo{font-weight:700;color:var(--adm-top-text);margin-right:14px;font-size:17px;letter-spacing:.5px}.top .title{font-size:14px;color:var(--adm-top-muted)}
      .top .theme-pick{margin-left:auto;margin-right:14px;display:flex;align-items:center;gap:6px;font-size:12px;color:var(--adm-top-muted)}
      .top .theme-pick select{padding:4px 8px;border-radius:4px;border:1px solid #ccc;background:#fff;color:#333;font-size:12px;cursor:pointer;min-width:72px}
      .top .theme-pick select option{background:#fff;color:#333}
      .top .right{font-size:13px;color:var(--adm-top-muted)}
      .shell{display:flex;padding-top:56px;min-height:100vh}
      .side{width:248px;flex-shrink:0;background:var(--adm-side-bg);border-right:none;position:fixed;top:56px;bottom:0;overflow-y:auto;padding:12px 0;--side-pad:14px;--side-step:2em;box-shadow:2px 0 16px rgba(0,0,0,.08)}
      .side-group{margin:4px 0}
      .side-group-head{display:flex;align-items:center;gap:8px;padding:11px var(--side-pad);cursor:pointer;user-select:none;font-size:14px;font-weight:600;color:var(--adm-side-head);border-left:3px solid transparent;border-radius:0 8px 8px 0;margin-right:8px}
      .side-group[data-depth="1"]>.side-group-head{padding-left:calc(var(--side-pad) + var(--side-step));font-weight:500;font-size:13px;opacity:.88}
      .side-group[data-depth="2"]>.side-group-head{padding-left:calc(var(--side-pad) + var(--side-step) * 2);font-weight:500;font-size:13px;opacity:.82}
      .side-group-head:hover{background:var(--adm-side-hover-bg)}
      .side-ico{width:20px;text-align:center;flex-shrink:0;font-size:16px;line-height:1}
      .side-group-title{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
      .side-arrow{margin-left:auto;font-size:10px;opacity:.45;transition:transform .2s;flex-shrink:0}
      .side-group.collapsed>.side-group-body{display:none}
      .side-group.collapsed>.side-group-head .side-arrow{transform:rotate(-90deg)}
      .side-group-body{padding:2px 0 6px}
      .side a,.side-disabled{display:block;padding:9px var(--side-pad);color:var(--adm-side-link);text-decoration:none;font-size:13px;border-left:3px solid transparent;line-height:1.4;margin-right:8px;border-radius:0 8px 8px 0;transition:background .15s,color .15s}
      .side a.side-depth-1,.side-disabled.side-depth-1{padding-left:calc(var(--side-pad) + var(--side-step));font-size:13px;opacity:.92}
      .side a.side-depth-2,.side-disabled.side-depth-2{padding-left:calc(var(--side-pad) + var(--side-step) * 2);font-size:12px;opacity:.85}
      .side a.side-depth-3,.side-disabled.side-depth-3{padding-left:calc(var(--side-pad) + var(--side-step) * 3);font-size:12px;opacity:.8}
      .side a:hover{background:var(--adm-side-hover-bg);color:var(--adm-side-head)}
      .side a.active{background:var(--adm-side-active-bg);color:var(--adm-side-active-color);border-left-color:var(--adm-side-active-border);font-weight:600}
      .side-disabled{color:#c8c8c8;cursor:default;user-select:none}
      .side .foot{border-top:1px solid #f0f0f0;margin-top:12px;padding:8px 16px}
      .side .foot a{padding:8px 0;font-size:13px;color:#3498db;text-decoration:none;display:block;border:none}
      .main{flex:1;margin-left:248px;padding:20px 24px 40px;min-height:calc(100vh - 56px)}
      .card{background:#fff;border-radius:8px;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:16px}
      table{width:100%;border-collapse:collapse;font-size:14px} th,td{padding:10px 12px;border-bottom:1px solid #f0f0f0;text-align:left}
      th{background:#fafafa;font-weight:600} .btn{display:inline-block;padding:8px 16px;background:#2ecc71;color:#fff;border:none;border-radius:4px;cursor:pointer;text-decoration:none;font-size:13px}
      .btn-sm{padding:4px 10px;font-size:12px}.btn-danger{background:#e74c3c}.btn-secondary{background:#3498db}
      .main>h2:not(.page-title-wrap),.admin-content,.form-grid,.card>h3:not(.guide-heading),.msg{width:80%;max-width:1400px}
      .card table,.card .table-wrap{width:100%;max-width:none}
      .main>h2.page-title-wrap{width:auto;max-width:none;position:relative;z-index:50}
      .form-row{display:flex;align-items:center;gap:12px;margin-top:10px}
      .form-row-top{align-items:flex-start}
      .form-row>label{width:72px;flex-shrink:0;margin:0;font-weight:600;font-size:13px;text-align:left;color:#555}
      .settings-form .form-row{margin-top:12px}
      .settings-form .form-row>label{width:148px;flex-shrink:0}
      .settings-form .form-row>.field{width:420px;flex:0 0 420px;max-width:420px;min-width:420px}
      .settings-form .form-row .field input,.settings-form .form-row .field select,.settings-form .form-row .field textarea{width:100%;max-width:420px;box-sizing:border-box}
      .settings-form .form-field-hint{margin-top:14px}
      .settings-form .form-field-hint>.form-row{margin-top:0}
      .settings-form .form-field-hint>.form-hint{margin:6px 0 0 160px;font-size:13px;color:#888;line-height:1.55;max-width:720px}
      .settings-form .form-field-hint>.form-hint a{color:#409eff;text-decoration:none}
      .settings-form .form-field-hint>.form-hint a:hover{text-decoration:underline}
      .settings-form .input-readonly-mono{background:#f5f5f5;cursor:text;font-family:monospace;font-size:13px}
      .settings-form .form-field-checkbox-inline{margin-top:12px}
      .settings-form .form-row-inline{display:flex;align-items:center;gap:10px;margin-top:0;flex-wrap:wrap}
      .settings-form .form-row-inline>label{width:148px;flex-shrink:0;margin:0}
      .settings-form .form-row-inline>.checkbox-inline-field{width:auto;flex:0 0 auto;min-width:0;max-width:none}
      .settings-form .form-row-inline .form-hint-inline{font-size:13px;color:#888;line-height:1.5;flex:1;min-width:160px}
      .form-row-top>label{padding-top:8px}
      .form-row-top.field-editor-row>label{width:96px;white-space:nowrap}
      .field.link-field{display:flex;flex-direction:column;align-items:stretch;gap:8px;width:100%}
      .field.link-field .link-type-select,.field.link-field .link-internal-target,.field.link-field .link-external-input{width:100%;max-width:480px;box-sizing:border-box}
      .field.link-field .link-internal-box,.field.link-field .link-article-search,.field.link-field .link-product-search{width:100%;max-width:480px}
      .form-row-compact>label{width:100px;flex-shrink:0}
      .form-row-compact>.field.field-narrow{flex:0 0 100px;max-width:100px;min-width:100px}
      .form-row-compact>.field.field-narrow input{width:100%}
      .article-widget-form .form-row>label{width:96px;flex-shrink:0}
      .article-widget-form .form-row>.field{flex:0 1 280px;max-width:280px;min-width:160px}
      .article-widget-form .form-row>.field input,.article-widget-form .form-row>.field select{width:100%}
      .ql-editor img{max-width:100%;height:auto;cursor:pointer}
      .field-editor .ql-container{min-height:200px}
      .form-row>.field{flex:1;max-width:100%;min-width:0}
      .form-row .field-editor{width:100%;max-width:100%}
      .form-row .field input,.form-row .field textarea,.form-row .field select{width:100%;padding:6px 10px;border:1px solid #ddd;border-radius:4px;margin:0;font-size:14px}
      #editor,.ql-toolbar,.ql-container{width:100%}
      #editor{min-height:280px;background:#fff}
      .ql-editor{min-height:260px}
      .image-field{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
      .image-picker-trigger{display:flex;align-items:center;gap:10px;cursor:pointer;border:1px dashed #dcdfe6;border-radius:6px;padding:8px 10px;background:#fafafa;max-width:320px}
      .image-picker-trigger:hover{border-color:#409eff}
      .image-picker-thumb{width:48px;height:48px;object-fit:cover;border-radius:4px;flex-shrink:0}
      .image-picker-placeholder{width:48px;height:48px;border-radius:4px;background:#f0f2f5;display:flex;align-items:center;justify-content:center;font-size:22px;color:#999;flex-shrink:0}
      .image-picker-text{font-size:13px;color:#409eff}
      .video-field{display:flex;flex-direction:column;gap:8px;align-items:flex-start;max-width:520px}
      .admin-media-video-icon{width:100%;aspect-ratio:1;display:flex;align-items:center;justify-content:center;font-size:28px;background:#1a1a2e;color:#fff;border-radius:2px}
      .admin-media-external{padding:10px 20px 0;border-top:1px solid #ebeef5;font-size:13px;color:#606266}
      .admin-media-external input{width:100%;padding:8px 10px;border:1px solid #dcdfe6;border-radius:4px;margin-top:6px;font-size:13px}
      .admin-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:5000;align-items:center;justify-content:center;padding:20px}
      .admin-media-modal{background:#fff;border-radius:8px;width:min(920px,96vw);max-height:92vh;display:flex;flex-direction:column;box-shadow:0 12px 40px rgba(0,0,0,.2);overflow:hidden}
      .admin-media-head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px 10px;border-bottom:1px solid #ebeef5}
      .admin-media-head h3{margin:0;font-size:16px;font-weight:600}
      .admin-media-head small{font-size:12px;color:#999;font-weight:400;margin-left:6px}
      .admin-media-close{border:none;background:transparent;font-size:22px;line-height:1;color:#909399;cursor:pointer;padding:0 4px}
      .admin-media-body{display:flex;height:480px;border-bottom:1px solid #ebeef5;min-height:0}
      .admin-media-side{width:140px;flex-shrink:0;border-right:1px solid #ebeef5;background:#fafbfc;display:flex;flex-direction:column}
      .admin-media-tab{display:block;width:100%;text-align:left;padding:14px 16px;border:none;background:transparent;font-size:14px;color:#606266;cursor:pointer;position:relative}
      .admin-media-tab.active{color:#409eff;background:#fff;font-weight:600}
      .admin-media-tab.active::before{content:"";position:absolute;left:0;top:8px;bottom:8px;width:3px;background:#409eff;border-radius:2px}
      .admin-media-storage{margin-top:auto;padding:12px 14px;font-size:11px;color:#909399}
      .admin-media-main{flex:1;display:flex;flex-direction:column;min-width:0;padding:12px 14px;overflow:hidden}
      .admin-media-toolbar{display:flex;gap:8px;margin-bottom:10px;flex-shrink:0}
      .admin-media-toolbtn{display:inline-flex;align-items:center;gap:4px;padding:6px 12px;border:1px solid #dcdfe6;border-radius:4px;background:#fff;font-size:13px;color:#606266;cursor:pointer}
      .admin-media-toolbtn:hover{color:#409eff;border-color:#c6e2ff}
      .admin-media-breadcrumb{font-size:12px;color:#909399;margin-bottom:8px;flex-shrink:0}
      .admin-media-breadcrumb a{color:#409eff;text-decoration:none;cursor:pointer}
      .admin-media-breadcrumb a.active{color:#303133;pointer-events:none}
      .admin-media-breadcrumb .bc-sep{margin:0 6px;color:#c0c4cc}
      .admin-media-search{flex-shrink:0;margin-bottom:10px}
      .admin-media-search input{width:100%;padding:8px 10px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px}
      .admin-media-grid{flex:1;overflow-y:auto;display:grid;grid-template-columns:repeat(5,1fr);gap:12px;align-content:start;padding:4px 2px}
      .admin-media-item{position:relative;border:2px solid transparent;border-radius:4px;cursor:pointer;background:#f5f7fa;transition:border-color .15s}
      .admin-media-item:hover{border-color:#d9ecff}
      .admin-media-item.selected{border-color:#409eff}
      .admin-media-item.folder-item{padding:12px 8px 8px;text-align:center}
      .admin-media-folder-icon{width:56px;height:44px;margin:0 auto 6px;background:linear-gradient(180deg,#ffd54f 0%,#ffb300 100%);border-radius:4px 4px 2px 2px;position:relative}
      .admin-media-folder-icon::before{content:"";position:absolute;left:0;top:-8px;width:24px;height:10px;background:#ffca28;border-radius:4px 4px 0 0}
      .admin-media-item .item-del{position:absolute;top:4px;right:4px;width:20px;height:20px;border:none;border-radius:50%;background:rgba(0,0,0,.55);color:#fff;font-size:14px;line-height:1;cursor:pointer;opacity:0;transition:opacity .15s;display:flex;align-items:center;justify-content:center;padding:0}
      .admin-media-item:hover .item-del{opacity:1}
      .admin-media-item .item-ren{position:absolute;top:4px;left:4px;width:20px;height:20px;border:none;border-radius:50%;background:rgba(64,158,255,.85);color:#fff;font-size:12px;line-height:1;cursor:pointer;opacity:0;transition:opacity .15s;display:flex;align-items:center;justify-content:center;padding:0}
      .admin-media-item:hover .item-ren{opacity:1}
      .admin-media-item img{width:100%;aspect-ratio:1;object-fit:cover;display:block;border-radius:2px}
      .admin-media-item-name{font-size:11px;color:#606266;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:4px 4px 0;text-align:center}
      .admin-media-check{position:absolute;right:4px;bottom:22px;width:18px;height:18px;background:#409eff;color:#fff;border-radius:2px;font-size:12px;display:flex;align-items:center;justify-content:center}
      .admin-media-empty{grid-column:1/-1;text-align:center;color:#999;padding:60px 20px;font-size:13px}
      .admin-media-foot{display:flex;justify-content:center;gap:12px;padding:14px 20px 18px}
      .admin-modal-box{background:#fff;border-radius:10px;padding:20px;width:min(520px,96vw);box-shadow:0 12px 40px rgba(0,0,0,.2)}
      .admin-modal-box h3{margin:0 0 12px;font-size:16px}
      .admin-modal-preview{width:100%;max-height:220px;object-fit:contain;background:#f5f5f5;border-radius:8px;margin:12px 0;display:block}
      .admin-modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
      .color-field{display:flex;align-items:center;gap:8px}
      .color-field input[type=color]{width:44px;height:32px;padding:2px;border:1px solid #ddd;border-radius:4px;cursor:pointer;flex-shrink:0}
      .color-field input[type=text]{width:88px!important;flex:none!important}
      .color-swatch{width:44px;height:32px;border-radius:4px;border:1px solid #ddd;flex-shrink:0}
      .chk-inline{font-weight:normal!important;text-align:left!important;width:auto!important;display:flex;align-items:center;gap:6px}
      .msg{padding:10px 14px;border-radius:4px;margin-bottom:12px;font-size:14px}
      .msg-ok{background:#edfbf3;color:#1e8449}.msg-err{background:#fef0f0;color:#c0392b}
      .pagination-bar{display:flex;align-items:center;gap:12px;margin-top:16px;font-size:13px;flex-wrap:wrap}
      .pagination-bar select{padding:4px 8px;border:1px solid #ddd;border-radius:4px}
      .pagination-page{color:#666}
      .btn-featured{background:#f39c12}.btn-featured-done{background:#bdc3c7;color:#fff}
      .drag-handle{cursor:grab;color:#999;font-size:18px;user-select:none;width:32px;text-align:center}
      .sortable-table tbody tr{cursor:default}
      .sortable-table tbody tr.sortable-ghost{opacity:.45;background:#edfbf3}
      .tip{font-size:13px;color:#888;margin-top:8px}
      .datetime-field{display:flex;flex-direction:column;gap:6px;align-items:flex-start;max-width:420px}
      .datetime-input-row{display:flex;gap:8px;align-items:center;width:100%}
      .datetime-input-row input[type=text]{flex:1;min-width:0;padding:6px 10px;border:1px solid #ddd;border-radius:4px;font-size:14px;font-family:monospace;letter-spacing:.3px}
      .form-picker-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:6000;display:flex;align-items:center;justify-content:center;padding:20px;box-sizing:border-box}
      .form-picker-panel{background:#fff;width:100%;max-width:360px;border-radius:12px;padding:20px 16px 16px;box-sizing:border-box;box-shadow:0 8px 32px rgba(0,0,0,.18)}
      .form-picker-title{font-size:16px;font-weight:600;margin-bottom:16px;text-align:center;color:#333}
      .form-picker-label{font-size:12px;color:#999;margin-bottom:6px}
      .form-picker-row{display:flex;gap:8px;margin-bottom:14px}
      .form-picker-row select{flex:1;min-width:0;padding:10px 6px;border:1px solid #ddd;border-radius:8px;font-size:15px;text-align:center;background:#fafafa}
      .form-picker-preview{text-align:center;font-family:monospace;font-size:16px;color:#333;background:#f5f7fa;border-radius:8px;padding:10px;margin-bottom:14px}
      .form-picker-actions{display:flex;gap:10px}
      .form-picker-actions button{flex:1;padding:12px;border:none;border-radius:8px;font-size:15px;cursor:pointer}
      .form-picker-cancel{background:#f0f0f0;color:#666}
      .form-picker-confirm{background:#2ecc71;color:#fff}
      .page-title-wrap{display:inline-flex;align-items:center;gap:8px;flex-wrap:nowrap}
      .page-head-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:0 0 16px;width:80%;max-width:1400px}
      .page-head-bar .page-title-wrap{margin:0!important}
      .page-head-actions{display:flex;align-items:center;gap:8px;flex-shrink:0}
      .search-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:8px 12px;margin-bottom:16px}
      .search-toolbar>label{font-size:13px;font-weight:600;color:#555;margin:0}
      .input-short{width:10em;max-width:10em;padding:6px 10px;border:1px solid #ddd;border-radius:4px;font-size:14px}
      select.input-short{padding:6px 8px}
      .filter-chips{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
      .filter-chips a{display:inline-block;padding:4px 10px;font-size:12px;border-radius:4px;border:1px solid #ddd;text-decoration:none;color:#555;background:#fff;line-height:1.4}
      .filter-chips a:hover{border-color:#2ecc71;color:#27ae60}
      .filter-chips a.chip-active{background:#2ecc71;color:#fff;border-color:#2ecc71}
      .admin-form-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:5000;align-items:flex-start;justify-content:center;padding:24px 20px;overflow-y:auto}
      .admin-form-modal{background:#fff;border-radius:10px;width:min(1360px,96vw);max-height:calc(100vh - 48px);overflow-y:auto;padding:20px 24px;box-shadow:0 12px 40px rgba(0,0,0,.2);margin:auto}
      .admin-form-modal .form-grid{width:100%;max-width:none}
      .admin-form-modal h3{margin:0 0 16px;font-size:17px}
      .admin-form-modal .modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:16px;padding-top:12px;border-top:1px solid #f0f0f0}
      .admin-form-modal .form-field-checkbox-inline{margin-top:8px}
      .admin-form-modal .form-field-checkbox-inline .form-row-inline{align-items:center;flex-wrap:nowrap;gap:8px}
      .admin-form-modal .form-field-checkbox-inline .form-row-inline>label{width:auto;min-width:88px;flex-shrink:0}
      .admin-form-modal .form-field-checkbox-inline .form-hint-inline{white-space:nowrap;font-size:12px;color:#888}
      .admin-form-modal .form-row{margin-top:8px}
      .admin-form-modal #product-editor,.admin-form-modal #article-editor{min-height:220px;background:#fff}
      .admin-form-modal .field-editor .ql-container{min-height:180px}
      .status-on{color:#27ae60;font-weight:600}.status-off{color:#e67e22;font-weight:600}
      .stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px}
      .stat-card{background:#fafafa;border:1px solid #eee;border-radius:8px;padding:16px 18px}
      .stat-card .stat-label{font-size:13px;color:#888;margin-bottom:8px}
      .stat-card .stat-value{font-size:28px;font-weight:700;color:#333;line-height:1.2}
      .guide-wrap{position:relative;display:inline-flex;align-items:center;vertical-align:middle}
      .guide-inline{margin-left:4px}
      .guide-tip{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;border:1px solid #3498db;background:#eef6ff;color:#3498db;font-size:14px;font-weight:700;cursor:help;flex-shrink:0;line-height:1}
      .guide-wrap:hover .guide-preview,.guide-wrap:focus-within .guide-preview{display:block}
      .guide-preview{display:none;position:absolute;left:0;top:calc(100% + 10px);z-index:2000;width:420px;background:#fff;border:1px solid #d0d0d0;border-radius:10px;padding:12px;box-shadow:0 8px 32px rgba(0,0,0,.18)}
      .guide-preview img{width:390px;max-height:860px;height:auto;object-fit:contain;display:block;border:none;border-radius:0;background:transparent}
      .guide-preview{background:#f7f7f7}
      .guide-preview.guide-flow{width:920px}
      .guide-preview.guide-flow img{max-height:none;width:auto;max-width:100%}
      .guide-tip-flow{font-size:12px;background:#fff8e6;border-color:#f5d78e;color:#b7950b}
      .guide-preview .guide-cap{margin-top:8px;font-size:12px;color:#666;line-height:1.6}
      .guide-cap-only{font-size:13px;color:#555;line-height:1.7}
      h3.guide-heading{display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap;width:auto;max-width:none}
      .batch-bar{margin-bottom:12px}.col-check{width:40px;text-align:center}
      .admin-toast{display:none;position:fixed;left:50%;top:40%;transform:translate(-50%,-50%);z-index:10000;background:rgba(0,0,0,.78);color:#fff;padding:14px 24px;border-radius:10px;font-size:15px;max-width:80%;text-align:center;pointer-events:none;opacity:0;transition:opacity .2s ease}
      .admin-toast.show{display:block;opacity:1}
    </style><script>try{var _t=localStorage.getItem("adminTheme")||"dark";document.documentElement.setAttribute("data-admin-theme",_t);}catch(e){}</script></head><body>';
    echo '<div class="top"><span class="logo">●</span><span class="title">后台管理</span>';
    echo '<span class="theme-pick"><label for="adminThemeSelect">风格</label><select id="adminThemeSelect" aria-label="后台风格"><option value="dark">深色</option><option value="light">浅色</option><option value="blue">蓝色</option><option value="green">绿色</option></select></span>';
    echo '<span class="right">' . $user . ' · <a href="change_password.php" style="color:#3498db">改密</a> · <a href="logout.php" style="color:#e74c3c">退出</a></span></div>';
    echo '<div class="shell"><aside class="side nav-tree">';
    admin_render_sidebar_menu($menu, $activeHref, 0);
    echo '<div class="foot"><a href="/index.php" target="_blank">打开 H5 前台</a></div>';
    echo '<script>(function(){
      document.querySelectorAll(".side-group-head").forEach(function(head){
        head.addEventListener("click",function(){ var g=this.parentElement; if(!g) return; g.classList.toggle("collapsed"); this.setAttribute("aria-expanded", g.classList.contains("collapsed")?"false":"true"); });
        head.addEventListener("keydown",function(e){ if(e.key==="Enter"||e.key===" "){ e.preventDefault(); this.click(); } });
      });
    })();</script>';
    $guidePath = admin_guide_path($guideInstanceId);
    echo '</aside><main class="main">';
    if ($pageActions !== '') {
        echo '<div class="page-head-bar"><h2 class="page-title-wrap"><span>' . htmlspecialchars($title) . '</span>';
    } else {
        echo '<h2 style="margin:0 0 16px" class="page-title-wrap"><span>' . htmlspecialchars($title) . '</span>';
    }
    if ($guideFlowKey !== '' && admin_guide_path('flow_' . $guideFlowKey) !== '') {
        admin_guide_flow($guideFlowKey, $guideCaption);
    } elseif ($guideInstanceId !== '' && admin_guide_path($guideInstanceId) !== '') {
        admin_guide_tip($guideInstanceId, $guideCaption);
    } elseif ($guideCaption !== '') {
        admin_guide_tip('', $guideCaption);
    }
    if ($pageActions !== '') {
        echo '</h2><div class="page-head-actions">' . $pageActions . '</div></div>';
    } else {
        echo '</h2>';
    }
}

function admin_pagination(int $total, int $page, int $pageSize, string $baseUrl): void {
    $totalPages = max(1, (int)ceil($total / max(1, $pageSize)));
    $page = max(1, min($page, $totalPages));
    $sep = strpos($baseUrl, '?') !== false ? '&' : '?';
    echo '<div class="pagination-bar">';
    echo '<span>共 ' . $total . ' 条</span>';
    echo '<span>每页 <select onchange="location.href=this.value">';
    foreach ([10, 50, 100] as $ps) {
        $url = htmlspecialchars($baseUrl . $sep . 'ps=' . $ps . '&page=1');
        $sel = $ps === $pageSize ? ' selected' : '';
        echo '<option value="' . $url . '"' . $sel . '>' . $ps . '</option>';
    }
    echo '</select> 条</span>';
    if ($page > 1) {
        echo '<a class="btn btn-sm btn-secondary" href="' . htmlspecialchars($baseUrl . $sep . 'ps=' . $pageSize . '&page=' . ($page - 1)) . '">上一页</a>';
    }
    echo '<span class="pagination-page">第 ' . $page . ' / ' . $totalPages . ' 页</span>';
    if ($page < $totalPages) {
        echo '<a class="btn btn-sm btn-secondary" href="' . htmlspecialchars($baseUrl . $sep . 'ps=' . $pageSize . '&page=' . ($page + 1)) . '">下一页</a>';
    }
    echo '</div>';
}

function admin_csv_download(string $filename, array $headers, array $keys, array $rows): void {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        $line = [];
        foreach ($keys as $k) {
            $line[] = $row[$k] ?? '';
        }
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}

function admin_layout_end(): void {
    echo '<script>
function adminToast(msg, ms) {
  var el = document.getElementById("admin-toast");
  if (!el) { el = document.createElement("div"); el.id = "admin-toast"; el.className = "admin-toast"; document.body.appendChild(el); }
  el.textContent = msg || "";
  el.classList.add("show");
  clearTimeout(el._hideTimer);
  el._hideTimer = setTimeout(function(){ el.classList.remove("show"); }, ms || 2200);
}
(function(){
  var sel=document.getElementById("adminThemeSelect");
  if(!sel)return;
  var cur=document.documentElement.getAttribute("data-admin-theme")||"dark";
  sel.value=cur;
  sel.addEventListener("change",function(){
    var v=sel.value||"dark";
    document.documentElement.setAttribute("data-admin-theme",v);
    try{localStorage.setItem("adminTheme",v);}catch(e){}
  });
})();
function adminPad2(n){ return String(parseInt(n,10)||0).padStart(2,"0"); }
function adminPickerValue(y,m,d,h,mi,s,mode){
  var date=y+"-"+adminPad2(m)+"-"+adminPad2(d);
  if(mode==="date") return date;
  return date+" "+adminPad2(h)+":"+adminPad2(mi)+":"+adminPad2(s);
}
function adminParseDateValue(val,mode){
  var now=new Date();
  var y=now.getFullYear(),m=now.getMonth()+1,d=now.getDate(),h=now.getHours(),mi=now.getMinutes(),s=now.getSeconds();
  if(mode==="date"&&/^\\d{4}-\\d{2}-\\d{2}$/.test(val)){
    var p=val.split("-"); y=parseInt(p[0],10); m=parseInt(p[1],10); d=parseInt(p[2],10);
  }
  if(mode==="datetime"&&/^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}$/.test(val)){
    var parts=val.split(" "); var dp=parts[0].split("-"); var tp=parts[1].split(":");
    y=parseInt(dp[0],10); m=parseInt(dp[1],10); d=parseInt(dp[2],10);
    h=parseInt(tp[0],10); mi=parseInt(tp[1],10); s=parseInt(tp[2],10);
  }
  return {y:y,m:m,d:d,h:h,mi:mi,s:s};
}
function adminSelectOptions(min,max,cur,suffix){
  var html="";
  for(var i=min;i<=max;i++){
    html+="<option value=\""+i+"\""+(i===cur?" selected":"")+">"+adminPad2(i)+(suffix||"")+"</option>";
  }
  return html;
}
function adminShowDatePicker(input,mode){
  if(!input) return;
  document.querySelectorAll(".form-picker-overlay").forEach(function(el){ el.remove(); });
  var parsed=adminParseDateValue((input.value||"").trim(),mode);
  var overlay=document.createElement("div");
  overlay.className="form-picker-overlay";
  var yearOpts="";
  for(var yy=parsed.y-50;yy<=parsed.y+10;yy++){
    yearOpts+="<option value=\""+yy+"\""+(yy===parsed.y?" selected":"")+">"+yy+"年</option>";
  }
  var timeRow="";
  if(mode==="datetime"){
    timeRow="<div class=\"form-picker-label\">时 / 分 / 秒</div><div class=\"form-picker-row\"><select class=\"fp-h\">"+adminSelectOptions(0,23,parsed.h,"时")+"</select><select class=\"fp-mi\">"+adminSelectOptions(0,59,parsed.mi,"分")+"</select><select class=\"fp-s\">"+adminSelectOptions(0,59,parsed.s,"秒")+"</select></div>";
  }
  var preview=adminPickerValue(parsed.y,parsed.m,parsed.d,parsed.h,parsed.mi,parsed.s,mode);
  overlay.innerHTML="<div class=\"form-picker-panel\"><div class=\"form-picker-title\">"+(mode==="date"?"选择日期":"选择日期时间")+"</div><div class=\"form-picker-preview fp-preview\">"+preview+"</div><div class=\"form-picker-label\">年 / 月 / 日</div><div class=\"form-picker-row\"><select class=\"fp-y\">"+yearOpts+"</select><select class=\"fp-m\">"+adminSelectOptions(1,12,parsed.m,"月")+"</select><select class=\"fp-d\">"+adminSelectOptions(1,31,parsed.d,"日")+"</select></div>"+timeRow+"<div class=\"form-picker-actions\"><button type=\"button\" class=\"form-picker-cancel\">取消</button><button type=\"button\" class=\"form-picker-confirm\">确定</button></div></div>";
  document.body.appendChild(overlay);
  function refreshPreview(){
    var y=parseInt(overlay.querySelector(".fp-y").value,10);
    var mo=parseInt(overlay.querySelector(".fp-m").value,10);
    var da=parseInt(overlay.querySelector(".fp-d").value,10);
    var ho=mode==="datetime"?parseInt(overlay.querySelector(".fp-h").value,10):0;
    var mi=mode==="datetime"?parseInt(overlay.querySelector(".fp-mi").value,10):0;
    var se=mode==="datetime"?parseInt(overlay.querySelector(".fp-s").value,10):0;
    overlay.querySelector(".fp-preview").textContent=adminPickerValue(y,mo,da,ho,mi,se,mode);
  }
  overlay.querySelectorAll("select").forEach(function(sel){ sel.addEventListener("change", refreshPreview); });
  overlay.querySelector(".form-picker-cancel").addEventListener("click", function(){ overlay.remove(); });
  overlay.addEventListener("click", function(e){ if(e.target===overlay) overlay.remove(); });
  overlay.querySelector(".form-picker-panel").addEventListener("click", function(e){ e.stopPropagation(); });
  overlay.querySelector(".form-picker-confirm").addEventListener("click", function(){
    var y=parseInt(overlay.querySelector(".fp-y").value,10);
    var mo=parseInt(overlay.querySelector(".fp-m").value,10);
    var da=parseInt(overlay.querySelector(".fp-d").value,10);
    var ho=mode==="datetime"?parseInt(overlay.querySelector(".fp-h").value,10):0;
    var mi=mode==="datetime"?parseInt(overlay.querySelector(".fp-mi").value,10):0;
    var se=mode==="datetime"?parseInt(overlay.querySelector(".fp-s").value,10):0;
    input.value=adminPickerValue(y,mo,da,ho,mi,se,mode);
    overlay.remove();
  });
}
function adminBindDatetimeFields(root){
  var scope=root||document;
  scope.querySelectorAll(".admin-datetime-btn").forEach(function(btn){
    if(btn._pickerBound) return;
    btn._pickerBound=true;
    btn.addEventListener("click", function(){
      var id=btn.getAttribute("data-target")||"";
      var input=document.getElementById(id);
      adminShowDatePicker(input, btn.getAttribute("data-mode")||"datetime");
    });
  });
  scope.querySelectorAll(".admin-datetime-input").forEach(function(input){
    if(input._pickerBound) return;
    input._pickerBound=true;
    input.addEventListener("click", function(){ adminShowDatePicker(input, "datetime"); });
  });
  scope.querySelectorAll(".admin-date-input").forEach(function(input){
    if(input._pickerBound) return;
    input._pickerBound=true;
    input.addEventListener("click", function(){ adminShowDatePicker(input, "date"); });
  });
}
function adminValidateDatetimeFields(form){
  var fields=form.querySelectorAll(".admin-date-input");
  for(var i=0;i<fields.length;i++){
    var v=fields[i].value.trim(); if(!v) continue;
    if(!/^\\d{4}-\\d{2}-\\d{2}$/.test(v)){ adminToast("日期请按 2025-11-11 格式填写"); fields[i].focus(); return false; }
  }
  fields=form.querySelectorAll(".admin-datetime-input");
  for(var j=0;j<fields.length;j++){
    var dv=fields[j].value.trim(); if(!dv) continue;
    if(!/^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}$/.test(dv)){ adminToast("日期时间请按 2022-11-11 11:11:11 格式填写"); fields[j].focus(); return false; }
  }
  return true;
}
document.addEventListener("DOMContentLoaded", function(){
  adminBindDatetimeFields(document);
  adminBindLinkSearch(document);
  document.querySelectorAll(".link-type-select").forEach(function(s){ adminLinkTypeChange(s); });
  document.querySelectorAll(".link-internal-target").forEach(function(s){ adminLinkInternalChange(s); });
  document.querySelectorAll("form").forEach(function(form){
    form.addEventListener("submit", function(e){
      if(!adminValidateDatetimeFields(form)) e.preventDefault();
    });
  });
  if(window.__adminQuillBoot){
    adminInitQuill(window.__adminQuillBoot.sel, window.__adminQuillBoot.html);
    delete window.__adminQuillBoot;
  }
});
function adminLinkTypeChange(sel){
  var box=sel.closest(".link-field"); if(!box) return;
  var t=sel.value;
  var ib=box.querySelector(".link-internal-box");
  var ex=box.querySelector(".link-external-input");
  if(ib) ib.style.display=t==="internal"?"block":"none";
  if(ex) ex.style.display=t==="external"?"block":"none";
}
function adminLinkInternalChange(sel){
  var box=sel.closest(".link-field"); if(!box) return;
  var v=sel.value;
  var ar=box.querySelector(".link-article-search");
  var pr=box.querySelector(".link-product-search");
  if(ar) ar.style.display=v==="system:search-article"?"block":"none";
  if(pr) pr.style.display=v==="system:product-list"?"block":"none";
}
function adminRenderLinkSearchResults(container, rows, kind, hiddenInput, pickedEl){
  if(!container) return;
  container.innerHTML="";
  if(!rows||!rows.length){
    container.innerHTML="<div style=\"padding:8px;color:#999;font-size:12px\">无匹配结果</div>";
    return;
  }
  rows.forEach(function(row){
    var btn=document.createElement("button");
    btn.type="button";
    btn.className="btn btn-sm btn-secondary";
    btn.style.cssText="display:block;width:100%;text-align:left;margin:4px 0";
    var label=kind==="product"?(row.name||("ID"+row.id)):(row.title||("ID"+row.id));
    btn.textContent="#"+row.id+" "+label;
    btn.addEventListener("click", function(){
      if(hiddenInput) hiddenInput.value=String(row.id);
      if(pickedEl) pickedEl.textContent="已选"+(kind==="product"?"商品":"文章")+" ID: "+row.id+" · "+label;
    });
    container.appendChild(btn);
  });
}
function adminSearchLinkTargets(input){
  var wrap=input.closest(".link-article-search")||input.closest(".link-product-search");
  if(!wrap) return;
  var kind=wrap.classList.contains("link-product-search")?"product":"article";
  var q=input.value.trim();
  var results=wrap.querySelector(kind==="product"?".link-product-results":".link-article-results");
  var hidden=wrap.querySelector(kind==="product"?".link-product-id":".link-article-id");
  var picked=wrap.querySelector(kind==="product"?".link-product-picked":".link-article-picked");
  if(q.length<1){ if(results) results.innerHTML=""; return; }
  fetch("link_search.php?kind="+encodeURIComponent(kind)+"&q="+encodeURIComponent(q), {credentials:"same-origin"})
    .then(function(r){ return r.json(); })
    .then(function(j){
      adminRenderLinkSearchResults(results, (j&&j.data)||[], kind, hidden, picked);
    })
    .catch(function(){ if(results) results.innerHTML="<div style=\"padding:8px;color:#c00;font-size:12px\">搜索失败</div>"; });
}
function adminBindLinkSearch(scope){
  scope=scope||document;
  scope.querySelectorAll(".link-article-q,.link-product-q").forEach(function(input){
    if(input._linkSearchBound) return;
    input._linkSearchBound=true;
    var timer=null;
    input.addEventListener("input", function(){
      clearTimeout(timer);
      timer=setTimeout(function(){ adminSearchLinkTargets(input); }, 300);
    });
  });
}
function adminSyncColor(picker,textId){
  var t=document.getElementById(textId),sw=document.getElementById(textId+"_swatch");
  if(t)t.value=picker.value;
  if(sw)sw.style.background=picker.value;
}
function adminSyncColorText(text,pickerId){
  var p=document.getElementById(pickerId),sw=document.getElementById(text.id+"_swatch");
  if(/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(text.value)){
    if(p)p.value=text.value.length===4?text.value:text.value;
    if(sw)sw.style.background=text.value;
  }
}
function adminCompressImageFile(file, cb){
  if(!file||!/^image\//.test(file.type||"")){ cb(file); return; }
  if(file.size<=512*1024){ cb(file); return; }
  var img=new Image();
  var objUrl=URL.createObjectURL(file);
  img.onload=function(){
    URL.revokeObjectURL(objUrl);
    var maxW=1600,w=img.width,h=img.height;
    if(w>maxW){ h=Math.round(h*maxW/w); w=maxW; }
    var canvas=document.createElement("canvas");
    canvas.width=w; canvas.height=h;
    var ctx=canvas.getContext("2d");
    if(!ctx){ cb(file); return; }
    ctx.drawImage(img,0,0,w,h);
    canvas.toBlob(function(blob){
      if(!blob||blob.size>=file.size){ cb(file); return; }
      var name=(file.name||"upload.jpg").replace(/\.[^.]+$/,"")+".jpg";
      cb(new File([blob], name, {type:"image/jpeg", lastModified:Date.now()}));
    }, "image/jpeg", 0.88);
  };
  img.onerror=function(){ URL.revokeObjectURL(objUrl); cb(file); };
  img.src=objUrl;
}
function adminUploadImageFile(file, folder, done){
  adminCompressImageFile(file, function(small){
    var fd=new FormData(); fd.append("file", small);
    if(folder) fd.append("folder", folder);
    fetch("upload.php",{method:"POST",body:fd,credentials:"same-origin"})
      .then(function(r){
        if(r.status===413) throw new Error("图片过大，请换小图或联系管理员调大 Nginx client_max_body_size");
        return r.json();
      })
      .then(function(j){ done(null,j); })
      .catch(function(e){ done(e||new Error("上传失败")); });
  });
}
function adminPickImage(inputId){
  var input=document.getElementById(inputId);
  if(!input){ adminToast("图片字段未找到"); return; }
  var modal=document.getElementById("admin-image-modal");
  if(!modal){
    modal=document.createElement("div");
    modal.id="admin-image-modal";
    modal.className="admin-modal-overlay";
    modal.innerHTML="<div class=\"admin-media-modal\" onclick=\"event.stopPropagation()\"><div class=\"admin-media-head\"><h3>添加图片<small>（只能添加 jpg,jpeg,gif,png）</small></h3><button type=\"button\" class=\"admin-media-close\" aria-label=\"关闭\">×</button></div><div class=\"admin-media-body\"><aside class=\"admin-media-side\"><button type=\"button\" class=\"admin-media-tab active\" data-tab=\"mine\">我的文件</button><div class=\"admin-media-storage\"><span class=\"admin-media-used\">已用0B</span></div></aside><section class=\"admin-media-main\"><div class=\"admin-media-toolbar\"><button type=\"button\" class=\"admin-media-toolbtn admin-media-upload\">↑ 直接上传</button><button type=\"button\" class=\"admin-media-toolbtn admin-media-mkdir\">+ 新增文件夹</button></div><div class=\"admin-media-breadcrumb\"></div><div class=\"admin-media-grid\"></div></section></div><div class=\"admin-media-foot\"><button type=\"button\" class=\"btn admin-media-ok\" disabled>确定</button><button type=\"button\" class=\"btn btn-secondary admin-media-cancel\">取消</button></div></div>";
    document.body.appendChild(modal);
    modal._state={tab:"mine",keyword:"",selected:"",target:"",folder:""};
    modal.querySelector(".admin-media-close").onclick=function(){ modal.style.display="none"; };
    modal.querySelector(".admin-media-cancel").onclick=function(){ modal.style.display="none"; };
    modal.onclick=function(ev){ if(ev.target===modal) modal.style.display="none"; };
    modal.querySelectorAll(".admin-media-tab").forEach(function(btn){
      btn.onclick=function(){
        modal._state.tab=btn.getAttribute("data-tab")||"mine";
        modal.querySelectorAll(".admin-media-tab").forEach(function(b){ b.classList.toggle("active", b===btn); });
        var mkdirBtn=modal.querySelector(".admin-media-mkdir");
        if(mkdirBtn) mkdirBtn.style.display=modal._state.tab==="mine"?"inline-flex":"none";
        adminMediaLoad(modal);
      };
    });
    var mkdirBtn=modal.querySelector(".admin-media-mkdir");
    if(mkdirBtn){
      mkdirBtn.onclick=function(){
        if(modal._state.tab!=="mine") return;
        adminMediaCreateFolder(modal);
      };
    }
    var kw=modal.querySelector(".admin-media-keyword");
    if(kw){
      kw.onkeydown=function(ev){ if(ev.key==="Enter"){ modal._state.keyword=kw.value.trim(); adminMediaLoad(modal); } };
    }
    modal.querySelector(".admin-media-upload").onclick=function(){
      var picker=document.createElement("input"); picker.type="file"; picker.accept="image/jpeg,image/jpg,image/png,image/gif,image/webp";
      picker.onchange=function(){
        if(!picker.files||!picker.files[0]) return;
        adminUploadImageFile(picker.files[0], modal._state.folder||"", function(err,j){
          if(err){ adminToast(err.message||"上传失败"); return; }
          if(j&&j.code===0&&j.data&&j.data.url){
            modal._state.tab="mine";
            modal.querySelectorAll(".admin-media-tab").forEach(function(b){ b.classList.toggle("active", b.getAttribute("data-tab")==="mine"); });
            modal._state.selected=j.data.url;
            adminMediaLoad(modal);
          } else adminToast((j&&j.message)||"上传失败");
        });
      };
      picker.click();
    };
    modal.querySelector(".admin-media-ok").onclick=function(){
      var id=modal._state.target, inp=document.getElementById(id), url=modal._state.selected||"";
      if(inp&&url){ adminApplyImageField(id, url); }
      modal.style.display="none";
    };
  }
  modal._state.target=inputId;
  modal._state.selected=input.value||"";
  modal._state.tab="mine";
  modal._state.keyword="";
  modal._state.folder="";
  modal.querySelectorAll(".admin-media-tab").forEach(function(b){ b.classList.toggle("active", b.getAttribute("data-tab")==="mine"); });
  var mkdirBtn=modal.querySelector(".admin-media-mkdir");
  if(mkdirBtn) mkdirBtn.style.display="inline-flex";
  var okBtn=modal.querySelector(".admin-media-ok");
  if(okBtn) okBtn.disabled=!modal._state.selected;
  adminMediaLoad(modal);
  modal.style.display="flex";
}
function adminAssetUrl(url){
  if(!url) return "";
  if(/^https?:\/\//i.test(url)||url.indexOf("data:")===0) return url;
  if(url.indexOf("./")===0) return ".."+url.slice(1);
  if(url.indexOf("assets/")===0) return "../"+url;
  if(url.charAt(0)==="/") return url;
  return url;
}
function adminApplyImageField(inputId, url){
  var input=document.getElementById(inputId); if(!input) return;
  input.value=url||"";
  var trigger=input.parentElement&&input.parentElement.querySelector(".image-picker-trigger");
  if(!trigger) return;
  var old=trigger.querySelector(".image-picker-thumb,.image-picker-placeholder");
  if(old) old.remove();
  var text=trigger.querySelector(".image-picker-text");
  if(url){
    var img=document.createElement("img");
    img.className="image-picker-thumb";
    img.id=inputId+"_thumb";
    img.alt="";
    img.src=adminAssetUrl(url);
    trigger.insertBefore(img, text);
    if(text) text.textContent="更换图片";
  } else {
    var ph=document.createElement("div");
    ph.className="image-picker-placeholder";
    ph.id=inputId+"_thumb";
    ph.textContent="+";
    trigger.insertBefore(ph, text);
    if(text) text.textContent="添加图片";
  }
}
function adminFormatBytes(n){
  n=Number(n)||0;
  if(n<1024) return n+"B";
  if(n<1024*1024) return (n/1024).toFixed(1)+"K";
  return (n/1024/1024).toFixed(1)+"M";
}
function adminMediaRenderBreadcrumb(modal, list){
  var box=modal.querySelector(".admin-media-breadcrumb");
  if(!box) return;
  if(!list||!list.length||modal._state.tab!=="mine"){ box.innerHTML=""; box.style.display="none"; return; }
  box.style.display="block";
  var html="";
  list.forEach(function(bc,i){
    if(i>0) html+="<span class=\"bc-sep\">&gt;</span>";
    var cls=(i===list.length-1)?" class=\"active\"":"";
    html+="<a href=\"javascript:;\""+cls+" data-folder=\""+String(bc.id||"").replace(/"/g,"")+"\">"+(bc.name||"")+"</a>";
  });
  box.innerHTML=html;
  box.querySelectorAll("a[data-folder]").forEach(function(a){
    a.onclick=function(){ modal._state.folder=a.getAttribute("data-folder")||""; adminMediaLoad(modal); };
  });
}
function adminMediaPost(action, params, cb){
  var body=new URLSearchParams();
  body.set("action", action);
  Object.keys(params||{}).forEach(function(k){ body.set(k, params[k]); });
  fetch("media.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:body.toString(),credentials:"same-origin"})
    .then(function(r){ return r.json(); })
    .then(function(j){ cb(null,j); })
    .catch(function(e){ cb(e||new Error("request failed")); });
}
function adminMediaCreateFolder(modal){
  adminMediaPost("create_folder",{folder:modal._state.folder||""},function(err,j){
    if(err||!j||j.code!==0){ adminToast((j&&j.message)||"创建文件夹失败"); return; }
    adminMediaLoad(modal);
  });
}
function adminMediaDeleteFolder(modal, path, name){
  if(!confirm("确定删除文件夹「"+name+"」及其中的全部图片？")) return;
  adminMediaPost("delete_folder",{path:path},function(err,j){
    if(err||!j||j.code!==0){ adminToast((j&&j.message)||"删除失败"); return; }
    if(modal._state.selected&&modal._state.selected.indexOf(path+"/")===0) modal._state.selected="";
    adminMediaLoad(modal);
  });
}
function adminMediaRenameFolder(modal, path, oldName){
  var name=prompt("请输入新的文件夹名称", oldName||"");
  if(name===null) return;
  name=String(name).trim();
  if(!name||name===oldName) return;
  if(name.indexOf("/")>=0||name.indexOf(String.fromCharCode(92))>=0){ adminToast("名称不能包含斜杠或反斜杠"); return; }
  adminMediaPost("rename_folder",{path:path,name:name},function(err,j){
    if(err||!j||j.code!==0){ adminToast((j&&j.message)||"重命名失败"); return; }
    var d=j.data||{}, oldPath=d.old_path||path, newPath=d.path||"";
    var cur=modal._state.folder||"";
    if(cur===oldPath) modal._state.folder=newPath;
    else if(oldPath&&cur.indexOf(oldPath+"/")===0) modal._state.folder=newPath+cur.slice(oldPath.length);
    if(modal._state.selected){
      var urlPrefix="./assets/uploads/"+(oldPath?oldPath+"/":"");
      var newUrlPrefix="./assets/uploads/"+(newPath?newPath+"/":"");
      if(modal._state.selected.indexOf(urlPrefix)===0){
        modal._state.selected=newUrlPrefix+modal._state.selected.slice(urlPrefix.length);
      }
    }
    adminMediaLoad(modal);
  });
}
function adminMediaDeleteFile(modal, url, name){
  if(!confirm("确定删除图片「"+(name||"")+"」？")) return;
  adminMediaPost("delete_file",{url:url},function(err,j){
    if(err||!j||j.code!==0){ adminToast((j&&j.message)||"删除失败"); return; }
    if(modal._state.selected===url) modal._state.selected="";
    adminMediaLoad(modal);
  });
}
function adminMediaLoad(modal){
  var grid=modal.querySelector(".admin-media-grid");
  if(!grid) return;
  grid.innerHTML="<div class=\"admin-media-empty\">加载中…</div>";
  var qs="action=browse&tab="+encodeURIComponent(modal._state.tab||"mine");
  if(modal._state.folder) qs+="&folder="+encodeURIComponent(modal._state.folder);
  if(modal._state.keyword) qs+="&q="+encodeURIComponent(modal._state.keyword);
  fetch("media.php?"+qs,{credentials:"same-origin"})
    .then(function(r){return r.json();})
    .then(function(j){
      if(j.code!==0){ grid.innerHTML="<div class=\"admin-media-empty\">"+(j.message||"加载失败")+"</div>"; return; }
      var data=j.data||{}, files=data.files||[], folders=data.folders||[];
      var usedEl=modal.querySelector(".admin-media-used");
      if(usedEl&&data.storage) usedEl.textContent="已用"+adminFormatBytes(data.storage.used||0);
      adminMediaRenderBreadcrumb(modal, data.breadcrumb||[]);
      if(!folders.length&&!files.length){
        grid.innerHTML="<div class=\"admin-media-empty\">暂无文件，可点击「直接上传」或「新增文件夹」</div>";
        adminMediaSyncOk(modal);
        return;
      }
      grid.innerHTML="";
      folders.forEach(function(fd){
        var item=document.createElement("div");
        item.className="admin-media-item folder-item";
        item.onclick=function(){ modal._state.folder=fd.path||""; adminMediaLoad(modal); };
        var icon=document.createElement("div"); icon.className="admin-media-folder-icon"; item.appendChild(icon);
        var nm=document.createElement("div"); nm.className="admin-media-item-name"; nm.textContent=fd.name||""; nm.title=fd.name||""; item.appendChild(nm);
        var ren=document.createElement("button"); ren.type="button"; ren.className="item-ren"; ren.textContent="✎"; ren.title="重命名";
        ren.onclick=function(ev){ ev.stopPropagation(); adminMediaRenameFolder(modal, fd.path||"", fd.name||""); };
        item.appendChild(ren);
        var del=document.createElement("button"); del.type="button"; del.className="item-del"; del.textContent="×";
        del.onclick=function(ev){ ev.stopPropagation(); adminMediaDeleteFolder(modal, fd.path||"", fd.name||""); };
        item.appendChild(del);
        grid.appendChild(item);
      });
      files.forEach(function(f){
        var item=document.createElement("div");
        item.className="admin-media-item"+((modal._state.selected===f.url)?" selected":"");
        item.onclick=function(){
          modal._state.selected=f.url;
          grid.querySelectorAll(".admin-media-item").forEach(function(el){ el.classList.remove("selected"); el.querySelector(".admin-media-check")&&el.querySelector(".admin-media-check").remove(); });
          item.classList.add("selected");
          var ck=document.createElement("span"); ck.className="admin-media-check"; ck.textContent="✓"; item.appendChild(ck);
          adminMediaSyncOk(modal);
        };
        var img=document.createElement("img");
        img.src=adminAssetUrl(f.thumb||f.url);
        img.alt=f.name||"";
        item.appendChild(img);
        if(f.name){
          var nm=document.createElement("div"); nm.className="admin-media-item-name"; nm.textContent=f.name; nm.title=f.name; item.appendChild(nm);
        }
        if(modal._state.selected===f.url){
          var ck2=document.createElement("span"); ck2.className="admin-media-check"; ck2.textContent="✓"; item.appendChild(ck2);
        }
        var del=document.createElement("button"); del.type="button"; del.className="item-del"; del.textContent="×";
        del.onclick=function(ev){ ev.stopPropagation(); adminMediaDeleteFile(modal, f.url||"", f.name||""); };
        item.appendChild(del);
        grid.appendChild(item);
      });
      adminMediaSyncOk(modal);
    })
    .catch(function(){ grid.innerHTML="<div class=\"admin-media-empty\">加载失败</div>"; });
}
function adminMediaSyncOk(modal){
  var okBtn=modal.querySelector(".admin-media-ok");
  if(okBtn) okBtn.disabled=!modal._state.selected;
}
function adminUploadVideoFile(file, folder, done){
  var fd=new FormData(); fd.append("file", file);
  if(folder) fd.append("folder", folder);
  fetch("video_upload.php",{method:"POST",body:fd,credentials:"same-origin"})
    .then(function(r){
      if(r.status===413) throw new Error("视频过大，请填写 CDN 外链或联系管理员调大上传限制");
      return r.json();
    })
    .then(function(j){ done(null,j); })
    .catch(function(e){ done(e||new Error("上传失败")); });
}
function adminVideoMediaPost(action, params, cb){
  var body=new URLSearchParams();
  body.set("action", action);
  Object.keys(params||{}).forEach(function(k){ body.set(k, params[k]); });
  fetch("video_media.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:body.toString(),credentials:"same-origin"})
    .then(function(r){ return r.json(); })
    .then(function(j){ cb(null,j); })
    .catch(function(e){ cb(e||new Error("request failed")); });
}
function adminVideoMediaCreateFolder(modal){
  adminVideoMediaPost("create_folder",{folder:modal._state.folder||""},function(err,j){
    if(err||!j||j.code!==0){ adminToast((j&&j.message)||"创建文件夹失败"); return; }
    adminVideoMediaLoad(modal);
  });
}
function adminVideoMediaDeleteFolder(modal, path, name){
  if(!confirm("确定删除文件夹「"+name+"」及其中的全部视频？")) return;
  adminVideoMediaPost("delete_folder",{path:path},function(err,j){
    if(err||!j||j.code!==0){ adminToast((j&&j.message)||"删除失败"); return; }
    if(modal._state.selected&&modal._state.selected.indexOf(path+"/")===0) modal._state.selected="";
    adminVideoMediaLoad(modal);
  });
}
function adminVideoMediaRenameFolder(modal, path, oldName){
  var name=prompt("请输入新的文件夹名称", oldName||"");
  if(name===null) return;
  name=String(name).trim();
  if(!name||name===oldName) return;
  if(name.indexOf("/")>=0||name.indexOf(String.fromCharCode(92))>=0){ adminToast("名称不能包含斜杠或反斜杠"); return; }
  adminVideoMediaPost("rename_folder",{path:path,name:name},function(err,j){
    if(err||!j||j.code!==0){ adminToast((j&&j.message)||"重命名失败"); return; }
    var d=j.data||{}, oldPath=d.old_path||path, newPath=d.path||"";
    var cur=modal._state.folder||"";
    if(cur===oldPath) modal._state.folder=newPath;
    else if(oldPath&&cur.indexOf(oldPath+"/")===0) modal._state.folder=newPath+cur.slice(oldPath.length);
    if(modal._state.selected){
      var urlPrefix="./assets/uploads/videos/"+(oldPath?oldPath+"/":"");
      var newUrlPrefix="./assets/uploads/videos/"+(newPath?newPath+"/":"");
      if(modal._state.selected.indexOf(urlPrefix)===0){
        modal._state.selected=newUrlPrefix+modal._state.selected.slice(urlPrefix.length);
      }
    }
    adminVideoMediaLoad(modal);
  });
}
function adminVideoMediaDeleteFile(modal, url, name){
  if(!confirm("确定删除视频「"+(name||"")+"」？")) return;
  adminVideoMediaPost("delete_file",{url:url},function(err,j){
    if(err||!j||j.code!==0){ adminToast((j&&j.message)||"删除失败"); return; }
    if(modal._state.selected===url) modal._state.selected="";
    adminVideoMediaLoad(modal);
  });
}
function adminVideoMediaRenderBreadcrumb(modal, list){
  var box=modal.querySelector(".admin-media-breadcrumb");
  if(!box) return;
  if(!list||!list.length){ box.innerHTML=""; box.style.display="none"; return; }
  box.style.display="block";
  var html="";
  list.forEach(function(bc,i){
    if(i>0) html+="<span class=\"bc-sep\">&gt;</span>";
    var cls=(i===list.length-1)?" class=\"active\"":"";
    html+="<a href=\"javascript:;\""+cls+" data-folder=\""+String(bc.id||"").replace(/"/g,"")+"\">"+(bc.name||"")+"</a>";
  });
  box.innerHTML=html;
  box.querySelectorAll("a[data-folder]").forEach(function(a){
    a.onclick=function(){ modal._state.folder=a.getAttribute("data-folder")||""; adminVideoMediaLoad(modal); };
  });
}
function adminVideoMediaSyncOk(modal){
  var okBtn=modal.querySelector(".admin-media-ok");
  var ext=modal.querySelector(".admin-video-external-url");
  var extVal=ext?String(ext.value||"").trim():"";
  if(okBtn) okBtn.disabled=!modal._state.selected&&!extVal;
}
function adminVideoMediaLoad(modal){
  var grid=modal.querySelector(".admin-media-grid");
  if(!grid) return;
  grid.innerHTML="<div class=\"admin-media-empty\">加载中…</div>";
  var qs="action=browse";
  if(modal._state.folder) qs+="&folder="+encodeURIComponent(modal._state.folder);
  fetch("video_media.php?"+qs,{credentials:"same-origin"})
    .then(function(r){return r.json();})
    .then(function(j){
      if(j.code!==0){ grid.innerHTML="<div class=\"admin-media-empty\">"+(j.message||"加载失败")+"</div>"; return; }
      var data=j.data||{}, files=data.files||[], folders=data.folders||[];
      var usedEl=modal.querySelector(".admin-media-used");
      if(usedEl&&data.storage) usedEl.textContent="已用"+adminFormatBytes(data.storage.used||0);
      adminVideoMediaRenderBreadcrumb(modal, data.breadcrumb||[]);
      if(!folders.length&&!files.length){
        grid.innerHTML="<div class=\"admin-media-empty\">暂无视频，可上传、新建文件夹，或在下方填写 CDN 外链</div>";
        adminVideoMediaSyncOk(modal);
        return;
      }
      grid.innerHTML="";
      folders.forEach(function(fd){
        var item=document.createElement("div");
        item.className="admin-media-item folder-item";
        item.onclick=function(){ modal._state.folder=fd.path||""; adminVideoMediaLoad(modal); };
        var icon=document.createElement("div"); icon.className="admin-media-folder-icon"; item.appendChild(icon);
        var nm=document.createElement("div"); nm.className="admin-media-item-name"; nm.textContent=fd.name||""; nm.title=fd.name||""; item.appendChild(nm);
        var ren=document.createElement("button"); ren.type="button"; ren.className="item-ren"; ren.textContent="✎"; ren.title="重命名";
        ren.onclick=function(ev){ ev.stopPropagation(); adminVideoMediaRenameFolder(modal, fd.path||"", fd.name||""); };
        item.appendChild(ren);
        var del=document.createElement("button"); del.type="button"; del.className="item-del"; del.textContent="×";
        del.onclick=function(ev){ ev.stopPropagation(); adminVideoMediaDeleteFolder(modal, fd.path||"", fd.name||""); };
        item.appendChild(del);
        grid.appendChild(item);
      });
      files.forEach(function(f){
        var item=document.createElement("div");
        item.className="admin-media-item"+((modal._state.selected===f.url)?" selected":"");
        item.onclick=function(){
          modal._state.selected=f.url;
          var ext=modal.querySelector(".admin-video-external-url"); if(ext) ext.value="";
          grid.querySelectorAll(".admin-media-item").forEach(function(el){ el.classList.remove("selected"); el.querySelector(".admin-media-check")&&el.querySelector(".admin-media-check").remove(); });
          item.classList.add("selected");
          var ck=document.createElement("span"); ck.className="admin-media-check"; ck.textContent="✓"; item.appendChild(ck);
          adminVideoMediaSyncOk(modal);
        };
        var icon=document.createElement("div"); icon.className="admin-media-video-icon"; icon.textContent="▶"; item.appendChild(icon);
        if(f.name){
          var nm=document.createElement("div"); nm.className="admin-media-item-name"; nm.textContent=f.name; nm.title=f.name; item.appendChild(nm);
        }
        if(f.size){
          var sz=document.createElement("div"); sz.className="admin-media-item-name"; sz.style.color="#999"; sz.textContent=adminFormatBytes(f.size); item.appendChild(sz);
        }
        if(modal._state.selected===f.url){
          var ck2=document.createElement("span"); ck2.className="admin-media-check"; ck2.textContent="✓"; item.appendChild(ck2);
        }
        var del=document.createElement("button"); del.type="button"; del.className="item-del"; del.textContent="×";
        del.onclick=function(ev){ ev.stopPropagation(); adminVideoMediaDeleteFile(modal, f.url||"", f.name||""); };
        item.appendChild(del);
        grid.appendChild(item);
      });
      adminVideoMediaSyncOk(modal);
    })
    .catch(function(){ grid.innerHTML="<div class=\"admin-media-empty\">加载失败</div>"; });
}
function adminPickVideo(inputId){
  var input=document.getElementById(inputId); if(!input) return;
  var modal=document.getElementById("admin-video-modal");
  if(!modal){
    modal=document.createElement("div");
    modal.id="admin-video-modal";
    modal.className="admin-modal-overlay";
    modal.innerHTML="<div class=\"admin-media-modal\" onclick=\"event.stopPropagation()\"><div class=\"admin-media-head\"><h3>视频库<small>（mp4/webm/mov，大文件建议填 CDN 外链）</small></h3><button type=\"button\" class=\"admin-media-close\" aria-label=\"关闭\">×</button></div><div class=\"admin-media-body\" style=\"height:460px\"><aside class=\"admin-media-side\" style=\"width:120px\"><div class=\"admin-media-storage\" style=\"margin-top:12px\"><span class=\"admin-media-used\">已用0B</span></div></aside><section class=\"admin-media-main\"><div class=\"admin-media-toolbar\"><button type=\"button\" class=\"admin-media-toolbtn admin-video-upload\">↑ 上传视频</button><button type=\"button\" class=\"admin-media-toolbtn admin-video-mkdir\">+ 新建文件夹</button></div><div class=\"admin-media-breadcrumb\"></div><div class=\"admin-media-grid\"></div></section></div><div class=\"admin-media-external\"><label>外部视频地址 / CDN</label><input type=\"text\" class=\"admin-video-external-url\" placeholder=\"https://cdn.example.com/video.mp4\"></div><div class=\"admin-media-foot\"><button type=\"button\" class=\"btn admin-media-ok\" disabled>确定</button><button type=\"button\" class=\"btn btn-secondary admin-media-cancel\">取消</button></div></div>";
    document.body.appendChild(modal);
    modal._state={selected:"",target:"",folder:""};
    modal.querySelector(".admin-media-close").onclick=function(){ modal.style.display="none"; };
    modal.querySelector(".admin-media-cancel").onclick=function(){ modal.style.display="none"; };
    modal.onclick=function(ev){ if(ev.target===modal) modal.style.display="none"; };
    modal.querySelector(".admin-video-mkdir").onclick=function(){ adminVideoMediaCreateFolder(modal); };
    modal.querySelector(".admin-video-upload").onclick=function(){
      var picker=document.createElement("input"); picker.type="file"; picker.accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov";
      picker.onchange=function(){
        if(!picker.files||!picker.files[0]) return;
        adminUploadVideoFile(picker.files[0], modal._state.folder||"", function(err,j){
          if(err){ adminToast(err.message||"上传失败"); return; }
          if(j&&j.code===0&&j.data&&j.data.url){
            modal._state.selected=j.data.url;
            var ext=modal.querySelector(".admin-video-external-url"); if(ext) ext.value="";
            adminVideoMediaLoad(modal);
          } else adminToast((j&&j.message)||"上传失败");
        });
      };
      picker.click();
    };
    var extInp=modal.querySelector(".admin-video-external-url");
    if(extInp){
      extInp.oninput=function(){
        if(String(extInp.value||"").trim()){
          modal._state.selected="";
          var grid=modal.querySelector(".admin-media-grid");
          if(grid) grid.querySelectorAll(".admin-media-item.selected").forEach(function(el){ el.classList.remove("selected"); el.querySelector(".admin-media-check")&&el.querySelector(".admin-media-check").remove(); });
        }
        adminVideoMediaSyncOk(modal);
      };
    }
    modal.querySelector(".admin-media-ok").onclick=function(){
      var id=modal._state.target, inp=document.getElementById(id);
      var ext=modal.querySelector(".admin-video-external-url");
      var url=(ext&&String(ext.value||"").trim())||modal._state.selected||"";
      if(inp&&url){ inp.value=url; }
      modal.style.display="none";
    };
  }
  modal._state.target=inputId;
  modal._state.selected=(input.value||"").match(/^https?:\/\//i)?"":(input.value||"");
  modal._state.folder="";
  var extInp=modal.querySelector(".admin-video-external-url");
  if(extInp){
    extInp.value=(input.value||"").match(/^https?:\/\//i)?input.value:"";
  }
  adminVideoMediaLoad(modal);
  modal.style.display="flex";
}
function adminBatchToggleAll(master){
  var form=master.closest("form"); if(!form) return;
  form.querySelectorAll(".batch-row-check").forEach(function(cb){cb.checked=master.checked;});
}
function adminBatchDeleteConfirm(form){
  var n=form.querySelectorAll(".batch-row-check:checked").length;
  if(n===0){adminToast("请先勾选要删除的记录");return false;}
  var msg=form.getAttribute("data-confirm")||"确定删除选中的记录？删除后不可恢复";
  return confirm(msg.replace("{n}",String(n)));
}
window.adminQuillMap=window.adminQuillMap||{};
function adminQuillAssetUrl(u){
  if(!u) return u;
  if(/^https?:\\/\\//i.test(u)||u.indexOf("//")===0) return u;
  if(u.indexOf("./")===0) u=u.slice(1);
  if(u.charAt(0)!=="/") u="/"+u;
  return u;
}
function adminQuillNormalizeHtml(html){
  var s=String(html||"");
  var q=String.fromCharCode(34);
  return s.replace(/src="(\\.\\/[^"]+)"/g,function(m,p){return "src="+q+adminQuillAssetUrl(p)+q;});
}
function adminInitQuill(editorSel,initialHtml){
  if(typeof Quill==="undefined") return null;
  window.adminQuillMap=window.adminQuillMap||{};
  if(window.adminQuillMap[editorSel]) return window.adminQuillMap[editorSel];
  var el=document.querySelector(editorSel);
  if(!el) return null;
  try {
  var q=new Quill(editorSel,{theme:"snow",modules:{toolbar:[[{header:[1,2,3,false]}],["bold","italic","underline"],[{color:[]},{background:[]}],[{list:"ordered"},{list:"bullet"}],["link","image"],["clean"]]}});
  q.root.innerHTML=adminQuillNormalizeHtml(initialHtml);
  q.root.addEventListener("click",function(ev){
    if(ev.target&&ev.target.tagName==="IMG"){
      var cur=ev.target.style.width||ev.target.getAttribute("width")||"100%";
      var w=prompt("图片宽度（如 100% 或 300px）",cur);
      if(w!==null){ ev.target.style.width=w; ev.target.style.height="auto"; ev.target.removeAttribute("width"); ev.target.removeAttribute("height"); }
    }
  });
  q.getModule("toolbar").addHandler("image",function(){
    var picker=document.createElement("input");picker.type="file";picker.accept="image/*";
    picker.onchange=function(){
      if(!picker.files||!picker.files[0]) return;
      var fd=new FormData();fd.append("file",picker.files[0]);
      fetch("upload.php",{method:"POST",body:fd,credentials:"same-origin"}).then(function(r){return r.json();}).then(function(j){
        if(j.code===0&&j.data&&j.data.url){
          var url=adminQuillAssetUrl(j.data.url);
          var range=q.getSelection(true);
          q.insertEmbed(range.index,"image",url);
          q.setSelection(range.index+1);
        } else adminToast(j.message||"上传失败");
      }).catch(function(){adminToast("上传失败");});
    };picker.click();
  });
  window.adminQuillMap[editorSel]=q;
  return q;
  } catch(e) { console.error("Quill init failed", e); return null; }
}
function adminQuillSync(editorSel,hiddenId){
  var q=window.adminQuillMap[editorSel];
  if(q&&hiddenId) document.getElementById(hiddenId).value=q.root.innerHTML;
  return true;
}
</script>';
    echo '</main></div></body></html>';
}

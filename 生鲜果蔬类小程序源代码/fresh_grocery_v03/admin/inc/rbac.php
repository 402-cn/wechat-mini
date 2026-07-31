<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once dirname(__DIR__, 2) . '/api/core/bootstrap.php';

function admin_rbac_ensure_tables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_groups (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL,
        slug VARCHAR(50) NOT NULL,
        is_system TINYINT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id), UNIQUE KEY uk_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_group_permissions (
        group_id BIGINT UNSIGNED NOT NULL,
        permission_key VARCHAR(100) NOT NULL,
        PRIMARY KEY (group_id, permission_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_audit_logs (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        admin_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        admin_username VARCHAR(50) NOT NULL DEFAULT '',
        action VARCHAR(20) NOT NULL DEFAULT '',
        module VARCHAR(80) NOT NULL DEFAULT '',
        detail TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id), KEY idx_created (created_at), KEY idx_admin (admin_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $cols = $pdo->query("SHOW COLUMNS FROM admins LIKE 'group_id'")->fetch();
    if (!$cols) {
        $pdo->exec("ALTER TABLE admins ADD COLUMN group_id BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER nickname");
    }
}

function admin_rbac_super_group_id(PDO $pdo): int {
    admin_rbac_ensure_tables($pdo);
    $id = (int)$pdo->query("SELECT id FROM admin_groups WHERE slug='super' LIMIT 1")->fetchColumn();
    if ($id > 0) return $id;
    $pdo->exec("INSERT INTO admin_groups (name, slug, is_system) VALUES ('超级管理员','super',1)");
    return (int)$pdo->lastInsertId();
}

function admin_rbac_all_permission_keys(): array {
    $keys = [];
    $raw = @file_get_contents(dirname(__DIR__) . '/_menu.json');
    $menu = json_decode($raw ?: '[]', true) ?: [];
    foreach (admin_menu_flatten_leaves($menu) as $item) {
        $id = (string)($item['id'] ?? '');
        if ($id !== '') {
            $keys[$id] = ['label' => (string)($item['label'] ?? $id), 'group' => admin_menu_leaf_group($item)];
        }
    }
    foreach ([
        'system_admins' => ['label' => '管理员', 'group' => '系统'],
        'system_admin_groups' => ['label' => '管理员组', 'group' => '系统'],
        'system_audit_logs' => ['label' => '操作日志', 'group' => '系统'],
    ] as $k => $meta) {
        $keys[$k] = $meta;
    }
    return $keys;
}

function admin_rbac_grant_super_all(PDO $pdo): void {
    $gid = admin_rbac_super_group_id($pdo);
    $pdo->prepare('DELETE FROM admin_group_permissions WHERE group_id=?')->execute([$gid]);
    $stmt = $pdo->prepare('INSERT IGNORE INTO admin_group_permissions (group_id, permission_key) VALUES (?,?)');
    foreach (array_keys(admin_rbac_all_permission_keys()) as $key) {
        $stmt->execute([$gid, $key]);
    }
}

function admin_rbac_load_session(int $adminId): void {
    $pdo = db();
    admin_rbac_ensure_tables($pdo);
    $stmt = $pdo->prepare('SELECT a.id,a.username,a.status,a.group_id,g.slug AS group_slug,g.is_system FROM admins a LEFT JOIN admin_groups g ON a.group_id=g.id WHERE a.id=? LIMIT 1');
    $stmt->execute([$adminId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || (int)$row['status'] !== 1) {
        session_destroy();
        header('Location: index.php?error=1');
        exit;
    }
    $_SESSION['admin_id'] = (int)$row['id'];
    $_SESSION['admin_username'] = (string)$row['username'];
    $_SESSION['admin_group_id'] = (int)$row['group_id'];
    $_SESSION['admin_is_super'] = ((string)($row['group_slug'] ?? '') === 'super');
    if ($_SESSION['admin_is_super']) {
        $_SESSION['admin_permissions'] = array_keys(admin_rbac_all_permission_keys());
        return;
    }
    $pstmt = $pdo->prepare('SELECT permission_key FROM admin_group_permissions WHERE group_id=?');
    $pstmt->execute([(int)$row['group_id']]);
    $_SESSION['admin_permissions'] = array_column($pstmt->fetchAll(PDO::FETCH_ASSOC), 'permission_key');
}

function admin_rbac_bootstrap(): void {
    if (empty($_SESSION['admin_id'])) return;
    admin_rbac_load_session((int)$_SESSION['admin_id']);
}

function admin_is_super(): bool {
    return !empty($_SESSION['admin_is_super']);
}

function admin_can(string $permKey): bool {
    if (admin_is_super()) return true;
    $perms = $_SESSION['admin_permissions'] ?? [];
    return in_array($permKey, $perms, true);
}

function admin_filter_menu(array $menu): array {
    if (admin_is_super()) return $menu;
    $out = [];
    foreach ($menu as $node) {
        $filtered = admin_filter_menu_node($node);
        if ($filtered !== null) $out[] = $filtered;
    }
    return $out;
}

function admin_filter_menu_node(array $node): ?array {
    $children = $node['children'] ?? [];
    if ($children) {
        $next = [];
        foreach ($children as $child) {
            $filtered = admin_filter_menu_node($child);
            if ($filtered !== null) $next[] = $filtered;
        }
        if (!$next) return null;
        $node['children'] = $next;
        return $node;
    }
    $id = (string)($node['id'] ?? '');
    if ($id !== '' && admin_can($id)) return $node;
    return null;
}

function admin_menu_flatten_leaves(array $nodes, string $groupLabel = ''): array {
    $out = [];
    foreach ($nodes as $node) {
        $label = (string)($node['label'] ?? '');
        $children = $node['children'] ?? [];
        $nextGroup = $groupLabel;
        if ($children && $groupLabel === '') {
            $nextGroup = $label;
        }
        if ($children) {
            foreach (admin_menu_flatten_leaves($children, $nextGroup !== '' ? $nextGroup : $label) as $leaf) {
                $out[] = $leaf;
            }
            continue;
        }
        $node['_menu_group'] = $groupLabel !== '' ? $groupLabel : '其他';
        $out[] = $node;
    }
    return $out;
}

function admin_menu_leaf_group(array $item): string {
    return (string)($item['_menu_group'] ?? '其他');
}

function admin_menu_find_id_by_href(array $nodes, string $href): ?string {
    foreach ($nodes as $node) {
        if ((string)($node['href'] ?? '') === $href) {
            return (string)($node['id'] ?? '');
        }
        $children = $node['children'] ?? [];
        if ($children) {
            $found = admin_menu_find_id_by_href($children, $href);
            if ($found !== null && $found !== '') return $found;
        }
    }
    return null;
}

function admin_resolve_permission_key(): ?string {
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $exempt = ['change_password.php','upload.php','link_search.php','media.php','video_upload.php','video_media.php'];
    if (in_array($script, $exempt, true)) return null;
    $static = [
        'navigation_map.php' => 'navigation_map', 'dashboard.php' => 'navigation_map',
        'settings.php' => 'settings', 'products.php' => 'products',
        'product_categories.php' => 'product_categories', 'articles.php' => 'articles',
        'article_categories.php' => 'article_categories', 'users.php' => 'users',
        'orders.php' => 'orders', 'coupons.php' => 'coupons', 'user_coupons.php' => 'user_coupons',
        'user_addresses.php' => 'user_addresses', 'user_invites.php' => 'user_invites',
        'member_levels.php' => 'member_levels', 'stats.php' => 'stats', 'form_data.php' => 'form_data',
        'form_submissions.php' => 'form_data', 'forms.php' => 'forms_index', 'product_widgets.php' => 'product_widgets',
        'article_widgets.php' => 'article_widgets', 'message_boards.php' => 'message_boards',
        'admins.php' => 'system_admins', 'admin_groups.php' => 'system_admin_groups',
        'audit_logs.php' => 'system_audit_logs',
    ];
    if (isset($static[$script])) return $static[$script];
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    $href = $script . ($qs !== '' ? '?' . $qs : '');
    $raw = @file_get_contents(dirname(__DIR__) . '/_menu.json');
    $menu = json_decode($raw ?: '[]', true) ?: [];
    $found = admin_menu_find_id_by_href($menu, $href);
    if ($found !== null && $found !== '') return $found;
    if (isset($_GET['id'])) {
        $partial = $script . '?id=' . rawurlencode((string)$_GET['id']);
        $found = admin_menu_find_id_by_href($menu, $partial);
        if ($found !== null && $found !== '') return $found;
    }
    if (isset($_GET['form_id'])) {
        $partial = $script . '?form_id=' . rawurlencode((string)$_GET['form_id']);
        $found = admin_menu_find_id_by_href($menu, $partial);
        if ($found !== null && $found !== '') return $found;
    }
    return null;
}

function admin_require_permission(): void {
    $key = admin_resolve_permission_key();
    if ($key === null || $key === '') return;
    if (!admin_can($key)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>无权限</title></head><body style="font-family:sans-serif;padding:40px">';
        echo '<h2>无访问权限</h2><p>您的账号所属管理员组未开通「' . htmlspecialchars($key) . '」功能。</p>';
        echo '<p><a href="navigation_map.php">返回导航地图</a></p></body></html>';
        exit;
    }
}

function admin_audit(string $action, string $module, string $detail): void {
    try {
        $pdo = db();
        admin_rbac_ensure_tables($pdo);
        $pdo->prepare('INSERT INTO admin_audit_logs (admin_id,admin_username,action,module,detail) VALUES (?,?,?,?,?)')->execute([
            (int)($_SESSION['admin_id'] ?? 0),
            (string)($_SESSION['admin_username'] ?? ''),
            $action, $module, $detail,
        ]);
    } catch (Throwable $e) { /* 审计失败不阻断业务 */ }
}

function admin_audit_field_changes(array $before, array $after, array $fields): string {
    $parts = [];
    foreach ($fields as $label => $key) {
        $old = (string)($before[$key] ?? '');
        $new = (string)($after[$key] ?? '');
        if ($old !== $new) $parts[] = '把 ' . $label . ' 从「' . $old . '」改成了「' . $new . '」';
    }
    return implode('；', $parts);
}

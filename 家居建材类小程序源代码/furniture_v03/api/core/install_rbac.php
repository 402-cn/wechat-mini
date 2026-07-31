<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/install_demo.php';

function install_rbac_ensure_tables(PDO $pdo): void {
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

function install_rbac_super_group_id(PDO $pdo): int {
    install_rbac_ensure_tables($pdo);
    $id = (int)$pdo->query("SELECT id FROM admin_groups WHERE slug='super' LIMIT 1")->fetchColumn();
    if ($id > 0) return $id;
    $pdo->exec("INSERT INTO admin_groups (name, slug, is_system) VALUES ('超级管理员','super',1)");
    return (int)$pdo->lastInsertId();
}

function install_rbac_permission_keys(): array {
    $keys = [];
    $menuPath = dirname(__DIR__, 2) . '/admin/_menu.json';
    $raw = @file_get_contents($menuPath);
    $menu = json_decode($raw ?: '[]', true) ?: [];
    foreach ($menu as $item) {
        $id = (string)($item['id'] ?? '');
        if ($id !== '') $keys[] = $id;
    }
    foreach (['system_admins', 'system_admin_groups', 'system_audit_logs'] as $k) {
        $keys[] = $k;
    }
    return array_values(array_unique($keys));
}

function install_rbac_grant_super_all(PDO $pdo): void {
    $gid = install_rbac_super_group_id($pdo);
    $pdo->prepare('DELETE FROM admin_group_permissions WHERE group_id=?')->execute([$gid]);
    $stmt = $pdo->prepare('INSERT IGNORE INTO admin_group_permissions (group_id, permission_key) VALUES (?,?)');
    foreach (install_rbac_permission_keys() as $key) {
        $stmt->execute([$gid, $key]);
    }
}

function install_init_rbac(PDO $pdo, string $adminUsername): void {
    install_rbac_ensure_tables($pdo);
    $superId = install_rbac_super_group_id($pdo);
    install_rbac_grant_super_all($pdo);
    $uname = $adminUsername !== '' ? $adminUsername : 'admin';
    $pdo->prepare('UPDATE admins SET group_id=? WHERE username=?')->execute([$superId, $uname]);
}

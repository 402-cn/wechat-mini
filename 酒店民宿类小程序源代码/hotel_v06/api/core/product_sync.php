<?php
/**
 * 筑码引擎 www.402.cn
 */

/** 商品表结构自动补齐 */
function ensure_product_schema(PDO $pdo): void {
    if (!$pdo->query("SHOW TABLES LIKE 'products'")->fetch()) return;
    foreach ([
        'is_demo TINYINT NOT NULL DEFAULT 0',
        'is_featured TINYINT NOT NULL DEFAULT 0',
        'stock INT NOT NULL DEFAULT -1',
        'is_flash_sale TINYINT NOT NULL DEFAULT 0',
        'flash_stock INT NOT NULL DEFAULT -1',
        'flash_end_at DATETIME NULL',
        'view_count INT NOT NULL DEFAULT 0',
    ] as $col) {
        $name = preg_replace('/ .*/', '', $col);
        if (!$pdo->query("SHOW COLUMNS FROM products LIKE '$name'")->fetch()) {
            $pdo->exec("ALTER TABLE products ADD COLUMN $col");
        }
    }
    if (!$pdo->query("SHOW TABLES LIKE 'product_widgets'")->fetch()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS product_widgets (
          instance_id VARCHAR(64) NOT NULL,
          page_key VARCHAR(50) NOT NULL DEFAULT '',
          label VARCHAR(100) NOT NULL DEFAULT '',
          layout VARCHAR(20) NOT NULL DEFAULT 'grid',
          columns INT NOT NULL DEFAULT 2,
          row_count INT NOT NULL DEFAULT 3,
          show_limit INT NOT NULL DEFAULT 6,
          product_ids JSON,
          featured_ids JSON,
          status TINYINT NOT NULL DEFAULT 1,
          updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (instance_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    if (!$pdo->query("SHOW TABLES LIKE 'product_scroll_widgets'")->fetch()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS product_scroll_widgets (
          instance_id VARCHAR(64) NOT NULL,
          page_key VARCHAR(50) NOT NULL DEFAULT '',
          label VARCHAR(100) NOT NULL DEFAULT '',
          title VARCHAR(100) NOT NULL DEFAULT '限时秒杀',
          item_count INT NOT NULL DEFAULT 6,
          product_ids JSON,
          status TINYINT NOT NULL DEFAULT 1,
          updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (instance_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    // 兼容旧库演示商品默认库存 999 → 不限库存
    $pdo->exec('UPDATE products SET stock = -1 WHERE stock = 999');
}

/** stock < 0 表示不限库存 */
function product_stock_unlimited(int $stock): bool {
    return $stock < 0;
}

/** API 商品/文章图：保留 /uploads/stock 路径；H5 端 assetUrl() 映射本地包，小程序 assetUrl() 走远程 API */
function product_frontend_image(string $url): string {
    $url = trim($url);
    if ($url === '' || preg_match('#^(https?:)?//#i', $url) || strpos($url, 'data:') === 0) {
        return $url;
    }
    if (preg_match('#/uploads/(?:stock|images)/([^?#]+)#', $url, $m)) {
        return '/uploads/stock/' . $m[1];
    }
    if (preg_match('#(?:\./)?assets/images/([^?#]+)#', $url, $m)) {
        return '/uploads/stock/' . $m[1];
    }
    return $url;
}

function product_normalize_row_images(array &$rows): void {
    foreach ($rows as &$row) {
        if (isset($row['image'])) {
            $row['image'] = product_frontend_image((string)$row['image']);
        }
    }
    unset($row);
}

function product_stock_available(int $stock, int $qty): bool {
    if ($qty <= 0) return false;
    if (product_stock_unlimited($stock)) return true;
    return $stock >= $qty;
}

/** 支付成功时扣减；限库存商品用 stock >= qty 条件防并发超卖 */
function product_deduct_stock(PDO $pdo, int $productId, int $qty): bool {
    if ($productId <= 0 || $qty <= 0) return false;
    $stmt = $pdo->prepare('SELECT stock FROM products WHERE id=? AND status=1 LIMIT 1');
    $stmt->execute([$productId]);
    $stock = $stmt->fetchColumn();
    if ($stock === false) return false;
    if (product_stock_unlimited((int)$stock)) return true;
    $upd = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND status = 1 AND stock >= ?');
    $upd->execute([$qty, $productId, $qty]);
    return $upd->rowCount() > 0;
}

/** 取消已支付订单时归还库存 */
function product_restore_stock(PDO $pdo, int $productId, int $qty): void {
    if ($productId <= 0 || $qty <= 0) return;
    $stmt = $pdo->prepare('SELECT stock FROM products WHERE id=? LIMIT 1');
    $stmt->execute([$productId]);
    $stock = $stmt->fetchColumn();
    if ($stock === false || product_stock_unlimited((int)$stock)) return;
    $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id=?')->execute([$qty, $productId]);
}

function product_validate_order_items(PDO $pdo, array $items): ?string {
    foreach ($items as $it) {
        $pid = (int)($it['product_id'] ?? 0);
        $qty = max(1, (int)($it['quantity'] ?? 1));
        $s = $pdo->prepare('SELECT stock, name FROM products WHERE id=? AND status=1 LIMIT 1');
        $s->execute([$pid]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if (!$row) return '商品不存在或已下架';
        if (!product_stock_available((int)$row['stock'], $qty)) {
            return '「' . ($row['name'] ?? '') . '」库存不足';
        }
    }
    return null;
}

function product_deduct_order_items(PDO $pdo, int $orderId): ?string {
    $stmt = $pdo->prepare('SELECT product_id, quantity, product_name FROM order_items WHERE order_id=?');
    $stmt->execute([$orderId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $it) {
        $pid = (int)$it['product_id'];
        $qty = max(1, (int)$it['quantity']);
        if (!product_deduct_stock($pdo, $pid, $qty)) {
            return '「' . ($it['product_name'] ?? '') . '」库存不足，无法完成支付';
        }
    }
    return null;
}

function product_restore_order_items(PDO $pdo, int $orderId): void {
    $stmt = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id=?');
    $stmt->execute([$orderId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $it) {
        product_restore_stock($pdo, (int)$it['product_id'], max(1, (int)$it['quantity']));
    }
}

function demo_suppressed_product_ids(PDO $pdo): array {
    if (!$pdo->query("SHOW TABLES LIKE 'site_settings'")->fetch()) return [];
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key='demo_suppressed_product_ids' LIMIT 1");
    $stmt->execute();
    $raw = $stmt->fetchColumn();
    if (!$raw) return [];
    $data = json_decode((string)$raw, true);
    return is_array($data) ? array_values(array_map('intval', $data)) : [];
}

function demo_suppress_product_id(PDO $pdo, int $id): void {
    if ($id <= 0 || !$pdo->query("SHOW TABLES LIKE 'site_settings'")->fetch()) return;
    $ids = demo_suppressed_product_ids($pdo);
    if (!in_array($id, $ids, true)) $ids[] = $id;
    $pdo->prepare("INSERT INTO site_settings (setting_key,setting_value) VALUES ('demo_suppressed_product_ids',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([json_encode($ids)]);
}

function demo_clear_product_suppression(PDO $pdo): void {
    if (!$pdo->query("SHOW TABLES LIKE 'site_settings'")->fetch()) return;
    $pdo->exec("DELETE FROM site_settings WHERE setting_key='demo_suppressed_product_ids'");
}

function product_hard_delete(PDO $pdo, int $id): void {
    if ($id <= 0) return;
    $chk = $pdo->prepare('SELECT is_demo FROM products WHERE id=? LIMIT 1');
    $chk->execute([$id]);
    $row = $chk->fetch(PDO::FETCH_ASSOC);
    if ($row && (int)($row['is_demo'] ?? 0) === 1) demo_suppress_product_id($pdo, $id);
    product_remove_from_widget_lists($pdo, $id);
    $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
}

/** 画布导入的种子数据在 PHP 侧视为普通商品，清除 is_demo 并停止画布同步覆盖 */
function catalog_finalize_editable_products(PDO $pdo): void {
    if (!$pdo->query("SHOW TABLES LIKE 'products'")->fetch()) return;
    $pdo->exec('UPDATE products SET is_demo=0 WHERE is_demo=1');
}

function catalog_seed_product_ids(PDO $pdo): array {
    catalog_finalize_editable_products($pdo);
    $ids = [];
    foreach (demo_product_catalog() as $d) {
        $id = (int)$d[0];
        $chk = $pdo->prepare('SELECT id FROM products WHERE id=? LIMIT 1');
        $chk->execute([$id]);
        if ($chk->fetch()) $ids[] = $id;
    }
    if ($ids) return $ids;
    $rows = $pdo->query('SELECT id FROM products WHERE status=1 ORDER BY sort_order DESC,id ASC')->fetchAll(PDO::FETCH_COLUMN);
    return array_values(array_map('intval', $rows ?: []));
}

/** 与画布 demoProducts / 迁移种子一致的演示商品目录 */
function demo_product_catalog(): array {
    return [
        [7, 2, '豪华大床', './assets/images/hotel_16.jpg', 168.00],
        [8, 2, '行政房', './assets/images/hotel_17.jpg', 396.00],
        [9, 2, '景观房', './assets/images/hotel_18.jpg', 624.00],
        [10, 2, '亲子房', './assets/images/hotel_19.jpg', 852.00],
        [11, 2, '套房', './assets/images/hotel_20.jpg', 1080.00],
        [12, 2, '连通房', './assets/images/hotel_21.jpg', 1308.00],
        [13, 3, '总统套房', './assets/images/hotel_22.jpg', 168.00],
        [14, 3, '泳池别墅', './assets/images/hotel_23.jpg', 396.00],
        [15, 3, '海景房', './assets/images/hotel_24.jpg', 624.00],
        [16, 3, '山景房', './assets/images/hotel_25.jpg', 852.00],
        [17, 3, '蜜月房', './assets/images/hotel_26.jpg', 1080.00],
        [18, 3, '商务套房', './assets/images/hotel_27.jpg', 1308.00],
        [19, 4, '精品民宿', './assets/images/hotel_28.jpg', 168.00],
        [20, 4, 'loft民宿', './assets/images/hotel_29.jpg', 396.00],
        [21, 4, '独栋小院', './assets/images/hotel_30.jpg', 624.00],
        [22, 4, '古镇客栈', './assets/images/hotel_31.jpg', 852.00],
        [23, 4, '温泉民宿', './assets/images/hotel_32.jpg', 1080.00],
        [24, 4, '露营帐篷', './assets/images/hotel_33.jpg', 1308.00],
        [25, 5, '连住2晚', './assets/images/hotel_34.jpg', 168.00],
        [26, 5, '含早套餐', './assets/images/hotel_35.jpg', 396.00],
        [27, 5, '周末特惠', './assets/images/hotel_36.jpg', 624.00],
        [28, 5, '节日套餐', './assets/images/hotel_37.jpg', 852.00],
        [29, 5, '会议套餐', './assets/images/hotel_38.jpg', 1080.00],
        [30, 5, '长住优惠', './assets/images/hotel_39.jpg', 1308.00],
    ];
}

/** 仅安装时 allowSeed=true 才写入种子；运行时只返回目录 ID，不再同步画布图片 */
function ensure_demo_products(PDO $pdo, bool $allowSeed = false): array {
    ensure_product_schema($pdo);
    if (!$pdo->query("SHOW TABLES LIKE 'products'")->fetch()) return [];
    if (!$allowSeed) {
        return catalog_seed_product_ids($pdo);
    }
        $pdo->exec("INSERT IGNORE INTO product_categories (id,name,sort_order,status) VALUES (1,'经济',1,1)");
        $pdo->exec("INSERT IGNORE INTO product_categories (id,name,sort_order,status) VALUES (2,'舒适',2,1)");
        $pdo->exec("INSERT IGNORE INTO product_categories (id,name,sort_order,status) VALUES (3,'高档',3,1)");
        $pdo->exec("INSERT IGNORE INTO product_categories (id,name,sort_order,status) VALUES (4,'民宿',4,1)");
        $pdo->exec("INSERT IGNORE INTO product_categories (id,name,sort_order,status) VALUES (5,'套餐',5,1)");
    $desc = '请在PHP管理后台修改商品描述';
    $demos = demo_product_catalog();
    $tableEmpty = !$pdo->query('SELECT id FROM products LIMIT 1')->fetch();
    if ($tableEmpty) demo_clear_product_suppression($pdo);
    $suppressed = demo_suppressed_product_ids($pdo);
    $ins = $pdo->prepare('INSERT INTO products (id,category_id,name,image,price,description,is_demo,is_featured,sort_order,stock,status) VALUES (?,?,?,?,?,?,0,1,?, -1, 1)');
    $fix = $pdo->prepare('UPDATE products SET status=1,is_demo=0,category_id=?,name=?,image=?,price=?,sort_order=?,stock=-1 WHERE id=?');
    $ids = [];
    $order = 100;
    foreach ($demos as $d) {
        [$id, $catId, $name, $img, $price] = $d;
        if (in_array((int)$id, $suppressed, true)) continue;
        $sort = $order--;
        $chk = $pdo->prepare('SELECT id FROM products WHERE id=? LIMIT 1');
        $chk->execute([$id]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if ($tableEmpty) {
                $fix->execute([$catId, $name, $img, $price, $sort, $id]);
            }
            $ids[] = (int)$id;
        } elseif ($tableEmpty) {
            $ins->execute([$id, $catId, $name, $img, $price, $desc, $sort]);
            $ids[] = (int)$id;
        }
    }
    return $ids;
}

function product_add_to_widget_lists(PDO $pdo, int $productId): void {
    if ($productId <= 0 || !$pdo->query("SHOW TABLES LIKE 'product_widgets'")->fetch()) return;
    $stmt = $pdo->query('SELECT instance_id, featured_ids FROM product_widgets WHERE status=1');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $ids = json_decode($row['featured_ids'] ?: '[]', true);
        if (!is_array($ids)) $ids = [];
        $ids = array_values(array_map('intval', $ids));
        if (!in_array($productId, $ids, true)) {
            $ids[] = $productId;
            $pdo->prepare('UPDATE product_widgets SET featured_ids=? WHERE instance_id=?')->execute([json_encode($ids), $row['instance_id']]);
        }
    }
}

function product_remove_from_widget_lists(PDO $pdo, int $productId): void {
    if ($productId <= 0) return;
    if ($pdo->query("SHOW TABLES LIKE 'product_widgets'")->fetch()) {
        $stmt = $pdo->query('SELECT instance_id, product_ids, featured_ids FROM product_widgets WHERE status=1');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $allIds = json_decode($row['product_ids'] ?: '[]', true);
            if (!is_array($allIds)) $allIds = [];
            $allIds = array_values(array_filter(array_map('intval', $allIds), function ($id) use ($productId) { return $id !== $productId; }));
            $featIds = json_decode($row['featured_ids'] ?: '[]', true);
            if (!is_array($featIds)) $featIds = [];
            $featIds = array_values(array_filter(array_map('intval', $featIds), function ($id) use ($productId) { return $id !== $productId; }));
            $pdo->prepare('UPDATE product_widgets SET product_ids=?, featured_ids=? WHERE instance_id=?')->execute([json_encode($allIds), json_encode($featIds), $row['instance_id']]);
        }
    }
    if ($pdo->query("SHOW TABLES LIKE 'product_scroll_widgets'")->fetch()) {
        $stmt = $pdo->query('SELECT instance_id, product_ids FROM product_scroll_widgets WHERE status=1');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ids = json_decode($row['product_ids'] ?: '[]', true);
            if (!is_array($ids)) $ids = [];
            $ids = array_values(array_filter(array_map('intval', $ids), function ($id) use ($productId) { return $id !== $productId; }));
            $pdo->prepare('UPDATE product_scroll_widgets SET product_ids=? WHERE instance_id=?')->execute([json_encode($ids), $row['instance_id']]);
        }
    }
}

require_once __DIR__ . '/marketing_product_sync.php';

function ensure_product_widget_row(PDO $pdo, string $instanceId): void {
    if ($instanceId === '' || !$pdo->query("SHOW TABLES LIKE 'product_widgets'")->fetch()) return;
    $chk = $pdo->prepare('SELECT instance_id FROM product_widgets WHERE instance_id=? LIMIT 1');
    $chk->execute([$instanceId]);
    if ($chk->fetch()) {
        backfill_widget_product_ids($pdo, 'product_widgets', $instanceId, 6);
        return;
    }
    $pick = default_widget_demo_product_ids($pdo, 6);
    $idsJson = json_encode($pick);
    $pdo->prepare('INSERT INTO product_widgets (instance_id,page_key,label,layout,columns,row_count,show_limit,product_ids,featured_ids) VALUES (?,?,?,?,?,?,?,?,?)')->execute([
        $instanceId, 'home', '商品列表', 'grid', 2, 3, 6, $idsJson, $idsJson,
    ]);
}

function ensure_product_scroll_widget_row(PDO $pdo, string $instanceId): void {
    if ($instanceId === '' || !$pdo->query("SHOW TABLES LIKE 'product_scroll_widgets'")->fetch()) return;
    $chk = $pdo->prepare('SELECT instance_id FROM product_scroll_widgets WHERE instance_id=? LIMIT 1');
    $chk->execute([$instanceId]);
    if ($chk->fetch()) {
        backfill_widget_product_ids($pdo, 'product_scroll_widgets', $instanceId, 6);
        return;
    }
    $demoIds = default_widget_demo_product_ids($pdo, 6);
    $pdo->prepare('INSERT INTO product_scroll_widgets (instance_id,page_key,label,title,item_count,product_ids) VALUES (?,?,?,?,?,?)')->execute([
        $instanceId, 'home', '横滑商品', '限时秒杀', 6, json_encode($demoIds),
    ]);
}

function ensure_promo_banner_widget_row(PDO $pdo, string $instanceId): void {
    if ($instanceId === '' || !$pdo->query("SHOW TABLES LIKE 'promo_banner_widgets'")->fetch()) return;
    $chk = $pdo->prepare('SELECT instance_id FROM promo_banner_widgets WHERE instance_id=? LIMIT 1');
    $chk->execute([$instanceId]);
    if ($chk->fetch()) return;
    $pdo->prepare('INSERT INTO promo_banner_widgets (instance_id,page_key,label,title,subtitle,banner_image,banner_bg_color,columns,row_count,product_ids) VALUES (?,?,?,?,?,?,?,?,?,?)')->execute([
        $instanceId, 'home', '主题区块', '水果季', '新鲜水果 产地直采', '', '#e8f5e9', 2, 2, '[]',
    ]);
}

function sync_product_widgets_featured_ids(PDO $pdo, array $demoIds): void {
    if (!$pdo->query("SHOW TABLES LIKE 'product_widgets'")->fetch()) return;
    $featRows = $pdo->query('SELECT id FROM products WHERE status=1 AND is_featured=1 ORDER BY sort_order DESC,id ASC')->fetchAll(PDO::FETCH_COLUMN);
    $defaultIds = array_values(array_map('intval', $featRows ?: []));
    if (!$defaultIds && $demoIds) {
        $place = implode(',', array_fill(0, count($demoIds), '?'));
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id IN ($place) AND is_featured=1 AND status=1 ORDER BY sort_order DESC,id ASC");
        $stmt->execute($demoIds);
        $defaultIds = array_values(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []));
    }
    if (!$defaultIds) return;
    $defaultJson = json_encode($defaultIds);
    $stmt = $pdo->query('SELECT instance_id, featured_ids FROM product_widgets WHERE status=1');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $featured = json_decode($row['featured_ids'] ?: '[]', true);
        if (!is_array($featured)) $featured = [];
        $needSync = count($featured) === 0;
        if (!$needSync) {
            foreach ($featured as $v) {
                if (!is_numeric($v)) { $needSync = true; break; }
            }
        }
        if ($needSync) {
            $upd = $pdo->prepare('UPDATE product_widgets SET featured_ids=? WHERE instance_id=?');
            $upd->execute([$defaultJson, $row['instance_id']]);
        }
    }
}

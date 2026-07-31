<?php
/**
 * 筑码引擎 www.402.cn
 */

/** 文章表结构自动补齐（覆盖部署后旧库缺列时执行） */
function ensure_article_schema(PDO $pdo): void {
    if (!$pdo->query("SHOW TABLES LIKE 'articles'")->fetch()) return;
    foreach ([
        'summary VARCHAR(500) NOT NULL DEFAULT \'\' AFTER cover',
        'is_demo TINYINT NOT NULL DEFAULT 0',
        'is_featured TINYINT NOT NULL DEFAULT 0',
    ] as $col) {
        $name = preg_replace('/ .*/', '', $col);
        if (!$pdo->query("SHOW COLUMNS FROM articles LIKE '$name'")->fetch()) {
            $pdo->exec("ALTER TABLE articles ADD COLUMN $col");
        }
    }
}

function demo_suppressed_article_titles(PDO $pdo): array {
    if (!$pdo->query("SHOW TABLES LIKE 'site_settings'")->fetch()) return [];
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key='demo_suppressed_article_titles' LIMIT 1");
    $stmt->execute();
    $raw = $stmt->fetchColumn();
    if (!$raw) return [];
    $data = json_decode((string)$raw, true);
    return is_array($data) ? array_values(array_filter(array_map('strval', $data))) : [];
}

function demo_suppress_article_title(PDO $pdo, string $title): void {
    $title = trim($title);
    if ($title === '' || !$pdo->query("SHOW TABLES LIKE 'site_settings'")->fetch()) return;
    $titles = demo_suppressed_article_titles($pdo);
    if (!in_array($title, $titles, true)) $titles[] = $title;
    $pdo->prepare("INSERT INTO site_settings (setting_key,setting_value) VALUES ('demo_suppressed_article_titles',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([json_encode($titles)]);
}

function demo_clear_article_suppression(PDO $pdo): void {
    if (!$pdo->query("SHOW TABLES LIKE 'site_settings'")->fetch()) return;
    $pdo->exec("DELETE FROM site_settings WHERE setting_key='demo_suppressed_article_titles'");
}

function article_hard_delete(PDO $pdo, int $id): void {
    if ($id <= 0) return;
    $chk = $pdo->prepare('SELECT title,is_demo FROM articles WHERE id=? LIMIT 1');
    $chk->execute([$id]);
    $row = $chk->fetch(PDO::FETCH_ASSOC);
    if ($row && (int)($row['is_demo'] ?? 0) === 1) demo_suppress_article_title($pdo, (string)($row['title'] ?? ''));
    article_remove_from_widget_lists($pdo, $id);
    $pdo->prepare('DELETE FROM articles WHERE id=?')->execute([$id]);
}

/** 画布导入的种子数据在 PHP 侧视为普通文章，清除 is_demo 并停止画布同步覆盖 */
function catalog_finalize_editable_articles(PDO $pdo): void {
    if (!$pdo->query("SHOW TABLES LIKE 'articles'")->fetch()) return;
    $pdo->exec('UPDATE articles SET is_demo=0 WHERE is_demo=1');
}

function catalog_seed_article_ids(PDO $pdo): array {
    catalog_finalize_editable_articles($pdo);
    $ids = [];
    foreach (demo_article_catalog() as $d) {
        $title = (string)$d[0];
        $chk = $pdo->prepare('SELECT id FROM articles WHERE title=? LIMIT 1');
        $chk->execute([$title]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);
        if ($row) $ids[] = (int)$row['id'];
    }
    if ($ids) return $ids;
    $rows = $pdo->query('SELECT id FROM articles WHERE status=1 ORDER BY sort_order DESC,id ASC')->fetchAll(PDO::FETCH_COLUMN);
    return array_values(array_map('intval', $rows ?: []));
}

/** 与画布 articleCover 一致的演示文章目录 */
function demo_article_catalog(): array {
    return [
        ['欢迎来到美妆精选', '部署后可在后台编辑', './assets/images/beauty_7.jpg', '<p><strong>本文为演示数据</strong>，安装后可在 PHP 后台「文章管理」中修改或删除。正式运营请替换为真实内容。</p><!--ADMIN_GUIDE_FOOTER-->', 128, 1],
        ['美妆精选新品发布', '演示文章仅供参考', './assets/images/beauty_8.jpg', '<p>演示正文。部署后可在 PHP 后台「文章管理」中修改或删除。</p><!--ADMIN_GUIDE_FOOTER-->', 86, 1],
        ['会员权益说明', '演示数据', './assets/images/beauty_9.jpg', '<p>演示正文。部署后可在 PHP 后台「文章管理」中修改或删除。</p><!--ADMIN_GUIDE_FOOTER-->', 203, 1],
        ['服务与配送说明', '演示数据', './assets/images/beauty_48.jpg', '<p>演示正文。部署后可在 PHP 后台「文章管理」中修改或删除。</p><!--ADMIN_GUIDE_FOOTER-->', 57, 1],
        ['常见问题解答', '演示数据', './assets/images/beauty_49.jpg', '<p>演示正文。部署后可在 PHP 后台「文章管理」中修改或删除。</p><!--ADMIN_GUIDE_FOOTER-->', 91, 1],
    ];
}

/** 仅安装时 allowSeed=true 才写入种子；运行时只返回目录 ID，不再同步画布封面 */
function ensure_demo_articles(PDO $pdo, bool $allowSeed = false): array {
    ensure_article_schema($pdo);
    if (!$pdo->query("SHOW TABLES LIKE 'articles'")->fetch()) return [];
    if (!$allowSeed) {
        return catalog_seed_article_ids($pdo);
    }
    $tableEmpty = !$pdo->query('SELECT id FROM articles LIMIT 1')->fetch();
    if ($tableEmpty) demo_clear_article_suppression($pdo);
    $suppressed = demo_suppressed_article_titles($pdo);
    $demos = demo_article_catalog();
    $ins = $pdo->prepare('INSERT INTO articles (title,summary,cover,content,view_count,is_demo,is_featured,sort_order,status) VALUES (?,?,?,?,?,0,?,?,1)');
    $fix = $pdo->prepare('UPDATE articles SET status=1,is_demo=0,summary=?,cover=?,sort_order=? WHERE title=?');
    $ids = [];
    $order = 100;
    foreach ($demos as $d) {
        if (in_array($d[0], $suppressed, true)) continue;
        $chk = $pdo->prepare('SELECT id FROM articles WHERE title=? LIMIT 1');
        $chk->execute([$d[0]]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);
        $sort = $order--;
        if ($row) {
            $id = (int)$row['id'];
            $ids[] = $id;
            if ($tableEmpty) {
                $fix->execute([$d[1], $d[2], $sort, $d[0]]);
            }
        } elseif ($tableEmpty) {
            $ins->execute([$d[0], $d[1], $d[2], $d[3], $d[4], $d[5], $sort]);
            $ids[] = (int)$pdo->lastInsertId();
        }
    }
    if (!$pdo->query("SELECT id FROM article_categories WHERE status=1 LIMIT 1")->fetch()) {
        $pdo->exec("INSERT IGNORE INTO article_categories (id,name,sort_order,status) VALUES (1,'默认分类',0,1)");
    }
    return $ids;
}

/** 推荐文章加入各文章组件的展示列表 */
function article_add_to_widget_lists(PDO $pdo, int $articleId): void {
    if ($articleId <= 0 || !$pdo->query("SHOW TABLES LIKE 'article_widgets'")->fetch()) return;
    $stmt = $pdo->query('SELECT instance_id, featured_ids FROM article_widgets WHERE status=1');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $ids = json_decode($row['featured_ids'] ?: '[]', true);
        if (!is_array($ids)) $ids = [];
        $ids = array_values(array_map('intval', $ids));
        if (!in_array($articleId, $ids, true)) {
            $ids[] = $articleId;
            $pdo->prepare('UPDATE article_widgets SET featured_ids=? WHERE instance_id=?')->execute([json_encode($ids), $row['instance_id']]);
        }
    }
}

/** 文章取消推荐或删除时，从各文章组件展示列表移除 */
function article_remove_from_widget_lists(PDO $pdo, int $articleId): void {
    if ($articleId <= 0 || !$pdo->query("SHOW TABLES LIKE 'article_widgets'")->fetch()) return;
    $stmt = $pdo->query('SELECT instance_id, article_ids, featured_ids FROM article_widgets WHERE status=1');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $allIds = json_decode($row['article_ids'] ?: '[]', true);
        if (!is_array($allIds)) $allIds = [];
        $allIds = array_values(array_filter(array_map('intval', $allIds), function ($id) use ($articleId) { return $id !== $articleId; }));
        $featIds = json_decode($row['featured_ids'] ?: '[]', true);
        if (!is_array($featIds)) $featIds = [];
        $featIds = array_values(array_filter(array_map('intval', $featIds), function ($id) use ($articleId) { return $id !== $articleId; }));
        $pdo->prepare('UPDATE article_widgets SET article_ids=?, featured_ids=? WHERE instance_id=?')->execute([json_encode($allIds), json_encode($featIds), $row['instance_id']]);
    }
}

/** 将文章组件 featured_ids 中的 demo_ 占位或空值同步为已推荐文章 ID */
function ensure_article_widget_row(PDO $pdo, string $instanceId): void {
    if ($instanceId === '' || !$pdo->query("SHOW TABLES LIKE 'article_widgets'")->fetch()) return;
    $chk = $pdo->prepare('SELECT instance_id FROM article_widgets WHERE instance_id=? LIMIT 1');
    $chk->execute([$instanceId]);
    if ($chk->fetch()) return;
    $demoIds = ensure_demo_articles($pdo);
    $idsJson = json_encode(array_map('intval', $demoIds));
    $pdo->prepare('INSERT INTO article_widgets (instance_id,page_key,label,layout,show_limit,show_more,article_ids,featured_ids) VALUES (?,?,?,?,?,?,?,?)')->execute([
        $instanceId, 'home', '文章列表', 'image-text', 5, 1, $idsJson, $idsJson,
    ]);
}

function sync_article_widgets_featured_ids(PDO $pdo, array $demoIds): void {
    if (!$pdo->query("SHOW TABLES LIKE 'article_widgets'")->fetch()) return;
    $featRows = $pdo->query('SELECT id FROM articles WHERE status=1 AND is_featured=1 ORDER BY sort_order DESC,id ASC')->fetchAll(PDO::FETCH_COLUMN);
    $defaultIds = array_values(array_map('intval', $featRows ?: []));
    if (!$defaultIds && $demoIds) {
        $place = implode(',', array_fill(0, count($demoIds), '?'));
        $stmt = $pdo->prepare("SELECT id FROM articles WHERE id IN ($place) AND is_featured=1 AND status=1 ORDER BY sort_order DESC,id ASC");
        $stmt->execute($demoIds);
        $defaultIds = array_values(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []));
    }
    if (!$defaultIds) return;
    $defaultJson = json_encode($defaultIds);
    $stmt = $pdo->query('SELECT instance_id, featured_ids FROM article_widgets WHERE status=1');
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
            $upd = $pdo->prepare('UPDATE article_widgets SET featured_ids=? WHERE instance_id=?');
            $upd->execute([$defaultJson, $row['instance_id']]);
        }
    }
}

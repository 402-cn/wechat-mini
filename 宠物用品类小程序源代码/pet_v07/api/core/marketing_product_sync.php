<?php
/**
 * 筑码引擎 www.402.cn
 */

/** 营销入口/组件商品同步（widget.php 与 product_sync 共用） */

function widget_product_ids_empty($raw): bool {
    if ($raw === null || $raw === '') return true;
    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || count($decoded) === 0) return true;
    foreach ($decoded as $id) {
        if (is_numeric($id) && (int)$id > 0) return false;
    }
    return true;
}

function default_widget_demo_product_ids(PDO $pdo, int $limit = 6): array {
    $demoIds = ensure_demo_products($pdo);
    $pick = array_slice(array_values(array_map('intval', $demoIds)), 0, $limit);
    if (count($pick) >= $limit) return $pick;
    $rows = $pdo->query('SELECT id FROM products WHERE status=1 ORDER BY sort_order DESC,id ASC LIMIT ' . (int)$limit)->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $pick = array_values(array_unique(array_merge($pick, array_map('intval', $rows))));
    return array_slice($pick, 0, $limit);
}

function backfill_widget_product_ids(PDO $pdo, string $table, string $instanceId, int $limit = 6): array {
    if ($instanceId === '' || !in_array($table, ['product_widgets', 'product_scroll_widgets'], true)) return [];
    if (!$pdo->query("SHOW TABLES LIKE '$table'")->fetch()) return [];
    $stmt = $pdo->prepare("SELECT product_ids FROM $table WHERE instance_id=? LIMIT 1");
    $stmt->execute([$instanceId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return [];
    if (!widget_product_ids_empty($row['product_ids'] ?? null)) {
        $decoded = json_decode($row['product_ids'], true);
        return array_values(array_filter(array_map('intval', is_array($decoded) ? $decoded : [])));
    }
    $pick = default_widget_demo_product_ids($pdo, $limit);
    if (!$pick) return [];
    $json = json_encode($pick);
    if ($table === 'product_widgets') {
        $pdo->prepare('UPDATE product_widgets SET product_ids=?,featured_ids=? WHERE instance_id=?')->execute([$json, $json, $instanceId]);
    } else {
        $pdo->prepare('UPDATE product_scroll_widgets SET product_ids=? WHERE instance_id=?')->execute([$json, $instanceId]);
    }
    return $pick;
}

function ensure_marketing_entry_product_row(PDO $pdo, string $instanceId, string $pageKey, string $label): void {
    if ($instanceId === '' || !$pdo->query("SHOW TABLES LIKE 'product_widgets'")->fetch()) return;
    $chk = $pdo->prepare('SELECT instance_id FROM product_widgets WHERE instance_id=? LIMIT 1');
    $chk->execute([$instanceId]);
    if ($chk->fetch()) {
        backfill_widget_product_ids($pdo, 'product_widgets', $instanceId, 6);
        return;
    }
    $demoIds = default_widget_demo_product_ids($pdo, 6);
    $idsJson = json_encode($demoIds);
    $pdo->prepare('INSERT INTO product_widgets (instance_id,page_key,label,layout,columns,row_count,show_limit,product_ids,featured_ids) VALUES (?,?,?,?,?,?,?,?,?)')->execute([
        $instanceId, $pageKey, $label, 'row', 1, 6, 6, $idsJson, $idsJson,
    ]);
}

function bootstrap_marketing_entry_products(PDO $pdo, string $entryInstanceId, string $entryType, array &$props): void {
    if ($entryInstanceId === '' || !in_array($entryType, ['groupBuy', 'flashSale'], true)) return;
    $pageKey = $entryType === 'groupBuy' ? 'group-buy' : 'flash-sale';
    $label = $entryType === 'groupBuy' ? '拼团商品' : '秒杀商品';
    ensure_marketing_entry_product_row($pdo, $entryInstanceId, $pageKey, $label);
    $ids = $props['productIds'] ?? [];
    if (!is_array($ids)) $ids = [];
    $ids = array_values(array_filter(array_map('intval', $ids)));
    if (!$ids) {
        $ids = backfill_widget_product_ids($pdo, 'product_widgets', $entryInstanceId, 6);
        if ($ids) {
            $props['productIds'] = $ids;
            $pdo->prepare('UPDATE widget_instances SET props_json=? WHERE instance_id=?')->execute([
                json_encode($props, JSON_UNESCAPED_UNICODE), $entryInstanceId,
            ]);
        }
    }
    if ($ids) sync_marketing_entry_products($pdo, $entryInstanceId, $entryType, $ids);
}

function sync_marketing_entry_products(PDO $pdo, string $entryInstanceId, string $entryType, array $ids): void {
    if ($entryInstanceId === '' || !in_array($entryType, ['groupBuy', 'flashSale'], true)) return;
    $pageKey = $entryType === 'groupBuy' ? 'group-buy' : 'flash-sale';
    $label = $entryType === 'groupBuy' ? '拼团商品' : '秒杀商品';
    ensure_marketing_entry_product_row($pdo, $entryInstanceId, $pageKey, $label);
    $clean = array_values(array_filter(array_map('intval', $ids)));
    $pdo->prepare('UPDATE product_widgets SET product_ids=?,featured_ids=?,layout=?,columns=?,row_count=?,show_limit=? WHERE instance_id=?')->execute([
        json_encode($clean), json_encode($clean), 'row', 1, 6, 6, $entryInstanceId,
    ]);
}

<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';

if (is_file(__DIR__ . '/../core/user_sync.php')) require_once __DIR__ . '/../core/user_sync.php';
function interaction_user_id(): int {
    return function_exists('user_current_id') ? user_current_id() : 0;
}
function interaction_visitor_key(): string {
    $body = json_decode(file_get_contents('php://input'), true);
    if (is_array($body) && !empty($body['visitor_key'])) return trim((string)$body['visitor_key']);
    $vk = trim((string)($_GET['visitor_key'] ?? ''));
    if ($vk !== '') return $vk;
    return 'v_' . substr(md5(($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 16);
}

$type = trim((string)($_GET['target_type'] ?? 'product'));
if (!in_array($type, ['product', 'article'], true)) json_error('参数错误');
$uid = interaction_user_id();
$vk = interaction_visitor_key();
$pdo = db();
$stmt = $pdo->prepare('SELECT target_id, created_at FROM user_favorites WHERE user_id=? AND visitor_key=? AND target_type=? ORDER BY id DESC LIMIT 200');
$stmt->execute([$uid, $vk, $type]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$list = [];
foreach ($rows as $r) {
    $tid = (int)$r['target_id'];
    if ($type === 'product' && $pdo->query("SHOW TABLES LIKE 'products'")->fetch()) {
        $s = $pdo->prepare('SELECT id,name,price,image FROM products WHERE id=? AND status=1 LIMIT 1');
        $s->execute([$tid]);
        $p = $s->fetch(PDO::FETCH_ASSOC);
        if ($p) $list[] = $p;
    } elseif ($type === 'article' && $pdo->query("SHOW TABLES LIKE 'articles'")->fetch()) {
        $s = $pdo->prepare('SELECT id,title,cover,summary FROM articles WHERE id=? AND status=1 LIMIT 1');
        $s->execute([$tid]);
        $a = $s->fetch(PDO::FETCH_ASSOC);
        if ($a) $list[] = $a;
    }
}
json_ok(['list' => $list, 'target_type' => $type]);

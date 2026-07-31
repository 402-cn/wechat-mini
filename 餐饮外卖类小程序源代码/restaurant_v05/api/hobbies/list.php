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

$type = trim((string)($_GET['target_type'] ?? ''));
$uid = interaction_user_id();
$vk = interaction_visitor_key();
$pdo = db();
$where = 'WHERE user_id=? AND visitor_key=? AND score>=5';
$params = [$uid, $vk];
if ($type !== '' && in_array($type, ['product', 'article'], true)) {
    $where .= ' AND target_type=?';
    $params[] = $type;
}
$stmt = $pdo->prepare('SELECT target_type, target_id, score, created_at FROM user_hobbies ' . $where . ' ORDER BY id DESC LIMIT 200');
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$list = [];
foreach ($rows as $r) {
    $tt = (string)$r['target_type'];
    $tid = (int)$r['target_id'];
    if ($tt === 'product' && $pdo->query("SHOW TABLES LIKE 'products'")->fetch()) {
        $s = $pdo->prepare('SELECT id,name,price,image FROM products WHERE id=? AND status=1 LIMIT 1');
        $s->execute([$tid]);
        $p = $s->fetch(PDO::FETCH_ASSOC);
        if ($p) { $p['target_type'] = 'product'; $p['score'] = (int)$r['score']; $list[] = $p; }
    } elseif ($tt === 'article' && $pdo->query("SHOW TABLES LIKE 'articles'")->fetch()) {
        $s = $pdo->prepare('SELECT id,title,cover,summary FROM articles WHERE id=? AND status=1 LIMIT 1');
        $s->execute([$tid]);
        $a = $s->fetch(PDO::FETCH_ASSOC);
        if ($a) { $a['target_type'] = 'article'; $a['score'] = (int)$r['score']; $list[] = $a; }
    }
}
json_ok(['list' => $list]);

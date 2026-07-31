<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
$id = trim($_GET['id'] ?? '');
if ($id === '') { header('Location: dashboard.php'); exit; }
$pdo = db();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('UPDATE notice_instances SET content=?, text_color=?, bg_color=?, font_size=?, scroll_direction=?, scroll_speed=? WHERE instance_id=?');
    $stmt->execute([
        trim($_POST['content'] ?? ''),
        trim($_POST['text_color'] ?? '#333333'),
        trim($_POST['bg_color'] ?? '#ffffff'),
        (int)($_POST['font_size'] ?? 28),
        trim($_POST['scroll_direction'] ?? 'left'),
        (int)($_POST['scroll_speed'] ?? 50),
        $id,
    ]);
    $msg = '保存成功';
}
$stmt = $pdo->prepare('SELECT * FROM notice_instances WHERE instance_id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo '公告不存在'; exit; }
admin_layout_start($row['label'], 'notice.php?id=' . $id, $id, '', admin_guide_flow_key((string)($row['page_key'] ?? ''), $id));
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="post" class="form-grid">';
admin_field_textarea('公告内容', 'content', $row['content'], 4);
admin_field_color('文字颜色', 'text_color', $row['text_color'], 'notice_text_color');
admin_field_color('背景颜色', 'bg_color', $row['bg_color'], 'notice_bg_color');
admin_field_text('字号(rpx)', 'font_size', (string)(int)$row['font_size'], 'number');
admin_field_select('滚动方向', 'scroll_direction', ['left' => '向左', 'right' => '向右'], $row['scroll_direction']);
admin_field_text('滚动速度', 'scroll_speed', (string)(int)$row['scroll_speed'], 'number');
echo '<p style="margin-top:16px"><button class="btn" type="submit">保存</button></p></form></div>';
admin_layout_end();

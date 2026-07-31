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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_props'])) {
    $props = json_decode($pdo->query("SELECT props_json FROM widget_instances WHERE instance_id=" . $pdo->quote($id))->fetchColumn() ?: '{}', true) ?: [];
    $props['title'] = trim($_POST['title'] ?? '');
    $props['subtitle'] = trim($_POST['subtitle'] ?? '');
    $props['buttonText'] = trim($_POST['buttonText'] ?? '立即打卡');
    $props['successText'] = trim($_POST['successText'] ?? '打卡成功');
    $props['requireLogin'] = !empty($_POST['requireLogin']);
    $props['rewardPoints'] = (int)($_POST['rewardPoints'] ?? 0);
    $props['rewardCoupon'] = !empty($_POST['rewardCoupon']);
    $pdo->prepare('UPDATE widget_instances SET props_json=? WHERE instance_id=?')->execute([
        json_encode($props, JSON_UNESCAPED_UNICODE), $id,
    ]);
    $msg = '配置已保存';
}
$stmt = $pdo->prepare('SELECT * FROM widget_instances WHERE instance_id = ? AND component_type = ? LIMIT 1');
$stmt->execute([$id, 'checkinActivity']);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo '打卡组件不存在'; exit; }
$props = json_decode($row['props_json'] ?? '{}', true) ?: [];
$records = $pdo->prepare('SELECT * FROM checkin_records WHERE instance_id = ? ORDER BY id DESC LIMIT 100');
$records->execute([$id]);
$recRows = $records->fetchAll(PDO::FETCH_ASSOC);
admin_layout_start($row['label'] . ' · 打卡管理', 'checkin_admin.php?id=' . $id, $id, '悬停「?」查看前台打卡活动位置。可配置活动规则与打卡记录。');
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="post" class="form-grid"><input type="hidden" name="save_props" value="1">';
admin_field_text('标题', 'title', (string)($props['title'] ?? '每日打卡'));
admin_field_text('副标题', 'subtitle', (string)($props['subtitle'] ?? ''));
admin_field_text('按钮文字', 'buttonText', (string)($props['buttonText'] ?? '立即打卡'));
admin_field_text('成功提示', 'successText', (string)($props['successText'] ?? '打卡成功'));
admin_field_text('奖励积分(0=不发)', 'rewardPoints', (string)($props['rewardPoints'] ?? 0), 'number');
echo '<label><input type="checkbox" name="requireLogin" value="1"' . (!empty($props['requireLogin']) ? ' checked' : '') . '> 需登录</label>';
echo '<label><input type="checkbox" name="rewardCoupon" value="1"' . (!empty($props['rewardCoupon']) ? ' checked' : '') . '> 发放优惠券(需用户系统)</label>';
echo '<p style="color:#666;font-size:13px">无用户系统时打卡仅记录并展示文案，不发积分/券。</p>';
echo '<p><button class="btn" type="submit">保存配置</button></p></form></div>';
echo '<div class="card"><h3>打卡记录</h3>';
if (!$recRows) echo '<p style="color:#999">暂无记录</p>';
else {
    echo '<table class="data-table"><tr><th>用户ID</th><th>日期</th><th>连续天数</th><th>时间</th></tr>';
    foreach ($recRows as $r) {
        echo '<tr><td>' . (int)$r['user_id'] . '</td><td>' . htmlspecialchars(admin_format_date((string)$r['checkin_date'])) . '</td><td>' . (int)$r['streak'] . '</td><td>' . htmlspecialchars(admin_format_datetime((string)$r['created_at'])) . '</td></tr>';
    }
    echo '</table>';
}
echo '</div>';
admin_layout_end();

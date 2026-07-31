<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
require_once dirname(__DIR__) . '/api/core/user_sync.php';
$pdo = db();
ensure_user_schema($pdo);
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $pdo->prepare('INSERT INTO coupons (name,type,value,min_amount,total_count,claim_type,claim_min_spend,start_at,end_at,status) VALUES (?,?,?,?,?,?,?,?,?,1)')->execute([
        trim($_POST['name']??''), 'amount', (float)($_POST['value']??0), (float)($_POST['min_amount']??0), (int)($_POST['total_count']??100),
        preg_replace('/[^a-z_]/', '', (string)($_POST['claim_type']??'all')),
        (float)($_POST['claim_min_spend']??0),
        trim($_POST['start_at']??'') ?: null,
        trim($_POST['end_at']??'') ?: null,
    ]);
    admin_audit('新增', '优惠券管理', '新增了优惠券「' . trim($_POST['name']??'') . '」');
    $msg = '已添加优惠券模板';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_coupon'])) {
    $id = (int)($_POST['coupon_id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare('UPDATE coupons SET name=?,value=?,min_amount=?,total_count=?,claim_type=?,claim_min_spend=?,start_at=?,end_at=?,status=? WHERE id=?')->execute([
            trim($_POST['name']??''), (float)($_POST['value']??0), (float)($_POST['min_amount']??0), (int)($_POST['total_count']??100),
            preg_replace('/[^a-z_]/', '', (string)($_POST['claim_type']??'all')),
            (float)($_POST['claim_min_spend']??0),
            trim($_POST['start_at']??'') ?: null,
            trim($_POST['end_at']??'') ?: null,
            (int)($_POST['status']??1), $id,
        ]);
        admin_audit('修改', '优惠券管理', '修改了优惠券「' . trim($_POST['name']??'') . '」(ID:' . $id . ')');
        $msg = '已保存';
    }
}
$rows = $pdo->query('SELECT * FROM coupons ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$editId = (int)($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    foreach ($rows as $r) { if ((int)$r['id'] === $editId) { $editRow = $r; break; } }
}
admin_layout_start('优惠券管理', 'coupons.php');
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="post" class="form-grid"><h3>' . ($editRow ? '编辑优惠券 #' . $editId : '新增优惠券模板') . '</h3>';
echo '<input type="hidden" name="' . ($editRow ? 'edit_coupon' : 'add_coupon') . '" value="1">';
if ($editRow) echo '<input type="hidden" name="coupon_id" value="' . $editId . '">';
admin_field_text('名称', 'name', $editRow['name'] ?? '');
admin_field_text('面额', 'value', $editRow ? (string)$editRow['value'] : '10', 'number', 'step="0.01"');
admin_field_text('最低消费', 'min_amount', $editRow ? (string)$editRow['min_amount'] : '50', 'number', 'step="0.01"');
admin_field_text('发放总量', 'total_count', $editRow ? (string)(int)$editRow['total_count'] : '100', 'number');
echo '<div class="form-row"><label>领取条件</label><div class="field"><select name="claim_type">';
$claimType = (string)($editRow['claim_type'] ?? 'all');
foreach (['all'=>'所有用户','new_user'=>'新注册用户','spend'=>'消费达标'] as $ck=>$cl) {
    echo '<option value="' . $ck . '"' . ($claimType===$ck?' selected':'') . '>' . $cl . '</option>';
}
echo '</select></div></div>';
admin_field_text('消费门槛(领取)', 'claim_min_spend', $editRow ? (string)$editRow['claim_min_spend'] : '0', 'number', 'step="0.01"');
admin_field_text('领取开始', 'start_at', $editRow['start_at'] ?? '', 'text', 'placeholder="2026-01-01 00:00:00"');
admin_field_text('领取截止', 'end_at', $editRow['end_at'] ?? '', 'text', 'placeholder="2026-12-31 23:59:59"');
if ($editRow) {
    echo '<div class="form-row"><label>状态</label><div class="field"><select name="status"><option value="1"' . ((int)$editRow['status']===1?' selected':'') . '>启用</option><option value="0"' . ((int)$editRow['status']===0?' selected':'') . '>停用</option></select></div></div>';
}
echo '<p><button class="btn" type="submit">保存</button> <a class="btn btn-secondary" href="user_coupons.php">查看用户领券记录 →</a>';
if ($editRow) echo ' <a class="btn btn-secondary" href="coupons.php">取消编辑</a>';
echo '</p></form></div>';
echo '<div class="card"><table><tr><th>ID</th><th>名称</th><th>面额</th><th>门槛</th><th>领取条件</th><th>截止</th><th>已领/总量</th><th>状态</th><th>操作</th></tr>';
$claimLabels = ['all'=>'所有用户','new_user'=>'新注册用户','spend'=>'消费达标'];
foreach ($rows as $r) {
    $cl = $claimLabels[$r['claim_type'] ?? 'all'] ?? ($r['claim_type'] ?? 'all');
    if (($r['claim_type'] ?? '') === 'spend') $cl .= ' ¥' . $r['claim_min_spend'];
    echo '<tr><td>' . (int)$r['id'] . '</td><td>' . htmlspecialchars($r['name']) . '</td>';
    echo '<td>¥' . $r['value'] . '</td><td>¥' . $r['min_amount'] . '</td><td>' . htmlspecialchars($cl) . '</td>';
    echo '<td>' . htmlspecialchars($r['end_at'] ?? '') . '</td>';
    echo '<td>' . (int)$r['used_count'] . '/' . (int)$r['total_count'] . '</td><td>' . ((int)$r['status']===1?'启用':'停用') . '</td>';
    echo '<td><a class="btn btn-sm" href="?edit=' . (int)$r['id'] . '">编辑</a></td></tr>';
}
echo '</table></div>';
admin_layout_end();

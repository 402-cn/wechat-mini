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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust'])) {
    $uid = (int)($_POST['user_id'] ?? 0);
    $maxAsset = 2000000000;
    $newBal = min($maxAsset, max(0, (float)($_POST['balance'] ?? 0)));
    $newPts = min($maxAsset, max(0, (int)($_POST['points'] ?? 0)));
    $remark = trim($_POST['remark'] ?? '后台调整');
    if ($uid > 0) {
        $stmt = $pdo->prepare('SELECT balance, points, nickname, phone FROM users WHERE id=? LIMIT 1');
        $stmt->execute([$uid]);
        $urow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($urow) {
            $oldBal = (float)$urow['balance'];
            $oldPts = (int)$urow['points'];
            $balDelta = round($newBal - $oldBal, 2);
            $ptsDelta = $newPts - $oldPts;
            $changes = [];
            if ($balDelta != 0) {
                wallet_change($pdo, $uid, $balDelta, 'admin_adjust', 'admin', 0, $remark);
                $changes[] = '余额 ' . $oldBal . ' → ' . $newBal;
            }
            if ($ptsDelta != 0) {
                points_change($pdo, $uid, $ptsDelta, 'admin_adjust', 'admin', 0, $remark);
                $changes[] = '积分 ' . $oldPts . ' → ' . $newPts;
            }
            if ($changes) {
                $label = trim((string)($urow['nickname'] ?? ''));
                if ($label === '') $label = trim((string)($urow['phone'] ?? ''));
                if ($label === '') $label = '用户#' . $uid;
                $detail = '修改了用户「' . $label . '」(ID:' . $uid . ')：' . implode('；', $changes);
                if ($remark !== '' && $remark !== '后台调整') $detail .= '（备注：' . $remark . '）';
                admin_audit('修改', '用户管理', $detail);
            }
            $msg = $changes ? '已调整' : '无变动';
        }
    }
}
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->exec('UPDATE users SET status = IF(status=1,0,1) WHERE id=' . $id);
    header('Location: users.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $uid = (int)($_POST['user_id'] ?? 0);
    $pwd = trim((string)($_POST['new_password'] ?? ''));
    if ($uid > 0 && strlen($pwd) >= 6) {
        $chk = $pdo->prepare('SELECT login_type FROM users WHERE id=? LIMIT 1');
        $chk->execute([$uid]);
        $u = $chk->fetch(PDO::FETCH_ASSOC);
        if ($u && (int)($u['login_type'] ?? 0) !== 2) {
            $hash = password_hash($pwd, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hash, $uid]);
            $msg = '密码已重置';
        }
    }
}
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = max(10, min(100, (int)($_GET['ps'] ?? 20)));
$where = '1=1';
$params = [];
if ($q !== '') {
    $where .= ' AND (phone LIKE ? OR nickname LIKE ? OR openid LIKE ? OR CAST(id AS CHAR) LIKE ?)';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like, $like];
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$offset = ($page - 1) * $pageSize;
$listStmt = $pdo->prepare("SELECT id,phone,nickname,openid,balance,points,deposit,member_level,login_type,status,invite_code,created_at FROM users WHERE $where ORDER BY id DESC LIMIT $pageSize OFFSET $offset");
$listStmt->execute($params);
$rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);
admin_layout_start('用户管理', 'users.php');
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="get" class="form-grid" style="margin-bottom:12px"><div class="form-row"><label>搜索</label><div class="field"><input name="q" value="' . htmlspecialchars($q) . '" placeholder="手机号 / 昵称 / OpenID / ID"></div></div>';
echo '<p><button class="btn btn-sm" type="submit">查询</button> <a class="btn btn-sm btn-secondary" href="users.php">重置</a></p></form>';
echo '<table><tr><th>ID</th><th>手机</th><th>昵称</th><th>OpenID</th><th>余额</th><th>积分</th><th>邀请码</th><th>等级</th><th>状态</th><th>操作</th></tr>';
foreach ($rows as $r) {
    $lt = (int)$r['login_type'] === 2 ? '微信' : '手机';
    $isActive = (int)$r['status']===1;
    $toggleLabel = $isActive ? '禁用用户' : '解封用户';
    echo '<tr><td>' . (int)$r['id'] . '</td><td>' . htmlspecialchars($r['phone']??'') . '</td>';
    echo '<td>' . htmlspecialchars($r['nickname']??'') . '</td><td style="font-size:11px;max-width:160px;word-break:break-all">' . htmlspecialchars($r['openid']??'') . '</td>';
    echo '<td>¥' . $r['balance'] . '</td><td>' . (int)$r['points'] . '</td><td>' . htmlspecialchars($r['invite_code']??'') . '</td>';
    echo '<td>V' . (int)$r['member_level'] . ' · ' . $lt . '</td><td>' . ($isActive?'正常':'禁用') . '</td>';
    echo '<td><a class="btn btn-sm" href="?adjust_form=' . (int)$r['id'] . '">修改余额积分</a> ';
    echo '<a class="btn btn-sm btn-secondary" href="?toggle=' . (int)$r['id'] . '">' . htmlspecialchars($toggleLabel) . '</a>';
    if ((int)$r['login_type'] !== 2) {
        echo ' <a class="btn btn-sm" href="?reset_pwd=' . (int)$r['id'] . '">重置密码</a>';
    }
    echo '</td></tr>';
}
echo '</table>';
admin_pagination($total, $page, $pageSize, 'users.php?q=' . urlencode($q));
echo '</div>';
if (!empty($_GET['adjust_form'])) {
    $aid = (int)$_GET['adjust_form'];
    $adjBal = '0';
    $adjPts = '0';
    $adjStmt = $pdo->prepare('SELECT balance, points FROM users WHERE id=? LIMIT 1');
    $adjStmt->execute([$aid]);
    if ($adjRow = $adjStmt->fetch(PDO::FETCH_ASSOC)) {
        $adjBal = (string)$adjRow['balance'];
        $adjPts = (string)(int)$adjRow['points'];
    }
    echo '<div class="card"><form method="post" class="form-grid"><h3>修改余额积分 #' . $aid . '</h3>';
    echo '<input type="hidden" name="adjust" value="1"><input type="hidden" name="user_id" value="' . $aid . '">';
    admin_field_text('余额', 'balance', $adjBal, 'number', 'step="0.01" min="0" max="2000000000"');
    admin_field_text('积分', 'points', $adjPts, 'number', 'min="0" max="2000000000"');
    admin_field_text('备注', 'remark', '后台调整');
    echo '<p><button class="btn" type="submit">保存</button></p></form></div>';
}
if (!empty($_GET['reset_pwd'])) {
    $rid = (int)$_GET['reset_pwd'];
    echo '<div class="card"><form method="post" class="form-grid"><h3>重置用户密码 #' . $rid . '</h3>';
    echo '<input type="hidden" name="reset_password" value="1"><input type="hidden" name="user_id" value="' . $rid . '">';
    admin_field_text('新密码（至少6位）', 'new_password', '', 'password');
    echo '<p><button class="btn" type="submit">确认重置</button> <a class="btn btn-secondary" href="users.php">取消</a></p></form></div>';
}
admin_layout_end();

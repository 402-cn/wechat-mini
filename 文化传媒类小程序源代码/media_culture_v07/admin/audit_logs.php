<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
$pdo = db();
admin_rbac_ensure_tables($pdo);
$day = trim((string)($_GET['day'] ?? ''));
if ($day !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) $day = '';
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 50;
$where = 'WHERE 1=1';
$params = [];
if ($day !== '') {
    $where .= ' AND created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)';
    $params[] = $day . ' 00:00:00';
    $params[] = $day;
}
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM admin_audit_logs ' . $where);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$offset = ($page - 1) * $pageSize;
$sql = 'SELECT * FROM admin_audit_logs ' . $where . ' ORDER BY id DESC LIMIT ? OFFSET ?';
$stmt = $pdo->prepare($sql);
foreach ($params as $i => $v) $stmt->bindValue($i + 1, $v);
$stmt->bindValue(count($params) + 1, $pageSize, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$listQs = 'day=' . urlencode($day) . '&page=' . $page;
admin_layout_start('操作日志', 'audit_logs.php');
echo '<div class="card"><form method="get" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">';
echo '<div><label style="font-size:13px;color:#666">按日期筛选（YYYY-MM-DD）</label><br><input name="day" value="' . htmlspecialchars($day) . '" placeholder="2026-06-17" pattern="\d{4}-\d{2}-\d{2}"></div>';
echo '<button type="submit" class="btn btn-sm">查询</button>';
if ($day !== '') echo '<a class="btn btn-sm btn-secondary" href="audit_logs.php">清除筛选</a>';
echo '</form></div>';
echo '<div class="card"><table><thead><tr><th>操作人</th><th>时间</th><th>操作</th><th>模块</th><th>详情</th></tr></thead><tbody>';
foreach ($rows as $r) {
    echo '<tr><td>' . htmlspecialchars((string)$r['admin_username']) . '</td><td>' . htmlspecialchars((string)$r['created_at']) . '</td><td>' . htmlspecialchars((string)$r['action']) . '</td><td>' . htmlspecialchars((string)$r['module']) . '</td><td>' . htmlspecialchars((string)$r['detail']) . '</td></tr>';
}
if (!$rows) echo '<tr><td colspan="5" style="text-align:center;color:#999">暂无记录</td></tr>';
echo '</tbody></table>';
admin_pagination($total, $page, $pageSize, 'audit_logs.php?' . $listQs);
echo '</div>';
admin_layout_end();

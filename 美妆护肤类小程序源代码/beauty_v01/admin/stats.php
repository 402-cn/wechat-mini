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
$hasProducts = (bool)$pdo->query("SHOW TABLES LIKE 'products'")->fetch();
$hasOrders = (bool)$pdo->query("SHOW TABLES LIKE 'orders'")->fetch();
$totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$todayUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$yesterdayUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at)=DATE_SUB(CURDATE(), INTERVAL 1 DAY)")->fetchColumn();
$totalProducts = 0;
$todaySold = 0;
$yesterdaySold = 0;
if ($hasProducts) {
    $totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE status=1')->fetchColumn();
}
if ($hasOrders && $hasProducts) {
    $soldSql = 'SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi INNER JOIN orders o ON o.id=oi.order_id WHERE o.paid_at IS NOT NULL';
    $todaySold = (int)$pdo->query($soldSql . ' AND DATE(o.paid_at)=CURDATE()')->fetchColumn();
    $yesterdaySold = (int)$pdo->query($soldSql . ' AND DATE(o.paid_at)=DATE_SUB(CURDATE(), INTERVAL 1 DAY)')->fetchColumn();
}
admin_layout_start('数据统计', 'stats.php');
echo '<div class="card"><div class="stats-grid">';
echo '<div class="stat-card"><div class="stat-label">用户总数</div><div class="stat-value">' . $totalUsers . '</div></div>';
echo '<div class="stat-card"><div class="stat-label">今日新增用户</div><div class="stat-value">' . $todayUsers . '</div></div>';
echo '<div class="stat-card"><div class="stat-label">昨日新增用户</div><div class="stat-value">' . $yesterdayUsers . '</div></div>';
if ($hasProducts) {
    echo '<div class="stat-card"><div class="stat-label">上架商品总数</div><div class="stat-value">' . $totalProducts . '</div></div>';
}
if ($hasOrders && $hasProducts) {
    echo '<div class="stat-card"><div class="stat-label">今日售出（件）</div><div class="stat-value">' . $todaySold . '</div></div>';
    echo '<div class="stat-card"><div class="stat-label">昨日售出（件）</div><div class="stat-value">' . $yesterdaySold . '</div></div>';
}
echo '</div></div>';
admin_layout_end();

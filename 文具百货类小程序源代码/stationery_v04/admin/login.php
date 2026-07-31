<?php
/**
 * 筑码引擎 www.402.cn
 */

session_start();
require_once dirname(__DIR__) . '/api/core/install_guard.php';
app_require_installed();
require_once dirname(__DIR__) . '/api/core/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
if ($username === '' || $password === '') {
    header('Location: index.php?error=1');
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, password_hash, status FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || (int)$row['status'] !== 1 || !password_verify($password, $row['password_hash'])) {
        header('Location: index.php?error=1');
        exit;
    }
    $_SESSION['admin_id'] = (int)$row['id'];
    $_SESSION['admin_username'] = $username;
    require_once dirname(__DIR__) . '/admin/inc/rbac.php';
    admin_rbac_load_session((int)$row['id']);
    header('Location: navigation_map.php');
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo '登录失败：' . htmlspecialchars($e->getMessage());
}

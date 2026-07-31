<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once dirname(__DIR__) . '/api/core/install_guard.php';
app_require_installed();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <title>管理后台登录</title>
  <style>
    body { font-family: sans-serif; background:#f5f5f5; display:flex; justify-content:center; align-items:center; min-height:100vh; }
    .box { background:#fff; padding:32px; border-radius:8px; width:360px; box-shadow:0 2px 12px rgba(0,0,0,.08); }
    input { width:100%; padding:10px; margin:8px 0 16px; box-sizing:border-box; }
    button { width:100%; padding:10px; background:#2ecc71; color:#fff; border:none; border-radius:4px; cursor:pointer; }
  </style>
</head>
<body>
  <div class="box">
    <h2>管理后台登录</h2>
    <?php if (!empty($_GET['error'])): ?><p style="color:#e74c3c;font-size:14px">用户名或密码错误</p><?php endif; ?>
    <form method="post" action="login.php">
      <label>用户名</label>
      <input name="username" required>
      <label>密码</label>
      <input name="password" type="password" required>
      <button type="submit">登录</button>
    </form>
  </div>
</body>
</html>

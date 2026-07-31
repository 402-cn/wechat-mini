<?php
/**
 * 筑码引擎 www.402.cn
 */

/** 用户与商城表结构、会话、资产变动 */
function ensure_user_schema(PDO $pdo): void {
    if (!$pdo->query("SHOW TABLES LIKE 'users'")->fetch()) return;
    foreach ([
        'balance DECIMAL(10,2) NOT NULL DEFAULT 0.00',
        'points INT NOT NULL DEFAULT 0',
        'deposit DECIMAL(10,2) NOT NULL DEFAULT 0.00',
        'member_level TINYINT NOT NULL DEFAULT 0',
        'last_login_at DATETIME NULL DEFAULT NULL',
        'invite_code VARCHAR(16) NULL DEFAULT NULL',
        'invited_by BIGINT UNSIGNED NOT NULL DEFAULT 0',
    ] as $col) {
        $name = preg_replace('/ .*/', '', $col);
        if (!$pdo->query("SHOW COLUMNS FROM users LIKE '$name'")->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $col");
        }
    }
    $tables = [
        "CREATE TABLE IF NOT EXISTS member_levels (
          id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(50) NOT NULL DEFAULT '',
          min_points INT NOT NULL DEFAULT 0, discount DECIMAL(4,2) NOT NULL DEFAULT 1.00,
          benefits TEXT, sort_order INT NOT NULL DEFAULT 0, status TINYINT NOT NULL DEFAULT 1,
          PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS user_wallet_logs (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT UNSIGNED NOT NULL,
          amount DECIMAL(10,2) NOT NULL DEFAULT 0.00, balance_after DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          type VARCHAR(30) NOT NULL DEFAULT '', ref_type VARCHAR(30) NOT NULL DEFAULT '',
          ref_id BIGINT UNSIGNED NOT NULL DEFAULT 0, remark VARCHAR(200) NOT NULL DEFAULT '',
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY idx_user (user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS user_points_logs (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT UNSIGNED NOT NULL,
          points INT NOT NULL DEFAULT 0, points_after INT NOT NULL DEFAULT 0,
          type VARCHAR(30) NOT NULL DEFAULT '', ref_type VARCHAR(30) NOT NULL DEFAULT '',
          ref_id BIGINT UNSIGNED NOT NULL DEFAULT 0, remark VARCHAR(200) NOT NULL DEFAULT '',
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY idx_user (user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS coupons (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(100) NOT NULL DEFAULT '',
          type VARCHAR(20) NOT NULL DEFAULT 'amount', value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          min_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00, total_count INT NOT NULL DEFAULT 0,
          used_count INT NOT NULL DEFAULT 0, start_at DATETIME NULL, end_at DATETIME NULL,
          status TINYINT NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS user_coupons (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT UNSIGNED NOT NULL,
          coupon_id BIGINT UNSIGNED NOT NULL, status TINYINT NOT NULL DEFAULT 0,
          used_at DATETIME NULL, order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id), KEY idx_user (user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS cart_items (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT UNSIGNED NOT NULL,
          product_id BIGINT UNSIGNED NOT NULL, quantity INT NOT NULL DEFAULT 1,
          selected TINYINT NOT NULL DEFAULT 1,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id), UNIQUE KEY uk_user_product (user_id, product_id), KEY idx_user (user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS orders (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, order_no VARCHAR(32) NOT NULL DEFAULT '',
          user_id BIGINT UNSIGNED NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending_pay',
          total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00, discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          pay_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00, pay_type VARCHAR(20) NOT NULL DEFAULT '',
          coupon_id BIGINT UNSIGNED NOT NULL DEFAULT 0, address_name VARCHAR(50) NOT NULL DEFAULT '',
          address_phone VARCHAR(20) NOT NULL DEFAULT '', address_detail VARCHAR(300) NOT NULL DEFAULT '',
          remark VARCHAR(200) NOT NULL DEFAULT '', paid_at DATETIME NULL, shipped_at DATETIME NULL,
          completed_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id), UNIQUE KEY uk_order_no (order_no), KEY idx_user (user_id), KEY idx_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS order_items (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, order_id BIGINT UNSIGNED NOT NULL,
          product_id BIGINT UNSIGNED NOT NULL, product_name VARCHAR(200) NOT NULL DEFAULT '',
          product_image VARCHAR(500) NOT NULL DEFAULT '', price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          quantity INT NOT NULL DEFAULT 1, PRIMARY KEY (id), KEY idx_order (order_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS wx_pay_orders (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, order_id BIGINT UNSIGNED NOT NULL,
          order_no VARCHAR(32) NOT NULL DEFAULT '', prepay_id VARCHAR(64) NOT NULL DEFAULT '',
          transaction_id VARCHAR(64) NOT NULL DEFAULT '', pay_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          status TINYINT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          paid_at DATETIME NULL, PRIMARY KEY (id), KEY idx_order (order_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS user_addresses (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT UNSIGNED NOT NULL,
          name VARCHAR(50) NOT NULL DEFAULT '', phone VARCHAR(20) NOT NULL DEFAULT '',
          detail VARCHAR(300) NOT NULL DEFAULT '', is_default TINYINT NOT NULL DEFAULT 0,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id), KEY idx_user (user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS user_invites (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, inviter_id BIGINT UNSIGNED NOT NULL,
          invitee_id BIGINT UNSIGNED NOT NULL, invite_code VARCHAR(16) NOT NULL DEFAULT '',
          points_reward INT NOT NULL DEFAULT 0,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id), UNIQUE KEY uk_invitee (invitee_id), KEY idx_inviter (inviter_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];
    foreach ($tables as $sql) { $pdo->exec($sql); }
    try {
        $pdo->exec('ALTER TABLE users MODIFY balance DECIMAL(15,2) NOT NULL DEFAULT 0.00');
        $pdo->exec('ALTER TABLE user_wallet_logs MODIFY amount DECIMAL(15,2) NOT NULL DEFAULT 0.00, MODIFY balance_after DECIMAL(15,2) NOT NULL DEFAULT 0.00');
    } catch (Throwable $e) {}
    foreach ([
        'claim_type VARCHAR(20) NOT NULL DEFAULT \'all\'',
        'claim_min_spend DECIMAL(10,2) NOT NULL DEFAULT 0.00',
    ] as $col) {
        $name = preg_replace('/ .*/', '', $col);
        if ($pdo->query("SHOW TABLES LIKE 'coupons'")->fetch() && !$pdo->query("SHOW COLUMNS FROM coupons LIKE '$name'")->fetch()) {
            $pdo->exec("ALTER TABLE coupons ADD COLUMN $col");
        }
    }
}

function user_session_start(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $sid = trim((string)($_SERVER['HTTP_X_SESSION_ID'] ?? ''));
    if ($sid !== '' && preg_match('/^[a-zA-Z0-9,-]{1,128}$/', $sid)) {
        session_id($sid);
    }
    session_start();
}

function user_set_session(int $userId): void {
    user_session_start();
    $_SESSION['user_id'] = $userId;
}

function user_current_id(): int {
    user_session_start();
    return (int)($_SESSION['user_id'] ?? 0);
}

function try_user(PDO $pdo): ?array {
    ensure_user_schema($pdo);
    $uid = user_current_id();
    if ($uid <= 0) return null;
    $stmt = $pdo->prepare('SELECT id,username,openid,nickname,avatar,phone,balance,points,deposit,member_level,login_type,status,created_at FROM users WHERE id=? AND status=1 LIMIT 1');
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function require_user(PDO $pdo): array {
    $user = try_user($pdo);
    if (!$user) json_error('请先登录', 401);
    return $user;
}

function user_public(array $user): array {
    $levelName = '';
    if ((int)($user['member_level'] ?? 0) > 0) {
        $s = db()->prepare('SELECT name FROM member_levels WHERE id=? AND status=1 LIMIT 1');
        $s->execute([(int)$user['member_level']]);
        $levelName = (string)($s->fetchColumn() ?: '');
    }
    return [
        'id' => (int)$user['id'],
        'nickname' => $user['nickname'] ?? '',
        'avatar' => $user['avatar'] ?? '',
        'phone' => $user['phone'] ?? '',
        'openid' => $user['openid'] ?? '',
        'balance' => (float)($user['balance'] ?? 0),
        'points' => (int)($user['points'] ?? 0),
        'deposit' => (float)($user['deposit'] ?? 0),
        'member_level' => (int)($user['member_level'] ?? 0),
        'member_level_name' => $levelName,
        'login_type' => (int)($user['login_type'] ?? 1),
    ];
}

function order_no_new(): string {
    return date('YmdHis') . sprintf('%06d', random_int(0, 999999));
}

function wallet_change(PDO $pdo, int $userId, float $amount, string $type, string $refType = '', int $refId = 0, string $remark = ''): void {
    $ownTxn = !$pdo->inTransaction();
    if ($ownTxn) $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT balance FROM users WHERE id=? FOR UPDATE');
        $stmt->execute([$userId]);
        $bal = (float)$stmt->fetchColumn();
        $after = round($bal + $amount, 2);
        if ($after < 0) throw new RuntimeException('余额不足');
        if ($after > 2000000000) throw new RuntimeException('余额不能超过20亿');
        $pdo->prepare('UPDATE users SET balance=? WHERE id=?')->execute([$after, $userId]);
        $pdo->prepare('INSERT INTO user_wallet_logs (user_id,amount,balance_after,type,ref_type,ref_id,remark) VALUES (?,?,?,?,?,?,?)')
            ->execute([$userId, $amount, $after, $type, $refType, $refId, $remark]);
        if ($ownTxn) $pdo->commit();
    } catch (Throwable $e) {
        if ($ownTxn && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function points_change(PDO $pdo, int $userId, int $points, string $type, string $refType = '', int $refId = 0, string $remark = ''): void {
    $ownTxn = !$pdo->inTransaction();
    if ($ownTxn) $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT points FROM users WHERE id=? FOR UPDATE');
        $stmt->execute([$userId]);
        $cur = (int)$stmt->fetchColumn();
        $after = $cur + $points;
        if ($after < 0) throw new RuntimeException('积分不足');
        if ($after > 2000000000) throw new RuntimeException('积分不能超过20亿');
        $pdo->prepare('UPDATE users SET points=? WHERE id=?')->execute([$after, $userId]);
        $pdo->prepare('INSERT INTO user_points_logs (user_id,points,points_after,type,ref_type,ref_id,remark) VALUES (?,?,?,?,?,?,?)')
            ->execute([$userId, $points, $after, $type, $refType, $refId, $remark]);
        if ($ownTxn) $pdo->commit();
    } catch (Throwable $e) {
        if ($ownTxn && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function order_status_label(string $status): string {
    if ($status === 'pending_review') {
        $status = 'completed';
    }
    $map = [
        'pending_pay' => '待付款', 'pending_ship' => '待发货', 'shipping' => '已发货',
        'completed' => '已完成', 'cancelled' => '已取消', 'refunding' => '退款中',
    ];
    return $map[$status] ?? $status;
}

function order_status_options(): array {
    return [
        'pending_pay' => '待付款',
        'pending_ship' => '待发货',
        'shipping' => '已发货',
        'completed' => '已完成',
        'cancelled' => '已取消',
    ];
}

function order_counts(PDO $pdo, int $userId): array {
    $counts = ['pending_pay'=>0,'pending_ship'=>0,'shipping'=>0,'completed'=>0];
    $stmt = $pdo->prepare('SELECT status, COUNT(*) AS c FROM orders WHERE user_id=? GROUP BY status');
    $stmt->execute([$userId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $k = $row['status'];
        if ($k === 'pending_review') {
            $k = 'completed';
        }
        if (isset($counts[$k])) $counts[$k] = (int)$row['c'];
    }
    return $counts;
}

function user_coupon_count(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_coupons WHERE user_id=? AND status=0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function gift_register_coupon(PDO $pdo, int $userId): void {
    $couponId = 1;
    $c = $pdo->prepare('SELECT id,total_count,used_count,status FROM coupons WHERE id=? AND status=1 LIMIT 1');
    $c->execute([$couponId]);
    $cp = $c->fetch(PDO::FETCH_ASSOC);
    if (!$cp) return;
    if ((int)$cp['total_count'] > 0 && (int)$cp['used_count'] >= (int)$cp['total_count']) return;
    $exist = $pdo->prepare('SELECT id FROM user_coupons WHERE user_id=? AND coupon_id=? LIMIT 1');
    $exist->execute([$userId, $couponId]);
    if ($exist->fetch()) return;
    $pdo->prepare('INSERT INTO user_coupons (user_id,coupon_id) VALUES (?,?)')->execute([$userId, $couponId]);
    $pdo->prepare('UPDATE coupons SET used_count=used_count+1 WHERE id=?')->execute([$couponId]);
}

function user_invite_code_new(): string {
    return strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}

function ensure_user_invite_code(PDO $pdo, int $userId): string {
    $stmt = $pdo->prepare('SELECT invite_code FROM users WHERE id=? LIMIT 1');
    $stmt->execute([$userId]);
    $code = trim((string)$stmt->fetchColumn());
    if ($code !== '') return $code;
    for ($i = 0; $i < 5; $i++) {
        $code = user_invite_code_new();
        try {
            $pdo->prepare('UPDATE users SET invite_code=? WHERE id=?')->execute([$code, $userId]);
            return $code;
        } catch (Throwable $e) { /* retry on duplicate */ }
    }
    return user_invite_code_new();
}

function bind_invite_code(PDO $pdo, int $inviteeId, string $code): void {
    $code = strtoupper(trim($code));
    if ($code === '' || $inviteeId <= 0) return;
    $stmt = $pdo->prepare('SELECT id FROM users WHERE invite_code=? AND id<>? LIMIT 1');
    $stmt->execute([$code, $inviteeId]);
    $inviterId = (int)$stmt->fetchColumn();
    if ($inviterId <= 0) return;
    $chk = $pdo->prepare('SELECT invited_by FROM users WHERE id=? LIMIT 1');
    $chk->execute([$inviteeId]);
    if ((int)$chk->fetchColumn() > 0) return;
    $exist = $pdo->prepare('SELECT id FROM user_invites WHERE invitee_id=? LIMIT 1');
    $exist->execute([$inviteeId]);
    if ($exist->fetch()) return;
    $rewardInviter = 50;
    $rewardInvitee = 20;
    $pdo->prepare('UPDATE users SET invited_by=? WHERE id=?')->execute([$inviterId, $inviteeId]);
    $pdo->prepare('INSERT INTO user_invites (inviter_id,invitee_id,invite_code,points_reward) VALUES (?,?,?,?)')
        ->execute([$inviterId, $inviteeId, $code, $rewardInviter]);
    points_change($pdo, $inviterId, $rewardInviter, 'invite_reward', 'user', $inviteeId, '邀请好友奖励');
    points_change($pdo, $inviteeId, $rewardInvitee, 'invite_welcome', 'user', $inviterId, '受邀注册奖励');
}

function sync_member_level_by_points(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare('SELECT points,member_level FROM users WHERE id=? LIMIT 1');
    $stmt->execute([$userId]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) return;
    $points = (int)$u['points'];
    $cur = (int)$u['member_level'];
    $levels = $pdo->query('SELECT id,min_points FROM member_levels WHERE status=1 ORDER BY min_points DESC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $best = 0;
    foreach ($levels as $lv) {
        if ($points >= (int)$lv['min_points']) { $best = (int)$lv['id']; break; }
    }
    if ($best > $cur) {
        $pdo->prepare('UPDATE users SET member_level=? WHERE id=?')->execute([$best, $userId]);
    }
}

if (!function_exists('site_base_url_normalize')) {
function site_base_url_normalize(string $url): string {
    $url = rtrim(trim($url), '/');
    if ($url === '') {
        return '';
    }
    if (strlen($url) >= 4 && substr($url, -4) === '/api') {
        $url = rtrim(substr($url, 0, -4), '/');
    }
    return $url;
}
}

function wx_pay_config(): array {
    $cfg = $GLOBALS['app_config']['wechat'] ?? [];
    return [
        'app_id' => $cfg['app_id'] ?? '',
        'mch_id' => $cfg['mch_id'] ?? '',
        'mch_key' => $cfg['mch_key'] ?? '',
        'notify_url' => $cfg['notify_url'] ?? '',
    ];
}

function wx_pay_enabled(): bool {
    $cfg = wx_pay_config();
    return trim((string)($cfg['app_id'] ?? '')) !== ''
        && trim((string)($cfg['mch_id'] ?? '')) !== ''
        && trim((string)($cfg['mch_key'] ?? '')) !== '';
}

function wx_pay_notify_url(): string {
    $app = $GLOBALS['app_config'] ?? [];
    $base = site_base_url_normalize((string)($app['api_base_url'] ?? ''));
    if ($base !== '') {
        return $base . '/api/order/wx_notify.php';
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/api/order/wx_notify.php';
}

function wx_sign(array $data, string $key): string {
    ksort($data);
    $parts = [];
    foreach ($data as $k => $v) {
        if ($v === '' || $v === null || $k === 'sign') continue;
        $parts[] = $k . '=' . $v;
    }
    $parts[] = 'key=' . $key;
    return strtoupper(md5(implode('&', $parts)));
}

function wx_unified_order(string $orderNo, float $amount, string $openid, string $body): array {
    $cfg = wx_pay_config();
    if ($cfg['app_id'] === '' || $cfg['mch_id'] === '' || $cfg['mch_key'] === '') {
        return ['error' => '未配置微信支付商户号(mch_id/mch_key)'];
    }
    $params = [
        'appid' => $cfg['app_id'], 'mch_id' => $cfg['mch_id'], 'nonce_str' => bin2hex(random_bytes(8)),
        'body' => $body, 'out_trade_no' => $orderNo,
        'total_fee' => (string)max(1, (int)round($amount * 100)),
        'spbill_create_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'notify_url' => wx_pay_notify_url(),
        'trade_type' => 'JSAPI', 'openid' => $openid,
    ];
    $params['sign'] = wx_sign($params, $cfg['mch_key']);
    $xml = '<xml>';
    foreach ($params as $k => $v) $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
    $xml .= '</xml>';
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: text/xml\r\n", 'content' => $xml, 'timeout' => 15]]);
    $resp = @file_get_contents('https://api.mch.weixin.qq.com/pay/unifiedorder', false, $ctx);
    if (!$resp) return ['error' => '微信支付请求失败'];
    $x = @simplexml_load_string($resp, 'SimpleXMLElement', LIBXML_NOCDATA);
    if (!$x) return ['error' => '微信支付响应异常'];
    if ((string)$x->return_code !== 'SUCCESS' || (string)$x->result_code !== 'SUCCESS') {
        return ['error' => (string)($x->err_code_des ?: $x->return_msg ?: '下单失败')];
    }
    $prepayId = (string)$x->prepay_id;
    $ts = (string)time();
    $pkg = 'prepay_id=' . $prepayId;
    $paySign = wx_sign(['appId'=>$cfg['app_id'],'timeStamp'=>$ts,'nonceStr'=>$params['nonce_str'],'package'=>$pkg,'signType'=>'MD5'], $cfg['mch_key']);
    return [
        'prepay_id' => $prepayId,
        'payment' => [
            'appId' => $cfg['app_id'], 'timeStamp' => $ts, 'nonceStr' => $params['nonce_str'],
            'package' => $pkg, 'signType' => 'MD5', 'paySign' => $paySign,
        ],
    ];
}

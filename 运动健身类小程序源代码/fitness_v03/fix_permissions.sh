#!/bin/bash
# 解压 ZIP 覆盖部署后执行（网站根目录）：
#   bash fix_permissions.sh
# 如 PHP 用户不是 apache，请把 OWNER 改成 www-data 或 nginx
set -e
ROOT="$(cd "$(dirname "$0")" && pwd)"
OWNER="${1:-apache}"
chown -R "$OWNER:$OWNER" "$ROOT"
chmod -R u+rwX "$ROOT"
# 覆盖 index.php 后必须 reload PHP-FPM，否则 OPcache 仍返回旧页面（图片路径与 assets 不一致会大面积 404）
if command -v systemctl >/dev/null 2>&1; then
  for svc in php-fpm php83-php-fpm php82-php-fpm php81-php-fpm; do
    if systemctl is-active --quiet "$svc" 2>/dev/null; then
      systemctl reload "$svc" && echo "已 reload $svc（清除 OPcache 旧 index.php）"
      break
    fi
  done
fi
echo "权限已设置为 $OWNER；请强制刷新浏览器（Ctrl+Shift+R）"

# 文化传媒 · 极简列表

## 部署步骤

1. 把此代码放到网站根目录网站（PHP 7.4+ / MySQL 8+）
2. 浏览器访问 **/install.php** 完成安装（API 域名会自动识别当前访问地址）
3. 若安装页「环境自检」提示目录不可写，请执行：chown -R apache:apache .（apache 换成你主机的 PHP 用户）
4. 安装成功后 install.php 会自动删除
5. H5：浏览器访问 https://你的域名/ 或 /index.php（未安装会自动跳转安装页）
6. 小程序：用微信开发者工具导入 frontend/mp-weixin 目录
7. 管理后台：/admin/ （默认账号在安装时设置）
8. 在微信公众平台配置 request 合法域名（与安装时 API 域名一致）

## 注意

- 覆盖部署会替换 PHP 文件，手动修改的代码会丢失，请先备份
- AppID / AppSecret 仅在安装器填写，不会写死在源码中

筑码引擎 www.402.cn 

## 小程序页面截图

![文化传媒 · 极简列表 图片](https://www.402.cn/uploads/templates/media_culture_v05.jpg)

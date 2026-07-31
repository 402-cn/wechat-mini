<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
require_once dirname(__DIR__) . '/api/core/user_sync.php';
require_once dirname(__DIR__) . '/api/core/mp_sync.php';
$pdo = db();
$configPath = dirname(__DIR__) . '/config/config.inc.php';
$appConfig = is_file($configPath) ? (require $configPath) : [];
$wechatCfg = is_array($appConfig['wechat'] ?? null) ? $appConfig['wechat'] : [];
$projectPages = json_decode(@file_get_contents(__DIR__ . '/_pages.json') ?: '[]', true) ?: [];
$pageNameMap = [];
foreach ($projectPages as $p) {
    $pageNameMap[$p['page_key'] ?? ''] = $p['page_name'] ?? '';
}
function settings_render_page_select(array $pages, string $selected): string {
    $html = '<select name="tab_page_key[]" class="tab-page-select" onchange="onTabPageChange(this)">';
    $html .= '<option value="">选择页面</option>';
    foreach ($pages as $p) {
        $key = $p['page_key'] ?? '';
        $label = $p['page_name'] ?? $key;
        $sel = $key === $selected ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($key) . '" data-label="' . htmlspecialchars($label) . '"' . $sel . '>' . htmlspecialchars($label) . '</option>';
    }
    return $html . '</select>';
}
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = [];
    $keys = $_POST['tab_page_key'] ?? [];
    $texts = $_POST['tab_text'] ?? [];
    foreach ($keys as $i => $pk) {
        $pk = trim((string)$pk);
        if ($pk === '') continue;
        $tx = trim((string)($texts[$i] ?? ''));
        if ($tx === '' && isset($pageNameMap[$pk])) $tx = $pageNameMap[$pk];
        if ($tx === '') continue;
        $items[] = ['page_key' => $pk, 'text' => $tx];
    }
    $prevStmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key='global_config' LIMIT 1");
    $prevRow = $prevStmt->fetch(PDO::FETCH_ASSOC);
    $cfg = json_decode($prevRow['setting_value'] ?? '{}', true) ?: [];
    $cfg['theme'] = [
        'primaryColor' => trim($_POST['primary_color'] ?? '#2ecc71'),
        'backgroundColor' => trim($_POST['background_color'] ?? '#f5f5f5'),
    ];
    $cfg['tabBar'] = [
        'enabled' => !empty($_POST['tabbar_enabled']),
        'items' => $items,
    ];
    $cfg['controls'] = [
        'sideHome' => ['enabled' => !empty($_POST['side_home_enabled'])],
        'sideService' => [
            'enabled' => !empty($_POST['side_service_enabled']),
            'phone' => trim($_POST['side_service_phone'] ?? ''),
        ],
        'splashPopup' => [
            'enabled' => !empty($_POST['splash_enabled']),
            'image' => trim($_POST['splash_image'] ?? ''),
            'link' => admin_link_build_from_post($_POST, 'splash_'),
        ],
        'noticePopup' => [
            'enabled' => !empty($_POST['notice_enabled']),
            'title' => trim($_POST['notice_title'] ?? '通知'),
            'content' => trim($_POST['notice_content'] ?? ''),
            'image' => trim($_POST['notice_image'] ?? ''),
            'link' => admin_link_build_from_post($_POST, 'notice_'),
            'frequency' => in_array($_POST['notice_frequency'] ?? '', ['daily','once','session'], true)
                ? $_POST['notice_frequency'] : 'daily',
        ],
    ];
    $json = json_encode($cfg, JSON_UNESCAPED_UNICODE);
    $pdo->prepare("INSERT INTO site_settings (setting_key,setting_value) VALUES ('global_config',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$json]);
    $siteUrl = site_base_url_normalize(trim($_POST['api_base_url'] ?? ''));
    $wxAppId = trim($_POST['wx_app_id'] ?? '');
    $wxPatch = [
        'app_id' => $wxAppId,
        'app_secret' => trim($_POST['wx_app_secret'] ?? ''),
    ];
    if (admin_flag('has_product')) {
        $wxPatch['mch_id'] = trim($_POST['wx_mch_id'] ?? '');
        $wxPatch['mch_key'] = trim($_POST['wx_mch_key'] ?? '');
    }
    if ($siteUrl !== '') {
        $wxPatch['notify_url'] = $siteUrl . '/api/order/wx_notify.php';
    }
    $topPatch = $siteUrl !== '' ? ['api_base_url' => $siteUrl] : [];
    $errors = [];
    if (!admin_save_app_config($topPatch, $wxPatch)) {
        $errors[] = 'config/config.inc.php 写入失败，请检查 config 目录及 config.inc.php 是否 Web 用户可写';
    } else {
        $lockPath = dirname(__DIR__) . '/install.lock';
        if ($siteUrl !== '' && is_file($lockPath)) {
            $lock = json_decode((string)file_get_contents($lockPath), true) ?: [];
            $lock['api_base_url'] = $siteUrl;
            @file_put_contents($lockPath, json_encode($lock, JSON_UNESCAPED_UNICODE));
        }
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($configPath, true);
        }
        $appConfig = require $configPath;
        $wechatCfg = is_array($appConfig['wechat'] ?? null) ? $appConfig['wechat'] : [];
    }
    $syncSiteUrl = $siteUrl !== '' ? $siteUrl : site_base_url_normalize((string)($appConfig['api_base_url'] ?? ''));
    if (!sync_mp_weixin_config($wxAppId, $syncSiteUrl)) {
        $errors[] = '小程序配置 frontend/mp-weixin（project.config.json / utils/mp_config.js）写入失败，请检查 frontend 目录权限';
    }
    if ($errors) {
        $msg = 'err:站点主题已保存。' . implode('；', $errors);
    } else {
        $msg = 'ok:保存成功（已写入 config.inc.php 并同步小程序 AppID 与 API 地址）';
    }
}
$stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key='global_config' LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$cfg = json_decode($row['setting_value'] ?? '{}', true) ?: [];
$theme = $cfg['theme'] ?? [];
$tabBar = $cfg['tabBar'] ?? [];
$tabItems = $tabBar['items'] ?? [];
if (!$tabItems) {
    $firstKey = $projectPages[0]['page_key'] ?? 'home';
    $firstName = $projectPages[0]['page_name'] ?? '首页';
    $tabItems = [['page_key' => $firstKey, 'text' => $firstName]];
}
$primaryColor = $theme['primaryColor'] ?? '#2ecc71';
$bgColor = $theme['backgroundColor'] ?? '#f5f5f5';
$controls = $cfg['controls'] ?? [];
$sideHome = $controls['sideHome'] ?? [];
$sideService = $controls['sideService'] ?? [];
$splash = $controls['splashPopup'] ?? [];
$notice = $controls['noticePopup'] ?? [];
$wxAppId = (string)($wechatCfg['app_id'] ?? '');
$wxSecret = (string)($wechatCfg['app_secret'] ?? '');
$wxMchId = (string)($wechatCfg['mch_id'] ?? '');
$wxMchKey = (string)($wechatCfg['mch_key'] ?? '');
$apiBaseUrl = site_base_url_normalize((string)($appConfig['api_base_url'] ?? ''));
$wxPayNotifyUrl = $apiBaseUrl !== '' ? ($apiBaseUrl . '/api/order/wx_notify.php') : wx_pay_notify_url();
$articleOptions = admin_article_options($pdo);
$productOptions = admin_product_options($pdo);
admin_layout_start('系统设置', 'settings.php');
echo '<style>
#tabItems{display:flex;flex-direction:column;gap:6px;margin-top:8px;max-width:480px}
.tab-row{display:inline-flex;gap:6px;align-items:center;padding:6px 8px;background:#fafafa;border:1px solid #eee;border-radius:6px;width:fit-content;max-width:100%}
.tab-row.dragging{opacity:.55;border-style:dashed}
.tab-drag{cursor:grab;color:#999;font-size:16px;padding:0 4px;user-select:none;flex-shrink:0}
.tab-page-select{width:110px!important;flex:none!important;margin:0!important;padding:4px 6px!important;font-size:13px!important}
.tab-text-input{width:120px!important;flex:none!important;margin:0!important;padding:4px 8px!important;font-size:13px!important}
.tab-actions{flex-shrink:0}
</style>';
if ($msg) {
    $cls = strpos($msg,'ok:')===0?'msg-ok':'msg-err';
    echo '<div class="msg ' . $cls . '">' . htmlspecialchars(preg_replace('/^(ok|err):/','',$msg)) . '</div>';
}
echo '<div class="card"><form method="post" class="form-grid settings-form">';
echo '<h3>主题</h3>';
admin_field_color('主题色', 'primary_color', $primaryColor, 'settings_primary');
admin_field_color('页面背景', 'background_color', $bgColor, 'settings_bg');
echo '<h3 style="margin-top:24px">微信小程序</h3>';
admin_field_text_hint('API 域名', 'api_base_url', $apiBaseUrl, '站点根域名，如 https://www.example.com（<strong>不要带 /api</strong>）。保存后自动写入 config.inc.php，并同步到 <code>utils/mp_config.js</code> 的 apiBase（自动拼接 <code>/api</code>）。', 'url', 'placeholder="https://www.example.com"', true);
admin_field_text_hint('AppID', 'wx_app_id', $wxAppId, wx_platform_link() . ' → 开发 → 开发管理 → 开发设置 → 开发者 ID → AppID（小程序 ID）。保存后同步到 <code>project.config.json</code> 的 appid。', 'text', '', true);
admin_field_text_hint('AppSecret', 'wx_app_secret', $wxSecret, wx_platform_link() . ' → 开发 → 开发管理 → 开发设置 → AppSecret（点击重置获取，仅显示一次请妥善保存）', 'text', '', true);
if (admin_flag('has_product')) {
    echo '<h4 style="margin:16px 0 6px;font-size:14px;font-weight:600">微信支付</h4>';
    admin_field_text_hint('商户号 mch_id', 'wx_mch_id', $wxMchId, wx_mch_link() . ' → 账户中心 → 商户信息 → 商户号', 'text', '', true);
    admin_field_text_hint('API 密钥 mch_key', 'wx_mch_key', $wxMchKey, wx_mch_link() . ' → 账户中心 → API 安全 → 设置 API v2 密钥', 'text', '', true);
    admin_field_readonly_hint('支付回调 notify_url', $wxPayNotifyUrl, '请复制此地址，填入' . wx_mch_link() . ' → 产品中心 → 开发配置 → 支付配置 → <strong>支付回调 URL</strong>（须 HTTPS 公网可访问）。保存系统设置时会自动写入 config.inc.php，并同步小程序配置。');
}
echo '<h3 class="guide-heading" style="margin-top:24px">底部导航 TabBar ';
admin_guide_tip('_tabbar', '红圈标注小程序底部的导航栏，即此处配置的 Tab。若发布时未启用底部导航，示意图会显示示例 Tab 供参考。');
echo '</h3>';
admin_field_checkbox_hint('启用底部导航', 'tabbar_enabled', !empty($tabBar['enabled']), '开启后小程序底部显示 Tab 栏');
echo '<p style="color:#888;font-size:13px;margin:8px 0 12px;grid-column:1/-1">拖动左侧把手调整顺序；选择页面后可修改底部显示文字。</p>';
echo '<div id="tabItems">';
foreach ($tabItems as $item) {
    echo '<div class="tab-row" draggable="true">';
    echo '<span class="tab-drag" title="拖动排序">☰</span>';
    echo settings_render_page_select($projectPages, $item['page_key'] ?? '');
    echo '<input name="tab_text[]" class="tab-text-input" placeholder="显示文字" value="' . htmlspecialchars($item['text'] ?? '') . '" oninput="this.dataset.auto=\'0\'">';
    echo '<span class="tab-actions"><button type="button" class="btn btn-sm btn-danger" onclick="removeTabRow(this)">删除</button></span></div>';
}
echo '</div>';
echo '<button type="button" class="btn btn-secondary btn-sm" onclick="addTabRow()">+ 添加 Tab</button>';
echo '<h3 style="margin-top:24px">全局控件</h3>';
admin_field_checkbox_hint('侧停 · 返回首页', 'side_home_enabled', !empty($sideHome['enabled']), '在页面侧边显示返回首页按钮');
admin_field_checkbox_hint('侧停 · 客服电话', 'side_service_enabled', !empty($sideService['enabled']), '在页面侧边显示客服电话入口');
admin_field_text_hint('客服电话', 'side_service_phone', $sideService['phone'] ?? '', '侧停客服入口拨打的号码', 'text', 'placeholder="400-000-0000"');
echo '<h4 style="margin:16px 0 6px;font-size:14px;font-weight:600">开屏弹窗</h4>';
admin_field_checkbox_hint('开屏弹窗', 'splash_enabled', !empty($splash['enabled']), '用户打开小程序时展示');
admin_field_image('弹窗图片', 'splash_image', 'splash_image', $splash['image'] ?? '');
admin_field_link('点击跳转', $projectPages, $articleOptions, $productOptions, (string)($splash['link'] ?? ''), 'splash_');
echo '<h4 style="margin:16px 0 6px;font-size:14px;font-weight:600">运营弹窗</h4>';
admin_field_checkbox_hint('运营弹窗', 'notice_enabled', !empty($notice['enabled']), '按设定频率向用户展示通知');
admin_field_text('标题', 'notice_title', $notice['title'] ?? '通知');
admin_field_textarea('通知内容', 'notice_content', $notice['content'] ?? '', 4);
admin_field_image('配图（可选）', 'notice_image', 'notice_image', $notice['image'] ?? '');
admin_field_link('跳转链接', $projectPages, $articleOptions, $productOptions, (string)($notice['link'] ?? ''), 'notice_');
$nf = $notice['frequency'] ?? 'daily';
admin_field_select('弹出频率', 'notice_frequency', ['daily' => '每天一次', 'once' => '仅一次', 'session' => '每次打开'], $nf);
echo '<p style="margin-top:20px"><button class="btn" type="submit">保存设置</button></p></form></div>';
$pagesJson = json_encode($projectPages, JSON_UNESCAPED_UNICODE);
echo '<script>var PROJECT_PAGES=' . $pagesJson . ';
function buildPageSelect(selected){
  var html=\'<select name="tab_page_key[]" class="tab-page-select" onchange="onTabPageChange(this)"><option value="">选择页面</option>\';
  PROJECT_PAGES.forEach(function(p){
    var sel=(p.page_key===selected)?" selected":"";
    html+=\'<option value="\'+p.page_key+\'" data-label="\'+p.page_name+\'"\'+sel+\'>\'+p.page_name+\'</option>\';
  });
  return html+\'</select>\';
}
function onTabPageChange(sel){
  var opt=sel.options[sel.selectedIndex];
  var textInput=sel.parentElement.querySelector(\'input[name="tab_text[]"]\');
  if(textInput&&opt&&opt.dataset.label){
    if(!textInput.value||textInput.dataset.auto==="1"){textInput.value=opt.dataset.label;textInput.dataset.auto="1";}
  }
}
var tabDragEl=null;
function initTabDrag(){
  document.querySelectorAll("#tabItems .tab-row").forEach(function(row){
    row.draggable=false;
    var handle=row.querySelector(".tab-drag");
    if(handle){handle.addEventListener("mousedown",function(){row.draggable=true;});}
    row.ondragstart=function(e){tabDragEl=row;row.classList.add("dragging");e.dataTransfer.effectAllowed="move";};
    row.ondragend=function(){row.classList.remove("dragging");row.draggable=false;tabDragEl=null;};
    row.ondragover=function(e){e.preventDefault();e.dataTransfer.dropEffect="move";};
    row.ondrop=function(e){
      e.preventDefault();
      if(!tabDragEl||tabDragEl===row)return;
      var wrap=document.getElementById("tabItems");
      var rows=Array.from(wrap.querySelectorAll(".tab-row"));
      var from=rows.indexOf(tabDragEl),to=rows.indexOf(row);
      if(from<0||to<0)return;
      if(from<to)row.after(tabDragEl);else row.before(tabDragEl);
    };
  });
}
function addTabRow(){
  var d=document.createElement("div");
  d.className="tab-row";
  d.innerHTML=\'<span class="tab-drag" title="拖动排序">☰</span>\'+buildPageSelect("")+
    \'<input name="tab_text[]" class="tab-text-input" placeholder="显示文字" data-auto="1" oninput="this.dataset.auto=\\\'0\\\'">\'+
    \'<span class="tab-actions"><button type="button" class="btn btn-sm btn-danger" onclick="removeTabRow(this)">删除</button></span>\';
  document.getElementById("tabItems").appendChild(d);
  initTabDrag();
}
function removeTabRow(btn){
  var wrap=document.getElementById("tabItems");
  if(wrap.querySelectorAll(".tab-row").length<=1){adminToast("至少保留一个 Tab");return;}
  btn.closest(".tab-row").remove();
}
initTabDrag();
</script>';
admin_layout_end();

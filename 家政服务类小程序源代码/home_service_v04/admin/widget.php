<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
$_psFile = dirname(__DIR__) . '/api/core/product_sync.php';
if (is_file($_psFile)) {
    require_once $_psFile;
}
$_mpsFile = dirname(__DIR__) . '/api/core/marketing_product_sync.php';
if (is_file($_mpsFile)) {
    require_once $_mpsFile;
}
$id = trim($_GET['id'] ?? '');
if ($id === '') { header('Location: dashboard.php'); exit; }
$pdo = db();
if (function_exists('ensure_demo_products')) {
    ensure_demo_products($pdo);
}
$msg = '';
$metaStmt = $pdo->prepare('SELECT component_type, props_json FROM widget_instances WHERE instance_id = ? LIMIT 1');
$metaStmt->execute([$id]);
$metaRow = $metaStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$metaType = (string)($metaRow['component_type'] ?? '');
$metaProps = json_decode($metaRow['props_json'] ?? '{}', true) ?: [];
$flashQs = 'id=' . urlencode($id);
if (in_array($metaType, ['flashSale', 'groupBuy'], true) && isset($_GET['add_product'])) {
    $pid = (int)$_GET['add_product'];
    if ($pid > 0) {
        $ids = $metaProps['productIds'] ?? [];
        if (!is_array($ids)) $ids = [];
        $ids = array_values(array_map('intval', $ids));
        if (!in_array($pid, $ids, true)) {
            $chk = $pdo->prepare('SELECT id FROM products WHERE id=? AND status=1 LIMIT 1');
            $chk->execute([$pid]);
            if ($chk->fetch()) {
                $ids[] = $pid;
                $metaProps['productIds'] = $ids;
                $pdo->prepare('UPDATE widget_instances SET props_json=? WHERE instance_id=?')->execute([json_encode($metaProps, JSON_UNESCAPED_UNICODE), $id]);
                if (function_exists('sync_marketing_entry_products')) sync_marketing_entry_products($pdo, $id, $metaType, $ids);
                $msg = $metaType === 'groupBuy' ? '已添加拼团商品' : '已添加秒杀商品';
            }
        }
    }
    header('Location: widget.php?' . $flashQs . '&msg=' . urlencode($msg ?: '操作完成'));
    exit;
}
if (in_array($metaType, ['flashSale', 'groupBuy'], true) && isset($_GET['remove_product'])) {
    $pid = (int)$_GET['remove_product'];
    if ($pid > 0) {
        $ids = $metaProps['productIds'] ?? [];
        if (!is_array($ids)) $ids = [];
        $ids = array_values(array_filter(array_map('intval', $ids), function ($v) use ($pid) { return $v !== $pid; }));
        $metaProps['productIds'] = $ids;
        $pdo->prepare('UPDATE widget_instances SET props_json=? WHERE instance_id=?')->execute([json_encode($metaProps, JSON_UNESCAPED_UNICODE), $id]);
        if (function_exists('sync_marketing_entry_products')) sync_marketing_entry_products($pdo, $id, $metaType, $ids);
    }
    header('Location: widget.php?' . $flashQs . '&msg=' . urlencode('已移除'));
    exit;
}
if (isset($_GET['del_item'])) {
    $pdo->prepare('DELETE FROM widget_items WHERE id = ? AND instance_id = ?')->execute([(int)$_GET['del_item'], $id]);
    $msg = '条目已删除';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_props'])) {
        $cur = $pdo->prepare('SELECT props_json FROM widget_instances WHERE instance_id = ? LIMIT 1');
        $cur->execute([$id]);
        $curRow = $cur->fetch(PDO::FETCH_ASSOC);
        $props = json_decode($curRow['props_json'] ?? '{}', true) ?: [];
        foreach (['title','subtitle','submitText','buttonText','successText','videoSrc','src','poster'] as $k) {
            if (isset($_POST['prop_'.$k])) $props[$k] = trim((string)$_POST['prop_'.$k]);
        }
        foreach (['requireLogin','rewardCoupon','showLocation','autoplay','showCount'] as $k) {
            if (isset($_POST['prop_'.$k])) $props[$k] = !empty($_POST['prop_'.$k]);
        }
        foreach (['rewardPoints','maxAttempts','passScore','zoom','height','latitude','longitude','columns','padding','count','totalScore'] as $k) {
            if (isset($_POST['prop_'.$k]) && $_POST['prop_'.$k] !== '') $props[$k] = (int)$_POST['prop_'.$k];
        }
        if (isset($_POST['prop_totalScore']) && $_POST['prop_totalScore'] !== '') $props['totalScore'] = (float)$_POST['prop_totalScore'];
        if (isset($_POST['prop_score']) && $_POST['prop_score'] !== '') $props['score'] = (float)$_POST['prop_score'];
        foreach (['showCountdown','allowUserRate'] as $k) {
            $props[$k] = !empty($_POST['prop_'.$k]);
        }
        if (!empty($_POST['prop_countdownEnd'])) $props['countdownEnd'] = admin_datetime_from_input(trim((string)$_POST['prop_countdownEnd']));
        if (isset($_POST['prop_bgColor'])) $props['bgColor'] = admin_norm_color(trim((string)$_POST['prop_bgColor']), '#ffffff');
        if (isset($_POST['prop_product_ids_json'])) {
            $ids = json_decode((string)$_POST['prop_product_ids_json'], true);
            if (!is_array($ids)) $ids = [];
            $clean = [];
            foreach ($ids as $fid) {
                $fid = (int)$fid;
                if ($fid > 0 && !in_array($fid, $clean, true)) $clean[] = $fid;
            }
            $props['productIds'] = $clean;
        }
        if (isset($_POST['prop_showArrow'])) $props['showArrow'] = !empty($_POST['prop_showArrow']);
        foreach (['brand','location','placeholder','clickAction','width','height','searchType','searchBtnText'] as $k) {
            if (isset($_POST['prop_'.$k])) $props[$k] = trim((string)$_POST['prop_'.$k]);
        }
        if ($metaType === 'pageHeader') {
            $props['showScan'] = !empty($_POST['prop_showScan']);
            $props['showMessage'] = !empty($_POST['prop_showMessage']);
            $props['showSearchBtn'] = !empty($_POST['prop_showSearchBtn']);
            if (isset($_POST['prop_bgColor'])) $props['bgColor'] = admin_norm_color(trim((string)$_POST['prop_bgColor']), '#2ecc71');
        }
        if ($metaType === 'image') {
            if (isset($_POST['prop_src'])) $props['src'] = trim((string)$_POST['prop_src']);
            $linkJson = admin_link_build_from_post($_POST, 'img_');
            $props['link'] = json_decode($linkJson, true) ?: ['type' => 'none'];
        }
        if ($metaType === 'serviceCard') {
            $linkJson = admin_link_build_from_post($_POST);
            $props['link'] = json_decode($linkJson, true) ?: ['type' => 'none'];
        }
        if (in_array($metaType, ['user','userVip','userBenefits','userOrders','userCommunity'], true)) {
            foreach (['title','subtitle','linkText'] as $k) {
                if (isset($_POST['prop_'.$k])) $props[$k] = trim((string)$_POST['prop_'.$k]);
            }
            if ($metaType === 'user') {
                $props['enableRegister'] = !empty($_POST['prop_enableRegister']);
                $props['enableWechatLogin'] = !empty($_POST['prop_enableWechatLogin']);
            }
            if ($metaType === 'userVip') {
                if (isset($_POST['prop_levelId']) && $_POST['prop_levelId'] !== '') $props['levelId'] = (int)$_POST['prop_levelId'];
                if (isset($_POST['prop_deductPoints']) && $_POST['prop_deductPoints'] !== '') $props['deductPoints'] = (int)$_POST['prop_deductPoints'];
                if (isset($_POST['prop_deductAmount']) && $_POST['prop_deductAmount'] !== '') $props['deductAmount'] = (float)$_POST['prop_deductAmount'];
                if (isset($_POST['prop_payType'])) $props['payType'] = trim((string)$_POST['prop_payType']);
            }
            if ($metaType === 'userBenefits' && isset($_POST['prop_items_lines'])) {
                $lines = preg_split('/\r\n|\r|\n/', (string)$_POST['prop_items_lines']);
                $props['items'] = array_values(array_filter(array_map('trim', $lines), function($v){ return $v !== ''; }));
            }
            if ($metaType === 'userCommunity') {
                $linkJson = admin_link_build_from_post($_POST);
                $props['link'] = json_decode($linkJson, true) ?: ['type' => 'none'];
            }
        }
        $pdo->prepare('UPDATE widget_instances SET props_json=? WHERE instance_id=?')->execute([
            json_encode($props, JSON_UNESCAPED_UNICODE), $id,
        ]);
        if (in_array($metaType, ['flashSale', 'groupBuy'], true) && isset($props['productIds']) && is_array($props['productIds'])) {
            if (function_exists('sync_marketing_entry_products')) sync_marketing_entry_products($pdo, $id, $metaType, $props['productIds']);
        }
        $msg = '配置已保存';
    }
    if (isset($_POST['save_item'])) {
        $itemId = (int)($_POST['item_id'] ?? 0);
        if ($itemId > 0) {
            $cur = $pdo->prepare('SELECT item_json FROM widget_items WHERE id=? AND instance_id=? LIMIT 1');
            $cur->execute([$itemId, $id]);
            $rowItem = $cur->fetch(PDO::FETCH_ASSOC);
            $data = json_decode($rowItem['item_json'] ?? '{}', true) ?: [];
            $data['title'] = trim($_POST['item_title'] ?? ($data['title'] ?? ''));
            $data['subtitle'] = trim($_POST['item_subtitle'] ?? ($data['subtitle'] ?? ''));
            $data['text'] = trim($_POST['item_text'] ?? ($data['text'] ?? ''));
            $data['name'] = trim($_POST['item_title'] ?? ($data['name'] ?? ($data['title'] ?? '')));
            $data['logo'] = trim($_POST['item_logo'] ?? ($data['logo'] ?? ''));
            $data['distance'] = trim($_POST['item_subtitle'] ?? ($data['distance'] ?? ''));
            $imgs = is_array($data['images'] ?? null) ? $data['images'] : [];
            $imgs[0] = trim($_POST['item_image'] ?? ($imgs[0] ?? ''));
            $imgs[1] = trim($_POST['item_image2'] ?? ($imgs[1] ?? ''));
            $imgs[2] = trim($_POST['item_image3'] ?? ($imgs[2] ?? ''));
            $data['images'] = array_values(array_filter($imgs, function($v){ return $v !== ''; }));
            $data['value'] = trim($_POST['item_value'] ?? ($data['value'] ?? ''));
            $data['image'] = trim($_POST['item_image'] ?? ($data['image'] ?? ''));
            $data['icon'] = trim($_POST['item_icon'] ?? ($data['icon'] ?? ''));
            $data['bgColor'] = trim($_POST['item_bgColor'] ?? ($data['bgColor'] ?? ''));
            $data['height'] = (int)($_POST['item_height'] ?? ($data['height'] ?? 0));
            $data['isVideo'] = !empty($_POST['item_isVideo']);
            $data['label'] = trim($_POST['item_label'] ?? ($data['label'] ?? ''));
            $linkPrefix = 'it' . $itemId . '_';
            $linkJson = admin_link_build_from_post($_POST, $linkPrefix);
            $data['link'] = json_decode($linkJson, true) ?: ['type' => 'none'];
            $sort = (int)($_POST['sort_order'] ?? 0);
            $pdo->prepare('UPDATE widget_items SET item_json=?, sort_order=? WHERE id=? AND instance_id=?')->execute([
                json_encode($data, JSON_UNESCAPED_UNICODE), $sort, $itemId, $id,
            ]);
            $msg = '条目已更新';
        }
    }
    if (isset($_POST['add_item'])) {
        $item = [
            'title' => trim($_POST['item_title'] ?? ''),
            'subtitle' => trim($_POST['item_subtitle'] ?? ''),
            'text' => trim($_POST['item_text'] ?? ''),
            'name' => trim($_POST['item_name'] ?? ''),
            'value' => trim($_POST['item_value'] ?? ''),
            'image' => trim($_POST['item_image'] ?? ''),
            'icon' => trim($_POST['item_icon'] ?? ''),
            'bgColor' => trim($_POST['item_bgColor'] ?? ''),
            'height' => (int)($_POST['item_height'] ?? 180),
            'isVideo' => !empty($_POST['item_isVideo']),
            'label' => trim($_POST['item_label'] ?? ''),
            'latitude' => (float)($_POST['item_lat'] ?? 0),
            'longitude' => (float)($_POST['item_lng'] ?? 0),
            'address' => trim($_POST['item_address'] ?? ''),
        ];
        $linkJson = admin_link_build_from_post($_POST);
        $item['link'] = json_decode($linkJson, true) ?: ['type' => 'none'];
        if ($metaType === 'serviceCard') {
            $item['name'] = trim($_POST['item_title'] ?? ($item['name'] ?? ''));
            $item['logo'] = trim($_POST['item_logo'] ?? '');
            $imgs = [
                trim($_POST['item_image'] ?? ''),
                trim($_POST['item_image2'] ?? ''),
                trim($_POST['item_image3'] ?? ''),
            ];
            $item['images'] = array_values(array_filter($imgs, function($v){ return $v !== ''; }));
        }
        $pdo->prepare('INSERT INTO widget_items (instance_id,item_key,item_json,sort_order) VALUES (?,?,?,?)')->execute([
            $id, 'item', json_encode($item, JSON_UNESCAPED_UNICODE), (int)($_POST['sort_order'] ?? 0),
        ]);
        $msg = '条目已添加';
    }
}
$stmt = $pdo->prepare('SELECT * FROM widget_instances WHERE instance_id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo '组件不存在'; exit; }
$type = $row['component_type'] ?? '';
if ($type === 'messageBoard') { header('Location: message_inbox.php?id=' . urlencode($id)); exit; }
if ($type === 'quiz') { header('Location: quiz_admin.php?id=' . urlencode($id)); exit; }
if ($type === 'checkinActivity') { header('Location: checkin_admin.php?id=' . urlencode($id)); exit; }
$props = json_decode($row['props_json'] ?? '{}', true) ?: [];
if (in_array($type, ['flashSale', 'groupBuy'], true)) {
    if (function_exists('bootstrap_marketing_entry_products')) {
        bootstrap_marketing_entry_products($pdo, $id, $type, $props);
    }
}
$items = $pdo->prepare('SELECT * FROM widget_items WHERE instance_id = ? AND status = 1 ORDER BY sort_order ASC, id ASC');
$items->execute([$id]);
$itemRows = $items->fetchAll(PDO::FETCH_ASSOC);
$projectPages = json_decode(@file_get_contents(__DIR__ . '/_pages.json') ?: '[]', true) ?: [];
$articleOptions = [];
$productOptions = [];
if ($pdo->query("SHOW TABLES LIKE 'articles'")->fetch()) {
    $articleOptions = $pdo->query('SELECT id,title FROM articles WHERE status=1 ORDER BY id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
if ($pdo->query("SHOW TABLES LIKE 'products'")->fetch()) {
    $productOptions = $pdo->query('SELECT id,name FROM products WHERE status=1 ORDER BY id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
if ($type === 'pageHeader') {
    $guideFlowKey = admin_guide_flow_key((string)($row['page_key'] ?? ''), $id);
    admin_layout_start($row['label'], 'widget.php?id=' . $id, $id, '配置品牌名、定位与搜索栏；保存后 H5 前台即时同步。', $guideFlowKey);
    if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
    echo '<div class="card"><form method="post" class="form-grid"><input type="hidden" name="save_props" value="1">';
    admin_field_text('品牌名称', 'prop_brand', (string)($props['brand'] ?? ''));
    admin_field_text('定位文案', 'prop_location', (string)($props['location'] ?? ''));
    admin_field_text('搜索占位', 'prop_placeholder', (string)($props['placeholder'] ?? ''));
    admin_field_select('搜索类型', 'prop_searchType', [''=>'关闭','article'=>'文章','product'=>'商品'], (string)($props['searchType'] ?? ''));
    admin_field_color('背景色', 'prop_bgColor', (string)($props['bgColor'] ?? '#2ecc71'));
    echo '<label><input type="checkbox" name="prop_showScan" value="1"' . (($props['showScan'] ?? false) ? ' checked' : '') . '> 显示扫码</label>';
    echo '<label><input type="checkbox" name="prop_showMessage" value="1"' . (($props['showMessage'] ?? true) ? ' checked' : '') . '> 显示消息</label>';
    echo '<label><input type="checkbox" name="prop_showSearchBtn" value="1"' . (!empty($props['showSearchBtn']) ? ' checked' : '') . '> 显示搜索按钮</label>';
    admin_field_text('搜索按钮文案', 'prop_searchBtnText', (string)($props['searchBtnText'] ?? '搜索'));
    echo '<p style="margin-top:16px"><button class="btn" type="submit">保存配置</button></p></form></div>';
    admin_layout_end();
    exit;
}
if ($type === 'image') {
    $guideFlowKey = admin_guide_flow_key((string)($row['page_key'] ?? ''), $id);
    admin_layout_start($row['label'], 'widget.php?id=' . $id, $id, '更换图片并配置点击行为；保存后 H5 前台即时同步。', $guideFlowKey);
    if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
    echo '<div class="card"><form method="post" class="form-grid"><input type="hidden" name="save_props" value="1">';
    admin_field_image('图片', 'prop_src', 'prop_image_src', (string)($props['src'] ?? ''));
    admin_field_text('宽度', 'prop_width', (string)($props['width'] ?? '100%'));
    admin_field_text('高度', 'prop_height', (string)($props['height'] ?? 'auto'));
    admin_field_select('点击行为', 'prop_clickAction', ['none'=>'无','preview'=>'预览大图','link'=>'跳转链接'], (string)($props['clickAction'] ?? 'none'));
    echo '<div id="image-link-wrap">';
    admin_field_link('跳转链接', $projectPages, $articleOptions, $productOptions, json_encode($props['link'] ?? ['type'=>'none'], JSON_UNESCAPED_UNICODE), 'img_');
    echo '</div>';
    echo '<p class="tip">点击行为选「跳转链接」时，请配置上方链接（与宫格导航相同：内链/外链/商品/文章）。</p>';
    echo '<p style="margin-top:16px"><button class="btn" type="submit">保存配置</button></p></form></div>';
    echo '<script>document.addEventListener("DOMContentLoaded",function(){
  var actionSel=document.querySelector("select[name=prop_clickAction]");
  var linkWrap=document.getElementById("image-link-wrap");
  function syncImageLink(){ if(linkWrap&&actionSel) linkWrap.style.display=actionSel.value==="link"?"":"none"; }
  if(actionSel){ actionSel.addEventListener("change",syncImageLink); syncImageLink(); }
});</script>';
    admin_layout_end();
    exit;
}
$canvasBlockTypes = ['user','userVip','userBenefits','userOrders','userCommunity'];
if (in_array($type, $canvasBlockTypes, true)) {
    $guideFlowKey = admin_guide_flow_key((string)($row['page_key'] ?? ''), $id);
    admin_layout_start($row['label'], 'widget.php?id=' . $id, $id, '与画布属性一致；保存后 H5 前台同步（需已 Build 部署）。', $guideFlowKey);
    if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
    echo '<div class="card"><form method="post" class="form-grid"><input type="hidden" name="save_props" value="1">';
    if ($type === 'user') {
        echo '<label><input type="checkbox" name="prop_enableRegister" value="1"' . (!empty($props['enableRegister']) ? ' checked' : '') . '> 允许注册</label>';
        echo '<label><input type="checkbox" name="prop_enableWechatLogin" value="1"' . (!empty($props['enableWechatLogin']) ? ' checked' : '') . '> 允许微信登录</label>';
        echo '<p class="tip">用户头像/昵称等来自用户管理，此处仅配置登录能力。</p>';
    } else {
        admin_field_text('标题', 'prop_title', (string)($props['title'] ?? ''));
        if ($type !== 'userOrders') admin_field_text('副标题', 'prop_subtitle', (string)($props['subtitle'] ?? ''));
        if ($type === 'userVip') {
            admin_field_text('等级ID', 'prop_levelId', (string)(int)($props['levelId'] ?? 6), 'number');
            admin_field_select('支付方式', 'prop_payType', ['balance'=>'余额','points'=>'积分'], (string)($props['payType'] ?? 'balance'));
            admin_field_text('扣减余额', 'prop_deductAmount', (string)($props['deductAmount'] ?? 99), 'number', 'step="0.01"');
            admin_field_text('扣减积分', 'prop_deductPoints', (string)(int)($props['deductPoints'] ?? 999), 'number');
        }
        if ($type === 'userBenefits') {
            $itemsLines = implode("\n", (array)($props['items'] ?? []));
            admin_field_textarea('权益项（每行一条）', 'prop_items_lines', $itemsLines, 6);
        }
        if ($type === 'userCommunity') {
            admin_field_text('链接文案', 'prop_linkText', (string)($props['linkText'] ?? ''));
            admin_field_link('链接目标', $projectPages, $articleOptions, $productOptions, json_encode($props['link'] ?? ['type'=>'none'], JSON_UNESCAPED_UNICODE));
        }
        if ($type === 'userOrders') {
            echo '<p class="tip">订单 Tab 内容请在画布「编辑订单状态子页」中装修；此处仅改标题文案。</p>';
        }
    }
    echo '<p style="margin-top:16px"><button class="btn" type="submit">保存配置</button></p></form></div>';
    admin_layout_end();
    exit;
}
$flashProductRows = [];
if (in_array($type, ['flashSale', 'groupBuy'], true)) {
    $flashIds = $props['productIds'] ?? [];
    if (!is_array($flashIds)) $flashIds = [];
    $flashIds = array_values(array_filter(array_map('intval', $flashIds)));
    if ($flashIds) {
        $place = implode(',', array_fill(0, count($flashIds), '?'));
        $fs = $pdo->prepare("SELECT p.id,p.name,p.price,p.image FROM products p WHERE p.status=1 AND p.id IN ($place)");
        $fs->execute($flashIds);
        $fmap = [];
        foreach ($fs->fetchAll(PDO::FETCH_ASSOC) as $fp) $fmap[(int)$fp['id']] = $fp;
        foreach ($flashIds as $fpid) {
            if (isset($fmap[$fpid])) $flashProductRows[] = $fmap[$fpid];
        }
    }
}
if (!empty($_GET['msg'])) $msg = (string)$_GET['msg'];
$contentItemTypes = ['listMenu','statsRow','waterfall','gridNav','promoPair','featureCard','promoGrid','serviceCard','tabNav','filterBar','quiz'];
$guideCaption = '悬停标题旁「导」查看前台操作路径（先点底部 Tab，再定位组件）。下方表单与画布属性面板一致，无需 JSON。';
if ($type === 'pageHeader') {
    $guideCaption = '红圈标注「我」页顶部的搜索/品牌区域（页面头部）。Build 后会自动生成位置示意图。';
}
$guideFlowKey = admin_guide_flow_key((string)($row['page_key'] ?? ''), $id);
admin_layout_start($row['label'], 'widget.php?id=' . $id, $id, $guideCaption, $guideFlowKey);
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="post" class="form-grid">';
echo '<input type="hidden" name="save_props" value="1">';
if ($type === 'searchBar') {
    admin_field_text('占位符', 'prop_placeholder', (string)($props['placeholder'] ?? '搜索'));
    admin_field_color('背景色', 'prop_bgColor', (string)($props['bgColor'] ?? '#ffffff'));
    echo '<p class="tip">搜索栏仅显示占位符与背景色；页面大标题请改同页的「标题栏」组件。</p>';
} else {
    admin_field_text('标题', 'prop_title', (string)($props['title'] ?? ''));
    admin_field_text('副标题', 'prop_subtitle', (string)($props['subtitle'] ?? ''));
}
if (in_array($type, ['video'], true)) {
    admin_field_video('视频地址', 'prop_src', 'prop_video_src', (string)($props['src'] ?? ''));
    admin_field_image('封面图', 'prop_poster', 'prop_video_poster', (string)($props['poster'] ?? ''));
    admin_field_text('高度(rpx)', 'prop_height', (string)($props['height'] ?? 400), 'number');
}
if ($type === 'map') {
    admin_field_text('中心纬度', 'prop_latitude', (string)($props['latitude'] ?? '31.23'));
    admin_field_text('中心经度', 'prop_longitude', (string)($props['longitude'] ?? '121.47'));
    admin_field_text('缩放级别', 'prop_zoom', (string)($props['zoom'] ?? '14'), 'number');
    admin_field_text('高度(rpx)', 'prop_height', (string)($props['height'] ?? '400'), 'number');
    echo '<label><input type="checkbox" name="prop_showLocation" value="1"' . (!empty($props['showLocation']) ? ' checked' : '') . '> 显示当前位置</label>';
}
if ($type === 'serviceCard') {
    admin_field_link('卡片跳转', $projectPages, $articleOptions, $productOptions, json_encode($props['link'] ?? ['type'=>'none'], JSON_UNESCAPED_UNICODE));
    echo '<p class="tip">配置链接后，前台整张服务商卡片可点击跳转。</p>';
}
if ($type === 'promoPair') {
    echo '<label><input type="checkbox" name="prop_showCountdown" value="1"' . (!empty($props['showCountdown']) ? ' checked' : '') . '> 显示倒计时</label>';
    admin_field_datetime('倒计时结束时间', 'prop_countdownEnd', (string)($props['countdownEnd'] ?? ''));
    echo '<p class="tip">双列促销卡片条目请在下方「内容条目」中维护（标题、副标题、图片、背景色）。</p>';
}
if ($type === 'flashSale') {
    admin_field_color('背景色', 'prop_bgColor', (string)($props['bgColor'] ?? '#fff3e0'));
    echo '<label><input type="checkbox" name="prop_showCountdown" value="1"' . (!empty($props['showCountdown']) ? ' checked' : '') . '> 显示倒计时</label>';
    admin_field_datetime('倒计时结束', 'prop_countdownEnd', (string)($props['countdownEnd'] ?? ''));
    echo '<p class="tip">请在下方「秒杀商品」中搜索并添加参与秒杀的商品，无需填写「内容条目」。</p>';
}
if (in_array($type, ['groupBuy','liveEntry','walletCard','floatingButton','loginBanner'], true)) {
    admin_field_color('背景色', 'prop_bgColor', (string)($props['bgColor'] ?? '#ffffff'));
}
if ($type === 'rate') {
    admin_field_text('评价总数', 'prop_count', (string)($props['count'] ?? 0), 'number');
    admin_field_text('评价总分', 'prop_totalScore', (string)($props['totalScore'] ?? 0), 'number');
    admin_field_text('平均分(自动)', 'prop_score', (string)($props['score'] ?? 0), 'number');
    echo '<label><input type="checkbox" name="prop_showCount" value="1"' . (($props['showCount'] ?? true) ? ' checked' : '') . '> 显示评价数</label>';
    echo '<label><input type="checkbox" name="prop_allowUserRate" value="1"' . (($props['allowUserRate'] ?? true) ? ' checked' : '') . '> 允许用户评分</label>';
}
if ($type === 'waterfall' || $type === 'gridNav') {
    echo '<p class="tip">条目内容请在下方「内容条目」中维护；保存后 H5 与小程序同步更新。</p>';
}
if ($type === 'listMenu') {
    echo '<label><input type="checkbox" name="prop_showArrow" value="1"' . (($props['showArrow'] ?? true) ? ' checked' : '') . '> 显示右侧箭头</label>';
    echo '<p class="tip">菜单项请在下方维护（图标、文字、跳转链接），与画布一致。</p>';
}
if ($type === 'statsRow') {
    admin_field_text('列数', 'prop_columns', (string)($props['columns'] ?? 3), 'number');
}
echo '<p style="margin-top:16px"><button class="btn" type="submit">保存配置</button></p></form></div>';
if (in_array($type, ['flashSale', 'groupBuy'], true)) {
    $entryLabel = $type === 'groupBuy' ? '拼团' : '秒杀';
    $flashIdsJson = json_encode(array_map(function($p){ return (int)$p['id']; }, $flashProductRows), JSON_UNESCAPED_UNICODE);
    echo '<div class="card"><h3 class="guide-heading">' . $entryLabel . '商品（子页固定列表，一行一个，默认6个）</h3>';
    echo '<p class="tip">此处配置会同步到「' . $entryLabel . '子页」不可删除的商品列表组件；可搜索添加、移除、保存顺序。</p>';
    echo '<form method="post" class="form-grid" id="flashSaleForm" onsubmit="document.getElementById(\'flashProductIdsJson\').value=JSON.stringify(Array.from(document.querySelectorAll(\'#flash-products tr[data-id]\')).map(function(tr){return parseInt(tr.getAttribute(\'data-id\'),10);}));return true;">';
    echo '<input type="hidden" name="save_props" value="1">';
    echo '<input type="hidden" name="prop_product_ids_json" id="flashProductIdsJson" value="' . htmlspecialchars($flashIdsJson) . '">';
    echo '<input type="hidden" name="prop_title" value="' . htmlspecialchars((string)($props['title'] ?? '')) . '">';
    echo '<input type="hidden" name="prop_subtitle" value="' . htmlspecialchars((string)($props['subtitle'] ?? '')) . '">';
    echo '<table id="flash-products"><thead><tr><th style="width:36px"></th><th>ID</th><th>商品</th><th>价格</th><th>操作</th></tr></thead><tbody>';
    if (!$flashProductRows) {
        echo '<tr><td colspan="5" style="color:#999">暂无' . $entryLabel . '商品，请下方搜索添加</td></tr>';
    } else {
        foreach ($flashProductRows as $fp) {
            echo '<tr data-id="' . (int)$fp['id'] . '"><td class="drag-handle" style="cursor:grab;color:#999">≡</td><td>' . (int)$fp['id'] . '</td><td>' . htmlspecialchars($fp['name']) . '</td><td>¥' . htmlspecialchars((string)$fp['price']) . '</td>';
            echo '<td><a class="btn btn-sm btn-danger" href="?remove_product=' . (int)$fp['id'] . '&' . htmlspecialchars($flashQs) . '">移除</a></td></tr>';
        }
    }
    echo '</tbody></table><p style="margin-top:12px"><button class="btn btn-sm" type="submit">保存商品顺序</button></p></form>';
    echo '<form method="get" class="form-grid" style="margin-top:16px"><input type="hidden" name="id" value="' . htmlspecialchars($id) . '">';
    admin_field_text('搜索商品', 'q', trim((string)($_GET['q'] ?? '')));
    echo '<p><button class="btn btn-sm btn-secondary" type="submit">搜索</button></p></form>';
    $sq = trim((string)($_GET['q'] ?? ''));
    if ($sq !== '') {
        $ss = $pdo->prepare('SELECT id,name,price FROM products WHERE status=1 AND name LIKE ? ORDER BY id DESC LIMIT 30');
        $ss->execute(['%' . $sq . '%']);
        foreach ($ss->fetchAll(PDO::FETCH_ASSOC) as $sr) {
            echo '<p style="display:flex;gap:8px;align-items:center;margin:8px 0"><span>#' . (int)$sr['id'] . ' ' . htmlspecialchars($sr['name']) . ' ¥' . htmlspecialchars((string)$sr['price']) . '</span>';
            echo '<a class="btn btn-sm" href="?add_product=' . (int)$sr['id'] . '&' . htmlspecialchars($flashQs) . '">添加</a></p>';
        }
    }
    echo '</div>';
}
if (in_array($type, ['flashSale', 'groupBuy'], true)) {
    echo '<script src="' . htmlspecialchars(asset_url('../assets/vendor/sortablejs/Sortable.min.js')) . '"></script>';
    echo '<script>
var flashProductsEl=document.getElementById("flash-products");
if(flashProductsEl){
  var flashBody=flashProductsEl.querySelector("tbody");
  if(flashBody&&flashBody.querySelector("tr[data-id]")){
    Sortable.create(flashBody,{handle:".drag-handle",animation:150,ghostClass:"sortable-ghost"});
  }
}
</script>';
}
if (in_array($type, $contentItemTypes, true)) {
    echo '<div class="card"><h3 class="guide-heading">内容条目</h3>';
    if ($itemRows) {
        foreach ($itemRows as $itemIdx => $it) {
            $data = json_decode($it['item_json'] ?? '{}', true) ?: [];
            $itemLinkPrefix = 'it' . (int)$it['id'] . '_';
            echo '<form method="post" class="form-grid item-edit-card" style="border:1px solid #eee;border-radius:8px;padding:12px;margin-bottom:12px">';
            echo '<input type="hidden" name="save_item" value="1"><input type="hidden" name="item_id" value="' . (int)$it['id'] . '">';
            echo '<p style="margin:0 0 8px;font-weight:600">条目 #' . ((int)$itemIdx + 1) . '</p>';
            if ($type === 'listMenu') {
                admin_field_text('图标(emoji/文字)', 'item_icon', (string)($data['icon'] ?? ''));
                admin_field_text('菜单文字', 'item_text', (string)($data['text'] ?? ''));
                admin_field_text('右侧文字', 'item_value', (string)($data['value'] ?? ''));
                admin_field_link('跳转链接', $projectPages, $articleOptions, $productOptions, json_encode($data['link'] ?? ['type'=>'none'], JSON_UNESCAPED_UNICODE), $itemLinkPrefix);
            } elseif ($type === 'statsRow') {
                admin_field_text('标签', 'item_label', (string)($data['label'] ?? ''));
                admin_field_text('数值', 'item_text', (string)($data['value'] ?? ''));
                admin_field_text('单位', 'item_subtitle', (string)($data['unit'] ?? ''));
            } elseif ($type === 'waterfall') {
                admin_field_text('标题', 'item_title', (string)($data['title'] ?? ''));
                admin_field_image('图片', 'item_image', 'item_image_' . (int)$it['id'], (string)($data['image'] ?? ''));
                admin_field_text('高度(px)', 'item_height', (string)($data['height'] ?? 180), 'number');
                echo '<label><input type="checkbox" name="item_isVideo" value="1"' . (!empty($data['isVideo']) ? ' checked' : '') . '> 视频标记</label>';
            } elseif ($type === 'gridNav') {
                admin_field_text('文字', 'item_text', (string)($data['text'] ?? ''));
                admin_field_image('图标', 'item_icon', 'item_icon_' . (int)$it['id'], (string)($data['icon'] ?? ''));
                admin_field_color('背景色', 'item_bgColor', (string)($data['bgColor'] ?? '#f5f7fa'));
                admin_field_link('跳转链接', $projectPages, $articleOptions, $productOptions, json_encode($data['link'] ?? ['type'=>'none'], JSON_UNESCAPED_UNICODE), $itemLinkPrefix);
            } elseif ($type === 'promoPair') {
                admin_field_text('标题', 'item_title', (string)($data['title'] ?? ''));
                admin_field_text('副标题', 'item_subtitle', (string)($data['subtitle'] ?? ''));
                admin_field_image('图片', 'item_image', 'item_image_' . (int)$it['id'], (string)($data['image'] ?? ''));
                admin_field_color('背景色', 'item_bgColor', (string)($data['bgColor'] ?? '#f5f5f5'));
                admin_field_link('跳转链接', $projectPages, $articleOptions, $productOptions, json_encode($data['link'] ?? ['type'=>'none'], JSON_UNESCAPED_UNICODE), $itemLinkPrefix);
            } elseif ($type === 'serviceCard') {
                admin_field_text('名称', 'item_title', (string)($data['name'] ?? ''));
                admin_field_image('Logo', 'item_logo', 'item_logo_' . (int)$it['id'], (string)($data['logo'] ?? ''));
                admin_field_image('展示图1', 'item_image', 'item_image_' . (int)$it['id'], (string)(($data['images'] ?? [])[0] ?? ''));
                admin_field_image('展示图2', 'item_image2', 'item_image2_' . (int)$it['id'], (string)(($data['images'] ?? [])[1] ?? ''));
                admin_field_image('展示图3', 'item_image3', 'item_image3_' . (int)$it['id'], (string)(($data['images'] ?? [])[2] ?? ''));
                admin_field_text('距离', 'item_subtitle', (string)($data['distance'] ?? ''));
            } elseif ($type === 'featureCard') {
                admin_field_text('标题', 'item_title', (string)($data['title'] ?? ''));
                admin_field_text('副标题', 'item_subtitle', (string)($data['subtitle'] ?? ''));
                admin_field_image('图片', 'item_image', 'item_image_' . (int)$it['id'], (string)($data['image'] ?? ''));
                admin_field_link('跳转链接', $projectPages, $articleOptions, $productOptions, json_encode($data['link'] ?? ['type'=>'none'], JSON_UNESCAPED_UNICODE), $itemLinkPrefix);
            } elseif ($type === 'promoGrid') {
                admin_field_text('标题/名称', 'item_title', (string)($data['title'] ?? $data['text'] ?? $data['name'] ?? ''));
                admin_field_text('副标题/文字', 'item_subtitle', (string)($data['subtitle'] ?? $data['text'] ?? ''));
                admin_field_image('图片', 'item_image', 'item_image_' . (int)$it['id'], (string)($data['image'] ?? ''));
                admin_field_link('跳转链接', $projectPages, $articleOptions, $productOptions, json_encode($data['link'] ?? ['type'=>'none'], JSON_UNESCAPED_UNICODE), $itemLinkPrefix);
            } else {
                admin_field_text('标题/名称', 'item_title', (string)($data['title'] ?? $data['text'] ?? $data['name'] ?? ''));
                admin_field_text('副标题/文字', 'item_subtitle', (string)($data['subtitle'] ?? $data['text'] ?? ''));
            }
            admin_field_text('排序', 'sort_order', (string)(int)$it['sort_order'], 'number');
            echo '<p style="display:flex;gap:8px;align-items:center">';
            echo '<button class="btn btn-sm" type="submit">保存条目</button>';
            echo '<a class="btn btn-sm btn-danger" href="?id=' . urlencode($id) . '&del_item=' . (int)$it['id'] . '" onclick="return confirm(\'确认删除?\')">删除</a>';
            echo '</p></form>';
        }
    } else {
        echo '<p style="color:#999">暂无条目，可在下方添加。</p>';
    }
    echo '<div class="card" style="background:#fafafa"><h4 style="margin:0 0 12px">添加条目</h4>';
    echo '<form method="post" class="form-grid">';
    echo '<input type="hidden" name="add_item" value="1">';
    if ($type === 'listMenu') {
        admin_field_text('图标', 'item_icon', '•');
        admin_field_text('菜单文字', 'item_text');
        admin_field_text('右侧文字', 'item_value');
        admin_field_link('跳转链接', $projectPages, $articleOptions, $productOptions, '');
    } elseif ($type === 'statsRow') {
        admin_field_text('标签', 'item_label');
        admin_field_text('数值', 'item_text');
        admin_field_text('单位', 'item_subtitle');
    } elseif ($type === 'waterfall') {
        admin_field_text('标题', 'item_title');
        admin_field_image('图片', 'item_image', 'item_image_new', '');
        admin_field_text('高度', 'item_height', '180', 'number');
        echo '<label><input type="checkbox" name="item_isVideo" value="1"> 视频</label>';
    } elseif ($type === 'gridNav') {
        admin_field_text('文字', 'item_text');
        admin_field_image('图标', 'item_icon', 'item_icon_new', '');
        admin_field_color('背景色', 'item_bgColor', '#f5f7fa');
        admin_field_link('跳转链接', $projectPages, $articleOptions, $productOptions, '');
    } elseif ($type === 'promoPair') {
        admin_field_text('标题', 'item_title');
        admin_field_text('副标题', 'item_subtitle');
        admin_field_image('图片', 'item_image', 'item_image_new', '');
        admin_field_color('背景色', 'item_bgColor', '#f5f5f5');
        admin_field_link('跳转链接', $projectPages, $articleOptions, $productOptions, '');
    } elseif ($type === 'serviceCard') {
        admin_field_text('名称', 'item_title');
        admin_field_image('Logo', 'item_logo', 'item_logo_new', '');
        admin_field_image('展示图1', 'item_image', 'item_image_new', '');
        admin_field_image('展示图2', 'item_image2', 'item_image2_new', '');
        admin_field_image('展示图3', 'item_image3', 'item_image3_new', '');
    } elseif ($type === 'featureCard') {
        admin_field_text('标题', 'item_title');
        admin_field_text('副标题', 'item_subtitle');
        admin_field_image('图片', 'item_image', 'item_image_new', '');
        admin_field_link('跳转链接', $projectPages, $articleOptions, $productOptions, '');
    } elseif ($type === 'promoGrid') {
        admin_field_text('标题/名称', 'item_title');
        admin_field_text('副标题/文字', 'item_subtitle');
        admin_field_image('图片', 'item_image', 'item_image_new', '');
        admin_field_link('跳转链接', $projectPages, $articleOptions, $productOptions, '');
    } else {
        admin_field_text('标题/名称', 'item_title');
        admin_field_text('副标题/文字', 'item_subtitle');
        if (in_array($type, ['featureCard','promoGrid','serviceCard'], true)) {
            admin_field_image('图片', 'item_image', 'item_image_new', '');
        }
    }
    if ($type === 'map') {
        admin_field_text('纬度', 'item_lat');
        admin_field_text('经度', 'item_lng');
        admin_field_text('地址', 'item_address');
    }
    admin_field_text('排序', 'sort_order', '0', 'number');
    echo '<p><button class="btn" type="submit">添加条目</button></p></form></div></div>';
}
admin_layout_end();

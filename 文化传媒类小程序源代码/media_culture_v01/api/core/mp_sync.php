<?php
/**
 * 筑码引擎 www.402.cn
 */

/** 同步 AppID / API 地址到 frontend/mp-weixin（project.config.json + utils/mp_config.js） */

function wx_platform_link(string $label = '微信公众平台'): string {
    return '<a href="https://mp.weixin.qq.com/" target="_blank" rel="noopener">' . htmlspecialchars($label) . '</a>';
}

function wx_mch_link(string $label = '微信商户平台'): string {
    return '<a href="https://pay.weixin.qq.com/" target="_blank" rel="noopener">' . htmlspecialchars($label) . '</a>';
}

/** 站点根域名，去掉末尾 /api（用户填 https://www.xx.com 即可） */
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

function mp_api_base_from_site(string $siteUrl): string {
    $siteUrl = site_base_url_normalize($siteUrl);
    if ($siteUrl === '') {
        return '';
    }
    return $siteUrl . '/api';
}

function mp_config_file_writable(string $path): bool {
    if (is_file($path)) {
        if (!is_writable($path)) {
            @chmod($path, 0666);
        }
        return is_writable($path);
    }
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!is_writable($dir)) {
        @chmod($dir, 0777);
    }
    return is_writable($dir);
}

function sync_mp_weixin_config(string $appId, string $siteUrl): bool {
    $root = dirname(__DIR__, 2);
    $mpRoot = $root . '/frontend/mp-weixin';
    if (!is_dir($mpRoot)) {
        return true;
    }
    $ok = true;
    $apiBase = mp_api_base_from_site($siteUrl);

    $mpConfigPath = $mpRoot . '/utils/mp_config.js';
    if (!mp_config_file_writable($mpConfigPath)) {
        return false;
    }
    $escapedApi = str_replace(['\\', "'"], ['\\\\', "\\'"], $apiBase);
    $siteRoot = site_base_url_normalize($siteUrl);
    $escapedSite = str_replace(['\\', "'"], ['\\\\', "\\'"], $siteRoot);
    $js = "// 小程序 API 根地址（与 install.php / 站点设置同步，可手动修改）\nmodule.exports = {\n  apiBase: '" . $escapedApi . "',\n  siteRoot: '" . $escapedSite . "',\n  assetRoot: '" . $escapedSite . "',\n};\n";
    if (@file_put_contents($mpConfigPath, $js, LOCK_EX) === false) {
        $ok = false;
    }

    $projPath = $mpRoot . '/project.config.json';
    if (!mp_config_file_writable($projPath)) {
        return false;
    }
    $json = [];
    if (is_file($projPath)) {
        $decoded = json_decode((string)file_get_contents($projPath), true);
        if (is_array($decoded)) {
            $json = $decoded;
        }
    }
    if (!$json) {
        $json = [
            'description' => '由低代码平台生成',
            'projectname' => 'wechat-app',
            'compileType' => 'miniprogram',
            'setting' => [
                'urlCheck' => false,
                'es6' => true,
                'enhance' => true,
                'postcss' => true,
                'minified' => true,
                'minifyWXSS' => true,
                'minifyWXML' => true,
            ],
        ];
    }
    if (!isset($json['setting']) || !is_array($json['setting'])) {
        $json['setting'] = [];
    }
    foreach (['es6' => true, 'enhance' => true, 'postcss' => true, 'minified' => true, 'minifyWXSS' => true, 'minifyWXML' => true] as $k => $v) {
        if (!isset($json['setting'][$k])) {
            $json['setting'][$k] = $v;
        }
    }
    $json['appid'] = $appId;
    if (@file_put_contents($projPath, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
        $ok = false;
    }
    return $ok;
}

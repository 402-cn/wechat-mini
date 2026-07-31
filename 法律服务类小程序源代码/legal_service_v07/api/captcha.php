<?php
/**
 * 筑码引擎 www.402.cn
 */

session_start();
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 4; $i++) $code .= $chars[random_int(0, strlen($chars)-1)];
$_SESSION['captcha'] = $code;
header('Content-Type: image/png');
$img = imagecreatetruecolor(120, 40);
$bg = imagecolorallocate($img, 245, 245, 245);
$fg = imagecolorallocate($img, 46, 204, 113);
imagefill($img, 0, 0, $bg);
imagestring($img, 5, 28, 12, $code, $fg);
imagepng($img);
imagedestroy($img);

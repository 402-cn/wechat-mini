<?php
/**
 * 筑码引擎 www.402.cn
 */

/** 上传图片压缩与缩略图（H5 最大宽 430px，>200KB 压缩，缩略图宽 100px） */
function image_thumb_url(string $url): string {
    if ($url === '' || preg_match('/\.svg$/i', $url)) return $url;
    return preg_replace('/(\.[^.]+)$/', '_thumb$1', $url);
}

function image_load_from_path(string $path) {
    $info = @getimagesize($path);
    if (!$info) return [null, ''];
    switch ($info['mime']) {
        case 'image/jpeg': return [imagecreatefromjpeg($path), 'jpeg'];
        case 'image/png': return [imagecreatefrompng($path), 'png'];
        case 'image/gif': return [imagecreatefromgif($path), 'gif'];
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) return [imagecreatefromwebp($path), 'webp'];
    }
    return [null, ''];
}

function image_resize_copy($src, int $sw, int $sh, int $dw, int $dh) {
    $dst = imagecreatetruecolor($dw, $dh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefill($dst, 0, 0, $transparent);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $dw, $dh, $sw, $sh);
    return $dst;
}

function image_save_jpeg($img, string $path, int $quality): bool {
    return imagejpeg($img, $path, $quality);
}

function image_process_uploaded_file(string $path): array {
    $maxW = 430;
    $maxBytes = 204800;
    $thumbW = 100;
    if (!is_file($path)) return ['path' => $path, 'thumb' => '', 'size' => 0];
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === 'svg') {
        return ['path' => $path, 'thumb' => '', 'size' => filesize($path)];
    }
    [$src, $fmt] = image_load_from_path($path);
    if (!$src) {
        return ['path' => $path, 'thumb' => '', 'size' => filesize($path)];
    }
    $sw = imagesx($src);
    $sh = imagesy($src);
    $dw = $sw;
    $dh = $sh;
    if ($sw > $maxW) {
        $dw = $maxW;
        $dh = (int)max(1, round($sh * $maxW / $sw));
    }
    $main = ($dw === $sw && $dh === $sh) ? $src : image_resize_copy($src, $sw, $sh, $dw, $dh);
    if ($main !== $src) imagedestroy($src);

    $outPath = $path;
    $useJpeg = in_array($fmt, ['png', 'webp', 'gif'], true) || filesize($path) > $maxBytes;
    if ($useJpeg) {
        $outPath = preg_replace('/\.[^.]+$/', '.jpg', $path);
    }
    $quality = 88;
    $saved = false;
    while ($quality >= 55) {
        if ($useJpeg) {
            $saved = image_save_jpeg($main, $outPath, $quality);
        } elseif ($fmt === 'png') {
            $saved = imagepng($main, $outPath, 6);
        } else {
            $saved = image_save_jpeg($main, $outPath, $quality);
        }
        if (!$saved) break;
        clearstatcache(true, $outPath);
        if (filesize($outPath) <= $maxBytes) break;
        $quality -= 8;
        $useJpeg = true;
        if ($outPath === $path && $fmt !== 'jpeg') {
            $outPath = preg_replace('/\.[^.]+$/', '.jpg', $path);
        }
    }
    if ($outPath !== $path && is_file($path)) @unlink($path);

    $th = (int)max(1, round($dh * $thumbW / $dw));
    $thumbImg = image_resize_copy($main, $dw, $dh, $thumbW, $th);
    $thumbPath = preg_replace('/(\.[^.]+)$/', '_thumb$1', $outPath);
    if (preg_match('/\.jpe?g$/i', $outPath)) {
        $thumbPath = preg_replace('/\.jpe?g$/i', '_thumb.jpg', $outPath);
    }
    image_save_jpeg($thumbImg, $thumbPath, 82);
    imagedestroy($thumbImg);
    imagedestroy($main);

    clearstatcache(true, $outPath);
    return ['path' => $outPath, 'thumb' => $thumbPath, 'size' => (int)filesize($outPath)];
}

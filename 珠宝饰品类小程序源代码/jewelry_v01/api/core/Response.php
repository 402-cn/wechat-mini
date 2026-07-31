<?php
/**
 * 筑码引擎 www.402.cn
 */

function json_ok($data = [], string $message = '操作成功'): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 0, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $code = 400): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => $code, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

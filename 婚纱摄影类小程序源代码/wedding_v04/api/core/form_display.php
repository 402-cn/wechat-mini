<?php
/**
 * 筑码引擎 www.402.cn
 */

function form_fields_by_key(array $fields): array {
    $map = [];
    foreach ($fields as $f) { if (!empty($f['key'])) $map[$f['key']] = $f; }
    return $map;
}

function form_option_label_map(array $field): array {
    $map = [];
    foreach (($field['options'] ?? []) as $opt) {
        $v = (string)($opt['value'] ?? '');
        $l = (string)($opt['label'] ?? $v);
        if ($v !== '') $map[$v] = $l;
    }
    return $map;
}

function form_format_display_value(string $col, $value, array $fieldsByKey): string {
    if ($value === null || $value === '') return '';
    $field = $fieldsByKey[$col] ?? null;
    if (!$field) return (string)$value;
    $type = $field['type'] ?? 'text';
    if (!in_array($type, ['radio', 'select', 'checkbox'], true)) return (string)$value;
    $optMap = form_option_label_map($field);
    if ($type === 'checkbox') {
        $arr = json_decode((string)$value, true);
        if (!is_array($arr)) $arr = [(string)$value];
        $labels = [];
        foreach ($arr as $v) { $labels[] = $optMap[(string)$v] ?? (string)$v; }
        return implode('、', $labels);
    }
    return $optMap[(string)$value] ?? (string)$value;
}

function form_load_site_name(): string {
    $path = dirname(__DIR__, 2) . '/migrations/manifest.json';
    $raw = @file_get_contents($path);
    if ($raw) {
        $data = json_decode($raw, true);
        $name = trim((string)($data['siteName'] ?? ''));
        if ($name !== '') return $name;
    }
    return '站点';
}

function form_export_filename(?string $siteName = null): string {
    $name = trim((string)($siteName ?? form_load_site_name()));
    $name = preg_replace('/[\\\\\/:*?"<>|]/u', '', $name);
    if ($name === '') $name = '站点';
    return $name . '_数据导出_' . date('Ymd') . '.csv';
}

function form_send_csv_headers(string $filename): void {
    $ascii = 'export_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"$ascii\"; filename*=UTF-8''" . rawurlencode($filename));
}

function form_format_csv_value(string $col, $value, array $fieldsByKey): string {
    $text = form_format_display_value($col, $value, $fieldsByKey);
    if ($text === '') return '';
    $field = $fieldsByKey[$col] ?? null;
    $type = $field['type'] ?? '';
    if (in_array($type, ['phone', 'number'], true) || preg_match('/^1[3-9]\d{9}$/', $text)) {
        return "\t" . $text;
    }
    return $text;
}

function form_placeholder_setting_key(string $formId): string {
    return 'form_placeholders_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $formId);
}

function form_load_placeholders(PDO $pdo, string $formId): array {
    if (!$pdo->query("SHOW TABLES LIKE 'site_settings'")->fetch()) return [];
    $key = form_placeholder_setting_key($formId);
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $raw = $stmt->fetchColumn();
    if (!$raw) return [];
    $decoded = json_decode((string)$raw, true);
    return is_array($decoded) ? $decoded : [];
}

function form_save_placeholders(PDO $pdo, string $formId, array $overrides): void {
    if (!$pdo->query("SHOW TABLES LIKE 'site_settings'")->fetch()) return;
    $key = form_placeholder_setting_key($formId);
    $json = json_encode($overrides, JSON_UNESCAPED_UNICODE);
    $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$key, $json]);
}

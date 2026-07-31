<?php
/**
 * 筑码引擎 www.402.cn
 */

/** 表单表结构自动补齐（覆盖部署后新增字段时执行） */
function form_field_sql_type(string $type): string {
    switch ($type) {
        case 'textarea': case 'checkbox': return 'TEXT NULL';
        case 'number': return 'INT NULL DEFAULT NULL';
        case 'phone': return "VARCHAR(20) NOT NULL DEFAULT ''";
        case 'email': return "VARCHAR(200) NOT NULL DEFAULT ''";
        case 'radio': case 'select': return "VARCHAR(200) NOT NULL DEFAULT ''";
        case 'date': case 'datetime': return "VARCHAR(50) NOT NULL DEFAULT ''";
        default: return "VARCHAR(500) NOT NULL DEFAULT ''";
    }
}

function ensure_form_table_columns(PDO $pdo, string $table, array $fields): void {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) throw new InvalidArgumentException('invalid table');
    $existing = [];
    $stmt = $pdo->query('SHOW COLUMNS FROM ' . quote_table_name($table));
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $existing[$row['Field']] = true; }
    foreach ($fields as $field) {
        $key = $field['key'] ?? '';
        if ($key === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $key) || isset($existing[$key])) continue;
        $type = $field['type'] ?? 'text';
        $label = str_replace("'", "''", (string)($field['label'] ?? $key));
        $sqlType = form_field_sql_type($type);
        $pdo->exec('ALTER TABLE ' . quote_table_name($table) . ' ADD COLUMN ' . quote_table_name($key) . ' ' . $sqlType . " COMMENT '" . $label . "'");
        $existing[$key] = true;
    }
}

function quote_table_name(string $name): string { return '`' . $name . '`'; }

<?php
$wp_root = $argv[1] ?? '/Applications/XAMPP/xamppfiles/htdocs/pw-ofertas';
$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require $wp_root . '/wp-load.php';

global $wpdb;

echo "=== Recent campaigns ===\n";
$rows = $wpdb->get_results("
    SELECT id, name, strategy, active, deleted_at, start_date, end_date, conditions
    FROM {$wpdb->prefix}pwoa_campaigns
    ORDER BY id DESC LIMIT 10
");
foreach ($rows as $r) {
    echo "ID={$r->id} strategy={$r->strategy} active={$r->active} deleted=" . ($r->deleted_at ? 'YES' : 'NO') . " start={$r->start_date} end={$r->end_date}\n";
    echo "  conditions={$r->conditions}\n";
}

echo "\n=== getActive() result ===\n";
$active = $wpdb->get_results("
    SELECT id, name, strategy, active, deleted_at
    FROM {$wpdb->prefix}pwoa_campaigns
    WHERE active = 1
    AND deleted_at IS NULL
    AND (start_date IS NULL OR start_date <= NOW())
    AND (end_date IS NULL OR end_date >= NOW())
    ORDER BY priority DESC
");
foreach ($active as $r) {
    echo "ID={$r->id} strategy={$r->strategy}\n";
}
echo "Total active: " . count($active) . "\n";

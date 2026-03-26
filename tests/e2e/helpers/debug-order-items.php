<?php
$wp_root = $argv[1] ?? '/Applications/XAMPP/xamppfiles/htdocs/pw-ofertas';
$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require $wp_root . '/wp-load.php';

global $wpdb;

// Show all order items for HPOS orders of user_id=1
$rows = $wpdb->get_results(
    "SELECT oi.order_id, oi.order_item_type, oim.meta_key, oim.meta_value
     FROM {$wpdb->prefix}woocommerce_order_items oi
     LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
     JOIN {$wpdb->prefix}wc_orders o ON oi.order_id = o.id
     WHERE o.customer_id = 1
       AND o.type = 'shop_order'
       AND o.status IN ('wc-completed','wc-processing')
       AND oi.order_item_type = 'line_item'
       AND oim.meta_key IN ('_product_id', '_qty')
     ORDER BY oi.order_id, oim.meta_key"
);

echo "Order line items for user_id=1:\n";
foreach ($rows as $r) {
    echo "  order_id={$r->order_id} {$r->meta_key}={$r->meta_value}\n";
}
echo "Total rows: " . count($rows) . "\n";

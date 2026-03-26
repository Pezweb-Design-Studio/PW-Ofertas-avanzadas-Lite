<?php
$wp_root = $argv[1] ?? '/Applications/XAMPP/xamppfiles/htdocs/pw-ofertas';
$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require $wp_root . '/wp-load.php';

global $wpdb;
$hpos = $wpdb->prefix . 'wc_orders';

// All recent order items (IDs > 150)
$rows = $wpdb->get_results("
    SELECT oi.order_id, oim.meta_key, oim.meta_value, o.customer_id, o.status
    FROM {$wpdb->prefix}woocommerce_order_items oi
    LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
    JOIN {$hpos} o ON oi.order_id = o.id
    WHERE oi.order_id > 150
      AND oi.order_item_type = 'line_item'
      AND oim.meta_key IN ('_product_id','_qty')
    ORDER BY oi.order_id, oim.meta_key
");

foreach ($rows as $r) {
    echo "order_id={$r->order_id} cust={$r->customer_id} status={$r->status} {$r->meta_key}={$r->meta_value}\n";
}
echo "Rows: " . count($rows) . "\n";

// Also run the HPOS query for any product > 150
$product_ids = $wpdb->get_col("
    SELECT DISTINCT oim.meta_value
    FROM {$wpdb->prefix}woocommerce_order_itemmeta oim
    JOIN {$wpdb->prefix}woocommerce_order_items oi ON oim.order_item_id = oi.order_item_id
    WHERE oim.meta_key = '_product_id' AND CAST(oim.meta_value AS UNSIGNED) > 150
");
echo "Product IDs in recent orders: " . implode(',', $product_ids) . "\n";

foreach ($product_ids as $pid) {
    $count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT oi.order_id)
         FROM {$wpdb->prefix}woocommerce_order_items AS oi
         INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim
             ON oi.order_item_id = oim.order_item_id AND oim.meta_key = '_product_id'
         INNER JOIN {$hpos} AS o ON oi.order_id = o.id
         WHERE o.type = 'shop_order'
           AND o.status IN ('wc-completed', 'wc-processing')
           AND o.customer_id = 1
           AND oim.meta_value = %d",
        (int) $pid
    ));
    echo "  HPOS count for product_id=$pid, user_id=1: $count\n";
}

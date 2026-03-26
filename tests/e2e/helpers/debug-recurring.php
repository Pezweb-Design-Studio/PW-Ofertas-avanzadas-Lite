<?php
/**
 * Diagnostico: verifica las ordenes del usuario admin para recurring_purchase.
 * Uso: php debug-recurring.php /path/to/wp-root user_id product_id
 */
$wp_root    = $argv[1] ?? '/Applications/XAMPP/xamppfiles/htdocs/pw-ofertas';
$user_id    = (int) ($argv[2] ?? 1);
$product_id = (int) ($argv[3] ?? 0);

$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/';

require $wp_root . '/wp-load.php';

global $wpdb;

// 1. Verificar si wp_wc_orders existe (HPOS)
$hpos_table  = $wpdb->prefix . 'wc_orders';
$hpos_exists = $wpdb->get_var("SHOW TABLES LIKE '{$hpos_table}'") === $hpos_table;
echo "HPOS table ({$hpos_table}) exists: " . ($hpos_exists ? 'YES' : 'NO') . "\n";

// 2. Contar ordenes en wp_posts para user_id
$legacy_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}posts p
     JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
     WHERE p.post_type = 'shop_order'
     AND p.post_status IN ('wc-completed','wc-processing')
     AND pm.meta_key = '_customer_user' AND pm.meta_value = %d",
    $user_id
));
echo "Orders in wp_posts for user_id={$user_id}: {$legacy_count}\n";

// 3. Si HPOS, contar en wp_wc_orders
if ($hpos_exists) {
    $hpos_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$hpos_table} WHERE type='shop_order'
         AND status IN ('wc-completed','wc-processing') AND customer_id = %d",
        $user_id
    ));
    echo "Orders in wp_wc_orders for user_id={$user_id}: {$hpos_count}\n";

    // 4. HPOS purchase count for product
    if ($product_id > 0) {
        $hpos_product_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT oi.order_id)
             FROM {$wpdb->prefix}woocommerce_order_items AS oi
             INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim
                 ON oi.order_item_id = oim.order_item_id AND oim.meta_key = '_product_id'
             INNER JOIN {$hpos_table} AS o ON oi.order_id = o.id
             WHERE o.type = 'shop_order'
               AND o.status IN ('wc-completed', 'wc-processing')
               AND o.customer_id = %d
               AND oim.meta_value = %d",
            $user_id, $product_id
        ));
        echo "HPOS purchase count for product_id={$product_id}: {$hpos_product_count}\n";
    }
}

// 5. wc_get_orders
$order_ids = wc_get_orders([
    'customer' => $user_id,
    'status'   => ['completed', 'processing'],
    'limit'    => -1,
    'return'   => 'ids',
    'type'     => 'shop_order',
]);
echo "wc_get_orders(customer={$user_id}): " . count($order_ids) . " orders: [" . implode(',', $order_ids) . "]\n";

// 6. Mostrar todas las ordenes del sistema
$all = $wpdb->get_results("SELECT ID, post_status, post_type FROM {$wpdb->prefix}posts WHERE post_type='shop_order' LIMIT 20");
echo "All shop_orders in wp_posts: " . count($all) . "\n";
foreach ($all as $row) {
    echo "  ID={$row->ID} status={$row->post_status}\n";
}

if ($hpos_exists) {
    $all_hpos = $wpdb->get_results("SELECT id, status, customer_id FROM {$hpos_table} WHERE type='shop_order' LIMIT 20");
    echo "All shop_orders in wp_wc_orders: " . count($all_hpos) . "\n";
    foreach ($all_hpos as $row) {
        echo "  ID={$row->id} status={$row->status} customer_id={$row->customer_id}\n";
    }
}

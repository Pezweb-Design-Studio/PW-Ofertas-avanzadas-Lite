<?php
/**
 * Crea 3 ordenes de prueba para user_id=1 con un product_id dado,
 * luego verifica la query HPOS, luego las elimina.
 */
$wp_root    = $argv[1] ?? '/Applications/XAMPP/xamppfiles/htdocs/pw-ofertas';
$product_id = (int) ($argv[2] ?? 0);

if (!$product_id) { echo "Uso: php debug-hpos-query.php /wp-root product_id\n"; exit(1); }

$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require $wp_root . '/wp-load.php';

global $wpdb;
$user_id = 1;

// Crear 3 ordenes de prueba
$order_ids = [];
for ($i = 0; $i < 3; $i++) {
    $order = wc_create_order(['customer_id' => $user_id, 'status' => 'completed']);
    $product = wc_get_product($product_id);
    if ($product) {
        $order->add_product($product, 1);
    }
    $order->set_billing_email('debug-test@example.com');
    $order->save();
    $order_ids[] = $order->get_id();
    echo "Created order ID: {$order->get_id()} for product_id=$product_id\n";
}

// Verificar los items en DB
$items = $wpdb->get_results($wpdb->prepare(
    "SELECT oi.order_id, oim.meta_key, oim.meta_value
     FROM {$wpdb->prefix}woocommerce_order_items oi
     JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
     WHERE oi.order_id IN (" . implode(',', $order_ids) . ")
     AND oim.meta_key = '_product_id'"
));
echo "\nOrder items stored:\n";
foreach ($items as $item) {
    echo "  order_id={$item->order_id} _product_id={$item->meta_value}\n";
}

// Ejecutar la query HPOS
$hpos_table = $wpdb->prefix . 'wc_orders';
$count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT oi.order_id)
     FROM {$wpdb->prefix}woocommerce_order_items AS oi
     INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim
         ON oi.order_item_id = oim.order_item_id AND oim.meta_key = '_product_id'
     INNER JOIN {$hpos_table} AS o ON oi.order_id = o.id
     WHERE o.type = 'shop_order'
       AND o.status IN ('wc-completed', 'wc-processing')
       AND o.customer_id = %d
       AND oim.meta_value = %d",
    $user_id,
    $product_id
));
echo "\nHPOS query count for product_id=$product_id, user_id=$user_id: $count\n";

// Limpiar
foreach ($order_ids as $oid) {
    $o = wc_get_order($oid);
    if ($o) $o->delete(true);
}
echo "Test orders deleted.\n";

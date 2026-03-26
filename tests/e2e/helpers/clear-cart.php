<?php
/**
 * Script CLI para limpiar el carrito persistente del admin antes de correr tests E2E.
 * Uso: /path/to/php clear-cart.php /path/to/wp-root admin_user_id
 */
$wp_root    = $argv[1] ?? '/Applications/XAMPP/xamppfiles/htdocs/pw-ofertas';
$user_id    = (int) ($argv[2] ?? 1);

$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/';

require $wp_root . '/wp-load.php';

// 1. Borrar el carrito persistente del usuario (user meta)
delete_user_meta($user_id, '_woocommerce_persistent_cart_1');

// 2. Borrar la sesion WC del usuario en la tabla de sesiones
global $wpdb;
$deleted = $wpdb->delete(
    $wpdb->prefix . 'woocommerce_sessions',
    [ 'session_key' => $user_id ],
    [ '%d' ]
);

echo 'OK sessions_deleted=' . (int) $deleted;

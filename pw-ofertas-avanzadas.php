<?php
/**
 * Plugin Name: PW - Ofertas Avanzadas
 * Description: Sistema de descuentos orientado a estrategias de marketing para WooCommerce
 * Version: 2.0.2
 * Requires PHP: 7.4
 * Author: Tu Nombre
 */

if (!defined('ABSPATH')) exit;

define('PWOA_VERSION', '2.0.2');
define('PWOA_PATH', plugin_dir_path(__FILE__));
define('PWOA_URL', plugin_dir_url(__FILE__));

require_once PWOA_PATH . 'vendor/autoload.php';

use PW\OfertasAvanzadas\Core\Plugin;
use PW\OfertasAvanzadas\Core\Activator;
use PW\OfertasAvanzadas\Core\Deactivator;

register_activation_hook(__FILE__, [Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [Deactivator::class, 'deactivate']);

add_action('plugins_loaded', function() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>PW - Ofertas Avanzadas requiere WooCommerce activo.</p></div>';
        });
        return;
    }
    Plugin::getInstance()->init();
});
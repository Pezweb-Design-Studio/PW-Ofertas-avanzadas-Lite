<?php
/**
 * Plugin Name: PW - Ofertas Avanzadas Lite
 * Description: Sistema de descuentos orientado a estrategias de marketing para WooCommerce (Versión Lite)
 * Version: 2.0.18
 * Requires PHP: 7.4
 * Author: PezWeb
 * Plugin URI: https://pezweb.com/servicios/ofertas-avanzadas/
 * Text Domain: pw-ofertas-avanzadas
 */

defined('ABSPATH') || exit;

define('PWOA_VERSION', '2.0.18');
define('PWOA_EDITION', 'lite');
define('PWOA_PATH', plugin_dir_path(__FILE__));
define('PWOA_URL', plugin_dir_url(__FILE__));

add_action('admin_init', function () {
    if (!is_plugin_active('pw-ofertas-avanzadas-pro/pw-ofertas-avanzadas-pro.php')) return;
    deactivate_plugins(plugin_basename(__FILE__));
    add_action('admin_notices', function () {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>PW Ofertas Avanzadas Lite</strong> se ha desactivado automaticamente porque ya tienes la version Pro instalada.</p>';
        echo '</div>';
    });
});

require_once PWOA_PATH . 'vendor/autoload.php';

use PW\OfertasAvanzadas\Core\Plugin;
use PW\OfertasAvanzadas\Core\Activator;
use PW\OfertasAvanzadas\Core\Deactivator;

register_activation_hook(__FILE__, [Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [Deactivator::class, 'deactivate']);

add_action('plugins_loaded', function () {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>PW - Ofertas Avanzadas requiere WooCommerce activo.</p></div>';
        });
        return;
    }
    Plugin::getInstance()->init();
});

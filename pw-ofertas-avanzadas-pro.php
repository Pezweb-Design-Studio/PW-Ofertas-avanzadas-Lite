<?php
/**
 * Plugin Name: PW - Ofertas Avanzadas Pro
 * Description: Sistema de descuentos orientado a estrategias de marketing para WooCommerce (versión Pro).
 * Version: 2.0.7
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: PezWeb
 * Author URI: https://pezweb.com/
 * Plugin URI: https://pezweb.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pw-ofertas-avanzadas
 * Domain Path: /languages
 * WC requires at least: 8.0
 * WC tested up to: 9.8
 */

defined('ABSPATH') || exit;

define('PWOA_VERSION', '2.0.7');
define('PWOA_EDITION', 'pro');
define('PWOA_PATH', plugin_dir_path(__FILE__));
define('PWOA_URL', plugin_dir_url(__FILE__));
define('PWOA_PLUGIN_FILE', __FILE__);

add_action('admin_init', function () {
    if (!is_plugin_active('pw-ofertas-avanzadas-pro/pw-ofertas-avanzadas-pro.php')) return;
    deactivate_plugins(plugin_basename(__FILE__));
    add_action('admin_notices', function () {
        echo '<div class="notice notice-warning is-dismissible"><p><strong>';
        echo esc_html(__('PW Ofertas Avanzadas Lite was deactivated automatically because the Pro edition is already installed.', 'pw-ofertas-avanzadas'));
        echo '</strong></p></div>';
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
            echo '<div class="notice notice-error"><p>';
            echo esc_html(__('PW - Ofertas Avanzadas requires WooCommerce to be active.', 'pw-ofertas-avanzadas'));
            echo '</p></div>';
        });
        return;
    }
    Plugin::getInstance()->init();
});

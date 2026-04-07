<?php
namespace PW\OfertasAvanzadas\Core;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Admin\AdminController;
use PW\OfertasAvanzadas\Core\I18n;
use PW\OfertasAvanzadas\Core\UpgradeUrl;
use PW\BackendUI\BackendUI;
use PW\OfertasAvanzadas\Handlers\CartHandler;
use PW\OfertasAvanzadas\Handlers\ProductExpiryHandler;
use PW\OfertasAvanzadas\Handlers\ProductBadgeHandler;
use PW\OfertasAvanzadas\Handlers\OrderHandler;
use PW\OfertasAvanzadas\Shortcodes\ProductsShortcode;

class Plugin {
    private static ?self $instance = null;

    public static function getInstance(): self {
        return self::$instance ??= new self();
    }

    public function init(): void {
        Schema::ensureStatsTable();
        I18n::register();

        if (is_admin()) {
            BackendUI::init([
                'assets_url' => PWOA_URL . 'vendor/pw/backend-ui/assets/',
                'version'    => PWOA_VERSION,
                'slug'       => 'pwoa',
                'screens'    => [
                    'toplevel_page_pwoa-dashboard',
                    // Submenu screen IDs depend on the translated parent menu title (e.g. Ofertas → ofertas_, Offers → offers_).
                    'ofertas_page_pwoa-new-campaign',
                    'offers_page_pwoa-new-campaign',
                    'ofertas_page_pwoa-shortcodes',
                    'offers_page_pwoa-shortcodes',
                ],
                'brand' => [
                    'name'        => 'PEZWEB',
                    'plugin_name' => __('Ofertas Avanzadas', 'pw-ofertas-avanzadas'),
                ],
            ]);
            BackendUI::playground();
            new AdminController();
            new ProductExpiryHandler();
        }

        new CartHandler();
        new ProductBadgeHandler();
        new OrderHandler();
        new ProductsShortcode();
        $this->loadAssets();
    }

    private function loadAssets(): void {
        add_action('admin_enqueue_scripts', function ($hook) {
            if (strpos($hook, 'pwoa') === false) return;

            // Design tokens: BackendUI; utilities: pwoa-tailwind-admin.css (AdminAssets).
            wp_enqueue_script('pwoa-wizard', PWOA_URL . 'assets/js/wizard.js', ['jquery'], PWOA_VERSION, true);
            wp_enqueue_script('pwoa-wizard-lite-addon', PWOA_URL . 'assets/js/wizard.lite-addon.js', ['pwoa-wizard'], PWOA_VERSION, true);
            wp_localize_script('pwoa-wizard', 'pwoaData', [
                'ajaxUrl'    => admin_url('admin-ajax.php'),
                'nonce'      => wp_create_nonce('pwoa_nonce'),
                'adminUrl'   => admin_url(),
                'edition'    => PWOA_EDITION,
                'upgradeUrl' => UpgradeUrl::get(),
                'i18n'       => I18n::wizardScriptI18n(),
            ]);
        });
    }
}

<?php
namespace PW\OfertasAvanzadas\Core;

use PW\OfertasAvanzadas\Admin\AdminController;
use PW\OfertasAvanzadas\Handlers\CartHandler;
use PW\OfertasAvanzadas\Handlers\ProductExpiryHandler;
use PW\OfertasAvanzadas\Handlers\ProductBadgeHandler;
use PW\OfertasAvanzadas\Handlers\OrderHandler;

class Plugin {
    private static $instance = null;

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        if (is_admin()) {
            new AdminController();
            new ProductExpiryHandler();
        }

        new CartHandler();
        new ProductBadgeHandler();
        new OrderHandler();
        $this->loadAssets();
    }

    private function loadAssets(): void {
        add_action('admin_enqueue_scripts', function($hook) {
            if (strpos($hook, 'pwoa') === false) return;

            wp_enqueue_script('tailwind', 'https://cdn.tailwindcss.com', [], null);
            wp_enqueue_script('pwoa-wizard', PWOA_URL . 'assets/js/wizard.js', ['jquery'], PWOA_VERSION, true);
            wp_localize_script('pwoa-wizard', 'pwoaData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pwoa_nonce'),
                'adminUrl' => admin_url()
            ]);
        });
    }
}
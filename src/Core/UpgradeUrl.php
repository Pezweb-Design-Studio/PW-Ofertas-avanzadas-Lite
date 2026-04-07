<?php
namespace PW\OfertasAvanzadas\Core;

defined('ABSPATH') || exit;

/**
 * Pro / upgrade landing URL (Lite upsell, modals, etc.).
 *
 * Override when your storefront is live:
 *   add_filter( 'pwoa_upgrade_url', fn () => 'https://example.com/pro/' );
 */
final class UpgradeUrl
{
    public static function get(): string
    {
        $default = 'https://pezweb.com/producto/ofertas-avanzadas/';

        return (string) apply_filters('pwoa_upgrade_url', $default);
    }
}

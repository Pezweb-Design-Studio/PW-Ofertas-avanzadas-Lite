<?php
namespace PW\OfertasAvanzadas\Core;

defined('ABSPATH') || exit;

class Deactivator {
    public static function deactivate(): void {
        wp_clear_scheduled_hook('pwoa_cleanup_logs');
        flush_rewrite_rules();
    }
}

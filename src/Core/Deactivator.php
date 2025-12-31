<?php
namespace PW\OfertasAvanzadas\Core;

class Deactivator {

    public static function deactivate(): void {
        // Limpiar scheduled events si los hubiera
        wp_clear_scheduled_hook('pwoa_cleanup_logs');

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
<?php
namespace PW\OfertasAvanzadas\Core;

defined('ABSPATH') || exit;

final class Schema
{
    public static function statsTableSql(): string
    {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        return "CREATE TABLE {$wpdb->prefix}pwoa_stats (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id BIGINT UNSIGNED NOT NULL,
            campaign_snapshot JSON,
            order_id BIGINT UNSIGNED NOT NULL,
            discount_amount DECIMAL(10,2) NOT NULL,
            original_total DECIMAL(10,2) NOT NULL,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_campaign (campaign_id, applied_at)
        ) $charset;";
    }

    public static function ensureStatsTable(): void
    {
        global $wpdb;
        $name = $wpdb->prefix . 'pwoa_stats';
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $name));
        if ($found === $name) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta(self::statsTableSql());
    }
}

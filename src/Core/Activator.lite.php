<?php
namespace PW\OfertasAvanzadas\Core;

defined('ABSPATH') || exit;

class Activator {
    public static function activate(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql_campaigns = "CREATE TABLE {$wpdb->prefix}pwoa_campaigns (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            objective VARCHAR(50) NOT NULL,
            strategy VARCHAR(50) NOT NULL,
            discount_type VARCHAR(30) NOT NULL,
            config JSON NOT NULL,
            conditions JSON,
            stacking_mode VARCHAR(20) DEFAULT 'priority',
            priority INT DEFAULT 10,
            active TINYINT(1) DEFAULT 1,
            start_date DATETIME,
            end_date DATETIME,
            units_sold JSON,
            deleted_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_active (active, start_date, end_date),
            INDEX idx_deleted (deleted_at)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_campaigns);

        update_option('pwoa_db_version', PWOA_VERSION);
        update_option('pwoa_edition', 'lite');
    }
}

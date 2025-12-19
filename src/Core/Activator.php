<?php
namespace PW\OfertasAvanzadas\Core;

class Activator {
    public static function activate(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        // Tabla de campañas
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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_active (active, start_date, end_date)
        ) $charset;";

        // Tabla de estadísticas
        $sql_stats = "CREATE TABLE {$wpdb->prefix}pwoa_stats (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NOT NULL,
            discount_amount DECIMAL(10,2) NOT NULL,
            original_total DECIMAL(10,2) NOT NULL,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_campaign (campaign_id, applied_at)
        ) $charset;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_campaigns);
        dbDelta($sql_stats);

        // Guardar versión para futuras migraciones
        update_option('pwoa_db_version', PWOA_VERSION);
    }
}
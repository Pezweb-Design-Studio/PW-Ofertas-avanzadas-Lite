<?php
/**
 * Uninstall — remove plugin data when the plugin is deleted from WordPress.
 *
 * @package PW\OfertasAvanzadas
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'pwoa_campaigns');
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'pwoa_stats');

delete_option('pwoa_db_version');
delete_option('pwoa_edition');
delete_option('pwoa_stacking_behavior');

wp_clear_scheduled_hook('pwoa_cleanup_logs');

$patterns = [
    $wpdb->esc_like('_transient_pwoa_') . '%',
    $wpdb->esc_like('_transient_timeout_pwoa_') . '%',
    $wpdb->esc_like('_site_transient_pwoa_') . '%',
    $wpdb->esc_like('_site_transient_timeout_pwoa_') . '%',
];

foreach ($patterns as $like) {
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $like
        )
    );
}

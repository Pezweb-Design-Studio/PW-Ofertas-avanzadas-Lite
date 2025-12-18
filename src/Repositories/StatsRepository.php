<?php
namespace PW\OfertasAvanzadas\Repositories;

class StatsRepository {

    public static function getSummary(int $days = 30): array {
        global $wpdb;

        $date_from = date('Y-m-d', strtotime("-{$days} days"));

        $results = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(DISTINCT order_id) as total_orders,
                SUM(discount_amount) as total_discounted,
                AVG(discount_amount) as avg_discount,
                SUM(original_total) as total_revenue
            FROM {$wpdb->prefix}pwoa_stats
            WHERE applied_at >= %s
        ", $date_from));

        return [
            'total_orders' => intval($results->total_orders ?? 0),
            'total_discounted' => floatval($results->total_discounted ?? 0),
            'avg_discount' => floatval($results->avg_discount ?? 0),
            'total_revenue' => floatval($results->total_revenue ?? 0)
        ];
    }

    public static function getTopCampaigns(int $limit = 10): array {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                c.id,
                c.name,
                COUNT(s.id) as uses,
                SUM(s.discount_amount) as total_discounted
            FROM {$wpdb->prefix}pwoa_campaigns c
            LEFT JOIN {$wpdb->prefix}pwoa_stats s ON c.id = s.campaign_id
            WHERE c.active = 1
            GROUP BY c.id
            ORDER BY total_discounted DESC
            LIMIT %d
        ", $limit));
    }
}
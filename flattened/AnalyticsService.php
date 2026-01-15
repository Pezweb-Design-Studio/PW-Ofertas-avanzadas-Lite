<?php
namespace PW\OfertasAvanzadas\Services;

use PW\OfertasAvanzadas\Repositories\StatsRepository;

class AnalyticsService {

    public static function getPerformanceMetrics(int $days = 30): array {
        $summary = StatsRepository::getSummary($days);
        $top_campaigns = StatsRepository::getTopCampaigns(5);

        return [
            'summary' => $summary,
            'top_campaigns' => $top_campaigns,
            'conversion_rate' => self::calculateConversionRate($days),
            'roi' => self::calculateROI($summary)
        ];
    }

    public static function calculateConversionRate(int $days = 30): float {
        global $wpdb;

        $date_from = date('Y-m-d', strtotime("-{$days} days"));

        $total_orders = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT ID) 
            FROM {$wpdb->prefix}posts 
            WHERE post_type = 'shop_order' 
            AND post_date >= %s
        ", $date_from));

        $orders_with_discount = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT order_id) 
            FROM {$wpdb->prefix}pwoa_stats
            WHERE applied_at >= %s
        ", $date_from));

        return $total_orders > 0
            ? round(($orders_with_discount / $total_orders) * 100, 2)
            : 0;
    }

    public static function calculateROI(array $summary): float {
        if ($summary['total_discounted'] == 0) return 0;

        $revenue_generated = $summary['total_revenue'] - $summary['total_discounted'];
        $roi = (($revenue_generated - $summary['total_discounted']) / $summary['total_discounted']) * 100;

        return round($roi, 2);
    }

    public static function getCampaignTrends(int $campaign_id, int $days = 30): array {
        global $wpdb;

        $date_from = date('Y-m-d', strtotime("-{$days} days"));

        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(applied_at) as date,
                COUNT(*) as uses,
                SUM(discount_amount) as total_discount
            FROM {$wpdb->prefix}pwoa_stats
            WHERE campaign_id = %d
            AND applied_at >= %s
            GROUP BY DATE(applied_at)
            ORDER BY date ASC
        ", $campaign_id, $date_from));
    }
}
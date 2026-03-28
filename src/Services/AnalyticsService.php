<?php
namespace PW\OfertasAvanzadas\Services;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Repositories\StatsRepository;

class AnalyticsService {

    public static function getPerformanceMetrics(int $days = 30): array {
        $summary = StatsRepository::getSummary($days);

        return [
            'summary'         => $summary,
            'top_campaigns'   => StatsRepository::getTopCampaigns(5),
            'conversion_rate' => self::calculateConversionRate($days),
            'roi'             => self::calculateROI($summary),
        ];
    }

    public static function calculateConversionRate(int $days = 30): float {
        global $wpdb;

        $date_from = date('Y-m-d', strtotime("-{$days} days"));

        $total_orders = self::countOrdersSinceDate($date_from);

        if ($total_orders === 0) {
            return 0.0;
        }

        $orders_with_discount = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT order_id) FROM {$wpdb->prefix}pwoa_stats WHERE applied_at >= %s",
            $date_from
        ));

        return round(($orders_with_discount / $total_orders) * 100, 2);
    }

    /**
     * Order count since a calendar date (site timezone), compatible with HPOS and legacy storage.
     */
    private static function countOrdersSinceDate(string $date_from): int
    {
        if (!function_exists('wc_get_orders')) {
            return 0;
        }

        $result = wc_get_orders([
            'limit'      => 1,
            'paginate'   => true,
            'status'     => 'any',
            'date_after' => $date_from,
            'return'     => 'ids',
        ]);

        if (is_object($result) && isset($result->total)) {
            return (int) $result->total;
        }

        return 0;
    }

    public static function calculateROI(array $summary): float {
        if ($summary['total_discounted'] == 0) return 0.0;

        $revenue = $summary['total_revenue'] - $summary['total_discounted'];

        return round((($revenue - $summary['total_discounted']) / $summary['total_discounted']) * 100, 2);
    }

    public static function getCampaignTrends(int $campaign_id, int $days = 30): array {
        global $wpdb;

        $date_from = date('Y-m-d', strtotime("-{$days} days"));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(applied_at) as date, COUNT(*) as uses, SUM(discount_amount) as total_discount
             FROM {$wpdb->prefix}pwoa_stats
             WHERE campaign_id = %d AND applied_at >= %s
             GROUP BY DATE(applied_at)
             ORDER BY date ASC",
            $campaign_id,
            $date_from
        ));
    }
}

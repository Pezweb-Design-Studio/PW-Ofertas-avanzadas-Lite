<?php
namespace PW\OfertasAvanzadas\Services;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Repositories\CampaignRepository;

class DiscountEngine
{
    private const STRATEGY_MAP = [
        'basic_discount'             => 'PW\\OfertasAvanzadas\\Strategies\\Lite\\BasicDiscountStrategy',
        'min_amount'                 => 'PW\\OfertasAvanzadas\\Strategies\\Pro\\MinAmountStrategy',
        'free_shipping'              => 'PW\\OfertasAvanzadas\\Strategies\\Pro\\FreeShippingStrategy',
        'tiered_discount'            => 'PW\\OfertasAvanzadas\\Strategies\\Pro\\TieredDiscountStrategy',
        'bulk_discount'              => 'PW\\OfertasAvanzadas\\Strategies\\Lite\\BulkDiscountStrategy',
        'buy_x_pay_y'               => 'PW\\OfertasAvanzadas\\Strategies\\Lite\\BuyXPayYStrategy',
        'attribute_quantity_discount' => 'PW\\OfertasAvanzadas\\Strategies\\Lite\\AttributeQuantityDiscountStrategy',
        'expiry_based'               => 'PW\\OfertasAvanzadas\\Strategies\\Lite\\ExpiryBasedStrategy',
        'low_stock'                  => 'PW\\OfertasAvanzadas\\Strategies\\Pro\\LowStockStrategy',
        'recurring_purchase'         => 'PW\\OfertasAvanzadas\\Strategies\\Pro\\RecurringPurchaseStrategy',
        'flash_sale'                 => 'PW\\OfertasAvanzadas\\Strategies\\Pro\\FlashSaleStrategy',
    ];

    private const EMPTY_DISCOUNT = ['amount' => 0];

    public static function calculateBestDiscount(array $cart): array
    {
        $campaigns = CampaignRepository::getActive();

        if (empty($campaigns)) {
            return self::EMPTY_DISCOUNT;
        }

        $applicable = [];

        foreach ($campaigns as $campaign) {
            $strategy_class = self::STRATEGY_MAP[$campaign->strategy] ?? '';

            if ($strategy_class === '' || !class_exists($strategy_class)) {
                continue;
            }

            $strategy   = new $strategy_class();
            $config     = json_decode($campaign->config, true);
            $conditions = json_decode($campaign->conditions, true) ?? [];

            if (!$strategy->canApply($cart, $config, $conditions)) {
                continue;
            }

            $config['_conditions']  = $conditions;
            $config['_campaign_id'] = $campaign->id;

            $discount                  = $strategy->calculate($cart, $config);
            $discount['campaign_id']   = $campaign->id;
            $discount['campaign_name'] = $campaign->name;
            $discount['stacking_mode'] = $campaign->stacking_mode;

            $applicable[] = $discount;
        }

        return self::selectDiscount($applicable);
    }

    private static function selectDiscount(array $applicable): array
    {
        if (empty($applicable)) {
            return self::EMPTY_DISCOUNT;
        }

        $behavior = get_option('pwoa_stacking_behavior', 'priority_first');

        $priority = array_filter($applicable, fn($d) => $d['stacking_mode'] === 'priority');
        $stack    = array_filter($applicable, fn($d) => $d['stacking_mode'] === 'stack');

        return match ($behavior) {
            'priority_first' => !empty($priority) ? self::selectBestPriority($priority) : self::stackDiscounts($stack),
            'stack_first'    => !empty($stack) ? self::stackDiscounts($stack) : self::selectBestPriority($priority),
            'max_discount'   => self::maxOf(
                !empty($stack) ? self::stackDiscounts($stack) : self::EMPTY_DISCOUNT,
                !empty($priority) ? self::selectBestPriority($priority) : self::EMPTY_DISCOUNT
            ),
            default => self::selectBestPriority($priority) ?: self::stackDiscounts($stack),
        };
    }

    private static function maxOf(array $a, array $b): array
    {
        return $a['amount'] > $b['amount'] ? $a : $b;
    }

    private static function selectBestPriority(array $discounts): array
    {
        if (empty($discounts)) {
            return self::EMPTY_DISCOUNT;
        }

        usort($discounts, fn($a, $b) => $b['amount'] <=> $a['amount']);

        return [
            'amount'      => $discounts[0]['amount'],
            'campaign_id' => $discounts[0]['campaign_id'],
            'label'       => $discounts[0]['campaign_name'],
        ];
    }

    private static function stackDiscounts(array $discounts): array
    {
        return [
            'amount'      => array_sum(array_column($discounts, 'amount')),
            'campaign_id' => null,
            'label'       => implode(' + ', array_column($discounts, 'campaign_name')),
        ];
    }
}

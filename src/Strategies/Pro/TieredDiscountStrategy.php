<?php
namespace PW\OfertasAvanzadas\Strategies\Pro;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;
use PW\OfertasAvanzadas\Services\ProductMatcher;

class TieredDiscountStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $filtered_cart = ProductMatcher::filterCart($cart, $conditions);
        if (empty($filtered_cart)) return false;

        $tiers = $config['tiers'] ?? [];
        if (empty($tiers)) return false;

        return array_sum(array_column($filtered_cart, 'quantity')) >= min(array_column($tiers, 'quantity'));
    }

    public function calculate(array $cart, array $config): array {
        $total_items = array_sum(array_column($cart, 'quantity'));
        $subtotal    = array_sum(array_column($cart, 'line_total'));
        $tiers       = $config['tiers'] ?? [];

        usort($tiers, fn($a, $b) => $b['quantity'] <=> $a['quantity']);

        $applicable_tier = null;
        foreach ($tiers as $tier) {
            if ($total_items >= $tier['quantity']) {
                $applicable_tier = $tier;
                break;
            }
        }

        if (!$applicable_tier) {
            return ['amount' => 0, 'type' => 'percentage', 'items' => []];
        }

        $amount = $config['discount_type'] === 'percentage'
            ? $subtotal * ($applicable_tier['discount'] / 100)
            : $applicable_tier['discount'];

        return [
            'amount'       => $amount,
            'type'         => $config['discount_type'],
            'items'        => array_keys($cart),
            'tier_applied' => $applicable_tier,
        ];
    }

    public static function getMeta(): array {
        return [
            'key'           => 'tiered_discount',
            'name'          => __('Tiered quantity discount', 'pw-ofertas-avanzadas'),
            'description'   => __('Higher discounts as the number of items in the cart increases.', 'pw-ofertas-avanzadas'),
            'effectiveness' => 4,
            'when_to_use'   => __('Peak sales and volume campaigns. Strong with low unit-cost items. Example: 10% at 3 items, 20% at 5, 30% at 10.', 'pw-ofertas-avanzadas'),
            'objective'     => 'aov',
        ];
    }

    public static function getConfigFields(): array {
        return [
            [
                'key'      => 'discount_type',
                'label'    => __('Discount type', 'pw-ofertas-avanzadas'),
                'type'     => 'select',
                'options'  => ['percentage' => __('Percentage', 'pw-ofertas-avanzadas'), 'fixed' => __('Fixed amount', 'pw-ofertas-avanzadas')],
                'required' => true,
            ],
            [
                'key'         => 'tiers',
                'label'       => __('Discount tiers', 'pw-ofertas-avanzadas'),
                'type'        => 'repeater',
                'description' => __('Define tiers; the best matching tier is applied.', 'pw-ofertas-avanzadas'),
                'fields'      => [
                    ['key' => 'quantity', 'label' => __('Item quantity', 'pw-ofertas-avanzadas'), 'type' => 'number', 'required' => true],
                    ['key' => 'discount', 'label' => __('Discount', 'pw-ofertas-avanzadas'),            'type' => 'number', 'required' => true, 'description' => __('Percentage or fixed value, depending on discount type.', 'pw-ofertas-avanzadas')],
                ],
            ],
        ];
    }
}

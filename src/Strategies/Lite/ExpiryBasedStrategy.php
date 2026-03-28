<?php
namespace PW\OfertasAvanzadas\Strategies\Lite;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;
use PW\OfertasAvanzadas\Services\ProductMatcher;

class ExpiryBasedStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $filtered_cart = ProductMatcher::filterCart($cart, $conditions);
        if (empty($filtered_cart)) return false;

        $tiers = $config['tiers'] ?? [];
        if (empty($tiers)) return false;

        $max_days = max(array_column($tiers, 'days'));

        foreach ($filtered_cart as $item) {
            $expiry_date = get_post_meta($item['product_id'], '_expiry_date', true);
            if (!$expiry_date) continue;

            $days_to_expiry = (strtotime($expiry_date) - time()) / DAY_IN_SECONDS;
            if ($days_to_expiry >= 0 && $days_to_expiry <= $max_days) {
                return true;
            }
        }

        return false;
    }

    public function calculate(array $cart, array $config): array {
        $total_discount = 0;
        $affected_items = [];

        foreach ($cart as $key => $item) {
            $expiry_date = get_post_meta($item['product_id'], '_expiry_date', true);
            if (!$expiry_date) continue;

            $days_to_expiry = (strtotime($expiry_date) - time()) / DAY_IN_SECONDS;
            $discount_value = $this->getDiscountByDays($days_to_expiry, $config);

            if ($discount_value <= 0) continue;

            $total_discount  += $config['discount_type'] === 'percentage'
                ? $item['line_total'] * ($discount_value / 100)
                : $discount_value;
            $affected_items[] = $key;
        }

        return [
            'amount' => $total_discount,
            'type'   => $config['discount_type'],
            'items'  => $affected_items,
        ];
    }

    private function getDiscountByDays(float $days, array $config): float {
        $tiers = $config['tiers'] ?? [];
        if (empty($tiers)) return 0.0;

        usort($tiers, fn($a, $b) => $a['days'] <=> $b['days']);

        foreach ($tiers as $tier) {
            if ($days <= $tier['days']) {
                return (float) $tier['discount'];
            }
        }

        return 0.0;
    }

    public static function getMeta(): array {
        return [
            'key'           => 'expiry_based',
            'name'          => __('Expiry-based discount', 'pw-ofertas-avanzadas'),
            'description'   => __('Progressive discounts for products approaching their expiry date.', 'pw-ofertas-avanzadas'),
            'effectiveness' => 5,
            'when_to_use'   => __('Perishables, food, pharmacy. Increase the discount as the expiry date gets closer.', 'pw-ofertas-avanzadas'),
            'objective'     => 'liquidation',
        ];
    }

    public static function getConfigFields(): array {
        return [
            [
                'key'      => 'discount_type',
                'label'    => __('Discount type', 'pw-ofertas-avanzadas'),
                'type'     => 'select',
                'options'  => [
                    'percentage' => __('Percentage', 'pw-ofertas-avanzadas'),
                    'fixed'      => __('Fixed amount', 'pw-ofertas-avanzadas'),
                ],
                'required' => true,
            ],
            [
                'key'         => 'tiers',
                'label'       => __('Discount tiers', 'pw-ofertas-avanzadas'),
                'type'        => 'repeater',
                'description' => __('Set discounts by days until expiry. Products use the best matching tier. Example: 30 days → 10%, 15 days → 20%.', 'pw-ofertas-avanzadas'),
                'fields'      => [
                    ['key' => 'days',     'label' => __('Days until expiry', 'pw-ofertas-avanzadas'), 'type' => 'number'],
                    ['key' => 'discount', 'label' => __('Discount', 'pw-ofertas-avanzadas'),     'type' => 'number'],
                ],
            ],
        ];
    }
}

<?php
namespace PW\OfertasAvanzadas\Strategies\Lite;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;
use PW\OfertasAvanzadas\Services\ProductMatcher;

class BasicDiscountStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        return !empty(ProductMatcher::filterCart($cart, $conditions));
    }

    public function calculate(array $cart, array $config): array {
        $total = array_sum(array_column($cart, 'line_total'));

        $amount = $config['discount_type'] === 'percentage'
            ? $total * ($config['discount_value'] / 100)
            : $config['discount_value'];

        return [
            'amount' => $amount,
            'type'   => $config['discount_type'],
            'items'  => array_keys($cart),
        ];
    }

    public static function getMeta(): array {
        return [
            'key'           => 'basic_discount',
            'name'          => __('Basic product discount', 'pw-ofertas-avanzadas'),
            'description'   => __('Apply a simple percentage or fixed amount discount to selected products.', 'pw-ofertas-avanzadas'),
            'effectiveness' => 4,
            'when_to_use'   => __('General promotions, seasonal sales, product- or category-specific offers. Use when you want a straightforward discount without complex rules.', 'pw-ofertas-avanzadas'),
            'objective'     => 'basic',
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
                'key'         => 'discount_value',
                'label'       => __('Discount value', 'pw-ofertas-avanzadas'),
                'type'        => 'number',
                'required'    => true,
                'description' => __('Percentage (e.g. 15) or fixed amount in your store currency.', 'pw-ofertas-avanzadas'),
            ],
        ];
    }
}

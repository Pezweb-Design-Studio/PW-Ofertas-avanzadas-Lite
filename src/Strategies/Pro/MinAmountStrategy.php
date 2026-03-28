<?php
namespace PW\OfertasAvanzadas\Strategies\Pro;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;
use PW\OfertasAvanzadas\Services\ProductMatcher;

class MinAmountStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $filtered_cart = ProductMatcher::filterCart($cart, $conditions);
        if (empty($filtered_cart)) return false;

        return array_sum(array_column($filtered_cart, 'line_total')) >= ($config['min_amount'] ?? 0);
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
            'key'           => 'min_amount',
            'name'          => __('Minimum order discount', 'pw-ofertas-avanzadas'),
            'description'   => __('Apply a discount when the cart subtotal reaches a minimum amount.', 'pw-ofertas-avanzadas'),
            'effectiveness' => 5,
            'when_to_use'   => __('Works year-round to lift average order value. Especially strong before holidays and peak sales periods.', 'pw-ofertas-avanzadas'),
            'objective'     => 'aov',
        ];
    }

    public static function getConfigFields(): array {
        return [
            ['key' => 'min_amount',     'label' => __('Minimum amount', 'pw-ofertas-avanzadas'),        'type' => 'number', 'required' => true],
            ['key' => 'discount_type',  'label' => __('Discount type', 'pw-ofertas-avanzadas'),   'type' => 'select', 'options' => ['percentage' => __('Percentage', 'pw-ofertas-avanzadas'), 'fixed' => __('Fixed amount', 'pw-ofertas-avanzadas')], 'required' => true],
            ['key' => 'discount_value', 'label' => __('Discount value', 'pw-ofertas-avanzadas'), 'type' => 'number', 'required' => true],
        ];
    }
}

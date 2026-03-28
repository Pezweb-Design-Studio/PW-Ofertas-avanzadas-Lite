<?php
namespace PW\OfertasAvanzadas\Strategies\Pro;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;
use PW\OfertasAvanzadas\Services\ProductMatcher;

class FreeShippingStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $filtered_cart = ProductMatcher::filterCart($cart, $conditions);
        if (empty($filtered_cart)) return false;

        return array_sum(array_column($filtered_cart, 'line_total')) >= ($config['min_amount'] ?? 0);
    }

    public function calculate(array $cart, array $config): array {
        return [
            'amount' => WC()->cart->get_shipping_total(),
            'type'   => 'free_shipping',
            'items'  => array_keys($cart),
        ];
    }

    public static function getMeta(): array {
        return [
            'key'           => 'free_shipping',
            'name'          => __('Free shipping threshold', 'pw-ofertas-avanzadas'),
            'description'   => __('Waive shipping when the cart reaches a minimum subtotal.', 'pw-ofertas-avanzadas'),
            'effectiveness' => 5,
            'when_to_use'   => __('A strong always-on tactic; often lifts average order value by 20–35% across many stores.', 'pw-ofertas-avanzadas'),
            'objective'     => 'aov',
        ];
    }

    public static function getConfigFields(): array {
        return [
            [
                'key'         => 'min_amount',
                'label'       => __('Minimum subtotal for free shipping', 'pw-ofertas-avanzadas'),
                'type'        => 'number',
                'required'    => true,
                'description' => __('Amount in your store currency.', 'pw-ofertas-avanzadas'),
            ],
        ];
    }
}

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
            'name'          => 'Envio Gratis sobre Monto Minimo',
            'description'   => 'Elimina costo de envio cuando el carrito supera un monto especifico',
            'effectiveness' => 5,
            'when_to_use'   => 'Estrategia permanente altamente efectiva. Incrementa ticket promedio 20-35%. Ideal para todo tipo de e-commerce.',
            'objective'     => 'aov',
        ];
    }

    public static function getConfigFields(): array {
        return [
            [
                'key'         => 'min_amount',
                'label'       => 'Monto minimo para envio gratis',
                'type'        => 'number',
                'required'    => true,
                'description' => 'Monto en tu moneda local',
            ],
        ];
    }
}

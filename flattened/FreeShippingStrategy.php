<?php
namespace PW\OfertasAvanzadas\Strategies\Pro;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;
use function PW\OfertasAvanzadas\Strategies\AOV\WC;

class FreeShippingStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $filtered_cart = \PW\OfertasAvanzadas\Services\ProductMatcher::filterCart($cart, $conditions);

        if (empty($filtered_cart)) return false;

        $subtotal = array_sum(array_column($filtered_cart, 'line_total'));
        return $subtotal >= ($config['min_amount'] ?? 0);
    }

    public function calculate(array $cart, array $config): array {
        // WooCommerce maneja el envío gratis con cupones
        // Aquí solo retornamos el valor del envío
        $shipping_cost = WC()->cart->get_shipping_total();

        return [
            'amount' => $shipping_cost,
            'type' => 'free_shipping',
            'items' => array_keys($cart)
        ];
    }

    public static function getMeta(): array {
        return [
            'name' => 'Envío Gratis sobre Monto Mínimo',
            'description' => 'Elimina costo de envío cuando el carrito supera un monto específico',
            'effectiveness' => 5,
            'when_to_use' => 'Estrategia permanente altamente efectiva. Incrementa ticket promedio 20-35%. Ideal para todo tipo de e-commerce.',
            'objective' => 'aov'
        ];
    }

    public static function getConfigFields(): array {
        return [
            [
                'key' => 'min_amount',
                'label' => 'Monto mínimo para envío gratis',
                'type' => 'number',
                'required' => true,
                'description' => 'Monto en tu moneda local'
            ]
        ];
    }
}
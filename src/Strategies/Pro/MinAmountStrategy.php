<?php
namespace PW\OfertasAvanzadas\Strategies\Pro;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;

class MinAmountStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $filtered_cart = \PW\OfertasAvanzadas\Services\ProductMatcher::filterCart($cart, $conditions);

        if (empty($filtered_cart)) return false;

        $total = array_sum(array_column($filtered_cart, 'line_total'));
        return $total >= ($config['min_amount'] ?? 0);
    }

    public function calculate(array $cart, array $config): array {
        $total = array_sum(array_column($cart, 'line_total'));
        $amount = $config['discount_type'] === 'percentage'
            ? $total * ($config['discount_value'] / 100)
            : $config['discount_value'];

        return [
            'amount' => $amount,
            'type' => $config['discount_type'],
            'items' => array_keys($cart)
        ];
    }

    public static function getMeta(): array {
        return [
            'name' => 'Descuento por Monto Mínimo',
            'description' => 'Aplica descuento cuando el carrito supera un monto específico',
            'effectiveness' => 5,
            'when_to_use' => 'Efectivo todo el año. Ideal para aumentar ticket promedio. Recomendado en pre-navidad y fechas comerciales.',
            'objective' => 'aov'
        ];
    }

    public static function getConfigFields(): array {
        return [
            [
                'key' => 'min_amount',
                'label' => 'Monto mínimo',
                'type' => 'number',
                'required' => true
            ],
            [
                'key' => 'discount_type',
                'label' => 'Tipo de descuento',
                'type' => 'select',
                'options' => ['percentage' => 'Porcentaje', 'fixed' => 'Monto fijo'],
                'required' => true
            ],
            [
                'key' => 'discount_value',
                'label' => 'Valor del descuento',
                'type' => 'number',
                'required' => true
            ]
        ];
    }
}
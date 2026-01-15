<?php
namespace PW\OfertasAvanzadas\Strategies\Lite;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;

class BasicDiscountStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $filtered_cart = \PW\OfertasAvanzadas\Services\ProductMatcher::filterCart($cart, $conditions);
        return !empty($filtered_cart);
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
            'name' => 'Descuento Básico por Productos',
            'description' => 'Aplica un descuento simple por porcentaje o monto fijo a productos seleccionados',
            'effectiveness' => 4,
            'when_to_use' => 'Promociones generales, descuentos estacionales, ofertas específicas por producto o categoría. Ideal cuando necesitas un descuento directo sin condiciones complejas.',
            'objective' => 'basic'
        ];
    }

    public static function getConfigFields(): array {
        return [
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
                'required' => true,
                'description' => 'Porcentaje (ej: 15) o monto fijo en tu moneda local'
            ]
        ];
    }
}
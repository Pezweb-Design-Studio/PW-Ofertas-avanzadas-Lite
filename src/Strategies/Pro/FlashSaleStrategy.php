<?php
namespace PW\OfertasAvanzadas\Strategies\Pro;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;

class FlashSaleStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $current_time = current_time('timestamp');
        $start = strtotime($config['start_time'] ?? '');
        $end = strtotime($config['end_time'] ?? '');

        if ($current_time < $start || $current_time > $end) {
            return false;
        }

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
            'name' => 'Flash Sale (Oferta Relámpago)',
            'description' => 'Descuento por tiempo limitado para generar urgencia',
            'effectiveness' => 5,
            'when_to_use' => 'Black Friday, Cyber Monday, lanzamientos de productos. Máxima efectividad en ventanas de 6-24 horas.',
            'objective' => 'urgency'
        ];
    }

    public static function getConfigFields(): array {
        return [
            [
                'key' => 'start_time',
                'label' => 'Hora de inicio',
                'type' => 'datetime',
                'required' => true
            ],
            [
                'key' => 'end_time',
                'label' => 'Hora de fin',
                'type' => 'datetime',
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
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
            'name'          => 'Descuento Escalonado por Cantidad',
            'description'   => 'Descuentos progresivos segun cantidad de productos en el carrito',
            'effectiveness' => 4,
            'when_to_use'   => 'Black Friday, Cyber Monday, campanas de volumen. Muy efectivo con productos de bajo costo unitario. Ejemplo: 10% en 3 items, 20% en 5, 30% en 10.',
            'objective'     => 'aov',
        ];
    }

    public static function getConfigFields(): array {
        return [
            [
                'key'      => 'discount_type',
                'label'    => 'Tipo de descuento',
                'type'     => 'select',
                'options'  => ['percentage' => 'Porcentaje', 'fixed' => 'Monto fijo'],
                'required' => true,
            ],
            [
                'key'         => 'tiers',
                'label'       => 'Niveles de descuento',
                'type'        => 'repeater',
                'description' => 'Define los niveles. El sistema aplicara el mayor descuento que cumpla la condicion.',
                'fields'      => [
                    ['key' => 'quantity', 'label' => 'Cantidad de productos', 'type' => 'number', 'required' => true],
                    ['key' => 'discount', 'label' => 'Descuento',            'type' => 'number', 'required' => true, 'description' => 'Porcentaje o valor fijo segun tipo seleccionado'],
                ],
            ],
        ];
    }
}

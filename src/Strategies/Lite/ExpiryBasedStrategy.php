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
            'name'          => 'Descuento por Fecha de Vencimiento',
            'description'   => 'Aplica descuentos progresivos a productos proximos a vencer',
            'effectiveness' => 5,
            'when_to_use'   => 'Productos perecederos, alimentos, medicamentos. El descuento aumenta conforme se acerca la fecha de vencimiento.',
            'objective'     => 'liquidation',
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
                'description' => 'Define descuentos segun dias restantes hasta vencimiento. La campana se activara automaticamente para productos dentro del rango del tier mas alto. Ejemplo: si defines "30 dias -> 10%" y "15 dias -> 20%", productos con 25 dias recibiran 10%, productos con 10 dias recibiran 20%.',
                'fields'      => [
                    ['key' => 'days',     'label' => 'Dias restantes', 'type' => 'number'],
                    ['key' => 'discount', 'label' => 'Descuento',     'type' => 'number'],
                ],
            ],
        ];
    }
}

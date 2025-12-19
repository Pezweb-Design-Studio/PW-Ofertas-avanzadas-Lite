<?php
namespace PW\OfertasAvanzadas\Strategies\Liquidation;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;

class ExpiryBasedStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        foreach ($cart as $item) {
            $product = wc_get_product($item['product_id']);
            $expiry_date = get_post_meta($product->get_id(), '_expiry_date', true);

            if (!$expiry_date) continue;

            $days_to_expiry = (strtotime($expiry_date) - time()) / DAY_IN_SECONDS;

            // Ignorar productos ya vencidos
            if ($days_to_expiry <= 0) continue;

            if ($days_to_expiry <= ($config['days_threshold'] ?? 30)) {
                return true;
            }
        }

        return false;
    }

    public function calculate(array $cart, array $config): array {
        $total_discount = 0;
        $affected_items = [];

        foreach ($cart as $key => $item) {
            $product = wc_get_product($item['product_id']);
            $expiry_date = get_post_meta($product->get_id(), '_expiry_date', true);

            if (!$expiry_date) continue;

            $days_to_expiry = (strtotime($expiry_date) - time()) / DAY_IN_SECONDS;

            // Ignorar productos ya vencidos
            if ($days_to_expiry <= 0) continue;

            $discount_value = $this->getDiscountByDays($days_to_expiry, $config);

            if ($discount_value > 0) {
                $item_discount = $config['discount_type'] === 'percentage'
                    ? $item['line_total'] * ($discount_value / 100)
                    : $discount_value;

                $total_discount += $item_discount;
                $affected_items[] = $key;
            }
        }

        return [
            'amount' => $total_discount,
            'type' => $config['discount_type'],
            'items' => $affected_items
        ];
    }

    private function getDiscountByDays(float $days, array $config): float {
        $tiers = $config['tiers'] ?? [];

        // FIX: Ordenar tiers de menor a mayor dias
        usort($tiers, fn($a, $b) => ($a['days'] ?? 0) <=> ($b['days'] ?? 0));

        foreach ($tiers as $tier) {
            if ($days <= ($tier['days'] ?? 0)) {
                return $tier['discount'] ?? 0;
            }
        }

        return 0;
    }

    public static function getMeta(): array {
        return [
            'name' => 'Descuento por Fecha de Vencimiento',
            'description' => 'Aplica descuentos progresivos a productos proximos a vencer',
            'effectiveness' => 5,
            'when_to_use' => 'Productos perecederos, alimentos, medicamentos. El descuento aumenta conforme se acerca la fecha de vencimiento.',
            'objective' => 'liquidation'
        ];
    }

    public static function getConfigFields(): array {
        return [
            [
                'key' => 'days_threshold',
                'label' => 'Dias para activar promocion',
                'type' => 'number',
                'default' => 30,
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
                'key' => 'tiers',
                'label' => 'Niveles de descuento',
                'type' => 'repeater',
                'fields' => [
                    ['key' => 'days', 'label' => 'Dias restantes', 'type' => 'number'],
                    ['key' => 'discount', 'label' => 'Descuento', 'type' => 'number']
                ]
            ]
        ];
    }
}
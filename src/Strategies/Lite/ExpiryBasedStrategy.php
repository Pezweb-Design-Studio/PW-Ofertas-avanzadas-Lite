<?php
namespace PW\OfertasAvanzadas\Strategies\Lite;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;

class ExpiryBasedStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $filtered_cart = \PW\OfertasAvanzadas\Services\ProductMatcher::filterCart($cart, $conditions);

        if (empty($filtered_cart)) return false;

        $tiers = $config['tiers'] ?? [];
        if (empty($tiers)) return false;

        $max_days = max(array_column($tiers, 'days'));

        foreach ($filtered_cart as $item) {
            $product = wc_get_product($item['product_id']);
            $expiry_date = get_post_meta($product->get_id(), '_expiry_date', true);

            if (!$expiry_date) continue;

            $days_to_expiry = (strtotime($expiry_date) - time()) / DAY_IN_SECONDS;

            if ($days_to_expiry <= $max_days && $days_to_expiry >= 0) {
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
        if (empty($tiers)) return 0;

        // Ordenar tiers de menor a mayor días (más urgente primero)
        usort($tiers, fn($a, $b) => $a['days'] <=> $b['days']);

        // Aplicar el descuento del tier más alto que cumpla la condición
        $applicable_discount = 0;
        foreach ($tiers as $tier) {
            if ($days <= $tier['days']) {
                $applicable_discount = $tier['discount'];
                break;
            }
        }

        return $applicable_discount;
    }

    public static function getMeta(): array {
        return [
            'name' => 'Descuento por Fecha de Vencimiento',
            'description' => 'Aplica descuentos progresivos a productos próximos a vencer',
            'effectiveness' => 5,
            'when_to_use' => 'Productos perecederos, alimentos, medicamentos. El descuento aumenta conforme se acerca la fecha de vencimiento.',
            'objective' => 'liquidation'
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
                'key' => 'tiers',
                'label' => 'Niveles de descuento',
                'type' => 'repeater',
                'description' => 'Define descuentos según días restantes hasta vencimiento. La campaña se activará automáticamente para productos dentro del rango del tier más alto. Ejemplo: si defines "30 días → 10%" y "15 días → 20%", productos con 25 días recibirán 10%, productos con 10 días recibirán 20%.',
                'fields' => [
                    ['key' => 'days', 'label' => 'Días restantes', 'type' => 'number'],
                    ['key' => 'discount', 'label' => 'Descuento', 'type' => 'number']
                ]
            ]
        ];
    }
}
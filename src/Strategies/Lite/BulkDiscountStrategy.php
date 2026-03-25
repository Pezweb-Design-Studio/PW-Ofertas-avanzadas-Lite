<?php
namespace PW\OfertasAvanzadas\Strategies\Lite;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;

class BulkDiscountStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $bulk_items = $config['bulk_items'] ?? [];
        if (empty($bulk_items)) return false;

        $cart_product_ids = array_column($cart, 'product_id');

        foreach ($bulk_items as $bulk_item) {
            if (in_array((int) ($bulk_item['product_id'] ?? 0), $cart_product_ids, false)) {
                return true;
            }
        }

        return false;
    }

    public function calculate(array $cart, array $config): array {
        $bulk_items = $config['bulk_items'] ?? [];
        $total_discount = 0;
        $affected_items = [];

        $units_sold = $this->loadUnitsSold($config['_campaign_id'] ?? 0);

        foreach ($bulk_items as $bulk_item) {
            $product_id     = (int) ($bulk_item['product_id'] ?? 0);
            $max_quantity   = (int) ($bulk_item['max_quantity'] ?? 0);
            $discount_type  = $bulk_item['discount_type'] ?? 'percentage';
            $discount_value = (float) ($bulk_item['discount_value'] ?? 0);

            if ($max_quantity <= 0 || $discount_value <= 0) continue;

            $available = max(0, $max_quantity - (int) ($units_sold[$product_id] ?? 0));
            if ($available <= 0) continue;

            foreach ($cart as $key => $cart_item) {
                if ($cart_item['product_id'] != $product_id) continue;

                $cart_qty       = (int) $cart_item['quantity'];
                $discounted_qty = min($cart_qty, $available);
                if ($discounted_qty <= 0) break;

                $price_per_unit    = $cart_item['line_total'] / $cart_qty;
                $discount_per_unit = $discount_type === 'percentage'
                    ? $price_per_unit * ($discount_value / 100)
                    : $discount_value;

                $total_discount  += $discount_per_unit * $discounted_qty;
                $affected_items[] = $key;
                break;
            }
        }

        return [
            'amount'      => $total_discount,
            'type'        => 'bulk',
            'items'       => $affected_items,
            'bulk_config' => $bulk_items,
        ];
    }

    private function loadUnitsSold(int $campaign_id): array {
        if ($campaign_id <= 0) return [];

        global $wpdb;
        $campaign = $wpdb->get_row($wpdb->prepare(
            "SELECT units_sold FROM {$wpdb->prefix}pwoa_campaigns WHERE id = %d",
            $campaign_id
        ));

        return ($campaign && $campaign->units_sold)
            ? (json_decode($campaign->units_sold, true) ?: [])
            : [];
    }

    public static function getMeta(): array {
        return [
            'name'          => 'Descuentos por Volumen (Bulk)',
            'description'   => 'Configura multiples productos con descuentos individuales limitados por cantidad',
            'effectiveness' => 5,
            'when_to_use'   => 'Ideal para ofertas especificas por producto con limite de stock en descuento. Ejemplo: "10% OFF en las primeras 50 unidades" de varios productos diferentes. Muy efectivo para liquidaciones controladas y ofertas flash.',
            'objective'     => 'aov',
        ];
    }

    public static function getConfigFields(): array {
        return [
            [
                'key'         => 'bulk_items',
                'label'       => 'Productos en oferta',
                'type'        => 'repeater',
                'description' => 'Agrega cada producto con su configuracion individual. El descuento se aplicara SOLO a las primeras X unidades especificadas.',
                'fields'      => [
                    ['key' => 'product_id',     'label' => 'Producto',                       'type' => 'product_search', 'required' => true],
                    ['key' => 'discount_type',  'label' => 'Tipo de descuento',              'type' => 'select', 'options' => ['percentage' => 'Porcentaje', 'fixed' => 'Monto fijo por unidad'], 'required' => true],
                    ['key' => 'discount_value', 'label' => 'Valor del descuento',            'type' => 'number', 'required' => true, 'description' => 'Ej: 15 para 15% o monto fijo'],
                    ['key' => 'max_quantity',   'label' => 'Cantidad con descuento',          'type' => 'number', 'required' => true, 'description' => 'Maximo de unidades que recibiran descuento'],
                    ['key' => 'badge_text',     'label' => 'Badge personalizado (opcional)',  'type' => 'text',   'required' => false, 'description' => 'Ej: "Oferta unica", "Vencen pronto"'],
                ],
            ],
        ];
    }
}

<?php
namespace PW\OfertasAvanzadas\Strategies\AOV;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;

class BulkDiscountStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $bulk_items = $config['bulk_items'] ?? [];

        if (empty($bulk_items)) return false;

        // Verificar si al menos uno de los productos del bulk está en el cart
        foreach ($bulk_items as $bulk_item) {
            $product_id = intval($bulk_item['product_id'] ?? 0);

            foreach ($cart as $cart_item) {
                if ($cart_item['product_id'] == $product_id) {
                    return true;
                }
            }
        }

        return false;
    }

    public function calculate(array $cart, array $config): array {
        $bulk_items = $config['bulk_items'] ?? [];
        $total_discount = 0;
        $affected_items = [];

        // Obtener campaign_id desde config (lo pasaremos desde DiscountEngine)
        $campaign_id = $config['_campaign_id'] ?? 0;
        $units_sold = [];

        // Cargar units_sold desde DB si tenemos campaign_id
        if ($campaign_id > 0) {
            global $wpdb;
            $campaign = $wpdb->get_row($wpdb->prepare(
                "SELECT units_sold FROM {$wpdb->prefix}pwoa_campaigns WHERE id = %d",
                $campaign_id
            ));

            if ($campaign && $campaign->units_sold) {
                $units_sold = json_decode($campaign->units_sold, true) ?: [];
            }
        }

        foreach ($bulk_items as $bulk_item) {
            $product_id = intval($bulk_item['product_id'] ?? 0);
            $max_quantity = intval($bulk_item['max_quantity'] ?? 0);
            $discount_type = $bulk_item['discount_type'] ?? 'percentage';
            $discount_value = floatval($bulk_item['discount_value'] ?? 0);

            if ($max_quantity <= 0 || $discount_value <= 0) continue;

            // Calcular unidades ya vendidas para este producto
            $sold = intval($units_sold[$product_id] ?? 0);
            $available = max(0, $max_quantity - $sold);

            // Si no hay unidades disponibles, skip
            if ($available <= 0) continue;

            // Buscar producto en cart
            foreach ($cart as $key => $cart_item) {
                if ($cart_item['product_id'] != $product_id) continue;

                $cart_qty = intval($cart_item['quantity']);

                // Aplicar descuento solo a unidades disponibles
                $discounted_qty = min($cart_qty, $available);

                if ($discounted_qty <= 0) continue;

                // Calcular precio por unidad
                $price_per_unit = $cart_item['line_total'] / $cart_qty;

                // Calcular descuento por unidad
                if ($discount_type === 'percentage') {
                    $discount_per_unit = $price_per_unit * ($discount_value / 100);
                } else {
                    $discount_per_unit = $discount_value;
                }

                // Descuento total para este producto
                $item_discount = $discount_per_unit * $discounted_qty;
                $total_discount += $item_discount;
                $affected_items[] = $key;

                break; // Solo procesar una vez por producto
            }
        }

        return [
            'amount' => $total_discount,
            'type' => 'bulk',
            'items' => $affected_items,
            'bulk_config' => $bulk_items // Guardar config para badges
        ];
    }

    public static function getMeta(): array {
        return [
            'name' => 'Descuentos por Volumen (Bulk)',
            'description' => 'Configura múltiples productos con descuentos individuales limitados por cantidad',
            'effectiveness' => 5,
            'when_to_use' => 'Ideal para ofertas específicas por producto con límite de stock en descuento. Ejemplo: "10% OFF en las primeras 50 unidades" de varios productos diferentes. Muy efectivo para liquidaciones controladas y ofertas flash.',
            'objective' => 'aov'
        ];
    }

    public static function getConfigFields(): array {
        return [
            [
                'key' => 'bulk_items',
                'label' => 'Productos en oferta',
                'type' => 'repeater',
                'description' => 'Agrega cada producto con su configuración individual. El descuento se aplicará SOLO a las primeras X unidades especificadas.',
                'fields' => [
                    [
                        'key' => 'product_id',
                        'label' => 'Producto',
                        'type' => 'product_search',
                        'required' => true
                    ],
                    [
                        'key' => 'discount_type',
                        'label' => 'Tipo de descuento',
                        'type' => 'select',
                        'options' => [
                            'percentage' => 'Porcentaje',
                            'fixed' => 'Monto fijo por unidad'
                        ],
                        'required' => true
                    ],
                    [
                        'key' => 'discount_value',
                        'label' => 'Valor del descuento',
                        'type' => 'number',
                        'required' => true,
                        'description' => 'Ej: 15 para 15% o monto fijo'
                    ],
                    [
                        'key' => 'max_quantity',
                        'label' => 'Cantidad con descuento',
                        'type' => 'number',
                        'required' => true,
                        'description' => 'Máximo de unidades que recibirán descuento'
                    ],
                    [
                        'key' => 'badge_text',
                        'label' => 'Badge personalizado (opcional)',
                        'type' => 'text',
                        'required' => false,
                        'description' => 'Ej: "Oferta única", "Vencen pronto"'
                    ]
                ]
            ]
        ];
    }
}
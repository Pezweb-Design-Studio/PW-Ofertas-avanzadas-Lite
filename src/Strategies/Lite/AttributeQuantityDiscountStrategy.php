<?php
namespace PW\OfertasAvanzadas\Strategies\Lite;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;

class AttributeQuantityDiscountStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $attribute_slug = $config['attribute_slug'] ?? '';
        $attribute_value = $config['attribute_value'] ?? '';
        $min_qty = intval($config['min_quantity'] ?? 0);

        if (empty($attribute_slug) || empty($attribute_value) || $min_qty <= 0) {
            return false;
        }

        // PASO 1: Filtrar por atributo
        $filtered_cart = \PW\OfertasAvanzadas\Services\ProductMatcher::filterCartByAttribute(
            $cart,
            $attribute_slug,
            $attribute_value
        );

        if (empty($filtered_cart)) return false;

        // PASO 2: Aplicar filtros adicionales si existen
        if (!empty($conditions)) {
            $filtered_cart = \PW\OfertasAvanzadas\Services\ProductMatcher::filterCart(
                $filtered_cart,
                $conditions
            );
        }

        if (empty($filtered_cart)) return false;

        $total_qty = array_sum(array_column($filtered_cart, 'quantity'));
        return $total_qty >= $min_qty;
    }

    public function calculate(array $cart, array $config): array {
        $attribute_slug = $config['attribute_slug'] ?? '';
        $attribute_value = $config['attribute_value'] ?? '';
        $min_qty = intval($config['min_quantity'] ?? 0);
        $discount_type = $config['discount_type'] ?? 'percentage';
        $discount_value = floatval($config['discount_value'] ?? 0);
        $max_apps = intval($config['max_applications'] ?? 0);
        $conditions = $config['_conditions'] ?? []; // Workaround: conditions guardadas en config

        if (empty($attribute_slug) || empty($attribute_value) || $min_qty <= 0 || $discount_value <= 0) {
            return ['amount' => 0, 'type' => 'attribute_quantity', 'items' => []];
        }

        // PASO 1: Filtrar por atributo
        $filtered_cart = \PW\OfertasAvanzadas\Services\ProductMatcher::filterCartByAttribute(
            $cart,
            $attribute_slug,
            $attribute_value
        );

        if (empty($filtered_cart)) {
            return ['amount' => 0, 'type' => 'attribute_quantity', 'items' => []];
        }

        // PASO 2: Aplicar filtros adicionales si existen
        if (!empty($conditions)) {
            $filtered_cart = \PW\OfertasAvanzadas\Services\ProductMatcher::filterCart(
                $filtered_cart,
                $conditions
            );
        }

        if (empty($filtered_cart)) {
            return ['amount' => 0, 'type' => 'attribute_quantity', 'items' => []];
        }

        // Contar total de productos con ese atributo
        $total_qty = array_sum(array_column($filtered_cart, 'quantity'));

        // Calcular sets completos
        $complete_sets = floor($total_qty / $min_qty);

        // Aplicar límite si existe
        $applied_sets = ($max_apps > 0) ? min($complete_sets, $max_apps) : $complete_sets;

        // Productos con descuento
        $discounted_qty = $applied_sets * $min_qty;

        if ($discounted_qty <= 0) {
            return ['amount' => 0, 'type' => 'attribute_quantity', 'items' => []];
        }

        // Calcular descuento
        $total_discount = 0;
        $affected_items = [];
        $remaining_discount_qty = $discounted_qty;

        // Distribuir descuento proporcionalmente entre los items filtrados
        foreach ($filtered_cart as $key => $item) {
            if ($remaining_discount_qty <= 0) break;

            $item_qty = intval($item['quantity']);
            $qty_to_discount = min($item_qty, $remaining_discount_qty);

            $price_per_unit = $item['line_total'] / $item_qty;

            if ($discount_type === 'percentage') {
                $item_discount = ($qty_to_discount * $price_per_unit) * ($discount_value / 100);
            } else {
                $item_discount = $discount_value * $qty_to_discount;
            }

            $total_discount += $item_discount;
            $affected_items[] = $key;
            $remaining_discount_qty -= $qty_to_discount;
        }

        return [
            'amount' => $total_discount,
            'type' => 'attribute_quantity',
            'items' => $affected_items
        ];
    }

    public static function getMeta(): array {
        return [
            'name' => 'Descuento por Atributos',
            'description' => 'Aplica descuento cuando el cliente compra X productos con un atributo específico (ej: 3 productos de la marca Nike)',
            'effectiveness' => 5,
            'when_to_use' => 'Ideal para promociones de marca, material, color, etc. Ejemplo: "Lleva 3 productos Nike y obtén 15% OFF". Muy efectivo para liquidar stock de atributos específicos o promocionar marcas.',
            'objective' => 'aov'
        ];
    }

    public static function getConfigFields(): array {
        return [
            [
                'key' => 'attribute_slug',
                'label' => 'Atributo del producto',
                'type' => 'attribute_select',
                'required' => true,
                'description' => 'Selecciona el atributo (ej: Marca, Color, Talla)'
            ],
            [
                'key' => 'attribute_value',
                'label' => 'Valor del atributo',
                'type' => 'attribute_value_select',
                'required' => true,
                'description' => 'Selecciona el valor específico del atributo'
            ],
            [
                'key' => 'min_quantity',
                'label' => 'Cantidad mínima de productos',
                'type' => 'number',
                'required' => true,
                'description' => 'Cantidad de productos con este atributo para activar el descuento'
            ],
            [
                'key' => 'discount_type',
                'label' => 'Tipo de descuento',
                'type' => 'select',
                'options' => ['percentage' => 'Porcentaje', 'fixed' => 'Monto fijo por unidad'],
                'required' => true
            ],
            [
                'key' => 'discount_value',
                'label' => 'Valor del descuento',
                'type' => 'number',
                'required' => true
            ],
            [
                'key' => 'max_applications',
                'label' => 'Límite de aplicaciones (opcional)',
                'type' => 'number',
                'required' => false,
                'description' => 'Máximo de veces que se puede aplicar el descuento. Déjalo vacío para ilimitado'
            ]
        ];
    }
}
<?php
namespace PW\OfertasAvanzadas\Strategies\Lite;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;
use PW\OfertasAvanzadas\Services\ProductMatcher;

class AttributeQuantityDiscountStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $attribute_slug  = $config['attribute_slug'] ?? '';
        $attribute_value = $config['attribute_value'] ?? '';
        $min_qty         = (int) ($config['min_quantity'] ?? 0);

        if ($attribute_slug === '' || $attribute_value === '' || $min_qty <= 0) {
            return false;
        }

        $filtered_cart = $this->filterByAttributeAndConditions($cart, $attribute_slug, $attribute_value, $conditions);

        return !empty($filtered_cart)
            && array_sum(array_column($filtered_cart, 'quantity')) >= $min_qty;
    }

    public function calculate(array $cart, array $config): array {
        $empty = ['amount' => 0, 'type' => 'attribute_quantity', 'items' => []];

        $attribute_slug  = $config['attribute_slug'] ?? '';
        $attribute_value = $config['attribute_value'] ?? '';
        $min_qty         = (int) ($config['min_quantity'] ?? 0);
        $discount_type   = $config['discount_type'] ?? 'percentage';
        $discount_value  = (float) ($config['discount_value'] ?? 0);
        $max_apps        = (int) ($config['max_applications'] ?? 0);
        $conditions      = $config['_conditions'] ?? [];

        if ($attribute_slug === '' || $attribute_value === '' || $min_qty <= 0 || $discount_value <= 0) {
            return $empty;
        }

        $filtered_cart = $this->filterByAttributeAndConditions($cart, $attribute_slug, $attribute_value, $conditions);
        if (empty($filtered_cart)) return $empty;

        $total_qty      = array_sum(array_column($filtered_cart, 'quantity'));
        $complete_sets  = floor($total_qty / $min_qty);
        $applied_sets   = $max_apps > 0 ? min($complete_sets, $max_apps) : $complete_sets;
        $discounted_qty = $applied_sets * $min_qty;

        if ($discounted_qty <= 0) return $empty;

        $total_discount         = 0;
        $affected_items         = [];
        $remaining_discount_qty = $discounted_qty;

        foreach ($filtered_cart as $key => $item) {
            if ($remaining_discount_qty <= 0) break;

            $item_qty        = (int) $item['quantity'];
            $qty_to_discount = min($item_qty, $remaining_discount_qty);
            $price_per_unit  = $item['line_total'] / $item_qty;

            $total_discount += $discount_type === 'percentage'
                ? ($qty_to_discount * $price_per_unit) * ($discount_value / 100)
                : $discount_value * $qty_to_discount;

            $affected_items[]        = $key;
            $remaining_discount_qty -= $qty_to_discount;
        }

        return [
            'amount' => $total_discount,
            'type'   => 'attribute_quantity',
            'items'  => $affected_items,
        ];
    }

    /** @return array Filtered cart items matching attribute + conditions */
    private function filterByAttributeAndConditions(array $cart, string $slug, string $value, array $conditions): array {
        $filtered = ProductMatcher::filterCartByAttribute($cart, $slug, $value);
        if (empty($filtered) || empty($conditions)) return $filtered;

        return ProductMatcher::filterCart($filtered, $conditions);
    }

    public static function getMeta(): array {
        return [
            'name'          => 'Descuento por Atributos',
            'description'   => 'Aplica descuento cuando el cliente compra X productos con un atributo especifico (ej: 3 productos de la marca Nike)',
            'effectiveness' => 5,
            'when_to_use'   => 'Ideal para promociones de marca, material, color, etc. Ejemplo: "Lleva 3 productos Nike y obten 15% OFF". Muy efectivo para liquidar stock de atributos especificos o promocionar marcas.',
            'objective'     => 'aov',
        ];
    }

    public static function getConfigFields(): array {
        return [
            ['key' => 'attribute_slug',   'label' => 'Atributo del producto',             'type' => 'attribute_select',       'required' => true, 'description' => 'Selecciona el atributo (ej: Marca, Color, Talla)'],
            ['key' => 'attribute_value',  'label' => 'Valor del atributo',                'type' => 'attribute_value_select', 'required' => true, 'description' => 'Selecciona el valor especifico del atributo'],
            ['key' => 'min_quantity',     'label' => 'Cantidad minima de productos',      'type' => 'number',                 'required' => true, 'description' => 'Cantidad de productos con este atributo para activar el descuento'],
            ['key' => 'discount_type',    'label' => 'Tipo de descuento',                 'type' => 'select', 'options' => ['percentage' => 'Porcentaje', 'fixed' => 'Monto fijo por unidad'], 'required' => true],
            ['key' => 'discount_value',   'label' => 'Valor del descuento',               'type' => 'number',                 'required' => true],
            ['key' => 'max_applications', 'label' => 'Limite de aplicaciones (opcional)',  'type' => 'number',                 'required' => false, 'description' => 'Maximo de veces que se puede aplicar el descuento. Dejalo vacio para ilimitado'],
        ];
    }
}

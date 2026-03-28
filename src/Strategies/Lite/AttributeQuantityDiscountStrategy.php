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
            'key'           => 'attribute_quantity_discount',
            'name'          => __('Attribute-based quantity discount', 'pw-ofertas-avanzadas'),
            'description'   => __('Discount when the cart reaches X units that share a product attribute (e.g. three items with the same brand).', 'pw-ofertas-avanzadas'),
            'effectiveness' => 5,
            'when_to_use'   => __('Brand, material, color, or similar promos. Example: “Buy 3 Nike items, get 15% off.” Good for clearing attribute-specific stock.', 'pw-ofertas-avanzadas'),
            'objective'     => 'aov',
        ];
    }

    public static function getConfigFields(): array {
        return [
            ['key' => 'attribute_slug',   'label' => __('Product attribute', 'pw-ofertas-avanzadas'),             'type' => 'attribute_select',       'required' => true, 'description' => __('Choose the attribute (e.g. brand, color, size).', 'pw-ofertas-avanzadas')],
            ['key' => 'attribute_value',  'label' => __('Attribute value', 'pw-ofertas-avanzadas'),                'type' => 'attribute_value_select', 'required' => true, 'description' => __('Choose the specific attribute term.', 'pw-ofertas-avanzadas')],
            ['key' => 'min_quantity',     'label' => __('Minimum matching quantity', 'pw-ofertas-avanzadas'),      'type' => 'number',                 'required' => true, 'description' => __('How many matching items are required to trigger the discount.', 'pw-ofertas-avanzadas')],
            ['key' => 'discount_type',    'label' => __('Discount type', 'pw-ofertas-avanzadas'),                 'type' => 'select', 'options' => ['percentage' => __('Percentage', 'pw-ofertas-avanzadas'), 'fixed' => __('Fixed per unit', 'pw-ofertas-avanzadas')], 'required' => true],
            ['key' => 'discount_value',   'label' => __('Discount value', 'pw-ofertas-avanzadas'),               'type' => 'number',                 'required' => true],
            ['key' => 'max_applications', 'label' => __('Max applications (optional)', 'pw-ofertas-avanzadas'),  'type' => 'number',                 'required' => false, 'description' => __('Cap how many times the discount can apply. Leave empty for unlimited.', 'pw-ofertas-avanzadas')],
        ];
    }
}

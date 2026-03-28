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
            'key'           => 'bulk_discount',
            'name'          => __('Volume (bulk) discounts', 'pw-ofertas-avanzadas'),
            'description'   => __('Set up multiple products, each with its own quantity-capped discount.', 'pw-ofertas-avanzadas'),
            'effectiveness' => 5,
            'when_to_use'   => __('Great for per-product offers with a limited discounted quantity. Example: 10% off the first 50 units of several SKUs. Strong for controlled clearances and flash deals.', 'pw-ofertas-avanzadas'),
            'objective'     => 'aov',
        ];
    }

    public static function getConfigFields(): array {
        return [
            [
                'key'         => 'bulk_items',
                'label'       => __('Products on sale', 'pw-ofertas-avanzadas'),
                'type'        => 'repeater',
                'description' => __('Add each product with its own settings. The discount applies only to the first X units you specify.', 'pw-ofertas-avanzadas'),
                'fields'      => [
                    ['key' => 'product_id',     'label' => __('Product', 'pw-ofertas-avanzadas'),                       'type' => 'product_search', 'required' => true],
                    ['key' => 'discount_type',  'label' => __('Discount type', 'pw-ofertas-avanzadas'),              'type' => 'select', 'options' => ['percentage' => __('Percentage', 'pw-ofertas-avanzadas'), 'fixed' => __('Fixed per unit', 'pw-ofertas-avanzadas')], 'required' => true],
                    ['key' => 'discount_value', 'label' => __('Discount value', 'pw-ofertas-avanzadas'),            'type' => 'number', 'required' => true, 'description' => __('e.g. 15 for 15% or a fixed amount per unit.', 'pw-ofertas-avanzadas')],
                    ['key' => 'max_quantity',   'label' => __('Discounted quantity', 'pw-ofertas-avanzadas'),          'type' => 'number', 'required' => true, 'description' => __('Maximum units that receive the discount.', 'pw-ofertas-avanzadas')],
                    ['key' => 'badge_text',     'label' => __('Custom badge (optional)', 'pw-ofertas-avanzadas'),  'type' => 'text',   'required' => false, 'description' => __('e.g. “Limited offer”, “Expiring soon”.', 'pw-ofertas-avanzadas')],
                ],
            ],
        ];
    }
}

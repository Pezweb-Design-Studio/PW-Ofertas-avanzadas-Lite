<?php
namespace PW\OfertasAvanzadas\Strategies\Pro;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;
use PW\OfertasAvanzadas\Services\ProductMatcher;

class LowStockStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $filtered_cart = ProductMatcher::filterCart($cart, $conditions);
        if (empty($filtered_cart)) return false;

        $stock_threshold = $config['stock_threshold'] ?? 10;

        foreach ($filtered_cart as $item) {
            $product = wc_get_product($item['product_id']);
            if ($product->managing_stock() && $product->get_stock_quantity() <= $stock_threshold) {
                return true;
            }
        }

        return false;
    }

    public function calculate(array $cart, array $config): array {
        $stock_threshold = $config['stock_threshold'] ?? 10;
        $total_discount  = 0;
        $affected_items  = [];

        foreach ($cart as $key => $item) {
            $product = wc_get_product($item['product_id']);
            if (!$product->managing_stock()) continue;
            if ($product->get_stock_quantity() > $stock_threshold) continue;

            $total_discount  += $config['discount_type'] === 'percentage'
                ? $item['line_total'] * ($config['discount_value'] / 100)
                : $config['discount_value'] * $item['quantity'];
            $affected_items[] = $key;
        }

        return [
            'amount' => $total_discount,
            'type'   => $config['discount_type'],
            'items'  => $affected_items,
        ];
    }

    public static function getMeta(): array {
        return [
            'key'           => 'low_stock',
            'name'          => __('Low stock discount', 'pw-ofertas-avanzadas'),
            'description'   => __('Automatically discount products when on-hand quantity falls below a threshold.', 'pw-ofertas-avanzadas'),
            'effectiveness' => 4,
            'when_to_use'   => __('Seasonal clearance, end-of-line, or when you want urgency from scarcity.', 'pw-ofertas-avanzadas'),
            'objective'     => 'liquidation',
        ];
    }

    public static function getConfigFields(): array {
        return [
            ['key' => 'stock_threshold', 'label' => __('Stock threshold', 'pw-ofertas-avanzadas'),     'type' => 'number', 'default' => 10, 'required' => true, 'description' => __('Discount applies when stock is at or below this quantity.', 'pw-ofertas-avanzadas')],
            ['key' => 'discount_type',   'label' => __('Discount type', 'pw-ofertas-avanzadas'),   'type' => 'select', 'options' => ['percentage' => __('Percentage', 'pw-ofertas-avanzadas'), 'fixed' => __('Fixed per unit', 'pw-ofertas-avanzadas')], 'required' => true],
            ['key' => 'discount_value',  'label' => __('Discount value', 'pw-ofertas-avanzadas'), 'type' => 'number', 'required' => true],
        ];
    }
}

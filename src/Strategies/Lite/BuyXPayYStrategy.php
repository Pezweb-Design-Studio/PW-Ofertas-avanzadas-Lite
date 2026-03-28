<?php
namespace PW\OfertasAvanzadas\Strategies\Lite;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;
use PW\OfertasAvanzadas\Services\ProductMatcher;

class BuyXPayYStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $filtered_cart = ProductMatcher::filterCart($cart, $conditions);
        if (empty($filtered_cart)) return false;

        $buy_qty = (int) ($config['buy_quantity'] ?? 0);
        $pay_qty = (int) ($config['pay_quantity'] ?? 0);

        if ($buy_qty <= 0 || $pay_qty <= 0 || $buy_qty <= $pay_qty) return false;

        return array_sum(array_column($filtered_cart, 'quantity')) >= $buy_qty;
    }

    public function calculate(array $cart, array $config): array {
        $buy_qty  = (int) ($config['buy_quantity'] ?? 0);
        $pay_qty  = (int) ($config['pay_quantity'] ?? 0);
        $max_sets = (int) ($config['max_sets'] ?? 0);

        if ($buy_qty <= 0 || $pay_qty <= 0) {
            return ['amount' => 0, 'type' => 'buy_x_pay_y', 'items' => []];
        }

        $total_discount = 0;
        $affected_items = [];
        $free_per_set   = $buy_qty - $pay_qty;

        foreach ($cart as $key => $item) {
            $cart_qty = (int) $item['quantity'];
            if ($cart_qty < $buy_qty) continue;

            $complete_sets = floor($cart_qty / $buy_qty);
            $applied_sets  = $max_sets > 0 ? min($complete_sets, $max_sets) : $complete_sets;
            $free_units    = $applied_sets * $free_per_set;

            if ($free_units <= 0) continue;

            $total_discount  += $free_units * ($item['line_total'] / $cart_qty);
            $affected_items[] = $key;
        }

        return [
            'amount' => $total_discount,
            'type'   => 'buy_x_pay_y',
            'items'  => $affected_items,
        ];
    }

    public static function getMeta(): array {
        return [
            'key'           => 'buy_x_pay_y',
            'name'          => __('Buy X pay Y', 'pw-ofertas-avanzadas'),
            'description'   => __('“2 for 1”, “3 for 2”, etc. The customer takes X units but pays for only Y.', 'pw-ofertas-avanzadas'),
            'effectiveness' => 5,
            'when_to_use'   => __('Very effective for fast-moving stock: consumables, overstocks, or aggressive promos (e.g. 3-for-2, 2-for-1, buy 5 pay 4).', 'pw-ofertas-avanzadas'),
            'objective'     => 'aov',
        ];
    }

    public static function getConfigFields(): array {
        return [
            ['key' => 'buy_quantity', 'label' => __('Buy quantity', 'pw-ofertas-avanzadas'), 'type' => 'number', 'required' => true, 'description' => __('Units the customer receives in each set.', 'pw-ofertas-avanzadas')],
            ['key' => 'pay_quantity', 'label' => __('Pay quantity', 'pw-ofertas-avanzadas'),  'type' => 'number', 'required' => true, 'description' => __('Units the customer pays for in each set.', 'pw-ofertas-avanzadas')],
            ['key' => 'max_sets',    'label' => __('Max applications (optional)', 'pw-ofertas-avanzadas'), 'type' => 'number', 'required' => false, 'description' => __('Maximum sets per order. Leave empty for unlimited.', 'pw-ofertas-avanzadas')],
        ];
    }
}

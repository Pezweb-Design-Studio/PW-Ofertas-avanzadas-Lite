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
            'name'          => 'Lleva X Paga Y',
            'description'   => 'Descuento tipo "2x1", "3x2", etc. El cliente lleva X unidades pero paga solo Y unidades',
            'effectiveness' => 5,
            'when_to_use'   => 'Altamente efectivo para mover inventario rapido. Ideal para productos de consumo frecuente, stock excedente, o promociones agresivas. Ejemplos: 3x2 en shampoo, 2x1 en bebidas, lleva 5 paga 4 en snacks.',
            'objective'     => 'aov',
        ];
    }

    public static function getConfigFields(): array {
        return [
            ['key' => 'buy_quantity', 'label' => 'Llevas (cantidad)', 'type' => 'number', 'required' => true, 'description' => 'Cantidad de unidades que el cliente lleva'],
            ['key' => 'pay_quantity', 'label' => 'Pagas (cantidad)',  'type' => 'number', 'required' => true, 'description' => 'Cantidad de unidades que el cliente paga'],
            ['key' => 'max_sets',    'label' => 'Limite de aplicaciones (opcional)', 'type' => 'number', 'required' => false, 'description' => 'Maximo de sets que se pueden aplicar. Dejalo vacio para ilimitado'],
        ];
    }
}

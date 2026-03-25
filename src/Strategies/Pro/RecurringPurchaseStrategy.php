<?php
namespace PW\OfertasAvanzadas\Strategies\Pro;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;
use PW\OfertasAvanzadas\Services\ProductMatcher;

class RecurringPurchaseStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        if (!is_user_logged_in()) return false;

        $filtered_cart = ProductMatcher::filterCart($cart, $conditions);
        if (empty($filtered_cart)) return false;

        $user_id            = get_current_user_id();
        $required_purchases = $config['required_purchases'] ?? 3;

        foreach ($filtered_cart as $item) {
            if ($this->getUserProductPurchases($user_id, $item['product_id']) >= $required_purchases) {
                return true;
            }
        }

        return false;
    }

    public function calculate(array $cart, array $config): array {
        $user_id            = get_current_user_id();
        $required_purchases = $config['required_purchases'] ?? 3;
        $total_discount     = 0;
        $affected_items     = [];

        foreach ($cart as $key => $item) {
            if ($this->getUserProductPurchases($user_id, $item['product_id']) < $required_purchases) {
                continue;
            }

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

    private function getUserProductPurchases(int $user_id, int $product_id): int {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT order_items.order_id)
             FROM {$wpdb->prefix}woocommerce_order_items as order_items
             LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as itemmeta
                 ON order_items.order_item_id = itemmeta.order_item_id
             LEFT JOIN {$wpdb->prefix}posts as posts
                 ON order_items.order_id = posts.ID
             LEFT JOIN {$wpdb->prefix}postmeta as postmeta
                 ON posts.ID = postmeta.post_id
             WHERE posts.post_type = 'shop_order'
             AND posts.post_status IN ('wc-completed', 'wc-processing')
             AND postmeta.meta_key = '_customer_user'
             AND postmeta.meta_value = %d
             AND itemmeta.meta_key = '_product_id'
             AND itemmeta.meta_value = %d",
            $user_id,
            $product_id
        ));
    }

    public static function getMeta(): array {
        return [
            'name'          => 'Descuento por Compras Recurrentes',
            'description'   => 'Recompensa a clientes que compran el mismo producto multiples veces',
            'effectiveness' => 5,
            'when_to_use'   => 'Productos de recompra: cosmeticos, suplementos, alimentos. Aumenta retencion 40-60%. Ideal para suscripciones o productos consumibles.',
            'objective'     => 'loyalty',
        ];
    }

    public static function getConfigFields(): array {
        return [
            ['key' => 'required_purchases', 'label' => 'Numero de compras previas requeridas', 'type' => 'number', 'default' => 3, 'required' => true, 'description' => 'Cuantas veces debe haber comprado el producto para obtener descuento'],
            ['key' => 'discount_type',      'label' => 'Tipo de descuento',                    'type' => 'select', 'options' => ['percentage' => 'Porcentaje', 'fixed' => 'Monto fijo por unidad'], 'required' => true],
            ['key' => 'discount_value',     'label' => 'Valor del descuento',                  'type' => 'number', 'required' => true],
        ];
    }
}

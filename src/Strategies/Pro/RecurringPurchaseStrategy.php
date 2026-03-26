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
        $required_purchases = (int) ($config['required_purchases'] ?? 3);

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

        // Detectar si HPOS esta activo (tabla wp_wc_orders existe, WC 7.1+)
        $hpos_table = $wpdb->prefix . 'wc_orders';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$hpos_table}'") === $hpos_table) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT oi.order_id)
                 FROM {$wpdb->prefix}woocommerce_order_items AS oi
                 INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim
                     ON oi.order_item_id = oim.order_item_id AND oim.meta_key = '_product_id'
                 INNER JOIN {$wpdb->prefix}wc_orders AS o ON oi.order_id = o.id
                 WHERE o.type = 'shop_order'
                   AND o.status IN ('wc-completed', 'wc-processing')
                   AND o.customer_id = %d
                   AND oim.meta_value = %d",
                $user_id,
                $product_id
            ));
        }

        // Fallback: tabla legacy wp_posts
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT oi.order_id)
             FROM {$wpdb->prefix}woocommerce_order_items AS oi
             INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim
                 ON oi.order_item_id = oim.order_item_id AND oim.meta_key = '_product_id'
             INNER JOIN {$wpdb->prefix}posts AS p ON oi.order_id = p.ID
             INNER JOIN {$wpdb->prefix}postmeta AS pm
                 ON p.ID = pm.post_id AND pm.meta_key = '_customer_user'
             WHERE p.post_type = 'shop_order'
               AND p.post_status IN ('wc-completed', 'wc-processing')
               AND pm.meta_value = %d
               AND oim.meta_value = %d",
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

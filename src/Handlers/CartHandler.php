<?php
namespace PW\OfertasAvanzadas\Handlers;

use PW\OfertasAvanzadas\Services\DiscountEngine;

class CartHandler {

    public function __construct() {
        add_action('woocommerce_cart_calculate_fees', [$this, 'applyDiscounts']);
        add_action('woocommerce_checkout_order_processed', [$this, 'trackDiscount'], 10, 1);
    }

    public function applyDiscounts(): void {
        if (is_admin() && !defined('DOING_AJAX')) return;

        $cart = WC()->cart->get_cart();
        if (empty($cart)) return;

        $discount = DiscountEngine::calculateBestDiscount($cart);

        if ($discount['amount'] > 0) {
            WC()->cart->add_fee(
                $discount['label'] ?? 'Descuento aplicado',
                -$discount['amount'],
                false
            );

            WC()->session->set('pwoa_applied_campaign', $discount['campaign_id'] ?? null);
        }
    }

    public function trackDiscount(int $order_id): void {
        $campaign_id = WC()->session->get('pwoa_applied_campaign');

        if (!$campaign_id) return;

        $order = wc_get_order($order_id);

        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}pwoa_stats", [
            'campaign_id' => $campaign_id,
            'order_id' => $order_id,
            'discount_amount' => abs($order->get_total_discount()),
            'original_total' => $order->get_total() + abs($order->get_total_discount())
        ]);

        WC()->session->set('pwoa_applied_campaign', null);
    }
}
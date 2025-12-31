<?php
namespace PW\OfertasAvanzadas\Handlers;

use PW\OfertasAvanzadas\Services\DiscountEngine;

class CartHandler {

    public function __construct() {
        add_action('woocommerce_cart_calculate_fees', [$this, 'applyDiscounts']);
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
        } else {
            error_log('PWOA Debug - NO hay descuento (amount <= 0)');
        }
    }
}
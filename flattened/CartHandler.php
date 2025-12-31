<?php
namespace PW\OfertasAvanzadas\Handlers;

use PW\OfertasAvanzadas\Services\DiscountEngine;
use PW\OfertasAvanzadas\Repositories\CampaignRepository;

class CartHandler {

    public function __construct() {
        add_action('woocommerce_cart_calculate_fees', [$this, 'applyDiscounts']);
        add_action('woocommerce_checkout_order_processed', [$this, 'trackDiscount'], 10, 1);

        // DEBUG: Mostrar info en el carrito
        add_action('woocommerce_before_cart', [$this, 'debugCartInfo']);
    }

    public function applyDiscounts(): void {
        if (is_admin() && !defined('DOING_AJAX')) return;

        $cart = WC()->cart->get_cart();
        if (empty($cart)) return;

        $discount = DiscountEngine::calculateBestDiscount($cart);

        // DEBUG
        error_log('PWOA Debug - Descuento calculado: ' . print_r($discount, true));

        if ($discount['amount'] > 0) {
            WC()->cart->add_fee(
                $discount['label'] ?? 'Descuento aplicado',
                -$discount['amount'],
                false
            );

            WC()->session->set('pwoa_applied_campaign', $discount['campaign_id'] ?? null);

            // DEBUG
            error_log('PWOA Debug - Descuento aplicado: ' . $discount['amount']);
        } else {
            error_log('PWOA Debug - NO hay descuento (amount <= 0)');
        }
    }

    public function debugCartInfo(): void {
        $cart = WC()->cart->get_cart();

        echo '<div style="background: #fff3cd; border: 2px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px;">';
        echo '<h3 style="margin-top: 0;">DEBUG - Info del Carrito</h3>';
        echo '<p><strong>Total items:</strong> ' . count($cart) . '</p>';

        foreach ($cart as $key => $item) {
            $product = wc_get_product($item['product_id']);
            $expiry_date = get_post_meta($product->get_id(), '_expiry_date', true);
            $days_to_expiry = $expiry_date ? ceil((strtotime($expiry_date) - time()) / DAY_IN_SECONDS) : 'N/A';

            echo '<div style="background: white; padding: 10px; margin: 10px 0; border-left: 4px solid #007cba;">';
            echo '<p><strong>Producto:</strong> ' . $product->get_name() . ' (ID: ' . $product->get_id() . ')</p>';
            echo '<p><strong>Precio:</strong> ' . wc_price($item['line_total']) . '</p>';
            echo '<p><strong>Fecha vencimiento:</strong> ' . ($expiry_date ?: 'No configurada') . '</p>';
            echo '<p><strong>Dias para vencer:</strong> ' . $days_to_expiry . '</p>';
            echo '</div>';
        }

        $campaigns = CampaignRepository::getActive();
        echo '<p><strong>Campanas activas:</strong> ' . count($campaigns) . '</p>';

        foreach ($campaigns as $campaign) {
            echo '<div style="background: #e7f5ff; padding: 10px; margin: 10px 0;">';
            echo '<p><strong>Campana:</strong> ' . $campaign->name . '</p>';
            echo '<p><strong>Estrategia:</strong> ' . $campaign->strategy . '</p>';
            echo '<p><strong>Config:</strong> <pre style="font-size: 11px;">' . print_r(json_decode($campaign->config, true), true) . '</pre></p>';
            echo '</div>';
        }

        echo '<p><em>Revisa tambien: wp-content/debug.log</em></p>';
        echo '</div>';
    }

    public function trackDiscount(int $order_id): void {
        $campaign_id = WC()->session->get('pwoa_applied_campaign');

        if (!$campaign_id) return;

        $order = wc_get_order($order_id);
        $campaign = CampaignRepository::getById($campaign_id);

        if (!$campaign) return;

        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}pwoa_stats", [
            'campaign_id' => $campaign_id,
            'campaign_snapshot' => wp_json_encode([
                'name' => $campaign->name,
                'objective' => $campaign->objective,
                'strategy' => $campaign->strategy,
                'discount_type' => $campaign->discount_type,
                'config' => json_decode($campaign->config, true),
                'priority' => $campaign->priority
            ]),
            'order_id' => $order_id,
            'discount_amount' => abs($order->get_total_discount()),
            'original_total' => $order->get_total() + abs($order->get_total_discount())
        ]);

        WC()->session->set('pwoa_applied_campaign', null);
    }
}
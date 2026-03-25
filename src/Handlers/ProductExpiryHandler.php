<?php
namespace PW\OfertasAvanzadas\Handlers;

defined('ABSPATH') || exit;

class ProductExpiryHandler {

    public function __construct() {
        foreach ([
            ['woocommerce_product_options_general_product_data', 'addExpiryField'],
            ['woocommerce_process_product_meta', 'saveExpiryField'],
            ['woocommerce_single_product_summary', 'showExpiryNotice', 15],
        ] as $hook) {
            add_action($hook[0], [$this, $hook[1]], $hook[2] ?? 10);
        }
    }

    public function addExpiryField(): void {
        global $post;
        $value = esc_attr(get_post_meta($post->ID, '_expiry_date', true));
        echo <<<HTML
        <div class="options_group">
            <p class="form-field">
                <label for="_expiry_date">Fecha de Vencimiento</label>
                <input type="date" id="_expiry_date" name="_expiry_date" value="{$value}" style="width: 200px;">
                <span class="description">Fecha en que el producto vence (para descuentos automaticos)</span>
            </p>
        </div>
        HTML;
    }

    public function saveExpiryField(int $post_id): void {
        if (!isset($_POST['_expiry_date'])) {
            return;
        }

        $expiry_date = sanitize_text_field($_POST['_expiry_date']);
        $expiry_date
            ? update_post_meta($post_id, '_expiry_date', $expiry_date)
            : delete_post_meta($post_id, '_expiry_date');
    }

    public function showExpiryNotice(): void {
        global $product;

        if (!$product) return;

        $expiry_date = get_post_meta($product->get_id(), '_expiry_date', true);
        if (!$expiry_date) return;

        $days_to_expiry = (strtotime($expiry_date) - time()) / DAY_IN_SECONDS;
        if ($days_to_expiry <= 0 || $days_to_expiry > 30) return;

        $days = ceil($days_to_expiry);
        $class = esc_attr($days <= 7 ? 'error' : 'info');
        $label = esc_html($days) . ' dia' . ($days > 1 ? 's' : '');
        echo <<<HTML
        <div class="woocommerce-{$class} p-4 mb-4" style="background: #fff3cd; border: 1px solid #ffc107;">
            <p style="margin: 0; font-weight: bold;">Este producto vence en {$label}</p>
        </div>
        HTML;
    }
}

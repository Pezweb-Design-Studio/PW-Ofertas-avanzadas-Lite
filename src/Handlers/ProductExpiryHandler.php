<?php
namespace PW\OfertasAvanzadas\Handlers;

class ProductExpiryHandler {

    public function __construct() {
        add_action('woocommerce_product_options_general_product_data', [$this, 'addExpiryField']);
        add_action('woocommerce_process_product_meta', [$this, 'saveExpiryField']);
        add_action('woocommerce_single_product_summary', [$this, 'showExpiryNotice'], 15);
    }

    public function addExpiryField(): void {
        global $post;

        echo '<div class="options_group">';

        $current_value = get_post_meta($post->ID, '_expiry_date', true);

        echo '<p class="form-field">';
        echo '<label for="_expiry_date">Fecha de Vencimiento</label>';
        echo '<input type="date" id="_expiry_date" name="_expiry_date" value="' . esc_attr($current_value) . '" style="width: 200px;">';
        echo '<span class="description">Fecha en que el producto vence (para descuentos automaticos)</span>';
        echo '</p>';

        echo '</div>';
    }

    public function saveExpiryField(int $post_id): void {
        if (isset($_POST['_expiry_date'])) {
            $expiry_date = sanitize_text_field($_POST['_expiry_date']);

            if ($expiry_date) {
                update_post_meta($post_id, '_expiry_date', $expiry_date);
            } else {
                delete_post_meta($post_id, '_expiry_date');
            }
        }
    }

    public function showExpiryNotice(): void {
        global $product;

        if (!$product) return;

        $expiry_date = get_post_meta($product->get_id(), '_expiry_date', true);

        if (!$expiry_date) return;

        $days_to_expiry = (strtotime($expiry_date) - time()) / DAY_IN_SECONDS;

        if ($days_to_expiry > 0 && $days_to_expiry <= 30) {
            $days = ceil($days_to_expiry);
            $class = $days <= 7 ? 'error' : 'info';

            echo '<div class="woocommerce-' . esc_attr($class) . ' p-4 mb-4" style="background: #fff3cd; border: 1px solid #ffc107;">';
            echo '<p style="margin: 0; font-weight: bold;">Este producto vence en ' . esc_html($days) . ' dia' . ($days > 1 ? 's' : '') . '</p>';
            echo '</div>';
        }
    }
}
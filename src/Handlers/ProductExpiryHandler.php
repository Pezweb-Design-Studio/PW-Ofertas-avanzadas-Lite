<?php
namespace PW\OfertasAvanzadas\Handlers;

defined('ABSPATH') || exit;

class ProductExpiryHandler
{
    public function __construct()
    {
        foreach ([
            ['woocommerce_product_options_general_product_data', 'addExpiryField'],
            ['woocommerce_process_product_meta', 'saveExpiryField'],
            ['woocommerce_single_product_summary', 'showExpiryNotice', 15],
            ['admin_enqueue_scripts', 'enqueueAdminStyles'],
            ['wp_enqueue_scripts', 'enqueueFrontStyles'],
        ] as $hook) {
            add_action($hook[0], [$this, $hook[1]], $hook[2] ?? 10);
        }
    }

    public function enqueueAdminStyles(string $hook): void
    {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== 'product') {
            return;
        }
        wp_enqueue_style(
            'pwoa-product-expiry',
            PWOA_URL . 'assets/css/product-expiry.css',
            [],
            PWOA_VERSION,
        );
    }

    public function enqueueFrontStyles(): void
    {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }
        wp_enqueue_style(
            'pwoa-product-expiry',
            PWOA_URL . 'assets/css/product-expiry.css',
            [],
            PWOA_VERSION,
        );
    }

    public function addExpiryField(): void
    {
        global $post;
        $value = esc_attr(get_post_meta($post->ID, '_expiry_date', true));
        echo '<div class="options_group pwoa-expiry-field"><p class="form-field">';
        echo '<label for="_expiry_date">' . esc_html__('Expiry date', 'pw-ofertas-avanzadas') . '</label>';
        echo '<input type="date" id="_expiry_date" name="_expiry_date" value="' . $value . '" class="short">';
        echo '<span class="description">' . esc_html__(
            'Date when the product expires (used for automatic discounts).',
            'pw-ofertas-avanzadas',
        ) . '</span>';
        echo '</p></div>';
    }

    public function saveExpiryField(int $post_id): void
    {
        if (!isset($_POST['_expiry_date'])) {
            return;
        }

        $expiry_date = sanitize_text_field(wp_unslash((string) $_POST['_expiry_date']));
        $expiry_date
            ? update_post_meta($post_id, '_expiry_date', $expiry_date)
            : delete_post_meta($post_id, '_expiry_date');
    }

    public function showExpiryNotice(): void
    {
        global $product;

        if (!$product) {
            return;
        }

        $expiry_date = get_post_meta($product->get_id(), '_expiry_date', true);
        if (!$expiry_date) {
            return;
        }

        $days_to_expiry = (strtotime($expiry_date) - time()) / DAY_IN_SECONDS;
        if ($days_to_expiry <= 0 || $days_to_expiry > 30) {
            return;
        }

        $days   = (int) ceil($days_to_expiry);
        $urgent = $days <= 7;
        $class  = $urgent
            ? 'pwoa-product-expiry-notice pwoa-product-expiry-notice--soon'
            : 'pwoa-product-expiry-notice';

        echo '<div class="' . esc_attr($class) . '"><p>';
        if ($days === 1) {
            echo esc_html__('This product expires in 1 day.', 'pw-ofertas-avanzadas');
        } else {
            echo esc_html(
                sprintf(
                    /* translators: %d: whole days until expiry */
                    __('This product expires in %d days.', 'pw-ofertas-avanzadas'),
                    $days,
                ),
            );
        }
        echo '</p></div>';
    }
}

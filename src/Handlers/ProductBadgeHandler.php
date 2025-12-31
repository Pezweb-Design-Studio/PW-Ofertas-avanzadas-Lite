<?php
namespace PW\OfertasAvanzadas\Handlers;

use PW\OfertasAvanzadas\Repositories\CampaignRepository;
use PW\OfertasAvanzadas\Services\ProductMatcher;

class ProductBadgeHandler {

    private static $campaigns_cache = null;
    private static $badge_cache = [];

    public function __construct() {
        // Hooks estándar de WooCommerce
        add_filter('woocommerce_product_get_image', [$this, 'addBadgeToImage'], 10, 5);
        add_filter('woocommerce_cart_item_thumbnail', [$this, 'addBadgeToCartThumbnail'], 10, 3);
        add_filter('post_thumbnail_html', [$this, 'addBadgeToThumbnail'], 10, 5);

        // CSS y JavaScript para badges
        add_action('wp_head', [$this, 'addBadgeStyles'], 999);
        add_action('wp_footer', [$this, 'injectBadgeScript'], 999);
    }

    public function addBadgeStyles(): void {
        echo '<style>
            .pwoa-badge-wrapper {
                position: relative !important;
                display: inline-block !important;
            }
            .pwoa-discount-badge {
                position: absolute !important;
                top: 8px !important;
                right: 8px !important;
                background: #3b82f6 !important;
                color: white !important;
                padding: 6px 10px !important;
                border-radius: 6px !important;
                font-size: 13px !important;
                font-weight: bold !important;
                line-height: 1 !important;
                z-index: 999 !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3) !important;
                pointer-events: none !important;
            }
            .pwoa-discount-badge.fixed-amount {
                background: #10b981 !important;
            }
            
            /* Para bloques de WooCommerce */
            .wc-block-components-product-image {
                position: relative !important;
            }
            .wc-block-components-product-image .pwoa-discount-badge {
                position: absolute !important;
                top: 8px !important;
                right: 8px !important;
            }
        </style>';
    }

    public function injectBadgeScript(): void {
        if (!is_shop() && !is_product_category() && !is_product() && !is_cart() && !is_checkout()) {
            return;
        }

        // Obtener todos los productos visibles en la página
        global $wp_query;
        $product_badges = [];

        if (is_product()) {
            global $post;
            if ($post) {
                $badge = $this->getBadgeForProduct($post->ID);
                if ($badge) {
                    $product_badges[$post->ID] = $badge;
                }
            }
        } else {
            // Para listados de productos
            $products = wc_get_products([
                'limit' => 100,
                'status' => 'publish',
                'return' => 'ids'
            ]);

            foreach ($products as $product_id) {
                $badge = $this->getBadgeForProduct($product_id);
                if ($badge) {
                    $product_badges[$product_id] = $badge;
                }
            }
        }

        if (empty($product_badges)) return;

        ?>
        <script>
            (function() {
                const badges = <?php echo json_encode($product_badges); ?>;

                function addBadges() {
                    // Para listado de productos (bloques de WooCommerce)
                    document.querySelectorAll('[data-wp-context]').forEach(function(item) {
                        try {
                            const context = JSON.parse(item.dataset.wpContext);
                            const productId = context.productId;

                            if (!productId || !badges[productId]) return;

                            // Buscar el contenedor de imagen
                            const imageContainer = item.querySelector('.wc-block-components-product-image__inner-container');
                            if (!imageContainer) return;

                            // Verificar si ya existe el badge
                            if (imageContainer.querySelector('.pwoa-discount-badge')) return;

                            // Insertar badge
                            imageContainer.insertAdjacentHTML('beforeend', badges[productId]);
                        } catch (e) {
                            // Ignorar errores de parsing
                        }
                    });

                    // Para página de producto individual
                    const singleProductSelectors = [
                        '.woocommerce-product-gallery__wrapper',
                        '.woocommerce-product-gallery__image',
                        '.wp-block-woocommerce-product-image-gallery',
                        'div[data-block-name="woocommerce/product-image-gallery"]'
                    ];

                    for (let selector of singleProductSelectors) {
                        const container = document.querySelector(selector);
                        if (container && !container.querySelector('.pwoa-discount-badge')) {
                            const productId = Object.keys(badges)[0];
                            if (badges[productId]) {
                                container.style.position = 'relative';
                                container.insertAdjacentHTML('beforeend', badges[productId]);
                                break;
                            }
                        }
                    }

                    // Para imágenes de productos estándar (fallback)
                    document.querySelectorAll('.product img.wp-post-image').forEach(function(img) {
                        const productItem = img.closest('.product, .type-product');
                        if (!productItem) return;

                        // Intentar obtener el ID del producto del data attribute
                        let productId = null;

                        if (productItem.classList.contains('post-')) {
                            const classes = productItem.className.split(' ');
                            for (let cls of classes) {
                                if (cls.startsWith('post-')) {
                                    productId = cls.replace('post-', '');
                                    break;
                                }
                            }
                        }

                        if (!productId || !badges[productId]) return;

                        const imgParent = img.parentElement;
                        if (imgParent && !imgParent.querySelector('.pwoa-discount-badge')) {
                            imgParent.style.position = 'relative';
                            imgParent.style.display = 'inline-block';
                            imgParent.insertAdjacentHTML('beforeend', badges[productId]);
                        }
                    });
                }

                // Ejecutar múltiples veces para asegurar que funcione
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', addBadges);
                } else {
                    addBadges();
                }

                setTimeout(addBadges, 100);
                setTimeout(addBadges, 500);
                setTimeout(addBadges, 1000);
            })();
        </script>
        <?php
    }

    public function addBadgeToImage($image, $product, $size, $attr, $placeholder) {
        if (!$product || !is_object($product)) return $image;

        $badge_html = $this->getBadgeForProduct($product->get_id());

        if (empty($badge_html)) return $image;

        return '<div class="pwoa-badge-wrapper">' . $image . $badge_html . '</div>';
    }

    public function addBadgeToThumbnail($html, $post_id, $post_thumbnail_id, $size, $attr) {
        if (get_post_type($post_id) !== 'product') return $html;

        $badge_html = $this->getBadgeForProduct($post_id);

        if (empty($badge_html)) return $html;

        return '<div class="pwoa-badge-wrapper">' . $html . $badge_html . '</div>';
    }

    public function addBadgeToCartThumbnail($thumbnail, $cart_item, $cart_item_key) {
        if (empty($cart_item['product_id'])) return $thumbnail;

        $badge_html = $this->getBadgeForProduct($cart_item['product_id']);

        if (empty($badge_html)) return $thumbnail;

        return '<div class="pwoa-badge-wrapper">' . $thumbnail . $badge_html . '</div>';
    }

    private function getBadgeForProduct(int $product_id): string {
        if (isset(self::$badge_cache[$product_id])) {
            return self::$badge_cache[$product_id];
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            self::$badge_cache[$product_id] = '';
            return '';
        }

        $campaigns = $this->getActiveCampaigns();

        if (empty($campaigns)) {
            self::$badge_cache[$product_id] = '';
            return '';
        }

        $best_discount = $this->calculateBestDiscount($product, $campaigns);

        if ($best_discount['amount'] <= 0) {
            self::$badge_cache[$product_id] = '';
            return '';
        }

        $badge_html = $this->renderBadge($best_discount);
        self::$badge_cache[$product_id] = $badge_html;

        return $badge_html;
    }

    private function getActiveCampaigns(): array {
        if (self::$campaigns_cache !== null) {
            return self::$campaigns_cache;
        }

        self::$campaigns_cache = CampaignRepository::getActive();

        return self::$campaigns_cache;
    }

    private function calculateBestDiscount($product, array $campaigns): array {
        $best = ['amount' => 0, 'type' => 'percentage', 'value' => 0];

        foreach ($campaigns as $campaign) {
            $conditions = json_decode($campaign->conditions, true) ?? [];

            if (empty($conditions) || ProductMatcher::matches($product, $conditions)) {
                $config = json_decode($campaign->config, true);
                $discount_type = $campaign->discount_type;

                $discount_value = $this->getDiscountValue($product, $config, $discount_type, $campaign->strategy);

                if ($discount_value <= 0) continue;

                $discount_amount = $this->calculateAmount($product, $discount_value, $discount_type);

                if ($discount_amount > $best['amount']) {
                    $best = [
                        'amount' => $discount_amount,
                        'type' => $discount_type,
                        'value' => $discount_value
                    ];
                }
            }
        }

        return $best;
    }

    private function getDiscountValue($product, array $config, string $discount_type, string $strategy): float {
        switch ($strategy) {
            case 'basic_discount':
            case 'min_amount':
            case 'flash_sale':
                return floatval($config['discount_value'] ?? 0);

            case 'tiered_discount':
                return $this->getTieredDiscount($config);

            case 'expiry_based':
                return $this->getExpiryDiscount($product, $config);

            case 'low_stock':
                return $this->getLowStockDiscount($product, $config);

            case 'recurring_purchase':
                return floatval($config['discount_value'] ?? 0);

            case 'free_shipping':
                return 0;

            default:
                return floatval($config['discount_value'] ?? 0);
        }
    }

    private function getTieredDiscount(array $config): float {
        $tiers = $config['tiers'] ?? [];
        if (empty($tiers)) return 0;

        usort($tiers, fn($a, $b) => $b['quantity'] <=> $a['quantity']);

        return floatval($tiers[0]['discount'] ?? 0);
    }

    private function getExpiryDiscount($product, array $config): float {
        $expiry_date = get_post_meta($product->get_id(), '_expiry_date', true);
        if (!$expiry_date) return 0;

        $days_to_expiry = (strtotime($expiry_date) - time()) / DAY_IN_SECONDS;
        $tiers = $config['tiers'] ?? [];

        if (empty($tiers)) return 0;

        usort($tiers, fn($a, $b) => $a['days'] <=> $b['days']);

        foreach ($tiers as $tier) {
            if ($days_to_expiry <= $tier['days']) {
                return floatval($tier['discount']);
            }
        }

        return 0;
    }

    private function getLowStockDiscount($product, array $config): float {
        if (!$product->managing_stock()) return 0;

        $stock = $product->get_stock_quantity();
        $threshold = intval($config['stock_threshold'] ?? 10);

        if ($stock <= $threshold) {
            return floatval($config['discount_value'] ?? 0);
        }

        return 0;
    }

    private function calculateAmount($product, float $discount_value, string $discount_type): float {
        $price = floatval($product->get_price());

        if ($discount_type === 'percentage') {
            return $price * ($discount_value / 100);
        }

        return $discount_value;
    }

    private function renderBadge(array $discount): string {
        if ($discount['type'] === 'percentage') {
            $label = round($discount['value']) . '%';
            $class = 'pwoa-discount-badge';
        } else {
            $label = wc_price($discount['value']);
            $class = 'pwoa-discount-badge fixed-amount';
        }

        return sprintf(
            '<span class="%s">-%s</span>',
            esc_attr($class),
            $label
        );
    }

    public static function clearCache(): void {
        self::$campaigns_cache = null;
        self::$badge_cache = [];
    }
}
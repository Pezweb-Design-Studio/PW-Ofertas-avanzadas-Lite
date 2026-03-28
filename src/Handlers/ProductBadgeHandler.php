<?php
namespace PW\OfertasAvanzadas\Handlers;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Repositories\CampaignRepository;
use PW\OfertasAvanzadas\Services\ProductMatcher;

class ProductBadgeHandler {

    private static $campaigns_cache = null;
    private static $badge_cache = [];

    public function __construct() {
        add_filter('woocommerce_product_get_image', [$this, 'addBadgeToImage'], 10, 5);
        add_filter('woocommerce_cart_item_thumbnail', [$this, 'addBadgeToCartThumbnail'], 10, 3);
        add_filter('post_thumbnail_html', [$this, 'addBadgeToThumbnail'], 10, 5);
        add_filter('elementor/widget/render_content', [$this, 'addBadgeToElementorWidget'], 10, 2);
        add_action('wp_enqueue_scripts', [$this, 'enqueueBadgeStyles'], 20);
        add_action('wp_footer', [$this, 'enqueueBadgeScript'], 15);
    }

    private function wrapImageWithBadge(string $html, string $badge_html): string {
        return preg_replace(
            '/(<img[^>]*>)/',
            '<div class="pwoa-badge-image-wrap">$1' . $badge_html . '</div>',
            $html,
            1
        );
    }

    public function enqueueBadgeStyles(): void {
        if (is_admin()) {
            return;
        }

        wp_enqueue_style(
            'pwoa-product-badges',
            PWOA_URL . 'assets/css/product-badges.css',
            [],
            PWOA_VERSION,
        );
    }

    private function collectPageBadges(): array {
        if (is_product()) {
            global $post;
            if (!$post) {
                return [];
            }
            $badge = $this->getBadgeForProduct($post->ID);
            return $badge ? [$post->ID => $badge] : [];
        }

        if (is_cart() || is_checkout()) {
            if (!WC()->cart) {
                return [];
            }
            $badges = [];
            foreach (WC()->cart->get_cart() as $cart_item) {
                $product_id = $cart_item['product_id'];
                $badge = $this->getBadgeForProduct($product_id);
                if ($badge) {
                    $badges[$product_id] = $badge;
                }
            }
            return $badges;
        }

        $products = wc_get_products([
            'limit'  => 100,
            'status' => 'publish',
            'return' => 'ids',
        ]);

        $badges = [];
        foreach ($products as $product_id) {
            $badge = $this->getBadgeForProduct($product_id);
            if ($badge) {
                $badges[$product_id] = $badge;
            }
        }
        return $badges;
    }

    public function enqueueBadgeScript(): void {
        if (is_admin()) {
            return;
        }

        if (!is_shop() && !is_product_category() && !is_product() && !is_cart() && !is_checkout()) {
            return;
        }

        $product_badges = $this->collectPageBadges();

        if (empty($product_badges)) {
            return;
        }

        $slug_to_id = [];
        foreach (array_keys($product_badges) as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $slug_to_id[$product->get_slug()] = $product_id;
            }
        }

        wp_enqueue_script(
            'pwoa-product-badges',
            PWOA_URL . 'assets/js/product-badges.js',
            [],
            PWOA_VERSION,
            true,
        );

        wp_localize_script(
            'pwoa-product-badges',
            'pwoaBadgeConfig',
            [
                'badges'  => $product_badges,
                'slugMap' => $slug_to_id,
            ],
        );
    }

    public function addBadgeToImage($image, $product, $size, $attr, $placeholder) {
        if (!$product || !is_object($product)) {
            return $image;
        }

        $badge_html = $this->getBadgeForProduct($product->get_id());

        return $badge_html ? $this->wrapImageWithBadge($image, $badge_html) : $image;
    }

    public function addBadgeToThumbnail($html, $post_id, $post_thumbnail_id, $size, $attr) {
        if (get_post_type($post_id) !== 'product') {
            return $html;
        }

        $badge_html = $this->getBadgeForProduct($post_id);

        return $badge_html ? $this->wrapImageWithBadge($html, $badge_html) : $html;
    }

    public function addBadgeToCartThumbnail($thumbnail, $cart_item, $cart_item_key) {
        if (empty($cart_item['product_id'])) {
            return $thumbnail;
        }

        $badge_html = $this->getBadgeForProduct($cart_item['product_id']);

        return $badge_html ? $this->wrapImageWithBadge($thumbnail, $badge_html) : $thumbnail;
    }

    public function addBadgeToElementorWidget($content, $widget) {
        if (!in_array($widget->get_name(), ['jet-woo-builder-archive-product-thumbnail', 'woocommerce-product-image'])) {
            return $content;
        }

        global $product;
        if (!$product || !is_a($product, 'WC_Product')) {
            return $content;
        }

        $badge_html = $this->getBadgeForProduct($product->get_id());
        if (empty($badge_html)) {
            return $content;
        }

        if (strpos($content, 'jet-woo-builder-archive-product-thumbnail') !== false) {
            $content = preg_replace(
                '/class="(jet-woo-builder-archive-product-thumbnail)"/',
                'class="$1 pwoa-badge-image-wrap"',
                $content,
                1
            );

            return preg_replace(
                '/(<div[^>]*class="[^"]*jet-woo-builder-archive-product-thumbnail[^"]*"[^>]*>)/s',
                '$1' . $badge_html,
                $content,
                1
            );
        }

        return preg_replace(
            '/(<div[^>]*class="[^"]*woocommerce-product-gallery[^"]*"[^>]*>)/s',
            '$1' . $badge_html,
            $content,
            1
        );
    }

    private function getBadgeForProduct(int $product_id): string {
        if (isset(self::$badge_cache[$product_id])) {
            return self::$badge_cache[$product_id];
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            return self::$badge_cache[$product_id] = '';
        }

        $campaigns = $this->getActiveCampaigns();
        if (empty($campaigns)) {
            return self::$badge_cache[$product_id] = '';
        }

        $best_discount = $this->calculateBestDiscount($product, $campaigns);
        if ($best_discount['amount'] <= 0) {
            return self::$badge_cache[$product_id] = '';
        }

        return self::$badge_cache[$product_id] = $this->renderBadge($best_discount);
    }

    private function getActiveCampaigns(): array {
        return self::$campaigns_cache ??= CampaignRepository::getActive();
    }

    private function calculateBestDiscount($product, array $campaigns): array {
        $best = ['amount' => 0, 'type' => 'percentage', 'value' => 0, 'badge_text' => ''];

        foreach ($campaigns as $campaign) {
            $conditions = json_decode($campaign->conditions, true) ?? [];

            if (!empty($conditions) && !ProductMatcher::matches($product, $conditions)) {
                continue;
            }

            $config = json_decode($campaign->config, true);
            $discount_type = $campaign->discount_type;

            if ($campaign->strategy === 'bulk_discount') {
                $bulk_result = $this->getBulkDiscountForProduct($product, $config);

                if ($bulk_result['value'] <= 0) {
                    continue;
                }

                $discount_amount = $this->calculateAmount($product, $bulk_result['value'], $bulk_result['type']);

                if ($discount_amount > $best['amount']) {
                    $best = [
                        'amount'     => $discount_amount,
                        'type'       => $bulk_result['type'],
                        'value'      => $bulk_result['value'],
                        'badge_text' => $bulk_result['badge_text'],
                    ];
                }
                continue;
            }

            $discount_value = $this->getDiscountValue($product, $config, $discount_type, $campaign->strategy);

            if ($discount_value <= 0) {
                continue;
            }

            $discount_amount = $this->calculateAmount($product, $discount_value, $discount_type);

            if ($discount_amount > $best['amount']) {
                $best = [
                    'amount'     => $discount_amount,
                    'type'       => $discount_type,
                    'value'      => $discount_value,
                    'badge_text' => '',
                ];
            }
        }

        return $best;
    }

    private function getBulkDiscountForProduct($product, array $config): array {
        $bulk_items = $config['bulk_items'] ?? [];
        $product_id = $product->get_id();
        $best = ['value' => 0, 'type' => 'percentage', 'badge_text' => ''];

        foreach ($bulk_items as $bulk_item) {
            if (intval($bulk_item['product_id'] ?? 0) !== $product_id) {
                continue;
            }

            $discount_value = floatval($bulk_item['discount_value'] ?? 0);
            if ($discount_value > $best['value']) {
                $best = [
                    'value'      => $discount_value,
                    'type'       => $bulk_item['discount_type'] ?? 'percentage',
                    'badge_text' => $bulk_item['badge_text'] ?? '',
                ];
            }
        }

        return $best;
    }

    private function getDiscountValue($product, array $config, string $discount_type, string $strategy): float {
        return match ($strategy) {
            'tiered_discount' => $this->getTieredDiscount($config),
            'expiry_based'    => $this->getExpiryDiscount($product, $config),
            'low_stock'       => $this->getLowStockDiscount($product, $config),
            'free_shipping'   => 0.0,
            default           => floatval($config['discount_value'] ?? 0),
        };
    }

    private function getTieredDiscount(array $config): float {
        $tiers = $config['tiers'] ?? [];
        if (empty($tiers)) {
            return 0.0;
        }

        usort($tiers, fn($a, $b) => $b['quantity'] <=> $a['quantity']);

        return floatval($tiers[0]['discount'] ?? 0);
    }

    private function getExpiryDiscount($product, array $config): float {
        $expiry_date = get_post_meta($product->get_id(), '_expiry_date', true);
        if (!$expiry_date) {
            return 0.0;
        }

        $days_to_expiry = (strtotime($expiry_date) - time()) / DAY_IN_SECONDS;
        $tiers = $config['tiers'] ?? [];
        if (empty($tiers)) {
            return 0.0;
        }

        usort($tiers, fn($a, $b) => $a['days'] <=> $b['days']);

        foreach ($tiers as $tier) {
            if ($days_to_expiry <= $tier['days']) {
                return floatval($tier['discount']);
            }
        }

        return 0.0;
    }

    private function getLowStockDiscount($product, array $config): float {
        if (!$product->managing_stock()) {
            return 0.0;
        }

        $stock     = $product->get_stock_quantity();
        $threshold = intval($config['stock_threshold'] ?? 10);

        return $stock <= $threshold ? floatval($config['discount_value'] ?? 0) : 0.0;
    }

    private function calculateAmount($product, float $discount_value, string $discount_type): float {
        $price = floatval($product->get_price());

        return $discount_type === 'percentage'
            ? $price * ($discount_value / 100)
            : $discount_value;
    }

    private function renderBadge(array $discount): string {
        $badges = '';

        if ($discount['amount'] > 0) {
            $is_percentage = $discount['type'] === 'percentage';
            $label = $is_percentage ? round($discount['value']) . '%' : wc_price($discount['value']);
            $class = $is_percentage ? 'pwoa-discount-badge' : 'pwoa-discount-badge fixed-amount';

            $esc_class = esc_attr($class);
            $badges .= "<span class=\"{$esc_class}\">-{$label}</span>";
        }

        if (!empty($discount['badge_text'])) {
            $esc_text = esc_html($discount['badge_text']);
            $badges .= "<span class=\"pwoa-custom-badge\">{$esc_text}</span>";
        }

        return $badges;
    }

    public static function clearCache(): void {
        self::$campaigns_cache = null;
        self::$badge_cache = [];
    }
}

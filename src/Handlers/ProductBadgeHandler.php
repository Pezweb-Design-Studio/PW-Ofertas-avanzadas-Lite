<?php
namespace PW\OfertasAvanzadas\Handlers;

use PW\OfertasAvanzadas\Repositories\CampaignRepository;
use PW\OfertasAvanzadas\Services\ProductMatcher;

class ProductBadgeHandler {

    private static $campaigns_cache = null;
    private static $badge_cache = [];

    public function __construct() {
        // CAPA 1: Hooks estándar de WooCommerce (prioridad)
        add_filter('woocommerce_product_get_image', [$this, 'addBadgeToImage'], 10, 5);
        add_filter('woocommerce_cart_item_thumbnail', [$this, 'addBadgeToCartThumbnail'], 10, 3);
        add_filter('post_thumbnail_html', [$this, 'addBadgeToThumbnail'], 10, 5);

        // CAPA 2: Hook Elementor (edge case)
        add_filter('elementor/widget/render_content', [$this, 'addBadgeToElementorWidget'], 10, 2);

        // CAPA 3: CSS y JavaScript (último recurso)
        add_action('wp_head', [$this, 'addBadgeStyles'], 999);
        add_action('wp_footer', [$this, 'injectBadgeScript'], 999);
    }

    public function addBadgeStyles(): void {
        echo '<style>
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
            
            .pwoa-custom-badge {
                position: absolute !important;
                top: 48px !important;
                right: 8px !important;
                background: #10b981 !important;
                color: white !important;
                padding: 4px 8px !important;
                border-radius: 6px !important;
                font-size: 11px !important;
                font-weight: 600 !important;
                line-height: 1.2 !important;
                z-index: 998 !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3) !important;
                pointer-events: none !important;
                max-width: 120px !important;
                text-align: center !important;
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
        } elseif (is_cart() || is_checkout()) {
            // En carrito/checkout, obtener productos del carrito
            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $cart_item) {
                    $product_id = $cart_item['product_id'];
                    $badge = $this->getBadgeForProduct($product_id);
                    if ($badge) {
                        $product_badges[$product_id] = $badge;
                    }
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

        // Crear mapeo de slug → product ID para carrito de bloques
        $slug_to_id = [];
        foreach (array_keys($product_badges) as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $slug_to_id[$product->get_slug()] = $product_id;
            }
        }

        ?>
        <script>
            (function() {
                const badges = <?php echo json_encode($product_badges); ?>;
                const slugMap = <?php echo json_encode($slug_to_id); ?>;
                console.log('🗺️ Slug to ID map:', slugMap);

                function addBadges() {
                    // CASO 1: Single Product (página de producto individual)
                    if (Object.keys(badges).length === 1) {
                        const productId = Object.keys(badges)[0];
                        const singleSelectors = [
                            '.woocommerce-product-gallery__wrapper',
                            '.woocommerce-product-gallery__image:first-child',
                            '.wp-block-woocommerce-product-image-gallery',
                            'div[data-block-name="woocommerce/product-image-gallery"]'
                        ];

                        for (let sel of singleSelectors) {
                            const container = document.querySelector(sel);
                            if (container && !container.querySelector('.pwoa-discount-badge')) {
                                container.style.position = 'relative';
                                container.insertAdjacentHTML('beforeend', badges[productId]);
                                return; // Solo insertar una vez
                            }
                        }
                    }

                    // CASO 2: Listado de productos (loops)
                    document.querySelectorAll('.product, [data-product-id], [data-wp-context]').forEach(item => {
                        if (item.querySelector('.pwoa-discount-badge')) return;

                        let productId = null;

                        // Extraer ID del producto
                        if (item.dataset.productId) {
                            productId = item.dataset.productId;
                        } else if (item.dataset.wpContext) {
                            try {
                                const context = JSON.parse(item.dataset.wpContext);
                                productId = context.productId;
                            } catch(e) {}
                        } else {
                            const classes = item.className.split(' ');
                            const postClass = classes.find(c => c.startsWith('post-'));
                            if (postClass) {
                                productId = postClass.replace('post-', '');
                            }
                        }

                        if (!productId || !badges[productId]) return;

                        // Buscar contenedor de imagen
                        const selectors = [
                            '.jet-woo-builder-archive-product-thumbnail',
                            '.wc-block-components-product-image__inner-container',
                            'a.woocommerce-LoopProduct-link',
                            '.product-thumbnail',
                            'a[href*="producto"]'
                        ];

                        let container = null;
                        for (let sel of selectors) {
                            container = item.querySelector(sel);
                            if (container && !container.querySelector('.pwoa-discount-badge')) {
                                break;
                            }
                            container = null;
                        }

                        if (!container) return;

                        container.style.position = 'relative';
                        container.insertAdjacentHTML('beforeend', badges[productId]);
                    });

                    // CASO 3: Carrito (clásico y bloques)
                    if (Object.keys(badges).length > 0) {
                        console.log('🔍 PWOA Debug - Badges disponibles:', badges);

                        // Carrito clásico
                        const classicRows = document.querySelectorAll('.cart_item');
                        console.log('🛒 Carrito clásico - Rows encontrados:', classicRows.length);

                        classicRows.forEach(row => {
                            if (row.querySelector('.pwoa-discount-badge')) return;

                            const link = row.querySelector('.product-name a, td.product-name a');
                            console.log('🔗 Link encontrado:', link);
                            if (!link) return;

                            let productId = null;
                            const href = link.getAttribute('href');
                            console.log('📍 URL del producto:', href);

                            // Extraer ID del href
                            for (let id in badges) {
                                if (href.includes('/' + id + '/') || href.includes('?p=' + id) || href.includes('post=' + id)) {
                                    productId = id;
                                    console.log('✅ Product ID encontrado:', productId);
                                    break;
                                }
                            }

                            if (!productId || !badges[productId]) {
                                console.log('❌ No se encontró product ID o badge');
                                return;
                            }

                            const thumbnail = row.querySelector('.product-thumbnail, td.product-thumbnail');
                            console.log('🖼️ Thumbnail encontrado:', thumbnail);

                            if (thumbnail && !thumbnail.querySelector('.pwoa-discount-badge')) {
                                thumbnail.style.position = 'relative';
                                thumbnail.insertAdjacentHTML('beforeend', badges[productId]);
                                console.log('✅ Badge insertado en carrito clásico');
                            }
                        });

                        // Carrito bloques WooCommerce
                        const blockRows = document.querySelectorAll('.wc-block-cart-items__row');
                        console.log('🛒 Carrito bloques - Rows encontrados:', blockRows.length);

                        blockRows.forEach((row, index) => {
                            if (row.querySelector('.pwoa-discount-badge')) {
                                console.log('⏭️ Badge ya existe, skip');
                                return;
                            }

                            let productId = null;

                            // Método 1: Buscar en el link del thumbnail (puede tener data-product-id)
                            const thumbLink = row.querySelector('.wc-block-cart-item__image a');
                            if (thumbLink && thumbLink.dataset.productId) {
                                productId = thumbLink.dataset.productId;
                                console.log('✅ Product ID desde thumbnail link:', productId);
                            }

                            // Método 2: Buscar el ID en la estructura del row (bloques React)
                            if (!productId && row.dataset) {
                                // Intentar parsear cualquier data attribute que tenga JSON
                                for (let attr in row.dataset) {
                                    try {
                                        const data = JSON.parse(row.dataset[attr]);
                                        if (data.id || data.productId || data.product_id) {
                                            productId = data.id || data.productId || data.product_id;
                                            console.log('✅ Product ID desde data attribute:', productId);
                                            break;
                                        }
                                    } catch(e) {}
                                }
                            }

                            // Método 3: Extraer del href usando slugMap
                            if (!productId) {
                                const link = row.querySelector('.wc-block-components-product-name');
                                if (link) {
                                    const href = link.getAttribute('href');
                                    console.log('📍 URL del producto (bloques):', href);

                                    // Extraer slug de la URL
                                    const slug = href.split('/').filter(Boolean).pop();
                                    console.log('🔎 Slug extraído:', slug);

                                    // Buscar en slugMap
                                    if (slugMap[slug]) {
                                        productId = slugMap[slug];
                                        console.log('✅ Product ID encontrado via slugMap:', productId);
                                    }
                                }
                            }

                            // Método 4: Fallback por index
                            if (!productId) {
                                const badgeIds = Object.keys(badges);
                                if (badgeIds[index]) {
                                    productId = badgeIds[index];
                                    console.log('⚠️ Usando product ID por index (fallback):', productId);
                                }
                            }

                            if (!productId || !badges[productId]) {
                                console.log('❌ No se encontró product ID o badge (bloques)');
                                return;
                            }

                            const thumbnail = row.querySelector('.wc-block-cart-item__image');
                            console.log('🖼️ Thumbnail bloques encontrado:', thumbnail);

                            if (thumbnail && !thumbnail.querySelector('.pwoa-discount-badge')) {
                                thumbnail.style.position = 'relative';
                                thumbnail.insertAdjacentHTML('beforeend', badges[productId]);
                                console.log('✅ Badge insertado en carrito bloques');
                            }
                        });
                    }
                }

                // Ejecutar cuando DOM esté listo
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', addBadges);
                } else {
                    addBadges();
                }

                // MutationObserver para cargas dinámicas
                const observer = new MutationObserver(() => {
                    requestAnimationFrame(addBadges);
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });

                setTimeout(() => observer.disconnect(), 5000);
            })();
        </script>
        <?php
    }

    public function addBadgeToImage($image, $product, $size, $attr, $placeholder) {
        if (!$product || !is_object($product)) return $image;

        $badge_html = $this->getBadgeForProduct($product->get_id());

        if (empty($badge_html)) return $image;

        // Insertar badge directamente usando regex para encontrar el primer <img>
        return preg_replace(
                '/(<img[^>]*>)/',
                '<div style="position:relative;display:inline-block;">$1' . $badge_html . '</div>',
                $image,
                1
        );
    }

    public function addBadgeToThumbnail($html, $post_id, $post_thumbnail_id, $size, $attr) {
        if (get_post_type($post_id) !== 'product') return $html;

        $badge_html = $this->getBadgeForProduct($post_id);

        if (empty($badge_html)) return $html;

        // Insertar badge directamente
        return preg_replace(
                '/(<img[^>]*>)/',
                '<div style="position:relative;display:inline-block;">$1' . $badge_html . '</div>',
                $html,
                1
        );
    }

    public function addBadgeToCartThumbnail($thumbnail, $cart_item, $cart_item_key) {
        if (empty($cart_item['product_id'])) return $thumbnail;

        $badge_html = $this->getBadgeForProduct($cart_item['product_id']);

        if (empty($badge_html)) return $thumbnail;

        // Insertar badge directamente
        return preg_replace(
                '/(<img[^>]*>)/',
                '<div style="position:relative;display:inline-block;">$1' . $badge_html . '</div>',
                $thumbnail,
                1
        );
    }

    public function addBadgeToElementorWidget($content, $widget) {
        // Solo thumbnail widgets de Elementor/Jet
        if (!in_array($widget->get_name(), [
                'jet-woo-builder-archive-product-thumbnail',
                'woocommerce-product-image'
        ])) {
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

        // Estructura Jet: insertar badge DENTRO del contenedor, justo después del opening tag
        if (strpos($content, 'jet-woo-builder-archive-product-thumbnail') !== false) {
            // Agregar style al contenedor para position relative
            $content = preg_replace(
                    '/(<div class="jet-woo-builder-archive-product-thumbnail")/s',
                    '$1 style="position:relative"',
                    $content,
                    1
            );

            // Insertar badge justo después del opening tag (antes del <a>)
            $content = preg_replace(
                    '/(<div class="jet-woo-builder-archive-product-thumbnail"[^>]*>)/s',
                    '$1' . $badge_html,
                    $content,
                    1
            );
        } else {
            // Estructura genérica
            $content = preg_replace(
                    '/(<div[^>]*class="[^"]*woocommerce-product-gallery[^"]*"[^>]*>)/s',
                    '$1' . $badge_html,
                    $content,
                    1
            );
        }

        return $content;
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
        $best = ['amount' => 0, 'type' => 'percentage', 'value' => 0, 'badge_text' => ''];

        foreach ($campaigns as $campaign) {
            $conditions = json_decode($campaign->conditions, true) ?? [];

            if (empty($conditions) || ProductMatcher::matches($product, $conditions)) {
                $config = json_decode($campaign->config, true);
                $discount_type = $campaign->discount_type;

                // Para bulk_discount, buscar config especÃ­fica del producto
                if ($campaign->strategy === 'bulk_discount') {
                    $bulk_result = $this->getBulkDiscountForProduct($product, $config);

                    if ($bulk_result['value'] > 0) {
                        $discount_amount = $this->calculateAmount($product, $bulk_result['value'], $bulk_result['type']);

                        if ($discount_amount > $best['amount']) {
                            $best = [
                                    'amount' => $discount_amount,
                                    'type' => $bulk_result['type'],
                                    'value' => $bulk_result['value'],
                                    'badge_text' => $bulk_result['badge_text']
                            ];
                        }
                    }
                } else {
                    $discount_value = $this->getDiscountValue($product, $config, $discount_type, $campaign->strategy);

                    if ($discount_value <= 0) continue;

                    $discount_amount = $this->calculateAmount($product, $discount_value, $discount_type);

                    if ($discount_amount > $best['amount']) {
                        $best = [
                                'amount' => $discount_amount,
                                'type' => $discount_type,
                                'value' => $discount_value,
                                'badge_text' => ''
                        ];
                    }
                }
            }
        }

        return $best;
    }

    private function getBulkDiscountForProduct($product, array $config): array {
        $bulk_items = $config['bulk_items'] ?? [];
        $product_id = $product->get_id();

        $best = ['value' => 0, 'type' => 'percentage', 'badge_text' => ''];

        foreach ($bulk_items as $bulk_item) {
            if (intval($bulk_item['product_id'] ?? 0) === $product_id) {
                $discount_value = floatval($bulk_item['discount_value'] ?? 0);

                if ($discount_value > $best['value']) {
                    $best = [
                            'value' => $discount_value,
                            'type' => $bulk_item['discount_type'] ?? 'percentage',
                            'badge_text' => $bulk_item['badge_text'] ?? ''
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
        $badges = '';

        // Badge de descuento (siempre se muestra si hay descuento)
        if ($discount['amount'] > 0) {
            if ($discount['type'] === 'percentage') {
                $label = round($discount['value']) . '%';
                $class = 'pwoa-discount-badge';
            } else {
                $label = wc_price($discount['value']);
                $class = 'pwoa-discount-badge fixed-amount';
            }

            $badges .= sprintf(
                    '<span class="%s">-%s</span>',
                    esc_attr($class),
                    $label
            );
        }

        // Badge custom (opcional, solo si existe badge_text)
        if (!empty($discount['badge_text'])) {
            $badges .= sprintf(
                    '<span class="pwoa-custom-badge">%s</span>',
                    esc_html($discount['badge_text'])
            );
        }

        return $badges;
    }

    public static function clearCache(): void {
        self::$campaigns_cache = null;
        self::$badge_cache = [];
    }
}
<?php
namespace PW\OfertasAvanzadas\Admin;

use PW\OfertasAvanzadas\Repositories\CampaignRepository;
use PW\OfertasAvanzadas\Repositories\StatsRepository;

class AdminController {

    public function __construct() {
        add_action('admin_menu', [$this, 'addMenuPages']);
        add_action('wp_ajax_pwoa_get_strategies', [$this, 'ajaxGetStrategies']);
        add_action('wp_ajax_pwoa_save_campaign', [$this, 'ajaxSaveCampaign']);
        add_action('wp_ajax_pwoa_update_campaign', [$this, 'ajaxUpdateCampaign']);
        add_action('wp_ajax_pwoa_delete_campaign', [$this, 'ajaxDeleteCampaign']);
        add_action('wp_ajax_pwoa_get_campaign', [$this, 'ajaxGetCampaign']);
        add_action('wp_ajax_pwoa_toggle_campaign', [$this, 'ajaxToggleCampaign']);
        add_action('wp_ajax_pwoa_search_products', [$this, 'ajaxSearchProducts']);
        add_action('wp_ajax_pwoa_validate_conditions', [$this, 'ajaxValidateConditions']);
        add_action('wp_ajax_pwoa_get_matching_products', [$this, 'ajaxGetMatchingProducts']);
    }

    public function addMenuPages(): void {
        add_menu_page(
            'Ofertas Avanzadas',
            'Ofertas',
            'manage_woocommerce',
            'pwoa-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-megaphone',
            56
        );

        add_submenu_page(
            'pwoa-dashboard',
            'Nueva Campaña',
            'Nueva Campaña',
            'manage_woocommerce',
            'pwoa-new-campaign',
            [$this, 'renderWizard']
        );

        add_submenu_page(
            'pwoa-dashboard',
            'Analíticas',
            'Analíticas',
            'manage_woocommerce',
            'pwoa-analytics',
            [$this, 'renderAnalytics']
        );
    }

    public function renderDashboard(): void {
        $campaigns = CampaignRepository::getAll();
        include PWOA_PATH . 'src/Admin/Views/dashboard.php';
    }

    public function renderWizard(): void {
        include PWOA_PATH . 'src/Admin/Views/wizard.php';
    }

    public function renderAnalytics(): void {
        $stats = StatsRepository::getSummary();
        include PWOA_PATH . 'src/Admin/Views/analytics.php';
    }

    public function ajaxGetStrategies(): void {
        check_ajax_referer('pwoa_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permisos insuficientes');
        }

        $objective = sanitize_text_field($_POST['objective'] ?? '');
        $strategies = $this->getStrategiesByObjective($objective);

        wp_send_json_success($strategies);
    }

    public function ajaxSaveCampaign(): void {
        check_ajax_referer('pwoa_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permisos insuficientes');
        }

        $config = json_decode(stripslashes($_POST['config'] ?? '{}'), true);
        $strategy = sanitize_text_field($_POST['strategy'] ?? '');

        // Determinar discount_type según la estrategia
        $discount_type = sanitize_text_field($_POST['discount_type'] ?? '');

        if (empty($discount_type)) {
            $discount_type = $config['discount_type'] ?? '';

            if (empty($discount_type)) {
                $discount_type = match($strategy) {
                    'free_shipping' => 'free_shipping',
                    'flash_sale', 'min_amount', 'low_stock', 'recurring_purchase' => 'percentage',
                    'tiered_discount', 'expiry_based' => 'percentage',
                    default => 'percentage'
                };
            }
        }

        $data = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'objective' => sanitize_text_field($_POST['objective'] ?? ''),
            'strategy' => $strategy,
            'discount_type' => $discount_type,
            'config' => $config,
            'conditions' => json_decode(stripslashes($_POST['conditions'] ?? '{}'), true),
            'stacking_mode' => sanitize_text_field($_POST['stacking_mode'] ?? 'priority'),
            'priority' => intval($_POST['priority'] ?? 10),
            'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
            'end_date' => sanitize_text_field($_POST['end_date'] ?? '')
        ];

        $campaign_id = CampaignRepository::create($data);

        if (!$campaign_id) {
            global $wpdb;
            wp_send_json_error('Error al crear campaña: ' . $wpdb->last_error);
        }

        wp_send_json_success([
            'message' => 'Campaña creada correctamente',
            'campaign_id' => $campaign_id
        ]);
    }

    public function ajaxToggleCampaign(): void {
        check_ajax_referer('pwoa_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permisos insuficientes');
        }

        $campaign_id = intval($_POST['campaign_id'] ?? 0);
        $active = intval($_POST['active'] ?? 0);

        CampaignRepository::updateStatus($campaign_id, $active);

        wp_send_json_success(['message' => 'Estado actualizado']);
    }

    public function ajaxGetCampaign(): void {
        check_ajax_referer('pwoa_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permisos insuficientes');
        }

        $campaign_id = intval($_POST['campaign_id'] ?? 0);
        $campaign = CampaignRepository::getById($campaign_id);

        if (!$campaign) {
            wp_send_json_error('Campaña no encontrada');
        }

        // Decodificar JSON para enviar al frontend
        $campaign->config = json_decode($campaign->config, true);
        $campaign->conditions = json_decode($campaign->conditions, true);

        wp_send_json_success($campaign);
    }

    public function ajaxUpdateCampaign(): void {
        check_ajax_referer('pwoa_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permisos insuficientes');
        }

        $campaign_id = intval($_POST['campaign_id'] ?? 0);
        $config = json_decode(stripslashes($_POST['config'] ?? '{}'), true);
        $strategy = sanitize_text_field($_POST['strategy'] ?? '');

        $discount_type = sanitize_text_field($_POST['discount_type'] ?? '');

        if (empty($discount_type)) {
            $discount_type = $config['discount_type'] ?? '';

            if (empty($discount_type)) {
                $discount_type = match($strategy) {
                    'free_shipping' => 'free_shipping',
                    'flash_sale', 'min_amount', 'low_stock', 'recurring_purchase' => 'percentage',
                    'tiered_discount', 'expiry_based' => 'percentage',
                    default => 'percentage'
                };
            }
        }

        $data = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'objective' => sanitize_text_field($_POST['objective'] ?? ''),
            'strategy' => $strategy,
            'discount_type' => $discount_type,
            'config' => $config,
            'conditions' => json_decode(stripslashes($_POST['conditions'] ?? '{}'), true),
            'stacking_mode' => sanitize_text_field($_POST['stacking_mode'] ?? 'priority'),
            'priority' => intval($_POST['priority'] ?? 10),
            'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
            'end_date' => sanitize_text_field($_POST['end_date'] ?? '')
        ];

        $success = CampaignRepository::update($campaign_id, $data);

        if (!$success) {
            wp_send_json_error('Error al actualizar campaña');
        }

        wp_send_json_success([
            'message' => 'Campaña actualizada correctamente',
            'campaign_id' => $campaign_id
        ]);
    }

    public function ajaxDeleteCampaign(): void {
        check_ajax_referer('pwoa_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permisos insuficientes');
        }

        $campaign_id = intval($_POST['campaign_id'] ?? 0);

        // Soft delete
        $success = CampaignRepository::softDelete($campaign_id);

        if (!$success) {
            wp_send_json_error('Error al eliminar campaña');
        }

        wp_send_json_success(['message' => 'Campaña eliminada correctamente']);
    }

    private function getStrategiesByObjective(string $objective): array {
        $strategies_map = [
            'basic' => [
                'PW\\OfertasAvanzadas\\Strategies\\Basic\\BasicDiscountStrategy'
            ],
            'aov' => [
                'PW\\OfertasAvanzadas\\Strategies\\AOV\\MinAmountStrategy',
                'PW\\OfertasAvanzadas\\Strategies\\AOV\\FreeShippingStrategy',
                'PW\\OfertasAvanzadas\\Strategies\\AOV\\TieredDiscountStrategy',
                'PW\\OfertasAvanzadas\\Strategies\\AOV\\BulkDiscountStrategy'
            ],
            'liquidation' => [
                'PW\\OfertasAvanzadas\\Strategies\\Liquidation\\ExpiryBasedStrategy',
                'PW\\OfertasAvanzadas\\Strategies\\Liquidation\\LowStockStrategy'
            ],
            'loyalty' => [
                'PW\\OfertasAvanzadas\\Strategies\\Loyalty\\RecurringPurchaseStrategy'
            ],
            'urgency' => [
                'PW\\OfertasAvanzadas\\Strategies\\Urgency\\FlashSaleStrategy'
            ]
        ];

        $classes = $strategies_map[$objective] ?? [];
        $result = [];

        foreach ($classes as $class) {
            if (class_exists($class)) {
                $meta = $class::getMeta();
                $meta['config_fields'] = $class::getConfigFields();
                $result[] = $meta;
            }
        }

        return $result;
    }
    public function ajaxSearchProducts(): void {
        check_ajax_referer('pwoa_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permisos insuficientes');
        }

        $search = sanitize_text_field($_POST['search'] ?? '');

        if (strlen($search) < 2) {
            wp_send_json_success([]);
            return;
        }

        global $wpdb;
        $results = [];
        $found_ids = [];

        // Buscar por ID exacto (si es numérico)
        if (is_numeric($search)) {
            $product = wc_get_product(intval($search));
            if ($product && $product->get_status() === 'publish') {
                $found_ids[] = $product->get_id();
                $results[] = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'sku' => $product->get_sku() ?: '',
                    'price' => $product->get_price(),
                    'formatted_price' => wc_price($product->get_price())
                ];
            }
        }

        // Buscar por SKU
        $sku_query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_sku' 
            AND meta_value LIKE %s",
            '%' . $wpdb->esc_like($search) . '%'
        );
        $sku_ids = $wpdb->get_col($sku_query);

        foreach ($sku_ids as $product_id) {
            if (in_array($product_id, $found_ids)) continue;

            $product = wc_get_product($product_id);
            if ($product && $product->get_status() === 'publish') {
                $found_ids[] = $product->get_id();
                $results[] = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'sku' => $product->get_sku() ?: '',
                    'price' => $product->get_price(),
                    'formatted_price' => wc_price($product->get_price())
                ];
            }

            if (count($results) >= 20) break;
        }

        // Buscar por nombre
        if (count($results) < 20) {
            $name_query = new \WP_Query([
                'post_type' => 'product',
                'post_status' => 'publish',
                's' => $search,
                'posts_per_page' => 20 - count($results)
            ]);

            foreach ($name_query->posts as $post) {
                if (in_array($post->ID, $found_ids)) continue;

                $product = wc_get_product($post->ID);
                if ($product) {
                    $found_ids[] = $product->get_id();
                    $results[] = [
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                        'sku' => $product->get_sku() ?: '',
                        'price' => $product->get_price(),
                        'formatted_price' => wc_price($product->get_price())
                    ];
                }
            }
        }

        wp_send_json_success($results);
    }
    public function ajaxValidateConditions(): void {
        check_ajax_referer('pwoa_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permisos insuficientes');
        }

        $conditions = json_decode(stripslashes($_POST['conditions'] ?? '{}'), true);

        $count = \PW\OfertasAvanzadas\Services\ProductMatcher::countMatchingProducts($conditions);

        wp_send_json_success(['count' => $count]);
    }
    public function ajaxGetMatchingProducts(): void {
        check_ajax_referer('pwoa_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permisos insuficientes');
        }

        $conditions = json_decode(stripslashes($_POST['conditions'] ?? '{}'), true);

        // Obtener los productos que coinciden
        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 100, // Máximo 100 productos en el modal
            'fields' => 'ids'
        ];

        // Filtrar por IDs específicos
        if (!empty($conditions['product_ids'])) {
            $args['post__in'] = $conditions['product_ids'];
        }

        // Filtrar por categorías
        if (!empty($conditions['category_ids'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $conditions['category_ids']
                ]
            ];
        }

        // Filtrar por precio
        if (!empty($conditions['min_price']) || !empty($conditions['max_price'])) {
            $args['meta_query'] = [
                'relation' => 'AND'
            ];

            if (!empty($conditions['min_price'])) {
                $args['meta_query'][] = [
                    'key' => '_price',
                    'value' => floatval($conditions['min_price']),
                    'type' => 'NUMERIC',
                    'compare' => '>='
                ];
            }

            if (!empty($conditions['max_price'])) {
                $args['meta_query'][] = [
                    'key' => '_price',
                    'value' => floatval($conditions['max_price']),
                    'type' => 'NUMERIC',
                    'compare' => '<='
                ];
            }
        }

        $query = new \WP_Query($args);
        $product_ids = $query->posts;
        $products = [];

        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $products[] = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'sku' => $product->get_sku() ?: '',
                    'price' => $product->get_price(),
                    'formatted_price' => wc_price($product->get_price()),
                    'stock' => $product->get_stock_quantity()
                ];
            }
        }

        wp_send_json_success([
            'count' => count($products),
            'products' => $products
        ]);
    }
}
<?php
namespace PW\OfertasAvanzadas\Admin;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Repositories\CampaignRepository;
use PW\OfertasAvanzadas\Repositories\StatsRepository;

class AdminController
{
    private const AJAX_HOOKS = [
        'pwoa_get_wizard_data'          => 'ajaxGetWizardData',
        'pwoa_get_strategies'           => 'ajaxGetStrategies',
        'pwoa_save_campaign'            => 'ajaxSaveCampaign',
        'pwoa_update_campaign'          => 'ajaxUpdateCampaign',
        'pwoa_delete_campaign'          => 'ajaxDeleteCampaign',
        'pwoa_get_campaign'             => 'ajaxGetCampaign',
        'pwoa_toggle_campaign'          => 'ajaxToggleCampaign',
        'pwoa_search_products'          => 'ajaxSearchProducts',
        'pwoa_validate_conditions'      => 'ajaxValidateConditions',
        'pwoa_get_matching_products'    => 'ajaxGetMatchingProducts',
        'pwoa_get_attributes'           => 'ajaxGetAttributes',
        'pwoa_get_attribute_terms'      => 'ajaxGetAttributeTerms',
        'pwoa_validate_attribute'       => 'ajaxValidateAttribute',
        'pwoa_get_products_by_attribute' => 'ajaxGetProductsByAttribute',
        'pwoa_reset_units_sold'         => 'ajaxResetUnitsSold',
        'pwoa_get_campaigns_paginated'  => 'ajaxGetCampaignsPaginated',
        'pwoa_save_settings'            => 'ajaxSaveSettings',
    ];

    private const STRATEGIES_MAP = [
        'basic' => [
            'PW\\OfertasAvanzadas\\Strategies\\Lite\\BasicDiscountStrategy',
        ],
        'aov' => [
            'PW\\OfertasAvanzadas\\Strategies\\Pro\\MinAmountStrategy',
            'PW\\OfertasAvanzadas\\Strategies\\Pro\\FreeShippingStrategy',
            'PW\\OfertasAvanzadas\\Strategies\\Pro\\TieredDiscountStrategy',
            'PW\\OfertasAvanzadas\\Strategies\\Lite\\BulkDiscountStrategy',
            'PW\\OfertasAvanzadas\\Strategies\\Lite\\BuyXPayYStrategy',
            'PW\\OfertasAvanzadas\\Strategies\\Lite\\AttributeQuantityDiscountStrategy',
        ],
        'liquidation' => [
            'PW\\OfertasAvanzadas\\Strategies\\Lite\\ExpiryBasedStrategy',
            'PW\\OfertasAvanzadas\\Strategies\\Pro\\LowStockStrategy',
        ],
        'loyalty' => [
            'PW\\OfertasAvanzadas\\Strategies\\Pro\\RecurringPurchaseStrategy',
        ],
        'urgency' => [
            'PW\\OfertasAvanzadas\\Strategies\\Pro\\FlashSaleStrategy',
        ],
    ];

    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenuPages']);

        foreach (self::AJAX_HOOKS as $hook => $method) {
            add_action("wp_ajax_{$hook}", [$this, $method]);
        }
    }

    private function verifyAjax(): void
    {
        check_ajax_referer('pwoa_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permisos insuficientes');
        }
    }

    private function resolveDiscountType(string $posted, array $config, string $strategy): string
    {
        if (!empty($posted)) {
            return $posted;
        }

        $from_config = $config['discount_type'] ?? '';
        if (!empty($from_config)) {
            return $from_config;
        }

        return match ($strategy) {
            'free_shipping' => 'free_shipping',
            default         => 'percentage',
        };
    }

    private function buildCampaignData(string $strategy, string $discount_type, array $config): array
    {
        return [
            'name'          => sanitize_text_field($_POST['name'] ?? ''),
            'objective'     => sanitize_text_field($_POST['objective'] ?? ''),
            'strategy'      => $strategy,
            'discount_type' => $discount_type,
            'config'        => $config,
            'conditions'    => json_decode(stripslashes($_POST['conditions'] ?? '{}'), true),
            'stacking_mode' => sanitize_text_field($_POST['stacking_mode'] ?? 'priority'),
            'priority'      => intval($_POST['priority'] ?? 10),
            'start_date'    => sanitize_text_field($_POST['start_date'] ?? ''),
            'end_date'      => sanitize_text_field($_POST['end_date'] ?? ''),
        ];
    }

    private static function formatProductResult(\WC_Product $product, bool $include_stock = false): array
    {
        $result = [
            'id'              => $product->get_id(),
            'name'            => $product->get_name(),
            'sku'             => $product->get_sku() ?: '',
            'price'           => $product->get_price(),
            'formatted_price' => wc_price($product->get_price()),
        ];

        if ($include_stock) {
            $result['stock'] = $product->get_stock_quantity();
        }

        return $result;
    }

    private static function queryProductDetails(array $args): array
    {
        $query = new \WP_Query($args);

        return array_values(array_filter(array_map(static function ($product_id) {
            $product = wc_get_product($product_id);
            return $product ? self::formatProductResult($product, true) : null;
        }, $query->posts)));
    }

    public function addMenuPages(): void
    {
        add_menu_page(
            'Ofertas Avanzadas',
            'Ofertas',
            'manage_woocommerce',
            'pwoa-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-megaphone',
            56,
        );

        add_submenu_page(
            'pwoa-dashboard',
            'Nueva Campana',
            'Nueva Campana',
            'manage_woocommerce',
            'pwoa-new-campaign',
            [$this, 'renderWizard'],
        );

        add_submenu_page(
            'pwoa-dashboard',
            'Analiticas',
            'Analiticas',
            'manage_woocommerce',
            'pwoa-analytics',
            [$this, 'renderAnalytics'],
        );

        add_submenu_page(
            'pwoa-dashboard',
            'Ajustes',
            'Ajustes',
            'manage_woocommerce',
            'pwoa-settings',
            [$this, 'renderSettings'],
        );

        add_submenu_page(
            'pwoa-dashboard',
            'Shortcodes',
            'Shortcodes',
            'manage_woocommerce',
            'pwoa-shortcodes',
            [$this, 'renderShortcodes'],
        );
    }

    public function renderDashboard(): void
    {
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;

        $campaigns = CampaignRepository::getPaginated($page, $per_page);
        $total = CampaignRepository::getCount();
        $total_pages = ceil($total / $per_page);

        include PWOA_PATH . 'src/Admin/Views/dashboard.php';
    }

    public function renderWizard(): void
    {
        include PWOA_PATH . 'src/Admin/Views/wizard.php';
    }

    public function renderAnalytics(): void
    {
        $stats = StatsRepository::getSummary();
        include PWOA_PATH . 'src/Admin/Views/analytics.php';
    }

    public function renderSettings(): void
    {
        $stacking_behavior = get_option('pwoa_stacking_behavior', 'priority_first');
        include PWOA_PATH . 'src/Admin/Views/settings.php';
    }

    public function renderShortcodes(): void
    {
        include PWOA_PATH . 'src/Admin/Views/shortcodes.php';
    }

    public function ajaxSaveSettings(): void
    {
        $this->verifyAjax();

        $stacking_behavior = sanitize_text_field($_POST['stacking_behavior'] ?? 'priority_first');
        $valid_behaviors = ['priority_first', 'stack_first', 'max_discount'];

        if (!in_array($stacking_behavior, $valid_behaviors, true)) {
            wp_send_json_error('Valor invalido');
        }

        update_option('pwoa_stacking_behavior', $stacking_behavior);

        wp_send_json_success(['message' => 'Configuracion guardada correctamente']);
    }

    public function ajaxGetWizardData(): void
    {
        $this->verifyAjax();

        $campaign_id = intval($_POST['campaign_id'] ?? 0);
        $objective = sanitize_text_field($_POST['objective'] ?? '');

        $response = [
            'attributes' => $this->getCachedAttributes(),
            'categories' => $this->getCachedCategories(),
        ];

        if ($campaign_id > 0) {
            $campaign = CampaignRepository::getById($campaign_id);

            if (!$campaign) {
                wp_send_json_error('Campana no encontrada');
            }

            $campaign->config = json_decode($campaign->config, true);
            $campaign->conditions = json_decode($campaign->conditions, true);

            $response['campaign'] = $campaign;
            $response['strategies'] = $this->getCachedStrategies($campaign->objective);
        } elseif (!empty($objective)) {
            $response['strategies'] = $this->getCachedStrategies($objective);
        }

        wp_send_json_success($response);
    }

    public function ajaxGetCampaignsPaginated(): void
    {
        $this->verifyAjax();

        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $per_page = 20;

        $campaigns = CampaignRepository::getPaginated($page, $per_page);
        $total = CampaignRepository::getCount();

        wp_send_json_success([
            'campaigns'   => $campaigns,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => ceil($total / $per_page),
        ]);
    }

    public function ajaxGetStrategies(): void
    {
        $this->verifyAjax();

        $objective = sanitize_text_field($_POST['objective'] ?? '');
        wp_send_json_success($this->getCachedStrategies($objective));
    }

    public function ajaxSaveCampaign(): void
    {
        $this->verifyAjax();

        $config = json_decode(stripslashes($_POST['config'] ?? '{}'), true);
        $strategy = sanitize_text_field($_POST['strategy'] ?? '');
        $discount_type = $this->resolveDiscountType(
            sanitize_text_field($_POST['discount_type'] ?? ''),
            $config,
            $strategy,
        );

        $data = $this->buildCampaignData($strategy, $discount_type, $config);
        $campaign_id = CampaignRepository::create($data);

        if (!$campaign_id) {
            global $wpdb;
            wp_send_json_error('Error al crear campana: ' . esc_html($wpdb->last_error));
        }

        wp_send_json_success([
            'message'     => 'Campana creada correctamente',
            'campaign_id' => $campaign_id,
        ]);
    }

    public function ajaxToggleCampaign(): void
    {
        $this->verifyAjax();

        $campaign_id = intval($_POST['campaign_id'] ?? 0);
        $active = intval($_POST['active'] ?? 0);

        CampaignRepository::updateStatus($campaign_id, $active);

        wp_send_json_success(['message' => 'Estado actualizado']);
    }

    public function ajaxGetCampaign(): void
    {
        $this->verifyAjax();

        $campaign_id = intval($_POST['campaign_id'] ?? 0);
        $campaign = CampaignRepository::getById($campaign_id);

        if (!$campaign) {
            wp_send_json_error('Campana no encontrada');
        }

        $campaign->config = json_decode($campaign->config, true);
        $campaign->conditions = json_decode($campaign->conditions, true);

        wp_send_json_success($campaign);
    }

    public function ajaxUpdateCampaign(): void
    {
        $this->verifyAjax();

        $campaign_id = intval($_POST['campaign_id'] ?? 0);
        $config = json_decode(stripslashes($_POST['config'] ?? '{}'), true);
        $strategy = sanitize_text_field($_POST['strategy'] ?? '');
        $discount_type = $this->resolveDiscountType(
            sanitize_text_field($_POST['discount_type'] ?? ''),
            $config,
            $strategy,
        );

        $data = $this->buildCampaignData($strategy, $discount_type, $config);
        $success = CampaignRepository::update($campaign_id, $data);

        if (!$success) {
            wp_send_json_error('Error al actualizar campana');
        }

        wp_send_json_success([
            'message'     => 'Campana actualizada correctamente',
            'campaign_id' => $campaign_id,
        ]);
    }

    public function ajaxDeleteCampaign(): void
    {
        $this->verifyAjax();

        $campaign_id = intval($_POST['campaign_id'] ?? 0);
        $success = CampaignRepository::softDelete($campaign_id);

        if (!$success) {
            wp_send_json_error('Error al eliminar campana');
        }

        wp_send_json_success(['message' => 'Campana eliminada correctamente']);
    }

    private function getCachedStrategies(string $objective): array
    {
        $cache_key = 'pwoa_strategies_' . $objective;
        $strategies = get_transient($cache_key);

        if ($strategies === false) {
            $strategies = $this->getStrategiesByObjective($objective);
            set_transient($cache_key, $strategies, HOUR_IN_SECONDS);
        }

        return $strategies;
    }

    private function getCachedAttributes(): array
    {
        $cache_key = 'pwoa_attributes';
        $attributes = get_transient($cache_key);

        if ($attributes !== false) {
            return $attributes;
        }

        $attributes = array_map(static function ($attr) {
            return [
                'slug' => wc_attribute_taxonomy_name($attr->attribute_name),
                'name' => $attr->attribute_label,
            ];
        }, wc_get_attribute_taxonomies());

        $attributes = array_values($attributes);
        set_transient($cache_key, $attributes, HOUR_IN_SECONDS);

        return $attributes;
    }

    private function getCachedCategories(): array
    {
        $cache_key = 'pwoa_categories';
        $categories = get_transient($cache_key);

        if ($categories !== false) {
            return $categories;
        }

        $cats = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ]);

        $categories = array_map(static function ($cat) {
            return [
                'id'   => $cat->term_id,
                'name' => $cat->name,
            ];
        }, is_array($cats) ? $cats : []);

        set_transient($cache_key, $categories, HOUR_IN_SECONDS);

        return $categories;
    }

    private function getStrategiesByObjective(string $objective): array
    {
        $classes = self::STRATEGIES_MAP[$objective] ?? [];

        return array_values(array_filter(array_map(static function ($class) {
            if (!class_exists($class)) {
                return null;
            }

            $meta = $class::getMeta();
            $meta['config_fields'] = $class::getConfigFields();
            return $meta;
        }, $classes)));
    }

    public function ajaxSearchProducts(): void
    {
        $this->verifyAjax();

        $search = sanitize_text_field($_POST['search'] ?? '');

        if (strlen($search) < 2) {
            wp_send_json_success([]);
            return;
        }

        global $wpdb;
        $results = [];
        $found_ids = [];

        if (is_numeric($search)) {
            $product = wc_get_product(intval($search));
            if ($product && $product->get_status() === 'publish') {
                $found_ids[] = $product->get_id();
                $results[] = self::formatProductResult($product);
            }
        }

        $sku_query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_sku'
            AND meta_value LIKE %s",
            '%' . $wpdb->esc_like($search) . '%',
        );
        $sku_ids = $wpdb->get_col($sku_query);

        foreach ($sku_ids as $product_id) {
            if (in_array($product_id, $found_ids)) {
                continue;
            }

            $product = wc_get_product($product_id);
            if ($product && $product->get_status() === 'publish') {
                $found_ids[] = $product->get_id();
                $results[] = self::formatProductResult($product);
            }

            if (count($results) >= 20) {
                break;
            }
        }

        if (count($results) < 20) {
            $name_query = new \WP_Query([
                'post_type'      => 'product',
                'post_status'    => 'publish',
                's'              => $search,
                'posts_per_page' => 20 - count($results),
            ]);

            foreach ($name_query->posts as $post) {
                if (in_array($post->ID, $found_ids)) {
                    continue;
                }

                $product = wc_get_product($post->ID);
                if ($product) {
                    $found_ids[] = $product->get_id();
                    $results[] = self::formatProductResult($product);
                }
            }
        }

        wp_send_json_success($results);
    }

    public function ajaxValidateConditions(): void
    {
        $this->verifyAjax();

        $conditions = json_decode(stripslashes($_POST['conditions'] ?? '{}'), true);
        $count = \PW\OfertasAvanzadas\Services\ProductMatcher::countMatchingProducts($conditions);

        wp_send_json_success(['count' => $count]);
    }

    public function ajaxGetMatchingProducts(): void
    {
        $this->verifyAjax();

        $conditions = json_decode(stripslashes($_POST['conditions'] ?? '{}'), true);

        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'fields'         => 'ids',
        ];

        if (!empty($conditions['attribute_slug']) && !empty($conditions['attribute_value'])) {
            $args['tax_query'] = [[
                'taxonomy' => $conditions['attribute_slug'],
                'field'    => 'slug',
                'terms'    => $conditions['attribute_value'],
            ]];
        }

        if (!empty($conditions['product_ids'])) {
            $args['post__in'] = $conditions['product_ids'];
        }

        if (!empty($conditions['category_ids'])) {
            $args['tax_query'] = $args['tax_query'] ?? [];
            $args['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $conditions['category_ids'],
            ];
        }

        if (!empty($conditions['min_price']) || !empty($conditions['max_price'])) {
            $args['meta_query'] = ['relation' => 'AND'];

            if (!empty($conditions['min_price'])) {
                $args['meta_query'][] = [
                    'key'     => '_price',
                    'value'   => floatval($conditions['min_price']),
                    'type'    => 'NUMERIC',
                    'compare' => '>=',
                ];
            }

            if (!empty($conditions['max_price'])) {
                $args['meta_query'][] = [
                    'key'     => '_price',
                    'value'   => floatval($conditions['max_price']),
                    'type'    => 'NUMERIC',
                    'compare' => '<=',
                ];
            }
        }

        $products = self::queryProductDetails($args);

        wp_send_json_success([
            'count'    => count($products),
            'products' => $products,
        ]);
    }

    public function ajaxGetAttributes(): void
    {
        $this->verifyAjax();
        wp_send_json_success($this->getCachedAttributes());
    }

    public function ajaxGetAttributeTerms(): void
    {
        $this->verifyAjax();

        $attribute_slug = sanitize_text_field($_POST['attribute_slug'] ?? '');

        if (empty($attribute_slug)) {
            wp_send_json_error('Atributo no especificado');
        }

        $terms = get_terms([
            'taxonomy'   => $attribute_slug,
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms)) {
            wp_send_json_error('Error al obtener terminos');
        }

        $result = array_map(static function ($term) {
            return [
                'slug' => $term->slug,
                'name' => $term->name,
            ];
        }, $terms);

        wp_send_json_success(array_values($result));
    }

    public function ajaxValidateAttribute(): void
    {
        $this->verifyAjax();

        $attribute_slug = sanitize_text_field($_POST['attribute_slug'] ?? '');
        $attribute_value = sanitize_text_field($_POST['attribute_value'] ?? '');

        $count = \PW\OfertasAvanzadas\Services\ProductMatcher::countProductsByAttribute(
            $attribute_slug,
            $attribute_value,
        );

        wp_send_json_success(['count' => $count]);
    }

    public function ajaxGetProductsByAttribute(): void
    {
        $this->verifyAjax();

        $attribute_slug = sanitize_text_field($_POST['attribute_slug'] ?? '');
        $attribute_value = sanitize_text_field($_POST['attribute_value'] ?? '');

        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'fields'         => 'ids',
            'tax_query'      => [[
                'taxonomy' => $attribute_slug,
                'field'    => 'slug',
                'terms'    => $attribute_value,
            ]],
        ];

        $products = self::queryProductDetails($args);

        wp_send_json_success([
            'count'    => count($products),
            'products' => $products,
        ]);
    }

    public function ajaxResetUnitsSold(): void
    {
        $this->verifyAjax();

        $campaign_id = intval($_POST['campaign_id'] ?? 0);

        if (!$campaign_id) {
            wp_send_json_error('ID de campana invalido');
        }

        $success = CampaignRepository::resetUnitsSold($campaign_id);

        if (!$success) {
            wp_send_json_error('Error al resetear contador');
        }

        if (class_exists('PW\\OfertasAvanzadas\\Handlers\\ProductBadgeHandler')) {
            \PW\OfertasAvanzadas\Handlers\ProductBadgeHandler::clearCache();
        }

        wp_send_json_success(['message' => 'Contador reseteado correctamente']);
    }
}

<?php
namespace PW\OfertasAvanzadas\Admin;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Core\UpgradeUrl;
use PW\OfertasAvanzadas\Repositories\CampaignRepository;

class AdminController
{
    private const CAMPAIGN_LIMIT = 5;

    private const AJAX_HOOKS = [
        'pwoa_get_wizard_data'           => 'ajaxGetWizardData',
        'pwoa_get_strategies'            => 'ajaxGetStrategies',
        'pwoa_save_campaign'             => 'ajaxSaveCampaign',
        'pwoa_update_campaign'           => 'ajaxUpdateCampaign',
        'pwoa_delete_campaign'           => 'ajaxDeleteCampaign',
        'pwoa_get_campaign'              => 'ajaxGetCampaign',
        'pwoa_toggle_campaign'           => 'ajaxToggleCampaign',
        'pwoa_search_products'           => 'ajaxSearchProducts',
        'pwoa_validate_conditions'       => 'ajaxValidateConditions',
        'pwoa_get_matching_products'     => 'ajaxGetMatchingProducts',
        'pwoa_get_attributes'            => 'ajaxGetAttributes',
        'pwoa_get_attribute_terms'       => 'ajaxGetAttributeTerms',
        'pwoa_validate_attribute'        => 'ajaxValidateAttribute',
        'pwoa_get_products_by_attribute' => 'ajaxGetProductsByAttribute',
        'pwoa_reset_units_sold'          => 'ajaxResetUnitsSold',
        'pwoa_get_campaigns_paginated'   => 'ajaxGetCampaignsPaginated',
    ];

    private const LITE_STRATEGIES = [
        'basic_discount',
        'bulk_discount',
        'buy_x_pay_y',
        'attribute_quantity_discount',
        'expiry_based',
    ];

    private const PRO_STRATEGY_META = [
        'PW\\OfertasAvanzadas\\Strategies\\Pro\\MinAmountStrategy' => [
            'key'           => 'min_amount',
            'name'          => 'Minimum order discount',
            'description'   => 'Apply a discount when the cart subtotal reaches a minimum amount.',
            'effectiveness' => 5,
            'when_to_use'   => 'Works year-round to lift average order value. Especially strong before holidays and peak sales periods.',
            'objective'     => 'aov',
            'config_fields' => [],
        ],
        'PW\\OfertasAvanzadas\\Strategies\\Pro\\FreeShippingStrategy' => [
            'key'           => 'free_shipping',
            'name'          => 'Free shipping threshold',
            'description'   => 'Waive shipping when the cart reaches a minimum subtotal.',
            'effectiveness' => 5,
            'when_to_use'   => 'A strong always-on tactic; often lifts average order value by 20–35% across many stores.',
            'objective'     => 'aov',
            'config_fields' => [],
        ],
        'PW\\OfertasAvanzadas\\Strategies\\Pro\\TieredDiscountStrategy' => [
            'key'           => 'tiered_discount',
            'name'          => 'Tiered quantity discount',
            'description'   => 'Higher discounts as the number of items in the cart increases.',
            'effectiveness' => 4,
            'when_to_use'   => 'Peak sales and volume campaigns. Strong with low unit-cost items.',
            'objective'     => 'aov',
            'config_fields' => [],
        ],
        'PW\\OfertasAvanzadas\\Strategies\\Pro\\LowStockStrategy' => [
            'key'           => 'low_stock',
            'name'          => 'Low stock discount',
            'description'   => 'Automatically discount products when on-hand quantity falls below a threshold.',
            'effectiveness' => 4,
            'when_to_use'   => 'Seasonal clearance, end-of-line, or when you want urgency from scarcity.',
            'objective'     => 'liquidation',
            'config_fields' => [],
        ],
        'PW\\OfertasAvanzadas\\Strategies\\Pro\\RecurringPurchaseStrategy' => [
            'key'           => 'recurring_purchase',
            'name'          => 'Repeat purchase reward',
            'description'   => 'Reward customers who have bought the same product multiple times.',
            'effectiveness' => 5,
            'when_to_use'   => 'Consumables, supplements, cosmetics—any repurchase SKU.',
            'objective'     => 'loyalty',
            'config_fields' => [],
        ],
        'PW\\OfertasAvanzadas\\Strategies\\Pro\\FlashSaleStrategy' => [
            'key'           => 'flash_sale',
            'name'          => 'Flash sale',
            'description'   => 'Time-limited discount to create urgency.',
            'effectiveness' => 5,
            'when_to_use'   => 'Peak events and launches; often strongest in 6–24 hour windows.',
            'objective'     => 'urgency',
            'config_fields' => [],
        ],
    ];

    private const LITE_STRATEGIES_MAP = [
        'basic'       => ['PW\\OfertasAvanzadas\\Strategies\\Lite\\BasicDiscountStrategy'],
        'aov'         => [
            'PW\\OfertasAvanzadas\\Strategies\\Lite\\BulkDiscountStrategy',
            'PW\\OfertasAvanzadas\\Strategies\\Lite\\BuyXPayYStrategy',
            'PW\\OfertasAvanzadas\\Strategies\\Lite\\AttributeQuantityDiscountStrategy',
        ],
        'liquidation' => ['PW\\OfertasAvanzadas\\Strategies\\Lite\\ExpiryBasedStrategy'],
        'loyalty'     => [],
        'urgency'     => [],
    ];

    private const PRO_STRATEGIES_MAP = [
        'basic'       => [],
        'aov'         => [
            'PW\\OfertasAvanzadas\\Strategies\\Pro\\MinAmountStrategy',
            'PW\\OfertasAvanzadas\\Strategies\\Pro\\FreeShippingStrategy',
            'PW\\OfertasAvanzadas\\Strategies\\Pro\\TieredDiscountStrategy',
        ],
        'liquidation' => ['PW\\OfertasAvanzadas\\Strategies\\Pro\\LowStockStrategy'],
        'loyalty'     => ['PW\\OfertasAvanzadas\\Strategies\\Pro\\RecurringPurchaseStrategy'],
        'urgency'     => ['PW\\OfertasAvanzadas\\Strategies\\Pro\\FlashSaleStrategy'],
    ];

    public function __construct()
    {
        AdminAssets::register();

        add_action('admin_menu', [$this, 'addMenuPages']);

        foreach (self::AJAX_HOOKS as $hook => $method) {
            add_action("wp_ajax_{$hook}", [$this, $method]);
        }
    }

    private function verifyAjax(): void
    {
        check_ajax_referer('pwoa_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Insufficient permissions.', 'pw-ofertas-avanzadas'));
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
            'free_shipping'                             => 'free_shipping',
            'flash_sale', 'min_amount', 'low_stock',
            'recurring_purchase', 'tiered_discount',
            'expiry_based'                              => 'percentage',
            default                                     => 'percentage',
        };
    }

    private function buildCampaignData(string $strategy): array
    {
        $config = json_decode(stripslashes($_POST['config'] ?? '{}'), true);

        return [
            'name'          => sanitize_text_field($_POST['name'] ?? ''),
            'objective'     => sanitize_text_field($_POST['objective'] ?? ''),
            'strategy'      => $strategy,
            'discount_type' => $this->resolveDiscountType(
                sanitize_text_field($_POST['discount_type'] ?? ''),
                $config,
                $strategy
            ),
            'config'        => $config,
            'conditions'    => json_decode(stripslashes($_POST['conditions'] ?? '{}'), true),
            'stacking_mode' => sanitize_text_field($_POST['stacking_mode'] ?? 'priority'),
            'priority'      => intval($_POST['priority'] ?? 10),
            'start_date'    => sanitize_text_field($_POST['start_date'] ?? ''),
            'end_date'      => sanitize_text_field($_POST['end_date'] ?? ''),
        ];
    }

    private function formatProductResult(\WC_Product $product): array
    {
        return [
            'id'              => $product->get_id(),
            'name'            => $product->get_name(),
            'sku'             => $product->get_sku() ?: '',
            'price'           => $product->get_price(),
            'formatted_price' => wc_price($product->get_price()),
        ];
    }

    private function queryProductDetails(array $args, bool $include_stock = false): array
    {
        $query   = new \WP_Query($args);
        $results = [];

        foreach ($query->posts as $post_id) {
            $product = wc_get_product($post_id);
            if (!$product) {
                continue;
            }
            $row = $this->formatProductResult($product);
            if ($include_stock) {
                $row['stock'] = $product->get_stock_quantity();
            }
            $results[] = $row;
        }

        return $results;
    }

    public function addMenuPages(): void
    {
        add_menu_page(
            __('PW - Ofertas Avanzadas', 'pw-ofertas-avanzadas'),
            __('Offers', 'pw-ofertas-avanzadas'),
            'manage_woocommerce',
            'pwoa-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-megaphone',
            56
        );

        add_submenu_page(
            'pwoa-dashboard',
            __('New campaign', 'pw-ofertas-avanzadas'),
            __('New campaign', 'pw-ofertas-avanzadas'),
            'manage_woocommerce',
            'pwoa-new-campaign',
            [$this, 'renderWizard']
        );

        add_submenu_page(
            'pwoa-dashboard',
            __('Shortcodes', 'pw-ofertas-avanzadas'),
            __('Shortcodes', 'pw-ofertas-avanzadas'),
            'manage_woocommerce',
            'pwoa-shortcodes',
            [$this, 'renderShortcodes']
        );
    }

    public function renderDashboard(): void
    {
        $page       = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page   = 20;
        $campaigns  = CampaignRepository::getPaginated($page, $per_page);
        $total      = CampaignRepository::getCount();
        $total_pages = ceil($total / $per_page);
        $can_create = $total < self::CAMPAIGN_LIMIT;

        include PWOA_PATH . 'src/Admin/Views/dashboard.php';
    }

    public function renderWizard(): void
    {
        if (!isset($_GET['edit'])) {
            $total = CampaignRepository::getCount();
            if ($total >= self::CAMPAIGN_LIMIT) {
                $upgrade_url = UpgradeUrl::get();
                wp_die(
                    '<h1>' . esc_html(__('Campaign limit reached', 'pw-ofertas-avanzadas')) . '</h1>' .
                    '<p>' . esc_html(__('You have reached the limit of 5 campaigns on the Lite edition.', 'pw-ofertas-avanzadas')) . '</p>' .
                    '<p><a href="' . esc_url($upgrade_url) . '">' . esc_html(__('Upgrade to Pro', 'pw-ofertas-avanzadas')) . '</a> ' .
                    esc_html(__('for unlimited campaigns.', 'pw-ofertas-avanzadas')) . '</p>',
                    esc_html__('Campaign limit', 'pw-ofertas-avanzadas'),
                    ['back_link' => true]
                );
            }
        }

        include PWOA_PATH . 'src/Admin/Views/wizard.php';
    }

    public function renderShortcodes(): void
    {
        include PWOA_PATH . 'src/Admin/Views/shortcodes.php';
    }

    public function ajaxGetWizardData(): void
    {
        $this->verifyAjax();

        $campaign_id = intval($_POST['campaign_id'] ?? 0);
        $objective   = sanitize_text_field($_POST['objective'] ?? '');

        $response = [
            'attributes' => $this->getCachedAttributes(),
            'categories' => $this->getCachedCategories(),
        ];

        if ($campaign_id > 0) {
            $campaign = CampaignRepository::getById($campaign_id);
            if (!$campaign) {
                wp_send_json_error(__('Campaign not found.', 'pw-ofertas-avanzadas'));
            }
            $campaign->config     = json_decode($campaign->config, true);
            $campaign->conditions = json_decode($campaign->conditions, true);
            $response['campaign']   = $campaign;
            $response['strategies'] = $this->getCachedStrategies($campaign->objective);
        } elseif (!empty($objective)) {
            $response['strategies'] = $this->getCachedStrategies($objective);
        }

        wp_send_json_success($response);
    }

    public function ajaxGetCampaignsPaginated(): void
    {
        $this->verifyAjax();

        $page     = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $per_page = 20;
        $total    = CampaignRepository::getCount();

        wp_send_json_success([
            'campaigns'   => CampaignRepository::getPaginated($page, $per_page),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => ceil($total / $per_page),
        ]);
    }

    public function ajaxGetStrategies(): void
    {
        $this->verifyAjax();
        wp_send_json_success($this->getCachedStrategies(
            sanitize_text_field($_POST['objective'] ?? '')
        ));
    }

    public function ajaxSaveCampaign(): void
    {
        $this->verifyAjax();

        if (CampaignRepository::getCount() >= self::CAMPAIGN_LIMIT) {
            wp_send_json_error(__('You have reached the limit of 5 campaigns. Upgrade to Pro for unlimited campaigns.', 'pw-ofertas-avanzadas'));
            return;
        }

        $strategy = sanitize_text_field($_POST['strategy'] ?? '');
        if (!$this->isLiteStrategy($strategy)) {
            wp_send_json_error(__('This strategy is not available in the Lite edition. Upgrade to Pro.', 'pw-ofertas-avanzadas'));
            return;
        }

        $campaign_id = CampaignRepository::create($this->buildCampaignData($strategy));

        if (!$campaign_id) {
            global $wpdb;
            wp_send_json_error(
                __('Could not create campaign.', 'pw-ofertas-avanzadas') . ' ' . esc_html($wpdb->last_error),
            );
        }

        wp_send_json_success([
            'message'     => __('Campaign created successfully.', 'pw-ofertas-avanzadas'),
            'campaign_id' => $campaign_id,
        ]);
    }

    public function ajaxToggleCampaign(): void
    {
        $this->verifyAjax();
        CampaignRepository::updateStatus(intval($_POST['campaign_id'] ?? 0), intval($_POST['active'] ?? 0));
        wp_send_json_success(['message' => __('Status updated.', 'pw-ofertas-avanzadas')]);
    }

    public function ajaxGetCampaign(): void
    {
        $this->verifyAjax();

        $campaign = CampaignRepository::getById(intval($_POST['campaign_id'] ?? 0));
        if (!$campaign) {
            wp_send_json_error(__('Campaign not found.', 'pw-ofertas-avanzadas'));
        }

        $campaign->config     = json_decode($campaign->config, true);
        $campaign->conditions = json_decode($campaign->conditions, true);

        wp_send_json_success($campaign);
    }

    public function ajaxUpdateCampaign(): void
    {
        $this->verifyAjax();

        $strategy = sanitize_text_field($_POST['strategy'] ?? '');
        if (!$this->isLiteStrategy($strategy)) {
            wp_send_json_error(__('This strategy is not available in the Lite edition. Upgrade to Pro.', 'pw-ofertas-avanzadas'));
            return;
        }

        $campaign_id = intval($_POST['campaign_id'] ?? 0);
        $success     = CampaignRepository::update($campaign_id, $this->buildCampaignData($strategy));

        if (!$success) {
            wp_send_json_error(__('Could not update the campaign.', 'pw-ofertas-avanzadas'));
        }

        wp_send_json_success([
            'message'     => __('Campaign updated successfully.', 'pw-ofertas-avanzadas'),
            'campaign_id' => $campaign_id,
        ]);
    }

    public function ajaxDeleteCampaign(): void
    {
        $this->verifyAjax();

        if (!CampaignRepository::softDelete(intval($_POST['campaign_id'] ?? 0))) {
            wp_send_json_error(__('Could not delete the campaign.', 'pw-ofertas-avanzadas'));
        }

        wp_send_json_success(['message' => __('Campaign deleted successfully.', 'pw-ofertas-avanzadas')]);
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
        $results   = [];
        $found_ids = [];

        if (is_numeric($search)) {
            $product = wc_get_product(intval($search));
            if ($product && $product->get_status() === 'publish') {
                $found_ids[] = $product->get_id();
                $results[]   = $this->formatProductResult($product);
            }
        }

        $sku_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value LIKE %s",
            '%' . $wpdb->esc_like($search) . '%'
        ));

        foreach ($sku_ids as $pid) {
            if (in_array($pid, $found_ids)) {
                continue;
            }
            $product = wc_get_product($pid);
            if ($product && $product->get_status() === 'publish') {
                $found_ids[] = $product->get_id();
                $results[]   = $this->formatProductResult($product);
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
                    $results[]   = $this->formatProductResult($product);
                }
            }
        }

        wp_send_json_success($results);
    }

    public function ajaxValidateConditions(): void
    {
        $this->verifyAjax();
        $conditions = json_decode(stripslashes($_POST['conditions'] ?? '{}'), true);
        wp_send_json_success([
            'count' => \PW\OfertasAvanzadas\Services\ProductMatcher::countMatchingProducts($conditions),
        ]);
    }

    public function ajaxGetMatchingProducts(): void
    {
        $this->verifyAjax();

        $conditions = json_decode(stripslashes($_POST['conditions'] ?? '{}'), true);
        $args       = [
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
            $args['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $conditions['category_ids'],
            ];
        }

        if (!empty($conditions['min_price']) || !empty($conditions['max_price'])) {
            $meta_query = ['relation' => 'AND'];
            if (!empty($conditions['min_price'])) {
                $meta_query[] = ['key' => '_price', 'value' => floatval($conditions['min_price']), 'type' => 'NUMERIC', 'compare' => '>='];
            }
            if (!empty($conditions['max_price'])) {
                $meta_query[] = ['key' => '_price', 'value' => floatval($conditions['max_price']), 'type' => 'NUMERIC', 'compare' => '<='];
            }
            $args['meta_query'] = $meta_query;
        }

        $products = $this->queryProductDetails($args, true);
        wp_send_json_success(['count' => count($products), 'products' => $products]);
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
            wp_send_json_error(__('Attribute not specified.', 'pw-ofertas-avanzadas'));
        }

        $terms = get_terms(['taxonomy' => $attribute_slug, 'hide_empty' => false]);
        if (is_wp_error($terms)) {
            wp_send_json_error(__('Could not load attribute terms.', 'pw-ofertas-avanzadas'));
        }

        wp_send_json_success(array_map(fn($t) => ['slug' => $t->slug, 'name' => $t->name], $terms));
    }

    public function ajaxValidateAttribute(): void
    {
        $this->verifyAjax();
        wp_send_json_success([
            'count' => \PW\OfertasAvanzadas\Services\ProductMatcher::countProductsByAttribute(
                sanitize_text_field($_POST['attribute_slug'] ?? ''),
                sanitize_text_field($_POST['attribute_value'] ?? '')
            ),
        ]);
    }

    public function ajaxGetProductsByAttribute(): void
    {
        $this->verifyAjax();

        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'fields'         => 'ids',
            'tax_query'      => [[
                'taxonomy' => sanitize_text_field($_POST['attribute_slug'] ?? ''),
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_POST['attribute_value'] ?? ''),
            ]],
        ];

        $products = $this->queryProductDetails($args, true);
        wp_send_json_success(['count' => count($products), 'products' => $products]);
    }

    public function ajaxResetUnitsSold(): void
    {
        $this->verifyAjax();

        $campaign_id = intval($_POST['campaign_id'] ?? 0);
        if (!$campaign_id) {
            wp_send_json_error(__('Invalid campaign ID.', 'pw-ofertas-avanzadas'));
        }

        if (!CampaignRepository::resetUnitsSold($campaign_id)) {
            wp_send_json_error(__('Could not reset the counter.', 'pw-ofertas-avanzadas'));
        }

        if (class_exists('PW\\OfertasAvanzadas\\Handlers\\ProductBadgeHandler')) {
            \PW\OfertasAvanzadas\Handlers\ProductBadgeHandler::clearCache();
        }

        wp_send_json_success(['message' => __('Counter reset successfully.', 'pw-ofertas-avanzadas')]);
    }

    private function getCachedStrategies(string $objective): array
    {
        $cache_key  = 'pwoa_strategies_v2_' . $objective;
        $strategies = get_transient($cache_key);

        if ($strategies === false) {
            $strategies = $this->getStrategiesByObjective($objective);
            set_transient($cache_key, $strategies, HOUR_IN_SECONDS);
        }

        return $strategies;
    }

    private function getCachedAttributes(): array
    {
        $attributes = get_transient('pwoa_attributes');

        if ($attributes === false) {
            $attributes = array_map(fn($a) => [
                'slug' => wc_attribute_taxonomy_name($a->attribute_name),
                'name' => $a->attribute_label,
            ], wc_get_attribute_taxonomies());
            set_transient('pwoa_attributes', $attributes, HOUR_IN_SECONDS);
        }

        return $attributes;
    }

    private function getCachedCategories(): array
    {
        $categories = get_transient('pwoa_categories');

        if ($categories === false) {
            $cats       = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
            $categories = array_map(fn($c) => ['id' => $c->term_id, 'name' => $c->name], $cats);
            set_transient('pwoa_categories', $categories, HOUR_IN_SECONDS);
        }

        return $categories;
    }

    private function getStrategiesByObjective(string $objective): array
    {
        $result = [];

        foreach (self::LITE_STRATEGIES_MAP[$objective] ?? [] as $class) {
            if (class_exists($class)) {
                $meta                  = $class::getMeta();
                $meta['config_fields'] = $class::getConfigFields();
                $meta['available']     = true;
                $result[]              = $meta;
            }
        }

        foreach (self::PRO_STRATEGIES_MAP[$objective] ?? [] as $class) {
            $meta = self::PRO_STRATEGY_META[$class] ?? null;
            if ($meta) {
                $meta['available'] = false;
                $meta              = self::translateProStrategyLabels($meta);
                $result[]          = $meta;
            }
        }

        return $result;
    }

    /**
     * Literal strings only (WordPress.org / Polyglots tooling).
     */
    private static function translateProStrategyLabels(array $meta): array
    {
        switch ($meta['key'] ?? '') {
            case 'min_amount':
                $meta['name']        = __('Minimum order discount', 'pw-ofertas-avanzadas');
                $meta['description'] = __('Apply a discount when the cart subtotal reaches a minimum amount.', 'pw-ofertas-avanzadas');
                $meta['when_to_use']  = __('Works year-round to lift average order value. Especially strong before holidays and peak sales periods.', 'pw-ofertas-avanzadas');
                break;
            case 'free_shipping':
                $meta['name']        = __('Free shipping threshold', 'pw-ofertas-avanzadas');
                $meta['description'] = __('Waive shipping when the cart reaches a minimum subtotal.', 'pw-ofertas-avanzadas');
                $meta['when_to_use']  = __('A strong always-on tactic; often lifts average order value by 20–35% across many stores.', 'pw-ofertas-avanzadas');
                break;
            case 'tiered_discount':
                $meta['name']        = __('Tiered quantity discount', 'pw-ofertas-avanzadas');
                $meta['description'] = __('Higher discounts as the number of items in the cart increases.', 'pw-ofertas-avanzadas');
                $meta['when_to_use']  = __('Peak sales and volume campaigns. Strong with low unit-cost items.', 'pw-ofertas-avanzadas');
                break;
            case 'low_stock':
                $meta['name']        = __('Low stock discount', 'pw-ofertas-avanzadas');
                $meta['description'] = __('Automatically discount products when on-hand quantity falls below a threshold.', 'pw-ofertas-avanzadas');
                $meta['when_to_use']  = __('Seasonal clearance, end-of-line, or when you want urgency from scarcity.', 'pw-ofertas-avanzadas');
                break;
            case 'recurring_purchase':
                $meta['name']        = __('Repeat purchase reward', 'pw-ofertas-avanzadas');
                $meta['description'] = __('Reward customers who have bought the same product multiple times.', 'pw-ofertas-avanzadas');
                $meta['when_to_use']  = __('Consumables, supplements, cosmetics—any repurchase SKU.', 'pw-ofertas-avanzadas');
                break;
            case 'flash_sale':
                $meta['name']        = __('Flash sale', 'pw-ofertas-avanzadas');
                $meta['description'] = __('Time-limited discount to create urgency.', 'pw-ofertas-avanzadas');
                $meta['when_to_use']  = __('Peak events and launches; often strongest in 6–24 hour windows.', 'pw-ofertas-avanzadas');
                break;
        }

        return $meta;
    }

    private function isLiteStrategy(string $strategy): bool
    {
        return in_array($strategy, self::LITE_STRATEGIES, true);
    }
}

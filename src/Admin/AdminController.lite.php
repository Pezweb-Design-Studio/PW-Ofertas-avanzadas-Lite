<?php
namespace PW\OfertasAvanzadas\Admin;

use PW\OfertasAvanzadas\Repositories\CampaignRepository;

class AdminController
{
    public function __construct()
    {
        add_action("admin_menu", [$this, "addMenuPages"]);
        add_action("wp_ajax_pwoa_get_wizard_data", [
            $this,
            "ajaxGetWizardData",
        ]);
        add_action("wp_ajax_pwoa_get_strategies", [$this, "ajaxGetStrategies"]);
        add_action("wp_ajax_pwoa_save_campaign", [$this, "ajaxSaveCampaign"]);
        add_action("wp_ajax_pwoa_update_campaign", [
            $this,
            "ajaxUpdateCampaign",
        ]);
        add_action("wp_ajax_pwoa_delete_campaign", [
            $this,
            "ajaxDeleteCampaign",
        ]);
        add_action("wp_ajax_pwoa_get_campaign", [$this, "ajaxGetCampaign"]);
        add_action("wp_ajax_pwoa_toggle_campaign", [
            $this,
            "ajaxToggleCampaign",
        ]);
        add_action("wp_ajax_pwoa_search_products", [
            $this,
            "ajaxSearchProducts",
        ]);
        add_action("wp_ajax_pwoa_validate_conditions", [
            $this,
            "ajaxValidateConditions",
        ]);
        add_action("wp_ajax_pwoa_get_matching_products", [
            $this,
            "ajaxGetMatchingProducts",
        ]);
        add_action("wp_ajax_pwoa_get_attributes", [$this, "ajaxGetAttributes"]);
        add_action("wp_ajax_pwoa_get_attribute_terms", [
            $this,
            "ajaxGetAttributeTerms",
        ]);
        add_action("wp_ajax_pwoa_validate_attribute", [
            $this,
            "ajaxValidateAttribute",
        ]);
        add_action("wp_ajax_pwoa_get_products_by_attribute", [
            $this,
            "ajaxGetProductsByAttribute",
        ]);
        add_action("wp_ajax_pwoa_reset_units_sold", [
            $this,
            "ajaxResetUnitsSold",
        ]);
        add_action("wp_ajax_pwoa_get_campaigns_paginated", [
            $this,
            "ajaxGetCampaignsPaginated",
        ]);
    }

    public function addMenuPages(): void
    {
        add_menu_page(
            "Ofertas Avanzadas",
            "Ofertas",
            "manage_woocommerce",
            "pwoa-dashboard",
            [$this, "renderDashboard"],
            "dashicons-megaphone",
            56,
        );

        add_submenu_page(
            "pwoa-dashboard",
            "Nueva Campaña",
            "Nueva Campaña",
            "manage_woocommerce",
            "pwoa-new-campaign",
            [$this, "renderWizard"],
        );
    }

    public function renderDashboard(): void
    {
        $page = isset($_GET["paged"]) ? max(1, intval($_GET["paged"])) : 1;
        $per_page = 20;

        $campaigns = CampaignRepository::getPaginated($page, $per_page);
        $total = CampaignRepository::getCount();
        $total_pages = ceil($total / $per_page);

        $can_create = $total < 5;

        include PWOA_PATH . "src/Admin/Views/dashboard.php";
    }

    public function renderWizard(): void
    {
        if (!isset($_GET["edit"])) {
            $total = CampaignRepository::getCount();
            if ($total >= 5) {
                wp_die(
                    "<h1>Límite alcanzado</h1>" .
                        "<p>Has alcanzado el límite de 5 campañas en la versión Lite.</p>" .
                        '<p><a href="https://pezweb.com/">Actualiza a Pro</a> para campañas ilimitadas.</p>',
                    "Límite de campañas",
                    ["back_link" => true],
                );
            }
        }

        include PWOA_PATH . "src/Admin/Views/wizard.php";
    }

    public function ajaxGetWizardData(): void
    {
        check_ajax_referer("pwoa_nonce", "nonce");

        if (!current_user_can("manage_woocommerce")) {
            wp_send_json_error("Permisos insuficientes");
        }

        $campaign_id = isset($_POST["campaign_id"])
            ? intval($_POST["campaign_id"])
            : 0;
        $objective = sanitize_text_field($_POST["objective"] ?? "");

        $response = [
            "attributes" => $this->getCachedAttributes(),
            "categories" => $this->getCachedCategories(),
        ];

        if ($campaign_id > 0) {
            $campaign = CampaignRepository::getById($campaign_id);

            if (!$campaign) {
                wp_send_json_error("Campaña no encontrada");
            }

            $campaign->config = json_decode($campaign->config, true);
            $campaign->conditions = json_decode($campaign->conditions, true);

            $response["campaign"] = $campaign;
            $response["strategies"] = $this->getCachedStrategies(
                $campaign->objective,
            );
        } elseif (!empty($objective)) {
            $response["strategies"] = $this->getCachedStrategies($objective);
        }

        wp_send_json_success($response);
    }

    public function ajaxGetCampaignsPaginated(): void
    {
        check_ajax_referer("pwoa_nonce", "nonce");

        if (!current_user_can("manage_woocommerce")) {
            wp_send_json_error("Permisos insuficientes");
        }

        $page = isset($_POST["page"]) ? max(1, intval($_POST["page"])) : 1;
        $per_page = 20;

        $campaigns = CampaignRepository::getPaginated($page, $per_page);
        $total = CampaignRepository::getCount();

        wp_send_json_success([
            "campaigns" => $campaigns,
            "total" => $total,
            "page" => $page,
            "per_page" => $per_page,
            "total_pages" => ceil($total / $per_page),
        ]);
    }

    public function ajaxGetStrategies(): void
    {
        check_ajax_referer("pwoa_nonce", "nonce");

        if (!current_user_can("manage_woocommerce")) {
            wp_send_json_error("Permisos insuficientes");
        }

        $objective = sanitize_text_field($_POST["objective"] ?? "");
        $strategies = $this->getCachedStrategies($objective);

        wp_send_json_success($strategies);
    }

    public function ajaxSaveCampaign(): void
    {
        check_ajax_referer("pwoa_nonce", "nonce");

        if (!current_user_can("manage_woocommerce")) {
            wp_send_json_error("Permisos insuficientes");
        }

        // ⚠️ LITE: Validar límite de 5 campañas
        $total = CampaignRepository::getCount();
        if ($total >= 5) {
            wp_send_json_error(
                "Has alcanzado el límite de 5 campañas. Actualiza a Pro para campañas ilimitadas.",
            );
            return;
        }

        // ⚠️ LITE: Validar que strategy sea Lite
        $strategy = sanitize_text_field($_POST["strategy"] ?? "");
        if (!$this->isLiteStrategy($strategy)) {
            wp_send_json_error(
                "Esta estrategia no está disponible en la versión Lite. Actualiza a Pro.",
            );
            return;
        }

        $config = json_decode(stripslashes($_POST["config"] ?? "{}"), true);

        $discount_type = sanitize_text_field($_POST["discount_type"] ?? "");

        if (empty($discount_type)) {
            $discount_type = $config["discount_type"] ?? "";

            if (empty($discount_type)) {
                $discount_type = match ($strategy) {
                    "free_shipping" => "free_shipping",
                    "flash_sale",
                    "min_amount",
                    "low_stock",
                    "recurring_purchase"
                        => "percentage",
                    "tiered_discount", "expiry_based" => "percentage",
                    default => "percentage",
                };
            }
        }

        $data = [
            "name" => sanitize_text_field($_POST["name"] ?? ""),
            "objective" => sanitize_text_field($_POST["objective"] ?? ""),
            "strategy" => $strategy,
            "discount_type" => $discount_type,
            "config" => $config,
            "conditions" => json_decode(
                stripslashes($_POST["conditions"] ?? "{}"),
                true,
            ),
            "stacking_mode" => sanitize_text_field(
                $_POST["stacking_mode"] ?? "priority",
            ),
            "priority" => intval($_POST["priority"] ?? 10),
            "start_date" => sanitize_text_field($_POST["start_date"] ?? ""),
            "end_date" => sanitize_text_field($_POST["end_date"] ?? ""),
        ];

        $campaign_id = CampaignRepository::create($data);

        if (!$campaign_id) {
            global $wpdb;
            wp_send_json_error("Error al crear campaña: " . $wpdb->last_error);
        }

        wp_send_json_success([
            "message" => "Campaña creada correctamente",
            "campaign_id" => $campaign_id,
        ]);
    }

    public function ajaxToggleCampaign(): void
    {
        check_ajax_referer("pwoa_nonce", "nonce");

        if (!current_user_can("manage_woocommerce")) {
            wp_send_json_error("Permisos insuficientes");
        }

        $campaign_id = intval($_POST["campaign_id"] ?? 0);
        $active = intval($_POST["active"] ?? 0);

        CampaignRepository::updateStatus($campaign_id, $active);

        wp_send_json_success(["message" => "Estado actualizado"]);
    }

    public function ajaxGetCampaign(): void
    {
        check_ajax_referer("pwoa_nonce", "nonce");

        if (!current_user_can("manage_woocommerce")) {
            wp_send_json_error("Permisos insuficientes");
        }

        $campaign_id = intval($_POST["campaign_id"] ?? 0);
        $campaign = CampaignRepository::getById($campaign_id);

        if (!$campaign) {
            wp_send_json_error("Campaña no encontrada");
        }

        $campaign->config = json_decode($campaign->config, true);
        $campaign->conditions = json_decode($campaign->conditions, true);

        wp_send_json_success($campaign);
    }

    public function ajaxUpdateCampaign(): void
    {
        check_ajax_referer("pwoa_nonce", "nonce");

        if (!current_user_can("manage_woocommerce")) {
            wp_send_json_error("Permisos insuficientes");
        }

        // ⚠️ LITE: Validar que strategy sea Lite
        $strategy = sanitize_text_field($_POST["strategy"] ?? "");
        if (!$this->isLiteStrategy($strategy)) {
            wp_send_json_error(
                "Esta estrategia no está disponible en la versión Lite. Actualiza a Pro.",
            );
            return;
        }

        $campaign_id = intval($_POST["campaign_id"] ?? 0);
        $config = json_decode(stripslashes($_POST["config"] ?? "{}"), true);

        $discount_type = sanitize_text_field($_POST["discount_type"] ?? "");

        if (empty($discount_type)) {
            $discount_type = $config["discount_type"] ?? "";

            if (empty($discount_type)) {
                $discount_type = match ($strategy) {
                    "free_shipping" => "free_shipping",
                    "flash_sale",
                    "min_amount",
                    "low_stock",
                    "recurring_purchase"
                        => "percentage",
                    "tiered_discount", "expiry_based" => "percentage",
                    default => "percentage",
                };
            }
        }

        $data = [
            "name" => sanitize_text_field($_POST["name"] ?? ""),
            "objective" => sanitize_text_field($_POST["objective"] ?? ""),
            "strategy" => $strategy,
            "discount_type" => $discount_type,
            "config" => $config,
            "conditions" => json_decode(
                stripslashes($_POST["conditions"] ?? "{}"),
                true,
            ),
            "stacking_mode" => sanitize_text_field(
                $_POST["stacking_mode"] ?? "priority",
            ),
            "priority" => intval($_POST["priority"] ?? 10),
            "start_date" => sanitize_text_field($_POST["start_date"] ?? ""),
            "end_date" => sanitize_text_field($_POST["end_date"] ?? ""),
        ];

        $success = CampaignRepository::update($campaign_id, $data);

        if (!$success) {
            wp_send_json_error("Error al actualizar campaña");
        }

        wp_send_json_success([
            "message" => "Campaña actualizada correctamente",
            "campaign_id" => $campaign_id,
        ]);
    }

    public function ajaxDeleteCampaign(): void
    {
        check_ajax_referer("pwoa_nonce", "nonce");

        if (!current_user_can("manage_woocommerce")) {
            wp_send_json_error("Permisos insuficientes");
        }

        $campaign_id = intval($_POST["campaign_id"] ?? 0);

        $success = CampaignRepository::softDelete($campaign_id);

        if (!$success) {
            wp_send_json_error("Error al eliminar campaña");
        }

        wp_send_json_success(["message" => "Campaña eliminada correctamente"]);
    }

    private function getCachedStrategies(string $objective): array
    {
        $cache_key = "pwoa_strategies_" . $objective;
        $strategies = get_transient($cache_key);

        if ($strategies === false) {
            $strategies = $this->getStrategiesByObjective($objective);
            set_transient($cache_key, $strategies, HOUR_IN_SECONDS);
        }

        return $strategies;
    }

    private function getCachedAttributes(): array
    {
        $cache_key = "pwoa_attributes";
        $attributes = get_transient($cache_key);

        if ($attributes === false) {
            $attrs = wc_get_attribute_taxonomies();
            $attributes = [];

            foreach ($attrs as $attr) {
                $attributes[] = [
                    "slug" => wc_attribute_taxonomy_name($attr->attribute_name),
                    "name" => $attr->attribute_label,
                ];
            }

            set_transient($cache_key, $attributes, HOUR_IN_SECONDS);
        }

        return $attributes;
    }

    private function getCachedCategories(): array
    {
        $cache_key = "pwoa_categories";
        $categories = get_transient($cache_key);

        if ($categories === false) {
            $cats = get_terms([
                "taxonomy" => "product_cat",
                "hide_empty" => false,
            ]);
            $categories = [];

            foreach ($cats as $cat) {
                $categories[] = [
                    "id" => $cat->term_id,
                    "name" => $cat->name,
                ];
            }

            set_transient($cache_key, $categories, HOUR_IN_SECONDS);
        }

        return $categories;
    }

    // ⚠️ LITE: Strategies disponibles + Pro bloqueadas
    private function getStrategiesByObjective(string $objective): array
    {
        $lite_map = [
            "basic" => [
                "PW\\OfertasAvanzadas\\Strategies\\Lite\\BasicDiscountStrategy",
            ],
            "aov" => [
                "PW\\OfertasAvanzadas\\Strategies\\Lite\\BulkDiscountStrategy",
                "PW\\OfertasAvanzadas\\Strategies\\Lite\\BuyXPayYStrategy",
                "PW\\OfertasAvanzadas\\Strategies\\Lite\\AttributeQuantityDiscountStrategy",
            ],
            "liquidation" => [
                "PW\\OfertasAvanzadas\\Strategies\\Lite\\ExpiryBasedStrategy",
            ],
            "loyalty" => [],
            "urgency" => [],
        ];

        // ⚡ NUEVO: Strategies Pro bloqueadas (solo metadata)
        $pro_map = [
            "basic" => [],
            "aov" => [
                "PW\\OfertasAvanzadas\\Strategies\\Pro\\MinAmountStrategy",
                "PW\\OfertasAvanzadas\\Strategies\\Pro\\FreeShippingStrategy",
                "PW\\OfertasAvanzadas\\Strategies\\Pro\\TieredDiscountStrategy",
            ],
            "liquidation" => [
                "PW\\OfertasAvanzadas\\Strategies\\Pro\\LowStockStrategy",
            ],
            "loyalty" => [
                "PW\\OfertasAvanzadas\\Strategies\\Pro\\RecurringPurchaseStrategy",
            ],
            "urgency" => [
                "PW\\OfertasAvanzadas\\Strategies\\Pro\\FlashSaleStrategy",
            ],
        ];

        $result = [];

        // Cargar strategies LITE (disponibles)
        $lite_classes = $lite_map[$objective] ?? [];
        foreach ($lite_classes as $class) {
            if (class_exists($class)) {
                $meta = $class::getMeta();
                $meta["config_fields"] = $class::getConfigFields();
                $meta["available"] = true; // ✅ Disponible en Lite
                $result[] = $meta;
            }
        }

        // Cargar strategies PRO (bloqueadas, solo metadata)
        $pro_classes = $pro_map[$objective] ?? [];
        foreach ($pro_classes as $class) {
            // Solo metadata, sin necesidad de que exista la clase
            $meta = $this->getProStrategyMeta($class);
            if ($meta) {
                $meta["available"] = false; // 🔒 Bloqueada
                $result[] = $meta;
            }
        }

        return $result;
    }

    // ⚡ NUEVO: Metadata hardcoded de strategies Pro
    private function getProStrategyMeta(string $class): ?array
    {
        $meta_map = [
            "PW\\OfertasAvanzadas\\Strategies\\Pro\\MinAmountStrategy" => [
                "name" => "Descuento por Monto Mínimo",
                "description" =>
                    "Aplica descuento cuando el carrito supera un monto específico",
                "effectiveness" => 5,
                "when_to_use" =>
                    "Efectivo todo el año. Ideal para aumentar ticket promedio.",
                "objective" => "aov",
                "config_fields" => [],
            ],
            "PW\\OfertasAvanzadas\\Strategies\\Pro\\FreeShippingStrategy" => [
                "name" => "Envío Gratis sobre Monto Mínimo",
                "description" =>
                    "Elimina costo de envío cuando el carrito supera un monto específico",
                "effectiveness" => 5,
                "when_to_use" =>
                    "Estrategia permanente altamente efectiva. Incrementa ticket promedio 20-35%.",
                "objective" => "aov",
                "config_fields" => [],
            ],
            "PW\\OfertasAvanzadas\\Strategies\\Pro\\TieredDiscountStrategy" => [
                "name" => "Descuento Escalonado por Cantidad",
                "description" =>
                    "Descuentos progresivos según cantidad de productos en el carrito",
                "effectiveness" => 4,
                "when_to_use" =>
                    "Black Friday, Cyber Monday, campañas de volumen.",
                "objective" => "aov",
                "config_fields" => [],
            ],
            "PW\\OfertasAvanzadas\\Strategies\\Pro\\LowStockStrategy" => [
                "name" => "Descuento por Stock Bajo",
                "description" =>
                    "Aplica descuentos automáticos a productos con pocas unidades disponibles",
                "effectiveness" => 4,
                "when_to_use" =>
                    "Liquidación de inventario, cambio de temporada, discontinuación de productos.",
                "objective" => "liquidation",
                "config_fields" => [],
            ],
            "PW\\OfertasAvanzadas\\Strategies\\Pro\\RecurringPurchaseStrategy" => [
                "name" => "Descuento por Compras Recurrentes",
                "description" =>
                    "Recompensa a clientes que compran el mismo producto múltiples veces",
                "effectiveness" => 5,
                "when_to_use" =>
                    "Productos de recompra: cosméticos, suplementos, alimentos. Aumenta retención 40-60%.",
                "objective" => "loyalty",
                "config_fields" => [],
            ],
            "PW\\OfertasAvanzadas\\Strategies\\Pro\\FlashSaleStrategy" => [
                "name" => "Flash Sale (Oferta Relámpago)",
                "description" =>
                    "Descuento por tiempo limitado para generar urgencia",
                "effectiveness" => 5,
                "when_to_use" =>
                    "Black Friday, Cyber Monday, lanzamientos de productos. Máxima efectividad en ventanas de 6-24 horas.",
                "objective" => "urgency",
                "config_fields" => [],
            ],
        ];

        return $meta_map[$class] ?? null;
    }

    // ⚡ NUEVO: Validar si strategy es Lite
    private function isLiteStrategy(string $strategy): bool
    {
        $lite_strategies = [
            "basic_discount",
            "bulk_discount",
            "buy_x_pay_y",
            "attribute_quantity_discount",
            "expiry_based",
        ];

        return in_array($strategy, $lite_strategies);
    }

    public function ajaxSearchProducts(): void
    {
        check_ajax_referer("pwoa_nonce", "nonce");

        if (!current_user_can("manage_woocommerce")) {
            wp_send_json_error("Permisos insuficientes");
        }

        $search = sanitize_text_field($_POST["search"] ?? "");

        if (strlen($search) < 2) {
            wp_send_json_success([]);
            return;
        }

        global $wpdb;
        $results = [];
        $found_ids = [];

        if (is_numeric($search)) {
            $product = wc_get_product(intval($search));
            if ($product && $product->get_status() === "publish") {
                $found_ids[] = $product->get_id();
                $results[] = [
                    "id" => $product->get_id(),
                    "name" => $product->get_name(),
                    "sku" => $product->get_sku() ?: "",
                    "price" => $product->get_price(),
                    "formatted_price" => wc_price($product->get_price()),
                ];
            }
        }

        $sku_query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_sku'
            AND meta_value LIKE %s",
            "%" . $wpdb->esc_like($search) . "%",
        );
        $sku_ids = $wpdb->get_col($sku_query);

        foreach ($sku_ids as $product_id) {
            if (in_array($product_id, $found_ids)) {
                continue;
            }

            $product = wc_get_product($product_id);
            if ($product && $product->get_status() === "publish") {
                $found_ids[] = $product->get_id();
                $results[] = [
                    "id" => $product->get_id(),
                    "name" => $product->get_name(),
                    "sku" => $product->get_sku() ?: "",
                    "price" => $product->get_price(),
                    "formatted_price" => wc_price($product->get_price()),
                ];
            }

            if (count($results) >= 20) {
                break;
            }
        }

        if (count($results) < 20) {
            $name_query = new \WP_Query([
                "post_type" => "product",
                "post_status" => "publish",
                "s" => $search,
                "posts_per_page" => 20 - count($results),
            ]);

            foreach ($name_query->posts as $post) {
                if (in_array($post->ID, $found_ids)) {
                    continue;
                }

                $product = wc_get_product($post->ID);
                if ($product) {
                    $found_ids[] = $product->get_id();
                    $results[] = [
                        "id" => $product->get_id(),
                        "name" => $product->get_name(),
                        "sku" => $product->get_sku() ?: "",
                        "price" => $product->get_price(),
                        "formatted_price" => wc_price($product->get_price()),
                    ];
                }
            }
        }

        wp_send_json_success($results);
    }

    public function ajaxValidateConditions(): void
    {
        check_ajax_referer("pwoa_nonce", "nonce");

        if (!current_user_can("manage_woocommerce")) {
            wp_send_json_error("Permisos insuficientes");
        }

        $conditions = json_decode(
            stripslashes($_POST["conditions"] ?? "{}"),
            true,
        );

        $count = \PW\OfertasAvanzadas\Services\ProductMatcher::countMatchingProducts(
            $conditions,
        );

        wp_send_json_success(["count" => $count]);
    }

    public function ajaxGetMatchingProducts(): void
    {
        check_ajax_referer("pwoa_nonce", "nonce");

        if (!current_user_can("manage_woocommerce")) {
            wp_send_json_error("Permisos insuficientes");
        }

        $conditions = json_decode(
            stripslashes($_POST["conditions"] ?? "{}"),
            true,
        );

        $args = [
            "post_type" => "product",
            "post_status" => "publish",
            "posts_per_page" => 20,
            "fields" => "ids",
        ];

        if (
            !empty($conditions["attribute_slug"]) &&
            !empty($conditions["attribute_value"])
        ) {
            $args["tax_query"] = [
                [
                    "taxonomy" => $conditions["attribute_slug"],
                    "field" => "slug",
                    "terms" => $conditions["attribute_value"],
                ],
            ];
        }

        if (!empty($conditions["product_ids"])) {
            $args["post__in"] = $conditions["product_ids"];
        }

        if (!empty($conditions["category_ids"])) {
            if (!isset($args["tax_query"])) {
                $args["tax_query"] = [];
            }
            $args["tax_query"][] = [
                "taxonomy" => "product_cat",
                "field" => "term_id",
                "terms" => $conditions["category_ids"],
            ];
        }

        if (
            !empty($conditions["min_price"]) ||
            !empty($conditions["max_price"])
        ) {
            $args["meta_query"] = [
                "relation" => "AND",
            ];

            if (!empty($conditions["min_price"])) {
                $args["meta_query"][] = [
                    "key" => "_price",
                    "value" => floatval($conditions["min_price"]),
                    "type" => "NUMERIC",
                    "compare" => ">=",
                ];
            }

            if (!empty($conditions["max_price"])) {
                $args["meta_query"][] = [
                    "key" => "_price",
                    "value" => floatval($conditions["max_price"]),
                    "type" => "NUMERIC",
                    "compare" => "<=",
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
                    "id" => $product->get_id(),
                    "name" => $product->get_name(),
                    "sku" => $product->get_sku() ?: "",
                    "price" => $product->get_price(),
                    "formatted_price" => wc_price($product->get_price()),
                    "stock" => $product->get_stock_quantity(),
                ];
            }
        }

        wp_send_json_success([
            "count" => count($products),
            "products" => $products,
        ]);
    }

    public function ajaxGetAttributes(): void
    {
        check_ajax_referer("pwoa_nonce", "nonce");

        if (!current_user_can("manage_woocommerce")) {
            wp_send_json_error("Permisos insuficientes");
        }

        $result = $this->getCachedAttributes();
        wp_send_json_success($result);
    }

    public function ajaxGetAttributeTerms(): void
    {
        check_ajax_referer("pwoa_nonce", "nonce");

        if (!current_user_can("manage_woocommerce")) {
            wp_send_json_error("Permisos insuficientes");
        }

        $attribute_slug = sanitize_text_field($_POST["attribute_slug"] ?? "");

        if (empty($attribute_slug)) {
            wp_send_json_error("Atributo no especificado");
        }

        $terms = get_terms([
            "taxonomy" => $attribute_slug,
            "hide_empty" => false,
        ]);

        if (is_wp_error($terms)) {
            wp_send_json_error("Error al obtener términos");
        }

        $result = [];
        foreach ($terms as $term) {
            $result[] = [
                "slug" => $term->slug,
                "name" => $term->name,
            ];
        }

        wp_send_json_success($result);
    }

    public function ajaxValidateAttribute(): void
    {
        check_ajax_referer("pwoa_nonce", "nonce");

        if (!current_user_can("manage_woocommerce")) {
            wp_send_json_error("Permisos insuficientes");
        }

        $attribute_slug = sanitize_text_field($_POST["attribute_slug"] ?? "");
        $attribute_value = sanitize_text_field($_POST["attribute_value"] ?? "");

        $count = \PW\OfertasAvanzadas\Services\ProductMatcher::countProductsByAttribute(
            $attribute_slug,
            $attribute_value,
        );

        wp_send_json_success(["count" => $count]);
    }

    public function ajaxGetProductsByAttribute(): void
    {
        check_ajax_referer("pwoa_nonce", "nonce");

        if (!current_user_can("manage_woocommerce")) {
            wp_send_json_error("Permisos insuficientes");
        }

        $attribute_slug = sanitize_text_field($_POST["attribute_slug"] ?? "");
        $attribute_value = sanitize_text_field($_POST["attribute_value"] ?? "");

        $args = [
            "post_type" => "product",
            "post_status" => "publish",
            "posts_per_page" => 20,
            "fields" => "ids",
            "tax_query" => [
                [
                    "taxonomy" => $attribute_slug,
                    "field" => "slug",
                    "terms" => $attribute_value,
                ],
            ],
        ];

        $query = new \WP_Query($args);
        $product_ids = $query->posts;
        $products = [];

        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $products[] = [
                    "id" => $product->get_id(),
                    "name" => $product->get_name(),
                    "sku" => $product->get_sku() ?: "",
                    "price" => $product->get_price(),
                    "formatted_price" => wc_price($product->get_price()),
                    "stock" => $product->get_stock_quantity(),
                ];
            }
        }

        wp_send_json_success([
            "count" => count($products),
            "products" => $products,
        ]);
    }

    public function ajaxResetUnitsSold(): void
    {
        check_ajax_referer("pwoa_nonce", "nonce");

        if (!current_user_can("manage_woocommerce")) {
            wp_send_json_error("Permisos insuficientes");
        }

        $campaign_id = intval($_POST["campaign_id"] ?? 0);

        if (!$campaign_id) {
            wp_send_json_error("ID de campaña inválido");
        }

        $success = CampaignRepository::resetUnitsSold($campaign_id);

        if (!$success) {
            wp_send_json_error("Error al resetear contador");
        }

        if (
            class_exists("PW\\OfertasAvanzadas\\Handlers\\ProductBadgeHandler")
        ) {
            \PW\OfertasAvanzadas\Handlers\ProductBadgeHandler::clearCache();
        }

        wp_send_json_success(["message" => "Contador reseteado correctamente"]);
    }
}

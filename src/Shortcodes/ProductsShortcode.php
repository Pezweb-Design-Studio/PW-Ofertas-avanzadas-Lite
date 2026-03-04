<?php
namespace PW\OfertasAvanzadas\Shortcodes;

use PW\OfertasAvanzadas\Repositories\CampaignRepository;

class ProductsShortcode {

    public function __construct() {
        add_shortcode('pwoa_productos_oferta', [$this, 'render']);
    }

    public function render($atts): string {
        if (!function_exists('woocommerce_product_loop_start')) {
            return '';
        }

        $atts = shortcode_atts([
            'campaign_id'        => '',
            'strategy'           => '',
            'category'           => '',
            'tag'                => '',
            'min_price'          => '',
            'max_price'          => '',
            'limit'              => 12,
            'columns'            => 4,
            'orderby'            => 'date',
            'order'              => 'DESC',
            'show_badge'         => 'true',
            'show_campaign_name' => 'false',
            'paginate'           => 'false',
            'per_page'           => 12,
        ], $atts, 'pwoa_productos_oferta');

        $show_badge         = filter_var($atts['show_badge'], FILTER_VALIDATE_BOOLEAN);
        $show_campaign_name = filter_var($atts['show_campaign_name'], FILTER_VALIDATE_BOOLEAN);
        $paginate           = filter_var($atts['paginate'], FILTER_VALIDATE_BOOLEAN);

        $query_args = $this->buildQueryArgs($atts, $paginate);

        ob_start();
        $this->renderProducts($query_args, (int) $atts['columns'], $show_badge, $show_campaign_name, $paginate);
        return ob_get_clean();
    }

    private function buildQueryArgs(array $atts, bool $paginate): array {
        $order   = in_array(strtoupper($atts['order']), ['ASC', 'DESC']) ? strtoupper($atts['order']) : 'DESC';
        $orderby = sanitize_key($atts['orderby']);

        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $paginate ? (int) $atts['per_page'] : (int) $atts['limit'],
            'orderby'        => $orderby,
            'order'          => $order,
        ];

        if ($paginate) {
            $args['paged'] = max(1, get_query_var('paged', 1));
        }

        if ($orderby === 'price') {
            $args['orderby']  = 'meta_value_num';
            $args['meta_key'] = '_price';
        }

        // Campaign-specific filter
        if (!empty($atts['campaign_id'])) {
            $campaign = CampaignRepository::getById((int) $atts['campaign_id']);
            if ($campaign) {
                $conditions = json_decode($campaign->conditions, true) ?? [];
                $this->applyConditionsToQuery($args, $conditions);
            }
        } elseif (!empty($atts['strategy'])) {
            $this->applyStrategyFilter($args, sanitize_key($atts['strategy']));
        }

        // Shortcode-level taxonomy filters
        if (!empty($atts['category'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => array_map('trim', explode(',', $atts['category'])),
            ];
        }

        if (!empty($atts['tag'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'product_tag',
                'field'    => 'slug',
                'terms'    => array_map('trim', explode(',', $atts['tag'])),
            ];
        }

        // Shortcode-level price filters
        if (!empty($atts['min_price']) || !empty($atts['max_price'])) {
            $meta_query = ['relation' => 'AND'];
            if (!empty($atts['min_price'])) {
                $meta_query[] = ['key' => '_price', 'value' => (float) $atts['min_price'], 'type' => 'NUMERIC', 'compare' => '>='];
            }
            if (!empty($atts['max_price'])) {
                $meta_query[] = ['key' => '_price', 'value' => (float) $atts['max_price'], 'type' => 'NUMERIC', 'compare' => '<='];
            }
            $args['meta_query'] = $meta_query;
        }

        return $args;
    }

    private function applyConditionsToQuery(array &$args, array $conditions): void {
        if (!empty($conditions['product_ids'])) {
            $args['post__in'] = array_map('intval', $conditions['product_ids']);
        }

        if (!empty($conditions['exclude_product_ids'])) {
            $args['post__not_in'] = array_map('intval', $conditions['exclude_product_ids']);
        }

        if (!empty($conditions['category_ids'])) {
            if (!isset($args['tax_query'])) $args['tax_query'] = [];
            $args['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => array_map('intval', $conditions['category_ids']),
            ];
        }

        if (!empty($conditions['tag_ids'])) {
            if (!isset($args['tax_query'])) $args['tax_query'] = [];
            $args['tax_query'][] = [
                'taxonomy' => 'product_tag',
                'field'    => 'term_id',
                'terms'    => array_map('intval', $conditions['tag_ids']),
            ];
        }

        if (!empty($conditions['attribute_slug']) && !empty($conditions['attribute_value'])) {
            if (!isset($args['tax_query'])) $args['tax_query'] = [];
            $args['tax_query'][] = [
                'taxonomy' => $conditions['attribute_slug'],
                'field'    => 'slug',
                'terms'    => $conditions['attribute_value'],
            ];
        }

        $meta_query = ['relation' => 'AND'];
        if (!empty($conditions['min_price'])) {
            $meta_query[] = ['key' => '_price', 'value' => (float) $conditions['min_price'], 'type' => 'NUMERIC', 'compare' => '>='];
        }
        if (!empty($conditions['max_price'])) {
            $meta_query[] = ['key' => '_price', 'value' => (float) $conditions['max_price'], 'type' => 'NUMERIC', 'compare' => '<='];
        }
        if (count($meta_query) > 1) {
            $args['meta_query'] = $meta_query;
        }
    }

    private function applyStrategyFilter(array &$args, string $strategy): void {
        $campaigns = CampaignRepository::getActive();
        $ids = [];

        foreach ($campaigns as $campaign) {
            if ($campaign->strategy !== $strategy) continue;
            $conditions = json_decode($campaign->conditions, true) ?? [];
            if (!empty($conditions['product_ids'])) {
                $ids = array_merge($ids, array_map('intval', $conditions['product_ids']));
            }
        }

        if (!empty($ids)) {
            $args['post__in'] = array_unique($ids);
        }
    }

    private function renderProducts(array $query_args, int $columns, bool $show_badge, bool $show_campaign_name, bool $paginate): void {
        $query = new \WP_Query($query_args);

        if (!$query->have_posts()) {
            echo '<p class="pwoa-no-products">' . esc_html__('No hay productos en oferta disponibles.', 'pw-ofertas-avanzadas') . '</p>';
            return;
        }

        if ($show_campaign_name) {
            add_action('woocommerce_after_shop_loop_item_title', [$this, 'renderCampaignName'], 15);
        }

        echo '<div class="pwoa-shortcode-wrapper woocommerce">';

        if (function_exists('wc_set_loop_prop')) {
            wc_set_loop_prop('columns', $columns);
        }

        woocommerce_product_loop_start();

        while ($query->have_posts()) {
            $query->the_post();
            wc_get_template_part('content', 'product');
        }

        woocommerce_product_loop_end();
        wp_reset_postdata();

        if ($paginate) {
            $this->renderPagination($query);
        }

        echo '</div>';

        if ($show_campaign_name) {
            remove_action('woocommerce_after_shop_loop_item_title', [$this, 'renderCampaignName'], 15);
        }
    }

    public function renderCampaignName(): void {
        global $product;
        if (!$product) return;

        $campaigns = CampaignRepository::getActive();
        $product_id = $product->get_id();

        foreach ($campaigns as $campaign) {
            $conditions = json_decode($campaign->conditions, true) ?? [];
            $product_ids = $conditions['product_ids'] ?? [];

            if (empty($product_ids) || in_array($product_id, $product_ids)) {
                echo '<span class="pwoa-campaign-label">' . esc_html($campaign->name) . '</span>';
                break;
            }
        }
    }

    private function renderPagination(\WP_Query $query): void {
        $big = 999999999;
        echo '<nav class="pwoa-pagination woocommerce-pagination">';
        echo paginate_links([
            'base'    => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format'  => '?paged=%#%',
            'current' => max(1, get_query_var('paged')),
            'total'   => $query->max_num_pages,
        ]);
        echo '</nav>';
    }
}

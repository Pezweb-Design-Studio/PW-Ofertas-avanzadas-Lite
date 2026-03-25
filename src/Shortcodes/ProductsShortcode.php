<?php
namespace PW\OfertasAvanzadas\Shortcodes;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Repositories\CampaignRepository;

class ProductsShortcode {

    private const DEFAULTS = [
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
        'show_campaign_name' => 'false',
        'paginate'           => 'false',
        'per_page'           => 12,
    ];

    public function __construct() {
        add_shortcode('pwoa_productos_oferta', [$this, 'render']);
    }

    public function render($atts): string {
        if (!function_exists('woocommerce_product_loop_start')) {
            return '';
        }

        $atts = shortcode_atts(self::DEFAULTS, $atts, 'pwoa_productos_oferta');

        $show_campaign_name = filter_var($atts['show_campaign_name'], FILTER_VALIDATE_BOOLEAN);
        $paginate           = filter_var($atts['paginate'], FILTER_VALIDATE_BOOLEAN);
        $query_args         = $this->buildQueryArgs($atts, $paginate);

        ob_start();
        $this->renderProducts($query_args, (int) $atts['columns'], $show_campaign_name, $paginate);
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
            $args['paged'] = max(1, (int) ($_GET['pwoa_page'] ?? 1));
        }

        if ($orderby === 'price') {
            $args['orderby']  = 'meta_value_num';
            $args['meta_key'] = '_price';
        }

        $this->applyVisibilityArgs($args);

        if (!empty($atts['campaign_id'])) {
            $campaign = CampaignRepository::getById((int) $atts['campaign_id']);
            if ($campaign) {
                $conditions = json_decode($campaign->conditions, true) ?? [];
                $this->applyConditionsToQuery($args, $conditions);
            }
        } elseif (!empty($atts['strategy'])) {
            $this->applyStrategyFilter($args, sanitize_key($atts['strategy']));
        } else {
            $this->applyActiveCampaignsFilter($args);
        }

        $this->applyTaxonomyFilter($args, 'product_cat', 'slug', $atts['category']);
        $this->applyTaxonomyFilter($args, 'product_tag', 'slug', $atts['tag']);
        $this->applyPriceMetaQuery($args, $atts['min_price'], $atts['max_price']);

        return $args;
    }

    private function applyTaxonomyFilter(array &$args, string $taxonomy, string $field, string $value): void {
        if (empty($value)) {
            return;
        }

        $args['tax_query'][] = [
            'taxonomy' => $taxonomy,
            'field'    => $field,
            'terms'    => array_map('trim', explode(',', $value)),
        ];
    }

    private function applyPriceMetaQuery(array &$args, $min_price, $max_price): void {
        if (empty($min_price) && empty($max_price)) {
            return;
        }

        $meta_query = ['relation' => 'AND'];

        if (!empty($min_price)) {
            $meta_query[] = ['key' => '_price', 'value' => (float) $min_price, 'type' => 'NUMERIC', 'compare' => '>='];
        }
        if (!empty($max_price)) {
            $meta_query[] = ['key' => '_price', 'value' => (float) $max_price, 'type' => 'NUMERIC', 'compare' => '<='];
        }

        $args['meta_query'] = $meta_query;
    }

    private function applyConditionsToQuery(array &$args, array $conditions): void {
        if (!empty($conditions['product_ids'])) {
            $args['post__in'] = array_map('intval', $conditions['product_ids']);
        }

        if (!empty($conditions['exclude_product_ids'])) {
            $args['post__not_in'] = array_map('intval', $conditions['exclude_product_ids']);
        }

        if (!empty($conditions['category_ids'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => array_map('intval', $conditions['category_ids']),
            ];
        }

        if (!empty($conditions['tag_ids'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'product_tag',
                'field'    => 'term_id',
                'terms'    => array_map('intval', $conditions['tag_ids']),
            ];
        }

        if (!empty($conditions['attribute_slug']) && !empty($conditions['attribute_value'])) {
            $args['tax_query'][] = [
                'taxonomy' => $conditions['attribute_slug'],
                'field'    => 'slug',
                'terms'    => $conditions['attribute_value'],
            ];
        }

        $this->applyPriceMetaQuery($args, $conditions['min_price'] ?? '', $conditions['max_price'] ?? '');
    }

    private function applyActiveCampaignsFilter(array &$args): void {
        $campaigns = CampaignRepository::getActive();

        if (empty($campaigns)) {
            $args['post__in'] = [0];
            return;
        }

        if (count($campaigns) === 1) {
            $conditions = json_decode($campaigns[0]->conditions, true);
            if (is_array($conditions) && !empty($conditions)) {
                $this->applyConditionsToQuery($args, $conditions);
            }
            return;
        }

        $all_ids     = [];
        $unrestricted = false;

        foreach ($campaigns as $campaign) {
            $conditions = json_decode($campaign->conditions, true);
            if (!is_array($conditions) || empty($conditions)) {
                $unrestricted = true;
                break;
            }

            $ids = $this->resolveProductIds($conditions);
            if ($ids === null) {
                $unrestricted = true;
                break;
            }

            $all_ids = array_merge($all_ids, $ids);
        }

        if ($unrestricted) {
            return;
        }

        $all_ids = array_values(array_unique(array_filter($all_ids)));
        $args['post__in'] = $all_ids ?: [0];
    }

    private function applyStrategyFilter(array &$args, string $strategy): void {
        $campaigns = CampaignRepository::getActive();
        $all_ids   = [];

        foreach ($campaigns as $campaign) {
            if ($campaign->strategy !== $strategy) {
                continue;
            }

            $conditions = json_decode($campaign->conditions, true) ?? [];
            $ids = $this->resolveProductIds($conditions);

            if ($ids === null) {
                return;
            }

            $all_ids = array_merge($all_ids, $ids);
        }

        if (!empty($all_ids)) {
            $args['post__in'] = array_values(array_unique($all_ids));
        }
    }

    /**
     * @return int[]|null
     */
    private function resolveProductIds(array $conditions): ?array {
        if (!empty($conditions['product_ids'])) {
            return array_values(array_filter(array_map('intval', $conditions['product_ids'])));
        }

        $has_scope = !empty($conditions['category_ids'])
            || !empty($conditions['tag_ids'])
            || !empty($conditions['attribute_slug'])
            || (isset($conditions['min_price']) && $conditions['min_price'] !== '')
            || (isset($conditions['max_price']) && $conditions['max_price'] !== '');

        if (!$has_scope) {
            return null;
        }

        $sub_args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];

        if (!empty($conditions['exclude_product_ids'])) {
            $sub_args['post__not_in'] = array_map('intval', $conditions['exclude_product_ids']);
        }

        if (!empty($conditions['category_ids'])) {
            $sub_args['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => array_map('intval', $conditions['category_ids']),
            ];
        }

        if (!empty($conditions['tag_ids'])) {
            $sub_args['tax_query'][] = [
                'taxonomy' => 'product_tag',
                'field'    => 'term_id',
                'terms'    => array_map('intval', $conditions['tag_ids']),
            ];
        }

        if (!empty($conditions['attribute_slug']) && !empty($conditions['attribute_value'])) {
            $sub_args['tax_query'][] = [
                'taxonomy' => $conditions['attribute_slug'],
                'field'    => 'slug',
                'terms'    => $conditions['attribute_value'],
            ];
        }

        $min = $conditions['min_price'] ?? '';
        $max = $conditions['max_price'] ?? '';
        $this->applyPriceMetaQuery($sub_args, $min, $max);

        $sub = new \WP_Query($sub_args);
        wp_reset_postdata();

        return array_values(array_filter(array_map('intval', $sub->posts)));
    }

    private function renderProducts(array $query_args, int $columns, bool $show_campaign_name, bool $paginate): void {
        $query = new \WP_Query($query_args);

        if (!$query->have_posts()) {
            echo '<p class="pwoa-no-products">' . esc_html__('No hay productos en oferta disponibles.', 'pw-ofertas-avanzadas') . '</p>';
            return;
        }

        if ($show_campaign_name) {
            add_action('woocommerce_after_shop_loop_item_title', [$this, 'renderCampaignName'], 15);
        }

        echo '<div class="pwoa-shortcode-wrapper woocommerce">';
        wc_set_loop_prop('columns', $columns);
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
        if (!$product) {
            return;
        }

        $product_id = $product->get_id();

        foreach (CampaignRepository::getActive() as $campaign) {
            $conditions  = json_decode($campaign->conditions, true) ?? [];
            $product_ids = $conditions['product_ids'] ?? [];

            if (empty($product_ids) || in_array($product_id, $product_ids)) {
                echo '<span class="pwoa-campaign-label">' . esc_html($campaign->name) . '</span>';
                break;
            }
        }
    }

    private function applyVisibilityArgs(array &$args): void {
        $args['tax_query'][] = [
            'taxonomy' => 'product_visibility',
            'field'    => 'name',
            'terms'    => 'exclude-from-catalog',
            'operator' => 'NOT IN',
        ];

        if ('yes' === get_option('woocommerce_hide_out_of_stock_items')) {
            $args['meta_query'][] = [
                'key'   => '_stock_status',
                'value' => 'instock',
            ];
        }
    }

    private function renderPagination(\WP_Query $query): void {
        $current = max(1, (int) ($_GET['pwoa_page'] ?? 1));
        echo '<nav class="pwoa-pagination woocommerce-pagination">';
        echo paginate_links([
            'base'    => add_query_arg('pwoa_page', '%#%', get_permalink()),
            'format'  => '',
            'current' => $current,
            'total'   => $query->max_num_pages,
        ]);
        echo '</nav>';
    }
}

<?php
namespace PW\OfertasAvanzadas\Services;

defined('ABSPATH') || exit;

class ProductMatcher
{
    public static function matches(\WC_Product $product, array $conditions): bool
    {
        if (empty($conditions)) {
            return true;
        }

        $id = $product->get_id();

        if (!empty($conditions['product_ids']) && !in_array($id, $conditions['product_ids'])) {
            return false;
        }

        if (!empty($conditions['exclude_product_ids']) && in_array($id, $conditions['exclude_product_ids'])) {
            return false;
        }

        if (!empty($conditions['category_ids'])
            && empty(array_intersect($product->get_category_ids(), $conditions['category_ids']))) {
            return false;
        }

        if (!empty($conditions['tag_ids'])) {
            $tags = wp_get_post_terms($id, 'product_tag', ['fields' => 'ids']);
            if (empty(array_intersect($tags, $conditions['tag_ids']))) {
                return false;
            }
        }

        $price = (float) $product->get_price();

        if (isset($conditions['min_price']) && $conditions['min_price'] !== '' && $price < (float) $conditions['min_price']) {
            return false;
        }

        if (isset($conditions['max_price']) && $conditions['max_price'] !== '' && $price > (float) $conditions['max_price']) {
            return false;
        }

        if (isset($conditions['on_sale']) && $conditions['on_sale'] === true && !$product->is_on_sale()) {
            return false;
        }

        return true;
    }

    public static function filterCart(array $cart, array $conditions): array
    {
        if (empty($conditions)) {
            return $cart;
        }

        return array_filter($cart, function ($item) use ($conditions) {
            $product = wc_get_product($item['product_id']);
            return $product && self::matches($product, $conditions);
        });
    }

    public static function countMatchingProducts(array $conditions): int
    {
        if (empty($conditions)) {
            return (int) wp_count_posts('product')->publish;
        }

        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];

        $tax_query = [];

        if (!empty($conditions['attribute_slug']) && !empty($conditions['attribute_value'])) {
            $tax_query[] = [
                'taxonomy' => $conditions['attribute_slug'],
                'field'    => 'slug',
                'terms'    => $conditions['attribute_value'],
            ];
        }

        if (!empty($conditions['category_ids'])) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $conditions['category_ids'],
            ];
        }

        if (!empty($conditions['tag_ids'])) {
            $tax_query[] = [
                'taxonomy' => 'product_tag',
                'field'    => 'term_id',
                'terms'    => $conditions['tag_ids'],
            ];
        }

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        if (!empty($conditions['product_ids'])) {
            $args['post__in'] = $conditions['product_ids'];
        }

        if (!empty($conditions['exclude_product_ids'])) {
            $args['post__not_in'] = $conditions['exclude_product_ids'];
        }

        $meta_query = [];

        if (isset($conditions['min_price']) && $conditions['min_price'] !== '') {
            $meta_query[] = [
                'key'     => '_price',
                'value'   => (float) $conditions['min_price'],
                'type'    => 'NUMERIC',
                'compare' => '>=',
            ];
        }

        if (isset($conditions['max_price']) && $conditions['max_price'] !== '') {
            $meta_query[] = [
                'key'     => '_price',
                'value'   => (float) $conditions['max_price'],
                'type'    => 'NUMERIC',
                'compare' => '<=',
            ];
        }

        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $args['meta_query'] = $meta_query;
        }

        if (isset($conditions['on_sale']) && $conditions['on_sale'] === true) {
            $args['post__in'] = wc_get_product_ids_on_sale();
        }

        return (new \WP_Query($args))->found_posts;
    }

    public static function matchesByAttribute(\WC_Product $product, string $attribute_slug, string $value): bool
    {
        $product_value = $product->get_attribute($attribute_slug);
        return $product_value !== '' && strtolower(trim($product_value)) === strtolower(trim($value));
    }

    public static function filterCartByAttribute(array $cart, string $attribute_slug, string $value): array
    {
        if ($attribute_slug === '' || $value === '') {
            return [];
        }

        return array_filter($cart, function ($item) use ($attribute_slug, $value) {
            $product = wc_get_product($item['product_id']);
            return $product && self::matchesByAttribute($product, $attribute_slug, $value);
        });
    }

    public static function countProductsByAttribute(string $attribute_slug, string $value): int
    {
        if ($attribute_slug === '' || $value === '') {
            return 0;
        }

        return (new \WP_Query([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => [[
                'taxonomy' => $attribute_slug,
                'field'    => 'slug',
                'terms'    => $value,
            ]],
        ]))->found_posts;
    }
}

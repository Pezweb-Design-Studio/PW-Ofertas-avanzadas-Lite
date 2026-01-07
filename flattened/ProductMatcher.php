<?php
namespace PW\OfertasAvanzadas\Services;

class ProductMatcher {

    public static function matches(\WC_Product $product, array $conditions): bool {
        if (empty($conditions)) return true;

        // Product IDs (whitelist)
        if (!empty($conditions['product_ids'])) {
            if (!in_array($product->get_id(), $conditions['product_ids'])) {
                return false;
            }
        }

        // Exclude Product IDs (blacklist)
        if (!empty($conditions['exclude_product_ids'])) {
            if (in_array($product->get_id(), $conditions['exclude_product_ids'])) {
                return false;
            }
        }

        // Categories
        if (!empty($conditions['category_ids'])) {
            $product_cats = $product->get_category_ids();
            if (empty(array_intersect($product_cats, $conditions['category_ids']))) {
                return false;
            }
        }

        // Tags
        if (!empty($conditions['tag_ids'])) {
            $product_tags = wp_get_post_terms($product->get_id(), 'product_tag', ['fields' => 'ids']);
            if (empty(array_intersect($product_tags, $conditions['tag_ids']))) {
                return false;
            }
        }

        // Price range
        $price = (float) $product->get_price();

        if (isset($conditions['min_price']) && $conditions['min_price'] !== '') {
            if ($price < (float) $conditions['min_price']) {
                return false;
            }
        }

        if (isset($conditions['max_price']) && $conditions['max_price'] !== '') {
            if ($price > (float) $conditions['max_price']) {
                return false;
            }
        }

        // On Sale
        if (isset($conditions['on_sale']) && $conditions['on_sale'] === true) {
            if (!$product->is_on_sale()) {
                return false;
            }
        }

        return true;
    }

    public static function filterCart(array $cart, array $conditions): array {
        if (empty($conditions)) return $cart;

        return array_filter($cart, function($item) use ($conditions) {
            $product = wc_get_product($item['product_id']);
            return $product && self::matches($product, $conditions);
        });
    }

    public static function countMatchingProducts(array $conditions): int {
        if (empty($conditions)) {
            return (int) wp_count_posts('product')->publish;
        }

        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ];

        // Attribute filter (PRIMERO, si existe)
        if (!empty($conditions['attribute_slug']) && !empty($conditions['attribute_value'])) {
            if (!isset($args['tax_query'])) {
                $args['tax_query'] = [];
            }
            $args['tax_query'][] = [
                'taxonomy' => $conditions['attribute_slug'],
                'field' => 'slug',
                'terms' => $conditions['attribute_value']
            ];
        }

        // Product IDs filter
        if (!empty($conditions['product_ids'])) {
            $args['post__in'] = $conditions['product_ids'];
        }

        // Exclude Product IDs
        if (!empty($conditions['exclude_product_ids'])) {
            $args['post__not_in'] = $conditions['exclude_product_ids'];
        }

        // Category filter
        if (!empty($conditions['category_ids'])) {
            if (!isset($args['tax_query'])) {
                $args['tax_query'] = [];
            }
            $args['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $conditions['category_ids']
            ];
        }

        // Tag filter
        if (!empty($conditions['tag_ids'])) {
            if (!isset($args['tax_query'])) {
                $args['tax_query'] = [];
            }
            $args['tax_query'][] = [
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $conditions['tag_ids']
            ];
        }

        // Price filter via meta_query
        if (isset($conditions['min_price']) || isset($conditions['max_price'])) {
            $meta_query = ['relation' => 'AND'];

            if (isset($conditions['min_price']) && $conditions['min_price'] !== '') {
                $meta_query[] = [
                    'key' => '_price',
                    'value' => (float) $conditions['min_price'],
                    'type' => 'NUMERIC',
                    'compare' => '>='
                ];
            }

            if (isset($conditions['max_price']) && $conditions['max_price'] !== '') {
                $meta_query[] = [
                    'key' => '_price',
                    'value' => (float) $conditions['max_price'],
                    'type' => 'NUMERIC',
                    'compare' => '<='
                ];
            }

            $args['meta_query'] = $meta_query;
        }

        // On sale filter
        if (isset($conditions['on_sale']) && $conditions['on_sale'] === true) {
            $args['post__in'] = wc_get_product_ids_on_sale();
        }

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    public static function matchesByAttribute(\WC_Product $product, string $attribute_slug, string $value): bool {
        $product_value = $product->get_attribute($attribute_slug);
        return !empty($product_value) && strtolower(trim($product_value)) === strtolower(trim($value));
    }

    public static function filterCartByAttribute(array $cart, string $attribute_slug, string $value): array {
        if (empty($attribute_slug) || empty($value)) return [];

        return array_filter($cart, function($item) use ($attribute_slug, $value) {
            $product = wc_get_product($item['product_id']);
            return $product && self::matchesByAttribute($product, $attribute_slug, $value);
        });
    }

    public static function countProductsByAttribute(string $attribute_slug, string $value): int {
        if (empty($attribute_slug) || empty($value)) return 0;

        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => $attribute_slug,
                    'field' => 'slug',
                    'terms' => $value
                ]
            ]
        ];

        $query = new \WP_Query($args);
        return $query->found_posts;
    }
}
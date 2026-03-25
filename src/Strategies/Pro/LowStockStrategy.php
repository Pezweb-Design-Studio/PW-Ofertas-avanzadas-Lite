<?php
namespace PW\OfertasAvanzadas\Strategies\Pro;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;
use PW\OfertasAvanzadas\Services\ProductMatcher;

class LowStockStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $filtered_cart = ProductMatcher::filterCart($cart, $conditions);
        if (empty($filtered_cart)) return false;

        $stock_threshold = $config['stock_threshold'] ?? 10;

        foreach ($filtered_cart as $item) {
            $product = wc_get_product($item['product_id']);
            if ($product->managing_stock() && $product->get_stock_quantity() <= $stock_threshold) {
                return true;
            }
        }

        return false;
    }

    public function calculate(array $cart, array $config): array {
        $stock_threshold = $config['stock_threshold'] ?? 10;
        $total_discount  = 0;
        $affected_items  = [];

        foreach ($cart as $key => $item) {
            $product = wc_get_product($item['product_id']);
            if (!$product->managing_stock()) continue;
            if ($product->get_stock_quantity() > $stock_threshold) continue;

            $total_discount  += $config['discount_type'] === 'percentage'
                ? $item['line_total'] * ($config['discount_value'] / 100)
                : $config['discount_value'] * $item['quantity'];
            $affected_items[] = $key;
        }

        return [
            'amount' => $total_discount,
            'type'   => $config['discount_type'],
            'items'  => $affected_items,
        ];
    }

    public static function getMeta(): array {
        return [
            'name'          => 'Descuento por Stock Bajo',
            'description'   => 'Aplica descuentos automaticos a productos con pocas unidades disponibles',
            'effectiveness' => 4,
            'when_to_use'   => 'Liquidacion de inventario, cambio de temporada, discontinuacion de productos. Genera urgencia al mostrar escasez.',
            'objective'     => 'liquidation',
        ];
    }

    public static function getConfigFields(): array {
        return [
            ['key' => 'stock_threshold', 'label' => 'Umbral de stock',     'type' => 'number', 'default' => 10, 'required' => true, 'description' => 'Cantidad de unidades o menos para activar descuento'],
            ['key' => 'discount_type',   'label' => 'Tipo de descuento',   'type' => 'select', 'options' => ['percentage' => 'Porcentaje', 'fixed' => 'Monto fijo por unidad'], 'required' => true],
            ['key' => 'discount_value',  'label' => 'Valor del descuento', 'type' => 'number', 'required' => true],
        ];
    }
}

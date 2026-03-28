<?php
namespace PW\OfertasAvanzadas\Strategies\Pro;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Strategies\DiscountStrategy;
use PW\OfertasAvanzadas\Services\ProductMatcher;

class FlashSaleStrategy implements DiscountStrategy {

    public function canApply(array $cart, array $config, array $conditions): bool {
        $now   = current_time('timestamp');
        $start = strtotime($config['start_time'] ?? '');
        $end   = strtotime($config['end_time'] ?? '');

        if ($now < $start || $now > $end) return false;

        return !empty(ProductMatcher::filterCart($cart, $conditions));
    }

    public function calculate(array $cart, array $config): array {
        $total = array_sum(array_column($cart, 'line_total'));

        $amount = $config['discount_type'] === 'percentage'
            ? $total * ($config['discount_value'] / 100)
            : $config['discount_value'];

        return [
            'amount' => $amount,
            'type'   => $config['discount_type'],
            'items'  => array_keys($cart),
        ];
    }

    public static function getMeta(): array {
        return [
            'key'           => 'flash_sale',
            'name'          => __('Flash sale', 'pw-ofertas-avanzadas'),
            'description'   => __('Time-limited discount to create urgency.', 'pw-ofertas-avanzadas'),
            'effectiveness' => 5,
            'when_to_use'   => __('Peak events and launches; often strongest in 6–24 hour windows.', 'pw-ofertas-avanzadas'),
            'objective'     => 'urgency',
        ];
    }

    public static function getConfigFields(): array {
        return [
            ['key' => 'start_time',     'label' => __('Start time', 'pw-ofertas-avanzadas'),      'type' => 'datetime', 'required' => true],
            ['key' => 'end_time',       'label' => __('End time', 'pw-ofertas-avanzadas'),         'type' => 'datetime', 'required' => true],
            ['key' => 'discount_type',  'label' => __('Discount type', 'pw-ofertas-avanzadas'),   'type' => 'select', 'options' => ['percentage' => __('Percentage', 'pw-ofertas-avanzadas'), 'fixed' => __('Fixed amount', 'pw-ofertas-avanzadas')], 'required' => true],
            ['key' => 'discount_value', 'label' => __('Discount value', 'pw-ofertas-avanzadas'), 'type' => 'number',   'required' => true],
        ];
    }
}

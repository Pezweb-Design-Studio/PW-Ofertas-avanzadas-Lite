<?php
namespace PW\OfertasAvanzadas\Services;

use PW\OfertasAvanzadas\Repositories\CampaignRepository;

class DiscountEngine {

    public static function calculateBestDiscount(array $cart): array {
        $campaigns = CampaignRepository::getActive();

        if (empty($campaigns)) {
            return ['amount' => 0];
        }

        $applicable = [];

        foreach ($campaigns as $campaign) {
            $strategy_class = self::getStrategyClass($campaign->strategy);

            if (!class_exists($strategy_class)) continue;

            $strategy = new $strategy_class();
            $config = json_decode($campaign->config, true);
            $conditions = json_decode($campaign->conditions, true) ?? [];

            if (!$strategy->canApply($cart, $config, $conditions)) continue;

            // Workaround: pasar conditions dentro de config para strategies que lo necesiten
            $config['_conditions'] = $conditions;

            $discount = $strategy->calculate($cart, $config);
            $discount['campaign_id'] = $campaign->id;
            $discount['campaign_name'] = $campaign->name;
            $discount['stacking_mode'] = $campaign->stacking_mode;

            $applicable[] = $discount;
        }

        return self::selectDiscount($applicable);
    }

    private static function selectDiscount(array $applicable): array {
        if (empty($applicable)) {
            return ['amount' => 0];
        }

        // PASO 1: Verificar si alguna campaña permite stacking
        $stack_discounts = array_filter($applicable, fn($d) => $d['stacking_mode'] === 'stack');

        if (!empty($stack_discounts)) {
            // Modo STACK: Sumar todos los descuentos que permiten apilamiento
            return self::stackDiscounts($stack_discounts);
        }

        // PASO 2: Modo PRIORITY (mejor descuento)
        // Ordenar por amount descendente, gana el de mayor descuento
        // Si hay empate en amount, se aplica cualquiera (primera del array)
        usort($applicable, fn($a, $b) => $b['amount'] <=> $a['amount']);

        return [
            'amount' => $applicable[0]['amount'],
            'campaign_id' => $applicable[0]['campaign_id'],
            'label' => $applicable[0]['campaign_name']
        ];
    }

    private static function stackDiscounts(array $discounts): array {
        $total = array_sum(array_column($discounts, 'amount'));
        $campaign_names = implode(' + ', array_column($discounts, 'campaign_name'));

        return [
            'amount' => $total,
            'campaign_id' => null, // Multiple campaigns
            'label' => $campaign_names
        ];
    }

    private static function getStrategyClass(string $strategy): string {
        $map = [
            'basic_discount' => 'PW\\OfertasAvanzadas\\Strategies\\Basic\\BasicDiscountStrategy',
            'min_amount' => 'PW\\OfertasAvanzadas\\Strategies\\AOV\\MinAmountStrategy',
            'free_shipping' => 'PW\\OfertasAvanzadas\\Strategies\\AOV\\FreeShippingStrategy',
            'tiered_discount' => 'PW\\OfertasAvanzadas\\Strategies\\AOV\\TieredDiscountStrategy',
            'bulk_discount' => 'PW\\OfertasAvanzadas\\Strategies\\AOV\\BulkDiscountStrategy',
            'buy_x_pay_y' => 'PW\\OfertasAvanzadas\\Strategies\\AOV\\BuyXPayYStrategy',
            'attribute_quantity_discount' => 'PW\\OfertasAvanzadas\\Strategies\\AOV\\AttributeQuantityDiscountStrategy',
            'expiry_based' => 'PW\\OfertasAvanzadas\\Strategies\\Liquidation\\ExpiryBasedStrategy',
            'low_stock' => 'PW\\OfertasAvanzadas\\Strategies\\Liquidation\\LowStockStrategy',
            'recurring_purchase' => 'PW\\OfertasAvanzadas\\Strategies\\Loyalty\\RecurringPurchaseStrategy',
            'flash_sale' => 'PW\\OfertasAvanzadas\\Strategies\\Urgency\\FlashSaleStrategy'
        ];

        return $map[$strategy] ?? '';
    }
}
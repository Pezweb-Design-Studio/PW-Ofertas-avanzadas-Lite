<?php
namespace PW\OfertasAvanzadas\Services;

use PW\OfertasAvanzadas\Repositories\CampaignRepository;

class DiscountEngine
{
    public static function calculateBestDiscount(array $cart): array
    {
        $campaigns = CampaignRepository::getActive();

        if (empty($campaigns)) {
            return ["amount" => 0];
        }

        $applicable = [];

        foreach ($campaigns as $campaign) {
            $strategy_class = self::getStrategyClass($campaign->strategy);

            if (!class_exists($strategy_class)) {
                continue;
            }

            $strategy = new $strategy_class();
            $config = json_decode($campaign->config, true);
            $conditions = json_decode($campaign->conditions, true) ?? [];

            if (!$strategy->canApply($cart, $config, $conditions)) {
                continue;
            }

            // Workaround: pasar conditions dentro de config para strategies que lo necesiten
            $config["_conditions"] = $conditions;
            // Pasar campaign_id para que strategies puedan consultar datos de la campaña
            $config["_campaign_id"] = $campaign->id;

            $discount = $strategy->calculate($cart, $config);
            $discount["campaign_id"] = $campaign->id;
            $discount["campaign_name"] = $campaign->name;
            $discount["stacking_mode"] = $campaign->stacking_mode;

            $applicable[] = $discount;
        }

        return self::selectDiscount($applicable);
    }

    private static function selectDiscount(array $applicable): array
    {
        if (empty($applicable)) {
            return ["amount" => 0];
        }

        $behavior = get_option("pwoa_stacking_behavior", "priority_first");

        $priority_discounts = array_filter(
            $applicable,
            fn($d) => $d["stacking_mode"] === "priority",
        );
        $stack_discounts = array_filter(
            $applicable,
            fn($d) => $d["stacking_mode"] === "stack",
        );

        switch ($behavior) {
            case "priority_first":
                return !empty($priority_discounts)
                    ? self::selectBestPriority($priority_discounts)
                    : self::stackDiscounts($stack_discounts);

            case "stack_first":
                return !empty($stack_discounts)
                    ? self::stackDiscounts($stack_discounts)
                    : self::selectBestPriority($priority_discounts);

            case "max_discount":
                $stacked = !empty($stack_discounts)
                    ? self::stackDiscounts($stack_discounts)
                    : ["amount" => 0];
                $best_priority = !empty($priority_discounts)
                    ? self::selectBestPriority($priority_discounts)
                    : ["amount" => 0];
                return $stacked["amount"] > $best_priority["amount"]
                    ? $stacked
                    : $best_priority;

            default:
                return self::selectBestPriority($priority_discounts) ?:
                    self::stackDiscounts($stack_discounts);
        }
    }

    private static function selectBestPriority(array $priority_discounts): array
    {
        if (empty($priority_discounts)) {
            return ["amount" => 0];
        }

        usort($priority_discounts, fn($a, $b) => $b["amount"] <=> $a["amount"]);

        return [
            "amount" => $priority_discounts[0]["amount"],
            "campaign_id" => $priority_discounts[0]["campaign_id"],
            "label" => $priority_discounts[0]["campaign_name"],
        ];
    }

    private static function stackDiscounts(array $discounts): array
    {
        $total = array_sum(array_column($discounts, "amount"));
        $campaign_names = implode(
            " + ",
            array_column($discounts, "campaign_name"),
        );

        return [
            "amount" => $total,
            "campaign_id" => null, // Multiple campaigns
            "label" => $campaign_names,
        ];
    }

    private static function getStrategyClass(string $strategy): string
    {
        $map = [
            "basic_discount" =>
                "PW\\OfertasAvanzadas\\Strategies\\Lite\\BasicDiscountStrategy",
            "min_amount" =>
                "PW\\OfertasAvanzadas\\Strategies\\Pro\\MinAmountStrategy",
            "free_shipping" =>
                "PW\\OfertasAvanzadas\\Strategies\\Pro\\FreeShippingStrategy",
            "tiered_discount" =>
                "PW\\OfertasAvanzadas\\Strategies\\Pro\\TieredDiscountStrategy",
            "bulk_discount" =>
                "PW\\OfertasAvanzadas\\Strategies\\Lite\\BulkDiscountStrategy",
            "buy_x_pay_y" =>
                "PW\\OfertasAvanzadas\\Strategies\\Lite\\BuyXPayYStrategy",
            "attribute_quantity_discount" =>
                "PW\\OfertasAvanzadas\\Strategies\\Lite\\AttributeQuantityDiscountStrategy",
            "expiry_based" =>
                "PW\\OfertasAvanzadas\\Strategies\\Lite\\ExpiryBasedStrategy",
            "low_stock" =>
                "PW\\OfertasAvanzadas\\Strategies\\Pro\\LowStockStrategy",
            "recurring_purchase" =>
                "PW\\OfertasAvanzadas\\Strategies\\Pro\\RecurringPurchaseStrategy",
            "flash_sale" =>
                "PW\\OfertasAvanzadas\\Strategies\\Pro\\FlashSaleStrategy",
        ];

        return $map[$strategy] ?? "";
    }
}

<?php
// src/Admin/Views/dashboard.lite.php — Lite build: copied to dashboard.php by build-deploy.sh
defined("ABSPATH") || exit();

use PW\OfertasAvanzadas\Repositories\CampaignRepository;
use PW\BackendUI\BackendUI;

/**
 * @var array $campaigns    Página actual de campañas (objetos).
 * @var int   $page         Página actual.
 * @var int   $total        Total de campañas.
 * @var int   $total_pages  Total de páginas.
 */

$strategy_labels = [
    "basic_discount"              => __('Basic', 'pw-ofertas-avanzadas'),
    "min_amount"                  => __('Minimum amount', 'pw-ofertas-avanzadas'),
    "free_shipping"               => __('Free shipping', 'pw-ofertas-avanzadas'),
    "tiered_discount"             => __('Tiered discount', 'pw-ofertas-avanzadas'),
    "bulk_discount"               => __('Volume (bulk)', 'pw-ofertas-avanzadas'),
    "expiry_based"                => __('Expiry based', 'pw-ofertas-avanzadas'),
    "low_stock"                   => __('Low stock', 'pw-ofertas-avanzadas'),
    "recurring_purchase"          => __('Recurring purchase', 'pw-ofertas-avanzadas'),
    "flash_sale"                  => __('Flash sale', 'pw-ofertas-avanzadas'),
    "buy_x_pay_y"                 => __('Buy X pay Y', 'pw-ofertas-avanzadas'),
    "attribute_quantity_discount" => __('By attributes', 'pw-ofertas-avanzadas'),
];

$objective_map = [
    "basic"       => ["label" => __('Basic', 'pw-ofertas-avanzadas'), "variant" => "default"],
    "aov"         => ["label" => __('AOV', 'pw-ofertas-avanzadas'), "variant" => "primary"],
    "liquidation" => ["label" => __('Liquidation', 'pw-ofertas-avanzadas'), "variant" => "warning"],
    "loyalty"     => ["label" => __('Loyalty', 'pw-ofertas-avanzadas'), "variant" => "info"],
    "urgency"     => ["label" => __('Urgency', 'pw-ofertas-avanzadas'), "variant" => "danger"],
];

$bui = BackendUI::init();

$bui->render_page([
    "title"       => __('Discount campaigns', 'pw-ofertas-avanzadas'),
    "description" => __('Manage your marketing strategies.', 'pw-ofertas-avanzadas'),
    "content"     => function ($bui) use (
        $campaigns, $page, $total, $total_pages,
        $strategy_labels, $objective_map
    ): void {
        $ui = $bui->ui();

        ?>
        <div class="flex justify-end items-center mb-5">
            <?php $ui->button([
                "label"   => __('+ New campaign', 'pw-ofertas-avanzadas'),
                "variant" => "primary",
                "href"    => admin_url("admin.php?page=pwoa-new-campaign"),
            ]); ?>
        </div>
        <?php

        if (empty($campaigns)) {
            $ui->card([
                "content" => function () use ($ui): void { ?>
                    <div class="text-center py-12">
                        <p class="text-lg font-semibold text-gray-900 mb-2"><?php esc_html_e('No active campaigns', 'pw-ofertas-avanzadas'); ?></p>
                        <p class="text-sm text-gray-500 mb-6"><?php esc_html_e('Create your first discount campaign to boost sales and optimize inventory.', 'pw-ofertas-avanzadas'); ?></p>
                        <?php $ui->button([
                            "label"   => __('+ Create first campaign', 'pw-ofertas-avanzadas'),
                            "variant" => "primary",
                            "href"    => admin_url("admin.php?page=pwoa-new-campaign"),
                        ]); ?>
                    </div>
                <?php },
            ]);
            return;
        }

        $now          = current_time("timestamp");
        $truly_active = array_filter($campaigns, function ($c) use ($now) {
            if ($c->active != 1) {
                return false;
            }
            if ($c->start_date && strtotime($c->start_date) > $now) {
                return false;
            }
            if ($c->end_date && strtotime($c->end_date) < $now) {
                return false;
            }
            return true;
        });
        $scheduled   = array_filter($campaigns, fn($c) => $c->start_date && strtotime($c->start_date) > $now);
        $expired     = array_filter($campaigns, fn($c) => $c->end_date && strtotime($c->end_date) < $now);
        $expired_ids = array_column($expired, "id");
        $paused      = array_filter($campaigns, fn($c) => $c->active == 0 && !in_array($c->id, $expired_ids));

        $stat_items = [
            ["label" => __('Total', 'pw-ofertas-avanzadas'),       "value" => number_format($total),  "class" => "text-gray-900"],
            ["label" => __('Active', 'pw-ofertas-avanzadas'),     "value" => count($truly_active),   "class" => "text-green-600"],
            ["label" => __('Paused', 'pw-ofertas-avanzadas'),    "value" => count($paused),         "class" => "text-gray-400"],
            ["label" => __('Scheduled', 'pw-ofertas-avanzadas'), "value" => count($scheduled),      "class" => "text-blue-600"],
        ]; ?>

        <div class="grid grid-cols-4 gap-3 mb-5">
            <?php foreach ($stat_items as $s):
                $ui->card([
                    "content" => function () use ($s): void { ?>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2"><?php echo esc_html($s["label"]); ?></div>
                        <div class="text-3xl font-extrabold leading-none <?php echo esc_attr($s["class"]); ?>"><?php echo esc_html($s["value"]); ?></div>
                    <?php },
                ]);
            endforeach; ?>
        </div>

        <?php
        $ui->card([
            "content" => function () use ($ui, $campaigns, $strategy_labels, $objective_map, $page, $total, $total_pages): void { ?>

                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide pb-3"><?php esc_html_e('Campaign', 'pw-ofertas-avanzadas'); ?></th>
                            <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide pb-3"><?php esc_html_e('Objective', 'pw-ofertas-avanzadas'); ?></th>
                            <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wide pb-3"><?php esc_html_e('Period', 'pw-ofertas-avanzadas'); ?></th>
                            <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wide pb-3"><?php esc_html_e('Status', 'pw-ofertas-avanzadas'); ?></th>
                            <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wide pb-3"><?php esc_html_e('Actions', 'pw-ofertas-avanzadas'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($campaigns as $campaign):
                            $obj          = $objective_map[$campaign->objective] ?? ["label" => __('N/A', 'pw-ofertas-avanzadas'), "variant" => "default"];
                            $is_scheduled = $campaign->start_date && strtotime($campaign->start_date) > current_time("timestamp");
                            $is_expired   = $campaign->end_date && strtotime($campaign->end_date) < current_time("timestamp");
                        ?>
                        <tr>

                            <td class="py-3 pr-4">
                                <div class="font-semibold text-gray-900 text-sm"><?php echo esc_html($campaign->name); ?></div>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="text-xs text-gray-500"><?php echo esc_html($strategy_labels[$campaign->strategy] ?? $campaign->strategy); ?></span>
                                    <?php if ($campaign->discount_type !== "free_shipping"): ?>
                                        <span class="text-xs bg-gray-100 border border-gray-200 rounded px-1"><?php echo $campaign->discount_type === "percentage" ? "%" : "$"; ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($campaign->strategy === "bulk_discount"):
                                    $config     = json_decode($campaign->config, true);
                                    $units_sold = $campaign->units_sold ? json_decode($campaign->units_sold, true) : [];
                                    $bulk_items = $config["bulk_items"] ?? [];
                                    if (!empty($bulk_items)):
                                        $total_sold = 0;
                                        $total_max  = 0;
                                        foreach ($bulk_items as $item) {
                                            $total_sold += intval($units_sold[$item["product_id"] ?? 0] ?? 0);
                                            $total_max  += intval($item["max_quantity"] ?? 0);
                                        }
                                        $pct     = $total_max > 0 ? min(100, ($total_sold / $total_max) * 100) : 0;
                                        $variant = $pct >= 80 ? "danger" : ($pct >= 50 ? "warning" : "success");
                                ?>
                                        <div class="mt-1.5 text-xs text-gray-500"><?php echo esc_html(sprintf(
                                            /* translators: 1: units sold, 2: max units */
                                            __('%1$s / %2$s units', 'pw-ofertas-avanzadas'),
                                            number_format($total_sold),
                                            number_format($total_max)
                                        )); ?></div>
                                        <div class="mt-1">
                                            <?php $ui->progress_bar(["value" => (int) $pct, "variant" => $variant, "size" => "sm", "show_value" => true]); ?>
                                        </div>
                                <?php   endif;
                                endif; ?>
                            </td>

                            <td class="py-3 pr-4">
                                <?php $ui->badge(["label" => $obj["label"], "variant" => $obj["variant"]]); ?>
                            </td>

                            <td class="py-3 pr-4 text-center text-xs">
                                <?php if ($campaign->start_date || $campaign->end_date):
                                    $start = $campaign->start_date ? date_i18n(get_option("date_format"), strtotime($campaign->start_date)) : "—";
                                    $end   = $campaign->end_date ? date_i18n(get_option("date_format"), strtotime($campaign->end_date)) : "—";
                                ?>
                                    <div class="text-gray-700"><?php echo esc_html($start); ?></div>
                                    <div class="text-gray-400"><?php esc_html_e('through', 'pw-ofertas-avanzadas'); ?></div>
                                    <div class="text-gray-700"><?php echo esc_html($end); ?></div>
                                <?php else: ?>
                                    <span class="text-gray-400"><?php esc_html_e('Ongoing', 'pw-ofertas-avanzadas'); ?></span>
                                <?php endif; ?>
                            </td>

                            <td class="py-3 pr-4 text-center">
                                <?php if ($is_expired): ?>
                                    <?php $ui->badge(["label" => __('Expired', 'pw-ofertas-avanzadas'),   "variant" => "default"]); ?>
                                <?php elseif ($is_scheduled): ?>
                                    <?php $ui->badge(["label" => __('Scheduled', 'pw-ofertas-avanzadas'), "variant" => "primary"]); ?>
                                <?php else: ?>
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" class="sr-only toggle-campaign"
                                            data-campaign-id="<?php echo esc_attr($campaign->id); ?>"
                                            data-campaign-name="<?php echo esc_attr($campaign->name); ?>"
                                            <?php echo $campaign->active ? 'checked' : ''; ?>>
                                        <span class="pwoa-toggle-track relative inline-block w-9 h-5 rounded-full transition-colors duration-200 <?php echo $campaign->active ? 'bg-green-500' : 'bg-gray-300'; ?>">
                                            <span class="absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-all duration-200 <?php echo $campaign->active ? 'right-0.5' : 'left-0.5'; ?>"></span>
                                        </span>
                                        <span class="pwoa-toggle-label text-xs <?php echo $campaign->active ? 'text-green-600' : 'text-gray-400'; ?>">
                                            <?php echo $campaign->active ? esc_html__('Active', 'pw-ofertas-avanzadas') : esc_html__('Paused', 'pw-ofertas-avanzadas'); ?>
                                        </span>
                                    </label>
                                <?php endif; ?>
                            </td>

                            <td class="py-3 text-right whitespace-nowrap">
                                <?php if ($campaign->strategy === "bulk_discount"): ?>
                                    <button class="btn-reset bg-transparent border-0 cursor-pointer text-green-600 hover:text-green-800 p-1 rounded hover:bg-green-50 transition-colors"
                                        data-campaign-id="<?php echo esc_attr($campaign->id); ?>"
                                        data-campaign-name="<?php echo esc_attr($campaign->name); ?>"
                                        title="<?php echo esc_attr(__('Reset counter', 'pw-ofertas-avanzadas')); ?>">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </button>
                                <?php endif; ?>
                                <button class="btn-edit bg-transparent border-0 cursor-pointer text-blue-600 hover:text-blue-800 p-1 rounded hover:bg-blue-50 transition-colors"
                                    data-campaign-id="<?php echo esc_attr($campaign->id); ?>"
                                    title="<?php echo esc_attr(__('Edit campaign', 'pw-ofertas-avanzadas')); ?>">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button class="btn-delete bg-transparent border-0 cursor-pointer text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50 transition-colors"
                                    data-campaign-id="<?php echo esc_attr($campaign->id); ?>"
                                    data-campaign-name="<?php echo esc_attr($campaign->name); ?>"
                                    data-has-stats="<?php echo CampaignRepository::hasStats($campaign->id) ? '1' : '0'; ?>"
                                    title="<?php echo esc_attr(__('Delete campaign', 'pw-ofertas-avanzadas')); ?>">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>

                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                    <div class="mt-4">
                        <?php $ui->pagination([
                            "current"  => $page,
                            "total"    => $total_pages,
                            "base_url" => admin_url("admin.php?page=pwoa-dashboard"),
                            "param"    => "paged",
                            "window"   => 2,
                        ]); ?>
                    </div>
                <?php endif; ?>

                <div class="mt-2 text-xs text-gray-500"><?php
                    echo esc_html(sprintf(
                        /* translators: %s: number of campaigns */
                        _n('%s campaign in total', '%s campaigns in total', (int) $total, 'pw-ofertas-avanzadas'),
                        number_format_i18n($total)
                    ));
                ?></div>

            <?php },
        ]);

    },
]);

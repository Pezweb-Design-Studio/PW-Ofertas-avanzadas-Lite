<?php
// src/Admin/Views/analytics.php
defined("ABSPATH") || exit();

use PW\BackendUI\BackendUI;

/**
 * @var object $stats  StatsRepository::getSummary() — {total_orders, total_discounted, avg_discount, total_revenue}
 */

$bui = BackendUI::init();

$bui->render_page([
    "title"       => "Analíticas",
    "description" => "Resumen de rendimiento de tus campañas de descuentos.",
    "content"     => function ($bui) use ($stats): void {
        $ui = $bui->ui();

        // ── KPI cards ──────────────────────────────────────────────────────────
        $kpis = [
            ["label" => "Órdenes con Descuento", "value" => number_format($stats["total_orders"]),      "variant" => null],
            ["label" => "Total Descontado",       "value" => wc_price($stats["total_discounted"]),      "variant" => "success"],
            ["label" => "Descuento Promedio",     "value" => wc_price($stats["avg_discount"]),          "variant" => null],
            ["label" => "Ingresos Totales",       "value" => wc_price($stats["total_revenue"]),         "variant" => "info"],
        ];

        $variant_color = [
            "success" => "var(--pw-color-success-fg)",
            "warning" => "var(--pw-color-warning-fg)",
            "danger"  => "var(--pw-color-danger-fg)",
            "info"    => "var(--pw-color-info-fg)",
        ];

        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:24px;">';
        foreach ($kpis as $kpi) {
            $color = $kpi["variant"]
                ? ($variant_color[$kpi["variant"]] ?? "var(--pw-color-fg-default)")
                : "var(--pw-color-fg-default)";

            $ui->card([
                "content" => function () use ($kpi, $color): void {
                    echo '<div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;'
                        . 'color:var(--pw-color-fg-muted);margin-bottom:10px;">'
                        . esc_html($kpi["label"]) . '</div>';
                    echo '<div style="font-size:28px;font-weight:800;line-height:1;color:' . esc_attr($color) . ';">'
                        . $kpi["value"] . '</div>';
                },
            ]);
        }
        echo '</div>';

        // ── Top Campaigns ──────────────────────────────────────────────────────
        $top_campaigns = \PW\OfertasAvanzadas\Repositories\StatsRepository::getTopCampaigns();

        $ui->card([
            "title"   => "Top Campañas",
            "content" => function () use ($ui, $top_campaigns): void {
                if (empty($top_campaigns)) {
                    $ui->notice(["type" => "info", "message" => "Sin datos de campañas aún."]);
                    return;
                }

                echo '<table class="wp-list-table widefat" style="width:100%;">';
                echo '<thead><tr>';
                echo '<th>Campaña</th>';
                echo '<th style="text-align:right;">Usos</th>';
                echo '<th style="text-align:right;">Total Descontado</th>';
                echo '</tr></thead><tbody>';

                foreach ($top_campaigns as $campaign) {
                    echo '<tr>';
                    echo '<td><strong>' . esc_html($campaign->name) . '</strong></td>';
                    echo '<td style="text-align:right;">' . number_format($campaign->uses) . '</td>';
                    echo '<td style="text-align:right;font-weight:700;">' . wc_price($campaign->total_discounted) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';
            },
        ]);
    },
]);

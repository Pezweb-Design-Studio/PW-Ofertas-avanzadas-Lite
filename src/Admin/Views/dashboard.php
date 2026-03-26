<?php
// src/Admin/Views/dashboard.php
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
    "basic_discount"              => "Básico",
    "min_amount"                  => "Monto Mínimo",
    "free_shipping"               => "Envío Gratis",
    "tiered_discount"             => "Descuento Escalonado",
    "bulk_discount"               => "Volumen (Bulk)",
    "expiry_based"                => "Por Vencimiento",
    "low_stock"                   => "Stock Bajo",
    "recurring_purchase"          => "Compra Recurrente",
    "flash_sale"                  => "Flash Sale",
    "buy_x_pay_y"                 => "Lleva X Paga Y",
    "attribute_quantity_discount" => "Por Atributos",
];

$objective_map = [
    "basic"       => ["label" => "Básico",      "variant" => "default"],
    "aov"         => ["label" => "AOV",          "variant" => "primary"],
    "liquidation" => ["label" => "Liquidación",  "variant" => "warning"],
    "loyalty"     => ["label" => "Fidelización", "variant" => "info"],
    "urgency"     => ["label" => "Urgencia",     "variant" => "danger"],
];

$behavior        = get_option("pwoa_stacking_behavior", "priority_first");
$behavior_labels = [
    "priority_first" => "Prioridad primero",
    "stack_first"    => "Solo apilables",
    "max_discount"   => "Mejor descuento",
];
$behavior_variant = [
    "priority_first" => "success",
    "stack_first"    => "primary",
    "max_discount"   => "info",
];

$bui = BackendUI::init();

$bui->render_page([
    "title"       => "Campañas de Descuentos",
    "description" => "Gestiona tus estrategias de marketing.",
    "content"     => function ($bui) use (
        $campaigns, $page, $total, $total_pages,
        $strategy_labels, $objective_map,
        $behavior, $behavior_labels, $behavior_variant
    ): void {
        $ui = $bui->ui();

        // ── Action bar ────────────────────────────────────────────────────────
        echo '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">';

        echo '<div style="display:flex;align-items:center;gap:10px;">';
        $ui->badge([
            "label"   => "Modo: " . ($behavior_labels[$behavior] ?? $behavior),
            "variant" => $behavior_variant[$behavior] ?? "default",
        ]);
        echo '<button id="help-button" type="button"
            style="width:28px;height:28px;border-radius:50%;border:1px solid var(--pw-color-border-default);
                   background:var(--pw-color-bg-subtle);color:var(--pw-color-fg-muted);
                   font-weight:700;cursor:pointer;font-size:13px;"
            title="Ayuda">?</button>';
        echo '</div>';

        $ui->button([
            "label"   => "+ Nueva Campaña",
            "variant" => "primary",
            "href"    => admin_url("admin.php?page=pwoa-new-campaign"),
        ]);

        echo '</div>';

        if (empty($campaigns)) {
            // ── Empty state ──────────────────────────────────────────────────
            $ui->card([
                "content" => function () use ($ui): void {
                    echo '<div style="text-align:center;padding:48px 0;">';
                    echo '<p style="font-size:18px;font-weight:600;color:var(--pw-color-fg-default);margin:0 0 8px;">No hay campañas activas</p>';
                    echo '<p style="font-size:13px;color:var(--pw-color-fg-muted);margin:0 0 24px;">Crea tu primera campaña de descuentos para aumentar ventas y optimizar tu inventario</p>';
                    $ui->button([
                        "label"   => "+ Crear Primera Campaña",
                        "variant" => "primary",
                        "href"    => admin_url("admin.php?page=pwoa-new-campaign"),
                    ]);
                    echo '</div>';
                },
            ]);
            return;
        }

        // ── Stats ─────────────────────────────────────────────────────────────
        $now         = current_time("timestamp");
        $truly_active = array_filter($campaigns, function ($c) use ($now) {
            if ($c->active != 1) return false;
            if ($c->start_date && strtotime($c->start_date) > $now) return false;
            if ($c->end_date   && strtotime($c->end_date)   < $now) return false;
            return true;
        });
        $scheduled   = array_filter($campaigns, fn($c) => $c->start_date && strtotime($c->start_date) > $now);
        $expired     = array_filter($campaigns, fn($c) => $c->end_date   && strtotime($c->end_date)   < $now);
        $expired_ids = array_column($expired, "id");
        $paused      = array_filter($campaigns, fn($c) => $c->active == 0 && !in_array($c->id, $expired_ids));

        $stat_items = [
            ["label" => "Total",      "value" => number_format($total),     "color" => "var(--pw-color-fg-default)"],
            ["label" => "Activas",    "value" => count($truly_active),      "color" => "var(--pw-color-success-fg)"],
            ["label" => "Pausadas",   "value" => count($paused),            "color" => "var(--pw-color-fg-muted)"],
            ["label" => "Programadas","value" => count($scheduled),         "color" => "var(--pw-color-info-fg)"],
        ];

        echo '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;">';
        foreach ($stat_items as $s) {
            $ui->card([
                "content" => function () use ($s): void {
                    echo '<div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;'
                        . 'color:var(--pw-color-fg-muted);margin-bottom:8px;">' . esc_html($s["label"]) . '</div>';
                    echo '<div style="font-size:26px;font-weight:800;line-height:1;color:' . esc_attr($s["color"]) . ';">'
                        . esc_html($s["value"]) . '</div>';
                },
            ]);
        }
        echo '</div>';

        // ── Campaigns table ───────────────────────────────────────────────────
        $ui->card([
            "content" => function () use ($ui, $campaigns, $strategy_labels, $objective_map, $page, $total, $total_pages): void {

                echo '<table class="wp-list-table widefat" style="width:100%;">';
                echo '<thead><tr>';
                echo '<th>Campaña</th>';
                echo '<th>Objetivo</th>';
                echo '<th style="text-align:center;">Periodo</th>';
                echo '<th style="text-align:center;">Estado</th>';
                echo '<th style="text-align:right;">Acciones</th>';
                echo '</tr></thead><tbody>';

                foreach ($campaigns as $campaign) {
                    $obj          = $objective_map[$campaign->objective] ?? ["label" => "N/A", "variant" => "default"];
                    $is_scheduled = $campaign->start_date && strtotime($campaign->start_date) > current_time("timestamp");
                    $is_expired   = $campaign->end_date   && strtotime($campaign->end_date)   < current_time("timestamp");

                    echo '<tr>';

                    // ── Name / Strategy ──────────────────────────────────────
                    echo '<td>';
                    echo '<strong style="color:var(--pw-color-fg-default);">' . esc_html($campaign->name) . '</strong><br>';
                    echo '<span style="font-size:12px;color:var(--pw-color-fg-muted);">'
                        . esc_html($strategy_labels[$campaign->strategy] ?? $campaign->strategy);
                    if ($campaign->discount_type !== "free_shipping") {
                        echo ' &nbsp;<span style="font-size:11px;background:var(--pw-color-bg-subtle);'
                            . 'border:1px solid var(--pw-color-border-muted);border-radius:3px;padding:1px 4px;">'
                            . ($campaign->discount_type === "percentage" ? "%" : "$")
                            . '</span>';
                    }
                    echo '</span>';

                    // Bulk discount progress
                    if ($campaign->strategy === "bulk_discount") {
                        $config     = json_decode($campaign->config, true);
                        $units_sold = $campaign->units_sold ? json_decode($campaign->units_sold, true) : [];
                        $bulk_items = $config["bulk_items"] ?? [];
                        if (!empty($bulk_items)) {
                            $total_sold = 0; $total_max = 0;
                            foreach ($bulk_items as $item) {
                                $total_sold += intval($units_sold[$item["product_id"] ?? 0] ?? 0);
                                $total_max  += intval($item["max_quantity"] ?? 0);
                            }
                            $pct     = $total_max > 0 ? min(100, ($total_sold / $total_max) * 100) : 0;
                            $variant = $pct >= 80 ? "danger" : ($pct >= 50 ? "warning" : "success");
                            echo '<div style="margin-top:6px;font-size:11px;color:var(--pw-color-fg-muted);">'
                                . number_format($total_sold) . ' / ' . number_format($total_max) . ' unidades</div>';
                            echo '<div style="margin-top:4px;">';
                            $ui->progress_bar(["value" => (int) $pct, "variant" => $variant, "size" => "sm", "show_value" => true]);
                            echo '</div>';
                        }
                    }
                    echo '</td>';

                    // ── Objective badge ──────────────────────────────────────
                    echo '<td>';
                    $ui->badge(["label" => $obj["label"], "variant" => $obj["variant"]]);
                    echo '</td>';

                    // ── Period ───────────────────────────────────────────────
                    echo '<td style="text-align:center;font-size:12px;">';
                    if ($campaign->start_date || $campaign->end_date) {
                        $start = $campaign->start_date ? date_i18n(get_option("date_format"), strtotime($campaign->start_date)) : "—";
                        $end   = $campaign->end_date   ? date_i18n(get_option("date_format"), strtotime($campaign->end_date))   : "—";
                        echo esc_html($start) . '<br>'
                            . '<span style="font-size:11px;color:var(--pw-color-fg-muted);">hasta</span><br>'
                            . esc_html($end);
                    } else {
                        echo '<span style="color:var(--pw-color-fg-muted);">Permanente</span>';
                    }
                    echo '</td>';

                    // ── Status ───────────────────────────────────────────────
                    echo '<td style="text-align:center;">';
                    if ($is_expired) {
                        $ui->badge(["label" => "Expirada",   "variant" => "default"]);
                    } elseif ($is_scheduled) {
                        $ui->badge(["label" => "Programada", "variant" => "primary"]);
                    } else {
                        echo '<label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">';
                        echo '<input type="checkbox" class="toggle-campaign"
                            data-campaign-id="'   . esc_attr($campaign->id)   . '"
                            data-campaign-name="' . esc_attr($campaign->name) . '"
                            style="width:0;height:0;opacity:0;position:absolute;"'
                            . ($campaign->active ? ' checked' : '') . '>';
                        $active_style = $campaign->active
                            ? "background:var(--pw-color-success-emphasis);"
                            : "background:var(--pw-color-border-default);";
                        echo '<span class="pwoa-toggle-track" style="display:inline-block;width:36px;height:20px;border-radius:10px;' . $active_style . 'position:relative;transition:background .2s;">'
                            . '<span style="position:absolute;top:2px;' . ($campaign->active ? 'right:2px;' : 'left:2px;')
                            . 'width:16px;height:16px;border-radius:50%;background:#fff;transition:all .2s;"></span>'
                            . '</span>';
                        echo '<span class="pwoa-toggle-label" style="font-size:12px;color:' . ($campaign->active ? 'var(--pw-color-success-fg)' : 'var(--pw-color-fg-muted)') . ';">'
                            . ($campaign->active ? "Activa" : "Pausada") . '</span>';
                        echo '</label>';
                    }
                    echo '</td>';

                    // ── Actions ───────────────────────────────────────────────
                    echo '<td style="text-align:right;white-space:nowrap;">';
                    if ($campaign->strategy === "bulk_discount") {
                        echo '<button class="btn-reset" type="button"
                            data-campaign-id="'   . esc_attr($campaign->id)   . '"
                            data-campaign-name="' . esc_attr($campaign->name) . '"
                            title="Resetear contador"
                            style="background:none;border:none;cursor:pointer;color:var(--pw-color-success-fg);padding:4px 6px;">'
                            . '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>'
                            . '</button>';
                    }
                    echo '<button class="btn-edit" type="button"
                        data-campaign-id="' . esc_attr($campaign->id) . '"
                        title="Editar campaña"
                        style="background:none;border:none;cursor:pointer;color:var(--pw-color-accent-fg);padding:4px 6px;">'
                        . '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                        . '</button>';
                    echo '<button class="btn-delete" type="button"
                        data-campaign-id="'   . esc_attr($campaign->id)   . '"
                        data-campaign-name="' . esc_attr($campaign->name) . '"
                        data-has-stats="'     . (CampaignRepository::hasStats($campaign->id) ? "1" : "0") . '"
                        title="Eliminar campaña"
                        style="background:none;border:none;cursor:pointer;color:var(--pw-color-danger-fg);padding:4px 6px;">'
                        . '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                        . '</button>';
                    echo '</td>';

                    echo '</tr>';
                }

                echo '</tbody></table>';

                // Pagination
                if ($total_pages > 1) {
                    echo '<div style="margin-top:16px;">';
                    $ui->pagination([
                        "current"  => $page,
                        "total"    => $total_pages,
                        "base_url" => admin_url("admin.php?page=pwoa-dashboard"),
                        "param"    => "paged",
                        "window"   => 2,
                    ]);
                    echo '</div>';
                }

                echo '<div style="margin-top:8px;font-size:12px;color:var(--pw-color-fg-muted);">'
                    . number_format($total) . ' campaña' . ($total !== 1 ? 's' : '') . ' en total</div>';
            },
        ]);
    },
]);
?>

<script>
document.querySelectorAll('.toggle-campaign').forEach(function (input) {
    input.addEventListener('change', async function () {
        const campaignId = this.dataset.campaignId;
        const active     = this.checked ? 1 : 0;
        const label      = this.parentElement.querySelector('.pwoa-toggle-label');
        const track      = this.parentElement.querySelector('.pwoa-toggle-track');
        const dot        = track ? track.querySelector('span') : null;

        if (label) {
            label.textContent = active ? 'Activa' : 'Pausada';
            label.style.color = active ? 'var(--pw-color-success-fg)' : 'var(--pw-color-fg-muted)';
        }
        if (track) track.style.background = active ? 'var(--pw-color-success-emphasis)' : 'var(--pw-color-border-default)';
        if (dot)   dot.style[active ? 'right' : 'left'] = '2px', dot.style[active ? 'left' : 'right'] = 'auto';

        try {
            const res  = await fetch(ajaxurl, { method: 'POST', body: new URLSearchParams({ action: 'pwoa_toggle_campaign', campaign_id: campaignId, active: active, nonce: '<?php echo wp_create_nonce("pwoa_nonce"); ?>' }) });
            const data = await res.json();
            if (!data.success) throw new Error(data.data || 'Error desconocido');
        } catch (err) {
            this.checked = !this.checked;
            alert('Error al actualizar campaña: ' + err.message);
        }
    });
});

document.querySelectorAll('.btn-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
        window.location.href = '<?php echo admin_url("admin.php?page=pwoa-new-campaign"); ?>&edit=' + this.dataset.campaignId;
    });
});

document.querySelectorAll('.btn-delete').forEach(function (btn) {
    btn.addEventListener('click', async function () {
        const campaignId = this.dataset.campaignId;
        let msg = '¿Estás seguro de eliminar la campaña "' + this.dataset.campaignName + '"?';
        if (this.dataset.hasStats === '1') msg += '\n\n⚠️ Esta campaña tiene estadísticas asociadas.';
        if (!confirm(msg)) return;

        this.disabled = true; this.style.opacity = '0.4';
        try {
            const res  = await fetch(ajaxurl, { method: 'POST', body: new URLSearchParams({ action: 'pwoa_delete_campaign', campaign_id: campaignId, nonce: '<?php echo wp_create_nonce("pwoa_nonce"); ?>' }) });
            const data = await res.json();
            if (!data.success) throw new Error(data.data || 'Error al eliminar');
            const row = this.closest('tr');
            if (row) { row.style.opacity = '0'; row.style.transition = 'opacity .3s'; }
            setTimeout(() => window.location.reload(), 300);
        } catch (err) {
            this.disabled = false; this.style.opacity = '1';
            alert('Error: ' + err.message);
        }
    });
});

document.querySelectorAll('.btn-reset').forEach(function (btn) {
    btn.addEventListener('click', async function () {
        if (!confirm('¿Resetear contador de unidades vendidas de "' + this.dataset.campaignName + '"?\n\nEsto pondrá en 0 el contador.')) return;
        this.disabled = true; this.style.opacity = '0.4';
        try {
            const res  = await fetch(ajaxurl, { method: 'POST', body: new URLSearchParams({ action: 'pwoa_reset_units_sold', campaign_id: this.dataset.campaignId, nonce: '<?php echo wp_create_nonce("pwoa_nonce"); ?>' }) });
            const data = await res.json();
            if (!data.success) throw new Error(data.data || 'Error');
            alert('✓ ' + data.data.message);
            window.location.reload();
        } catch (err) {
            this.disabled = false; this.style.opacity = '1';
            alert('Error: ' + err.message);
        }
    });
});

(function () {
    var helpBtn = document.getElementById('help-button');
    var modal   = document.getElementById('help-modal');
    if (!helpBtn || !modal) return;
    helpBtn.addEventListener('click', function () { modal.style.display = 'flex'; });
    document.getElementById('close-help')?.addEventListener('click',     function () { modal.style.display = 'none'; });
    document.getElementById('close-help-btn')?.addEventListener('click', function () { modal.style.display = 'none'; });
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.style.display = 'none'; });
    document.querySelectorAll('.help-accordion-header').forEach(function (h) {
        h.addEventListener('click', function () {
            var content = h.nextElementSibling;
            var icon    = h.querySelector('.accordion-icon');
            content.style.display = content.style.display === 'none' ? 'block' : 'none';
            if (icon) icon.textContent = content.style.display === 'none' ? '▶' : '▼';
        });
    });
}());
</script>

<!-- Modal de Ayuda -->
<div id="help-modal" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.6);align-items:center;justify-content:center;">
    <div style="background:var(--pw-color-bg-canvas);border:1px solid var(--pw-color-border-default);border-radius:6px;padding:0;width:680px;max-width:90vw;max-height:85vh;display:flex;flex-direction:column;">
        <div style="padding:20px 24px;border-bottom:1px solid var(--pw-color-border-muted);display:flex;justify-content:space-between;align-items:center;">
            <strong style="font-size:16px;color:var(--pw-color-fg-default);">Ayuda — Modo de Descuentos</strong>
            <button id="close-help" type="button" style="background:none;border:none;cursor:pointer;font-size:22px;color:var(--pw-color-fg-muted);">&times;</button>
        </div>
        <div style="padding:20px 24px;overflow-y:auto;flex:1;display:flex;flex-direction:column;gap:10px;">
            <?php
            $help_items = [
                [
                    "title"   => "¿Qué es \"Prioritario\" vs \"Apilable\"?",
                    "content" => "<p><strong>Modo Prioritario:</strong> Solo se aplica una campaña prioritaria — la de mayor descuento.<br><em>Ejemplo:</em> Dos campañas (5% y 10%), solo aplica el 10%.</p><p><strong>Modo Apilable:</strong> Las campañas apilables se suman entre sí.<br><em>Ejemplo:</em> 5% + 3% = 8%.</p>",
                ],
                [
                    "title"   => "¿Cómo funcionan con múltiples campañas?",
                    "content" => sprintf(
                        "<p>Comportamiento activo: <strong>%s</strong></p>",
                        esc_html($behavior_labels[$behavior] ?? $behavior)
                    ) . "<p><a href='" . admin_url("admin.php?page=pwoa-settings") . "'>→ Ir a Ajustes</a></p>",
                ],
            ];
            foreach ($help_items as $item): ?>
                <div style="border:1px solid var(--pw-color-border-muted);border-radius:4px;overflow:hidden;">
                    <button type="button" class="help-accordion-header"
                        style="width:100%;display:flex;justify-content:space-between;padding:14px 16px;background:var(--pw-color-bg-subtle);border:none;cursor:pointer;text-align:left;">
                        <strong style="font-size:13px;color:var(--pw-color-fg-default);"><?php echo esc_html($item["title"]); ?></strong>
                        <span class="accordion-icon" style="color:var(--pw-color-fg-muted);">▶</span>
                    </button>
                    <div style="display:none;padding:16px;background:var(--pw-color-bg-canvas);font-size:13px;color:var(--pw-color-fg-muted);">
                        <?php echo $item["content"]; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="padding:16px 24px;border-top:1px solid var(--pw-color-border-muted);text-align:right;">
            <button id="close-help-btn" type="button"
                style="padding:8px 18px;background:var(--pw-color-bg-subtle);border:1px solid var(--pw-color-border-default);border-radius:4px;cursor:pointer;font-size:13px;color:var(--pw-color-fg-default);">
                Cerrar
            </button>
        </div>
    </div>
</div>

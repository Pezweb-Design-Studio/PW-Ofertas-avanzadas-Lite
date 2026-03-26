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

require __DIR__ . '/data/stacking-options.php';

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
        $behavior, $behavior_labels, $behavior_variant, $stacking_options
    ): void {
        $ui = $bui->ui();

        // ── Action bar ────────────────────────────────────────────────────────
        ?>
        <div class="flex justify-between items-center mb-5">

            <div class="flex items-center gap-2.5">
                <?php $ui->badge([
                    "label"   => "Modo: " . ($behavior_labels[$behavior] ?? $behavior),
                    "variant" => $behavior_variant[$behavior] ?? "default",
                ]); ?>
                <button id="help-button" type="button"
                    class="w-7 h-7 rounded-full border border-gray-200 bg-gray-100 text-gray-500 font-bold cursor-pointer text-sm"
                    title="Ayuda">?</button>
            </div>

            <?php $ui->button([
                "label"   => "+ Nueva Campaña",
                "variant" => "primary",
                "href"    => admin_url("admin.php?page=pwoa-new-campaign"),
            ]); ?>

        </div>
        <?php

        // ── Help modal ────────────────────────────────────────────────────────
        // Output before any early return so the ? button always finds it in the DOM.
        $active_option = $stacking_options[$behavior] ?? null; ?>

        <div id="help-modal" class="hidden fixed inset-0 z-[99999] bg-black/60 flex items-center justify-center p-5">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg flex flex-col max-h-[85vh]">

                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <strong class="text-base text-gray-900">Modo activo: <?php echo esc_html($behavior_labels[$behavior] ?? $behavior); ?></strong>
                    <button id="close-help" type="button" class="bg-transparent border-0 cursor-pointer text-gray-400 hover:text-gray-600 text-2xl font-bold leading-none">&times;</button>
                </div>

                <?php if ($active_option): ?>
                <div class="px-6 py-5 overflow-y-auto flex-1">
                    <p class="text-sm text-gray-700 mb-4"><?php echo esc_html($active_option["description"]); ?></p>
                    <?php
                    $note_type = ($active_option["note_type"] ?? "neutral") === "warning" ? "warning" : "info";
                    $ui->notice(["type" => $note_type, "message" => $active_option["note"]]);
                    ?>
                </div>
                <?php endif; ?>

                <div class="flex justify-between items-center px-6 py-4 border-t border-gray-200">
                    <a href="<?php echo esc_url(admin_url("admin.php?page=pwoa-settings")); ?>" class="text-sm text-blue-600 hover:text-blue-800">→ Cambiar en Ajustes</a>
                    <button id="close-help-btn" type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 border border-gray-200 rounded text-sm text-gray-700 cursor-pointer">Cerrar</button>
                </div>

            </div>
        </div>

        <?php if (empty($campaigns)) {
            // ── Empty state ──────────────────────────────────────────────────
            $ui->card([
                "content" => function () use ($ui): void { ?>
                    <div class="text-center py-12">
                        <p class="text-lg font-semibold text-gray-900 mb-2">No hay campañas activas</p>
                        <p class="text-sm text-gray-500 mb-6">Crea tu primera campaña de descuentos para aumentar ventas y optimizar tu inventario</p>
                        <?php $ui->button([
                            "label"   => "+ Crear Primera Campaña",
                            "variant" => "primary",
                            "href"    => admin_url("admin.php?page=pwoa-new-campaign"),
                        ]); ?>
                    </div>
                <?php },
            ]);
            return;
        }

        // ── Stats ─────────────────────────────────────────────────────────────
        $now          = current_time("timestamp");
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
            ["label" => "Total",       "value" => number_format($total),  "class" => "text-gray-900"],
            ["label" => "Activas",     "value" => count($truly_active),   "class" => "text-green-600"],
            ["label" => "Pausadas",    "value" => count($paused),         "class" => "text-gray-400"],
            ["label" => "Programadas", "value" => count($scheduled),      "class" => "text-blue-600"],
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
        // ── Campaigns table ───────────────────────────────────────────────────
        $ui->card([
            "content" => function () use ($ui, $campaigns, $strategy_labels, $objective_map, $page, $total, $total_pages): void { ?>

                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide pb-3">Campaña</th>
                            <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide pb-3">Objetivo</th>
                            <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wide pb-3">Periodo</th>
                            <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wide pb-3">Estado</th>
                            <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wide pb-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($campaigns as $campaign):
                            $obj          = $objective_map[$campaign->objective] ?? ["label" => "N/A", "variant" => "default"];
                            $is_scheduled = $campaign->start_date && strtotime($campaign->start_date) > current_time("timestamp");
                            $is_expired   = $campaign->end_date   && strtotime($campaign->end_date)   < current_time("timestamp");
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
                                        $total_sold = 0; $total_max = 0;
                                        foreach ($bulk_items as $item) {
                                            $total_sold += intval($units_sold[$item["product_id"] ?? 0] ?? 0);
                                            $total_max  += intval($item["max_quantity"] ?? 0);
                                        }
                                        $pct     = $total_max > 0 ? min(100, ($total_sold / $total_max) * 100) : 0;
                                        $variant = $pct >= 80 ? "danger" : ($pct >= 50 ? "warning" : "success");
                                ?>
                                        <div class="mt-1.5 text-xs text-gray-500"><?php echo number_format($total_sold); ?> / <?php echo number_format($total_max); ?> unidades</div>
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
                                    $end   = $campaign->end_date   ? date_i18n(get_option("date_format"), strtotime($campaign->end_date))   : "—";
                                ?>
                                    <div class="text-gray-700"><?php echo esc_html($start); ?></div>
                                    <div class="text-gray-400">hasta</div>
                                    <div class="text-gray-700"><?php echo esc_html($end); ?></div>
                                <?php else: ?>
                                    <span class="text-gray-400">Permanente</span>
                                <?php endif; ?>
                            </td>

                            <td class="py-3 pr-4 text-center">
                                <?php if ($is_expired): ?>
                                    <?php $ui->badge(["label" => "Expirada",   "variant" => "default"]); ?>
                                <?php elseif ($is_scheduled): ?>
                                    <?php $ui->badge(["label" => "Programada", "variant" => "primary"]); ?>
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
                                            <?php echo $campaign->active ? 'Activa' : 'Pausada'; ?>
                                        </span>
                                    </label>
                                <?php endif; ?>
                            </td>

                            <td class="py-3 text-right whitespace-nowrap">
                                <?php if ($campaign->strategy === "bulk_discount"): ?>
                                    <button class="btn-reset bg-transparent border-0 cursor-pointer text-green-600 hover:text-green-800 p-1 rounded hover:bg-green-50 transition-colors"
                                        data-campaign-id="<?php echo esc_attr($campaign->id); ?>"
                                        data-campaign-name="<?php echo esc_attr($campaign->name); ?>"
                                        title="Resetear contador">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </button>
                                <?php endif; ?>
                                <button class="btn-edit bg-transparent border-0 cursor-pointer text-blue-600 hover:text-blue-800 p-1 rounded hover:bg-blue-50 transition-colors"
                                    data-campaign-id="<?php echo esc_attr($campaign->id); ?>"
                                    title="Editar campaña">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button class="btn-delete bg-transparent border-0 cursor-pointer text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50 transition-colors"
                                    data-campaign-id="<?php echo esc_attr($campaign->id); ?>"
                                    data-campaign-name="<?php echo esc_attr($campaign->name); ?>"
                                    data-has-stats="<?php echo CampaignRepository::hasStats($campaign->id) ? '1' : '0'; ?>"
                                    title="Eliminar campaña">
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

                <div class="mt-2 text-xs text-gray-500"><?php echo number_format($total); ?> campaña<?php echo $total !== 1 ? 's' : ''; ?> en total</div>

            <?php },
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
            label.classList.remove('text-green-600', 'text-gray-400');
            label.classList.add(active ? 'text-green-600' : 'text-gray-400');
        }
        if (track) {
            track.classList.remove('bg-green-500', 'bg-gray-300');
            track.classList.add(active ? 'bg-green-500' : 'bg-gray-300');
        }
        if (dot) {
            dot.classList.remove('left-0.5', 'right-0.5');
            dot.classList.add(active ? 'right-0.5' : 'left-0.5');
        }

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

        this.disabled = true; this.classList.add('opacity-40');
        try {
            const res  = await fetch(ajaxurl, { method: 'POST', body: new URLSearchParams({ action: 'pwoa_delete_campaign', campaign_id: campaignId, nonce: '<?php echo wp_create_nonce("pwoa_nonce"); ?>' }) });
            const data = await res.json();
            if (!data.success) throw new Error(data.data || 'Error al eliminar');
            const row = this.closest('tr');
            if (row) {
                row.classList.add('transition-opacity', 'duration-300');
                requestAnimationFrame(() => row.classList.add('opacity-0'));
            }
            setTimeout(() => window.location.reload(), 300);
        } catch (err) {
            this.disabled = false; this.classList.remove('opacity-40');
            alert('Error: ' + err.message);
        }
    });
});

document.querySelectorAll('.btn-reset').forEach(function (btn) {
    btn.addEventListener('click', async function () {
        if (!confirm('¿Resetear contador de unidades vendidas de "' + this.dataset.campaignName + '"?\n\nEsto pondrá en 0 el contador.')) return;
        this.disabled = true; this.classList.add('opacity-40');
        try {
            const res  = await fetch(ajaxurl, { method: 'POST', body: new URLSearchParams({ action: 'pwoa_reset_units_sold', campaign_id: this.dataset.campaignId, nonce: '<?php echo wp_create_nonce("pwoa_nonce"); ?>' }) });
            const data = await res.json();
            if (!data.success) throw new Error(data.data || 'Error');
            alert('✓ ' + data.data.message);
            window.location.reload();
        } catch (err) {
            this.disabled = false; this.classList.remove('opacity-40');
            alert('Error: ' + err.message);
        }
    });
});

// Help modal — elements are always in DOM above this script
(function () {
    var helpBtn = document.getElementById('help-button');
    var modal   = document.getElementById('help-modal');
    if (!helpBtn || !modal) return;

    helpBtn.addEventListener('click',     function ()  { modal.classList.remove('hidden'); });
    document.getElementById('close-help')?.addEventListener('click',     function () { modal.classList.add('hidden'); });
    document.getElementById('close-help-btn')?.addEventListener('click', function () { modal.classList.add('hidden'); });
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.classList.add('hidden'); });
}());
</script>

<?php
if (!defined('ABSPATH')) exit;

// Mapeo de estrategias a etiquetas legibles
$strategy_labels = [
        'min_amount' => 'Monto Mínimo',
        'free_shipping' => 'Envío Gratis',
        'tiered_discount' => 'Descuento Escalonado',
        'expiry_based' => 'Por Vencimiento',
        'low_stock' => 'Stock Bajo',
        'recurring_purchase' => 'Compra Recurrente',
        'flash_sale' => 'Flash Sale'
];

// Mapeo de objetivos a colores y etiquetas
$objective_config = [
        'aov' => ['label' => 'AOV', 'color' => 'blue', 'icon' => '📈'],
        'liquidation' => ['label' => 'Liquidación', 'color' => 'orange', 'icon' => '🏷️'],
        'loyalty' => ['label' => 'Fidelización', 'color' => 'purple', 'icon' => '💎'],
        'urgency' => ['label' => 'Urgencia', 'color' => 'red', 'icon' => '⚡']
];
?>

<div class="wrap bg-gray-50 -ml-5 -mr-2 p-8 min-h-screen">

    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Campañas de Descuentos</h1>
            <p class="text-gray-600 mt-1">Gestiona tus estrategias de marketing</p>
        </div>
        <a href="<?php echo admin_url('admin.php?page=pwoa-new-campaign'); ?>"
           class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-all hover:shadow-lg inline-flex items-center gap-2">
            <span class="text-xl">+</span>
            Nueva Campaña
        </a>
    </div>

    <?php if (empty($campaigns)): ?>

        <!-- Empty State -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-16 text-center">
            <div class="text-6xl mb-4">📊</div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No hay campañas activas</h3>
            <p class="text-gray-500 mb-8 max-w-md mx-auto">
                Crea tu primera campaña de descuentos para aumentar ventas y optimizar tu inventario
            </p>
            <a href="<?php echo admin_url('admin.php?page=pwoa-new-campaign'); ?>"
               class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg inline-flex items-center gap-2 font-medium transition-all hover:shadow-lg">
                <span class="text-xl">+</span>
                Crear Primera Campaña
            </a>
        </div>

    <?php else: ?>

        <!-- Stats Summary -->
        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Campañas</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo count($campaigns); ?></p>
                    </div>
                    <div class="text-3xl">📊</div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Activas</p>
                        <p class="text-2xl font-bold text-green-600 mt-1">
                            <?php echo count(array_filter($campaigns, fn($c) => $c->active == 1)); ?>
                        </p>
                    </div>
                    <div class="text-3xl">✅</div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Pausadas</p>
                        <p class="text-2xl font-bold text-gray-400 mt-1">
                            <?php echo count(array_filter($campaigns, fn($c) => $c->active == 0)); ?>
                        </p>
                    </div>
                    <div class="text-3xl">⏸️</div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Programadas</p>
                        <p class="text-2xl font-bold text-blue-600 mt-1">
                            <?php
                            $scheduled = count(array_filter($campaigns, function($c) {
                                return $c->start_date && strtotime($c->start_date) > time();
                            }));
                            echo $scheduled;
                            ?>
                        </p>
                    </div>
                    <div class="text-3xl">📅</div>
                </div>
            </div>
        </div>

        <!-- Campaigns List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

            <!-- Table Header -->
            <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
                <div class="grid grid-cols-12 gap-4 items-center text-sm font-semibold text-gray-700">
                    <div class="col-span-4">Campaña</div>
                    <div class="col-span-2">Objetivo</div>
                    <div class="col-span-2 text-center">Periodo</div>
                    <div class="col-span-1 text-center">Prioridad</div>
                    <div class="col-span-2 text-center">Estado</div>
                    <div class="col-span-1 text-right">Acciones</div>
                </div>
            </div>

            <!-- Table Body -->
            <div class="divide-y divide-gray-100">
                <?php foreach ($campaigns as $campaign):
                    $obj_config = $objective_config[$campaign->objective] ?? ['label' => 'N/A', 'color' => 'gray', 'icon' => '📌'];
                    $is_scheduled = $campaign->start_date && strtotime($campaign->start_date) > time();
                    $is_expired = $campaign->end_date && strtotime($campaign->end_date) < time();
                    ?>
                    <div class="px-6 py-5 hover:bg-gray-50 transition-colors">
                        <div class="grid grid-cols-12 gap-4 items-center">

                            <!-- Campaign Name & Strategy -->
                            <div class="col-span-4">
                                <h3 class="font-semibold text-gray-900 mb-1 text-base">
                                    <?php echo esc_html($campaign->name); ?>
                                </h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-500">
                                        <?php echo $strategy_labels[$campaign->strategy] ?? esc_html($campaign->strategy); ?>
                                    </span>
                                    <?php if ($campaign->discount_type !== 'free_shipping'): ?>
                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                                            <?php echo $campaign->discount_type === 'percentage' ? '%' : '$'; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Objective Badge -->
                            <div class="col-span-2">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-<?php echo $obj_config['color']; ?>-50 text-<?php echo $obj_config['color']; ?>-700 border border-<?php echo $obj_config['color']; ?>-200">
                                    <span><?php echo $obj_config['icon']; ?></span>
                                    <?php echo $obj_config['label']; ?>
                                </span>
                            </div>

                            <!-- Period -->
                            <div class="col-span-2 text-center text-sm">
                                <?php if ($campaign->start_date || $campaign->end_date): ?>
                                    <div class="text-gray-700">
                                        <?php echo $campaign->start_date ? date('d/m/Y', strtotime($campaign->start_date)) : '—'; ?>
                                    </div>
                                    <div class="text-gray-400 text-xs">hasta</div>
                                    <div class="text-gray-700">
                                        <?php echo $campaign->end_date ? date('d/m/Y', strtotime($campaign->end_date)) : '—'; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400">Permanente</span>
                                <?php endif; ?>
                            </div>

                            <!-- Priority -->
                            <div class="col-span-1 text-center">
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full font-bold text-sm
                                    <?php echo $campaign->priority >= 50 ? 'bg-red-100 text-red-700' :
                                        ($campaign->priority >= 20 ? 'bg-yellow-100 text-yellow-700' :
                                                'bg-gray-100 text-gray-600'); ?>">
                                    <?php echo esc_html($campaign->priority); ?>
                                </span>
                            </div>

                            <!-- Status Toggle -->
                            <div class="col-span-2 text-center">
                                <?php if ($is_expired): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                                        <span>⏱️</span> Expirada
                                    </span>
                                <?php elseif ($is_scheduled): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-blue-50 text-blue-700">
                                        <span>📅</span> Programada
                                    </span>
                                <?php else: ?>
                                    <label class="relative inline-flex items-center cursor-pointer group">
                                        <input type="checkbox"
                                               class="sr-only peer toggle-campaign"
                                               data-campaign-id="<?php echo esc_attr($campaign->id); ?>"
                                               data-campaign-name="<?php echo esc_attr($campaign->name); ?>"
                                                <?php checked($campaign->active, 1); ?>>
                                        <div class="w-14 h-7 bg-gray-300 rounded-full peer
                                                    peer-checked:bg-green-500
                                                    peer-focus:ring-4 peer-focus:ring-green-200
                                                    transition-all duration-200
                                                    relative">
                                            <div class="absolute top-0.5 left-0.5 bg-white w-6 h-6 rounded-full
                                                        transition-transform duration-200
                                                        peer-checked:translate-x-7"></div>
                                        </div>
                                        <span class="ml-3 text-sm font-medium text-gray-700 peer-checked:text-green-700">
                                            <?php echo $campaign->active ? 'Activa' : 'Pausada'; ?>
                                        </span>
                                    </label>
                                <?php endif; ?>
                            </div>

                            <!-- Actions -->
                            <div class="col-span-1 text-right">
                                <button class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors"
                                        title="Más opciones">
                                    <span class="text-xl">⋮</span>
                                </button>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

    <?php endif; ?>

</div>

<script>
    document.querySelectorAll('.toggle-campaign').forEach(toggle => {
        toggle.addEventListener('change', async function() {
            const campaignId = this.dataset.campaignId;
            const campaignName = this.dataset.campaignName;
            const active = this.checked ? 1 : 0;
            const statusText = this.nextElementSibling?.nextElementSibling;

            // Optimistic UI update
            if (statusText) {
                statusText.textContent = active ? 'Activa' : 'Pausada';
                statusText.classList.toggle('text-green-700', active);
                statusText.classList.toggle('text-gray-700', !active);
            }

            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'pwoa_toggle_campaign',
                        campaign_id: campaignId,
                        active: active,
                        nonce: '<?php echo wp_create_nonce('pwoa_nonce'); ?>'
                    })
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.data || 'Error desconocido');
                }

                // Success feedback (opcional: implementar toast)
                console.log(`✓ Campaña "${campaignName}" ${active ? 'activada' : 'pausada'}`);

            } catch (error) {
                // Revertir en caso de error
                this.checked = !this.checked;
                if (statusText) {
                    statusText.textContent = !active ? 'Activa' : 'Pausada';
                    statusText.classList.toggle('text-green-700', !active);
                    statusText.classList.toggle('text-gray-700', active);
                }
                console.error('Error:', error.message);
                alert('Error al actualizar campaña: ' + error.message);
            }
        });
    });
</script>
<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">

    <div class="flex justify-between items-center mb-12">
        <h1 class="text-4xl font-bold">Campañas Activas</h1>
        <a href="<?php echo admin_url('admin.php?page=pwoa-new-campaign'); ?>"
           class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
            Nueva Campaña
        </a>
    </div>

    <?php if (empty($campaigns)): ?>

        <div class="bg-white p-16 rounded-lg shadow text-center">
            <p class="text-gray-500 text-lg mb-6">No hay campañas creadas aún</p>
            <a href="<?php echo admin_url('admin.php?page=pwoa-new-campaign'); ?>"
               class="bg-blue-600 text-white px-8 py-3 rounded-lg inline-block hover:bg-blue-700">
                Crear Primera Campaña
            </a>
        </div>

    <?php else: ?>

        <div class="space-y-6">
            <?php foreach ($campaigns as $campaign): ?>
                <div class="bg-white p-8 rounded-lg shadow">

                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-2xl font-bold mb-2"><?php echo esc_html($campaign->name); ?></h2>
                            <span class="text-gray-500"><?php echo esc_html($campaign->strategy); ?></span>
                        </div>

                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox"
                                   class="sr-only peer toggle-campaign"
                                   data-campaign-id="<?php echo esc_attr($campaign->id); ?>"
                                <?php checked($campaign->active, 1); ?>>
                            <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:bg-green-600"></div>
                        </label>
                    </div>

                    <div class="grid grid-cols-3 gap-8 text-center">
                        <div>
                            <p class="text-gray-500 mb-2">Prioridad</p>
                            <p class="text-2xl font-bold"><?php echo esc_html($campaign->priority); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 mb-2">Inicio</p>
                            <p class="text-lg"><?php echo $campaign->start_date ? date('d/m/Y', strtotime($campaign->start_date)) : 'Sin fecha'; ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 mb-2">Fin</p>
                            <p class="text-lg"><?php echo $campaign->end_date ? date('d/m/Y', strtotime($campaign->end_date)) : 'Sin fecha'; ?></p>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div>

<script>
    document.querySelectorAll('.toggle-campaign').forEach(toggle => {
        toggle.addEventListener('change', async function() {
            const campaignId = this.dataset.campaignId;
            const active = this.checked ? 1 : 0;

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
                this.checked = !this.checked;
                alert('Error al actualizar campaña');
            }
        });
    });
</script>
<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">

    <h1 class="text-4xl font-bold mb-12">AnalÃ­ticas</h1>

    <div class="grid grid-cols-4 gap-6 mb-12">

        <div class="bg-white p-8 rounded-lg shadow">
            <p class="text-gray-500 mb-2">Ã“rdenes con Descuento</p>
            <p class="text-4xl font-bold"><?php echo number_format($stats['total_orders']); ?></p>
        </div>

        <div class="bg-white p-8 rounded-lg shadow">
            <p class="text-gray-500 mb-2">Total Descontado</p>
            <p class="text-4xl font-bold"><?php echo wc_price($stats['total_discounted']); ?></p>
        </div>

        <div class="bg-white p-8 rounded-lg shadow">
            <p class="text-gray-500 mb-2">Descuento Promedio</p>
            <p class="text-4xl font-bold"><?php echo wc_price($stats['avg_discount']); ?></p>
        </div>

        <div class="bg-white p-8 rounded-lg shadow">
            <p class="text-gray-500 mb-2">Ingresos Totales</p>
            <p class="text-4xl font-bold"><?php echo wc_price($stats['total_revenue']); ?></p>
        </div>

    </div>

    <div class="bg-white p-8 rounded-lg shadow">
        <h2 class="text-2xl font-bold mb-8">Top CampaÃ±as</h2>

        <?php
        $top_campaigns = \PW\OfertasAvanzadas\Repositories\StatsRepository::getTopCampaigns();
        if (empty($top_campaigns)):
            ?>
            <p class="text-gray-500 text-center py-8">Sin datos de campaÃ±as aÃºn</p>
        <?php else: ?>
            <table class="w-full">
                <thead>
                <tr class="border-b">
                    <th class="text-left py-4">CampaÃ±a</th>
                    <th class="text-right py-4">Usos</th>
                    <th class="text-right py-4">Total Descontado</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($top_campaigns as $campaign): ?>
                    <tr class="border-b">
                        <td class="py-4"><?php echo esc_html($campaign->name); ?></td>
                        <td class="text-right py-4"><?php echo number_format($campaign->uses); ?></td>
                        <td class="text-right py-4 font-bold"><?php echo wc_price($campaign->total_discounted); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>
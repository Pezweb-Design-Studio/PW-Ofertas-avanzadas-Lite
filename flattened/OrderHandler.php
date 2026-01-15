<?php
namespace PW\OfertasAvanzadas\Handlers;

use PW\OfertasAvanzadas\Repositories\CampaignRepository;

class OrderHandler {

    public function __construct() {
        add_action('woocommerce_order_status_completed', [$this, 'updateBulkUnitsSold']);
        add_action('woocommerce_checkout_order_processed', [$this, 'saveCampaignToOrder']);
    }

    public function saveCampaignToOrder(int $order_id): void {
        $campaign_id = WC()->session->get('pwoa_applied_campaign');

        if ($campaign_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_meta_data('_pwoa_campaign_id', $campaign_id);
                $order->save();
            }
        }
    }

    public function updateBulkUnitsSold(int $order_id): void {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $campaign_id = $order->get_meta('_pwoa_campaign_id');
        if (!$campaign_id) return;

        // Obtener campaña
        $campaign = CampaignRepository::getById($campaign_id);
        if (!$campaign || $campaign->strategy !== 'bulk_discount') return;

        // Obtener config para saber qué productos están en bulk
        $config = json_decode($campaign->config, true);
        $bulk_items = $config['bulk_items'] ?? [];

        if (empty($bulk_items)) return;

        // Extraer product_ids de bulk
        $bulk_product_ids = array_column($bulk_items, 'product_id');

        // Contar unidades vendidas por producto en esta orden
        $units_to_add = [];

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();

            if (in_array($product_id, $bulk_product_ids)) {
                $quantity = $item->get_quantity();
                $units_to_add[$product_id] = ($units_to_add[$product_id] ?? 0) + $quantity;
            }
        }

        if (empty($units_to_add)) return;

        // Actualizar units_sold en DB
        $this->incrementUnitsSold($campaign_id, $units_to_add);
    }

    private function incrementUnitsSold(int $campaign_id, array $units_to_add): void {
        global $wpdb;

        // Usar transacción para evitar race conditions
        $wpdb->query('START TRANSACTION');

        try {
            // Obtener campaña con lock
            $campaign = $wpdb->get_row($wpdb->prepare(
                "SELECT id, units_sold FROM {$wpdb->prefix}pwoa_campaigns 
                WHERE id = %d FOR UPDATE",
                $campaign_id
            ));

            if (!$campaign) {
                $wpdb->query('ROLLBACK');
                return;
            }

            // Decodificar units_sold actual
            $units_sold = $campaign->units_sold
                ? json_decode($campaign->units_sold, true)
                : [];

            // Agregar unidades vendidas
            foreach ($units_to_add as $product_id => $quantity) {
                $units_sold[$product_id] = ($units_sold[$product_id] ?? 0) + $quantity;
            }

            // Actualizar en DB
            $wpdb->update(
                "{$wpdb->prefix}pwoa_campaigns",
                ['units_sold' => wp_json_encode($units_sold)],
                ['id' => $campaign_id],
                ['%s'],
                ['%d']
            );

            $wpdb->query('COMMIT');

            // Invalidar cache de badges
            if (class_exists('PW\\OfertasAvanzadas\\Handlers\\ProductBadgeHandler')) {
                \PW\OfertasAvanzadas\Handlers\ProductBadgeHandler::clearCache();
            }

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('PWOA Error updating units_sold: ' . $e->getMessage());
        }
    }
}
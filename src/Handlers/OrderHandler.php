<?php
namespace PW\OfertasAvanzadas\Handlers;

defined('ABSPATH') || exit;

use PW\OfertasAvanzadas\Repositories\CampaignRepository;

class OrderHandler
{
	public function __construct()
	{
		$hooks = [
			['woocommerce_checkout_order_processed',          'saveCampaignToOrder',  10, 1],
			['woocommerce_store_api_checkout_order_processed', 'saveCampaignToOrder', 10, 1],
			['woocommerce_order_status_processing',           'updateBulkUnitsSold',  10, 1],
			['woocommerce_order_status_completed',            'updateBulkUnitsSold',  10, 1],
		];

		foreach ($hooks as [$hook, $method, $priority, $args]) {
			add_action($hook, [$this, $method], $priority, $args);
		}
	}

	public function saveCampaignToOrder($order_or_id): void
	{
		$order = $order_or_id instanceof \WC_Order ? $order_or_id : wc_get_order(intval($order_or_id));
		$campaign_id = WC()->session ? WC()->session->get('pwoa_applied_campaign') : null;

		if (!$campaign_id || !$order) {
			return;
		}

		$order->update_meta_data('_pwoa_campaign_id', $campaign_id);
		$order->save();
	}

	public function updateBulkUnitsSold(int $order_id): void
	{
		$order = wc_get_order($order_id);
		if (!$order) {
			return;
		}

		if ($order->get_meta('_pwoa_units_counted')) {
			return;
		}

		$campaign_id = $order->get_meta('_pwoa_campaign_id');
		if (!$campaign_id) {
			return;
		}

		$campaign = CampaignRepository::getById($campaign_id);
		if (!$campaign || $campaign->strategy !== 'bulk_discount') {
			return;
		}

		$bulk_items = json_decode($campaign->config, true)['bulk_items'] ?? [];
		if (empty($bulk_items)) {
			return;
		}

		$bulk_product_ids = array_column($bulk_items, 'product_id');
		$units_to_add = [];

		foreach ($order->get_items() as $item) {
			$product_id = $item->get_product_id();
			if (in_array($product_id, $bulk_product_ids)) {
				$units_to_add[$product_id] = ($units_to_add[$product_id] ?? 0) + $item->get_quantity();
			}
		}

		if (empty($units_to_add)) {
			return;
		}

		$this->incrementUnitsSold($campaign_id, $units_to_add);

		$order->update_meta_data('_pwoa_units_counted', 1);
		$order->save();
	}

	private function incrementUnitsSold(int $campaign_id, array $units_to_add): void
	{
		global $wpdb;
		$table = "{$wpdb->prefix}pwoa_campaigns";

		$wpdb->query('START TRANSACTION');

		try {
			$campaign = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, units_sold FROM {$table} WHERE id = %d FOR UPDATE",
					$campaign_id
				)
			);

			if (!$campaign) {
				$wpdb->query('ROLLBACK');
				return;
			}

			$units_sold = $campaign->units_sold ? json_decode($campaign->units_sold, true) : [];

			foreach ($units_to_add as $product_id => $quantity) {
				$units_sold[$product_id] = ($units_sold[$product_id] ?? 0) + $quantity;
			}

			$wpdb->update(
				$table,
				['units_sold' => wp_json_encode($units_sold)],
				['id' => $campaign_id],
				['%s'],
				['%d']
			);

			$wpdb->query('COMMIT');

			if (class_exists('PW\\OfertasAvanzadas\\Handlers\\ProductBadgeHandler')) {
				ProductBadgeHandler::clearCache();
			}
		} catch (\Exception $e) {
			$wpdb->query('ROLLBACK');
		}
	}
}

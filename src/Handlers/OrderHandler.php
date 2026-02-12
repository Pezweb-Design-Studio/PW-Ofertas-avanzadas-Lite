<?php
namespace PW\OfertasAvanzadas\Handlers;

use PW\OfertasAvanzadas\Repositories\CampaignRepository;

class OrderHandler
{
	public function __construct()
	{
		// Guardar campaign_id en order meta (clásico + bloques)
		add_action("woocommerce_checkout_order_processed", [
			$this,
			"saveCampaignToOrder",
		]);
		add_action("woocommerce_store_api_checkout_order_processed", [
			$this,
			"saveCampaignToOrder",
		]);

		// Actualizar units_sold (processing + completed)
		add_action("woocommerce_order_status_processing", [
			$this,
			"updateBulkUnitsSold",
		]);
		add_action("woocommerce_order_status_completed", [
			$this,
			"updateBulkUnitsSold",
		]);
	}

	public function saveCampaignToOrder($order_or_id): void
	{
		$order_id =
			$order_or_id instanceof \WC_Order
				? $order_or_id->get_id()
				: intval($order_or_id);
		$campaign_id = WC()->session
			? WC()->session->get("pwoa_applied_campaign")
			: null;

		if ($campaign_id) {
			$order =
				$order_or_id instanceof \WC_Order
					? $order_or_id
					: wc_get_order($order_id);
			if ($order) {
				$order->update_meta_data("_pwoa_campaign_id", $campaign_id);
				$order->save();
			}
		}
	}

	public function updateBulkUnitsSold(int $order_id): void
	{
		$order = wc_get_order($order_id);
		if (!$order) {
			return;
		}

		// Guard anti-duplicado: no contar dos veces si pasa por processing y completed
		if ($order->get_meta("_pwoa_units_counted")) {
			return;
		}

		$campaign_id = $order->get_meta("_pwoa_campaign_id");
		if (!$campaign_id) {
			return;
		}

		$campaign = CampaignRepository::getById($campaign_id);
		if (!$campaign || $campaign->strategy !== "bulk_discount") {
			return;
		}

		$config = json_decode($campaign->config, true);
		$bulk_items = $config["bulk_items"] ?? [];
		if (empty($bulk_items)) {
			return;
		}

		$bulk_product_ids = array_column($bulk_items, "product_id");
		$units_to_add = [];

		foreach ($order->get_items() as $item) {
			$product_id = $item->get_product_id();
			if (in_array($product_id, $bulk_product_ids)) {
				$units_to_add[$product_id] =
					($units_to_add[$product_id] ?? 0) + $item->get_quantity();
			}
		}

		if (empty($units_to_add)) {
			return;
		}

		$this->incrementUnitsSold($campaign_id, $units_to_add);

		// Marcar como contada
		$order->update_meta_data("_pwoa_units_counted", 1);
		$order->save();
	}

	private function incrementUnitsSold(
		int $campaign_id,
		array $units_to_add,
	): void {
		global $wpdb;

		// Usar transacción para evitar race conditions
		$wpdb->query("START TRANSACTION");

		try {
			// Obtener campaña con lock
			$campaign = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, units_sold FROM {$wpdb->prefix}pwoa_campaigns
                WHERE id = %d FOR UPDATE",
					$campaign_id,
				),
			);

			if (!$campaign) {
				$wpdb->query("ROLLBACK");
				return;
			}

			// Decodificar units_sold actual
			$units_sold = $campaign->units_sold
				? json_decode($campaign->units_sold, true)
				: [];

			// Agregar unidades vendidas
			foreach ($units_to_add as $product_id => $quantity) {
				$units_sold[$product_id] =
					($units_sold[$product_id] ?? 0) + $quantity;
			}

			// Actualizar en DB
			$wpdb->update(
				"{$wpdb->prefix}pwoa_campaigns",
				["units_sold" => wp_json_encode($units_sold)],
				["id" => $campaign_id],
				["%s"],
				["%d"],
			);

			$wpdb->query("COMMIT");

			// Invalidar cache de badges
			if (
				class_exists(
					"PW\\OfertasAvanzadas\\Handlers\\ProductBadgeHandler",
				)
			) {
				\PW\OfertasAvanzadas\Handlers\ProductBadgeHandler::clearCache();
			}
		} catch (\Exception $e) {
			$wpdb->query("ROLLBACK");
			error_log("PWOA Error updating units_sold: " . $e->getMessage());
		}
	}
}

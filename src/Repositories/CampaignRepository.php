<?php
namespace PW\OfertasAvanzadas\Repositories;

class CampaignRepository {

    public static function getActive(): array {
        global $wpdb;
        return $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}pwoa_campaigns 
            WHERE active = 1 
            AND (start_date IS NULL OR start_date <= NOW())
            AND (end_date IS NULL OR end_date >= NOW())
            ORDER BY priority DESC
        ");
    }

    public static function getAll(): array {
        global $wpdb;
        return $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}pwoa_campaigns 
            ORDER BY created_at DESC
        ");
    }

    public static function create(array $data): int {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}pwoa_campaigns", [
            'name' => sanitize_text_field($data['name']),
            'objective' => sanitize_text_field($data['objective']),
            'strategy' => sanitize_text_field($data['strategy']),
            'discount_type' => sanitize_text_field($data['discount_type']),
            'config' => wp_json_encode($data['config']),
            'conditions' => wp_json_encode($data['conditions'] ?? []),
            'stacking_mode' => sanitize_text_field($data['stacking_mode'] ?? 'priority'),
            'priority' => intval($data['priority'] ?? 10),
            'start_date' => $data['start_date'] ?: null,
            'end_date' => $data['end_date'] ?: null
        ]);
        return $wpdb->insert_id;
    }

    public static function updateStatus(int $id, int $active): bool {
        global $wpdb;
        return $wpdb->update(
                "{$wpdb->prefix}pwoa_campaigns",
                ['active' => $active],
                ['id' => $id],
                ['%d'],
                ['%d']
            ) !== false;
    }

    public static function delete(int $id): bool {
        global $wpdb;
        return $wpdb->delete(
                "{$wpdb->prefix}pwoa_campaigns",
                ['id' => $id],
                ['%d']
            ) !== false;
    }
}
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

        $insert_data = [
            'name' => sanitize_text_field($data['name']),
            'objective' => sanitize_text_field($data['objective']),
            'strategy' => sanitize_text_field($data['strategy']),
            'discount_type' => sanitize_text_field($data['discount_type']),
            'config' => wp_json_encode($data['config']),
            'conditions' => wp_json_encode($data['conditions'] ?? []),
            'stacking_mode' => sanitize_text_field($data['stacking_mode'] ?? 'priority'),
            'priority' => intval($data['priority'] ?? 10)
        ];

        // Solo agregar fechas si no están vacías
        if (!empty($data['start_date'])) {
            $insert_data['start_date'] = sanitize_text_field($data['start_date']);
        }

        if (!empty($data['end_date'])) {
            $insert_data['end_date'] = sanitize_text_field($data['end_date']);
        }

        $result = $wpdb->insert("{$wpdb->prefix}pwoa_campaigns", $insert_data);

        if ($result === false) {
            error_log('PWOA Campaign Insert Error: ' . $wpdb->last_error);
            error_log('PWOA Campaign Data: ' . print_r($insert_data, true));
            return 0;
        }

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
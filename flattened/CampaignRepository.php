<?php
namespace PW\OfertasAvanzadas\Repositories;

class CampaignRepository {

    public static function getActive(): array {
        global $wpdb;
        return $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}pwoa_campaigns 
            WHERE active = 1 
            AND deleted_at IS NULL
            AND (start_date IS NULL OR start_date <= NOW())
            AND (end_date IS NULL OR end_date >= NOW())
            ORDER BY priority DESC
        ");
    }

    public static function getAll(): array {
        global $wpdb;
        return $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}pwoa_campaigns 
            WHERE deleted_at IS NULL
            ORDER BY created_at DESC
        ");
    }

    // ⚡ NUEVO: Paginación
    public static function getPaginated(int $page = 1, int $per_page = 20): array {
        global $wpdb;

        $offset = ($page - 1) * $per_page;

        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}pwoa_campaigns 
            WHERE deleted_at IS NULL
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d
        ", $per_page, $offset));
    }

    // ⚡ NUEVO: Contar total de campañas
    public static function getCount(): int {
        global $wpdb;

        return (int) $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}pwoa_campaigns 
            WHERE deleted_at IS NULL
        ");
    }

    public static function getById(int $id): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}pwoa_campaigns 
            WHERE id = %d AND deleted_at IS NULL
        ", $id));
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

        if (class_exists('PW\\OfertasAvanzadas\\Handlers\\ProductBadgeHandler')) {
            \PW\OfertasAvanzadas\Handlers\ProductBadgeHandler::clearCache();
        }

        // ⚡ Limpiar cache de estrategias
        self::clearStrategiesCache();

        return $wpdb->insert_id;
    }

    public static function update(int $id, array $data): bool {
        global $wpdb;

        $update_data = [
            'name' => sanitize_text_field($data['name']),
            'objective' => sanitize_text_field($data['objective']),
            'strategy' => sanitize_text_field($data['strategy']),
            'discount_type' => sanitize_text_field($data['discount_type']),
            'config' => wp_json_encode($data['config']),
            'conditions' => wp_json_encode($data['conditions'] ?? []),
            'stacking_mode' => sanitize_text_field($data['stacking_mode'] ?? 'priority'),
            'priority' => intval($data['priority'] ?? 10)
        ];

        if (isset($data['start_date'])) {
            $update_data['start_date'] = !empty($data['start_date'])
                ? sanitize_text_field($data['start_date'])
                : null;
        }

        if (isset($data['end_date'])) {
            $update_data['end_date'] = !empty($data['end_date'])
                ? sanitize_text_field($data['end_date'])
                : null;
        }

        $result = $wpdb->update(
            "{$wpdb->prefix}pwoa_campaigns",
            $update_data,
            ['id' => $id, 'deleted_at' => null],
            array_fill(0, count($update_data), '%s'),
            ['%d', '%s']
        );

        if ($result === false) {
            error_log('PWOA Campaign Update Error: ' . $wpdb->last_error);
            return false;
        }

        if (class_exists('PW\\OfertasAvanzadas\\Handlers\\ProductBadgeHandler')) {
            \PW\OfertasAvanzadas\Handlers\ProductBadgeHandler::clearCache();
        }

        self::clearStrategiesCache();

        return true;
    }

    public static function updateStatus(int $id, int $active): bool {
        global $wpdb;
        $result = $wpdb->update(
                "{$wpdb->prefix}pwoa_campaigns",
                ['active' => $active],
                ['id' => $id, 'deleted_at' => null],
                ['%d'],
                ['%d', '%s']
            ) !== false;

        if ($result && class_exists('PW\\OfertasAvanzadas\\Handlers\\ProductBadgeHandler')) {
            \PW\OfertasAvanzadas\Handlers\ProductBadgeHandler::clearCache();
        }

        return $result;
    }

    public static function softDelete(int $id): bool {
        global $wpdb;
        $result = $wpdb->update(
                "{$wpdb->prefix}pwoa_campaigns",
                ['deleted_at' => current_time('mysql')],
                ['id' => $id],
                ['%s'],
                ['%d']
            ) !== false;

        if ($result && class_exists('PW\\OfertasAvanzadas\\Handlers\\ProductBadgeHandler')) {
            \PW\OfertasAvanzadas\Handlers\ProductBadgeHandler::clearCache();
        }

        self::clearStrategiesCache();

        return $result;
    }

    public static function delete(int $id): bool {
        global $wpdb;
        return $wpdb->delete(
                "{$wpdb->prefix}pwoa_campaigns",
                ['id' => $id],
                ['%d']
            ) !== false;
    }

    public static function hasStats(int $id): bool {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}pwoa_stats 
            WHERE campaign_id = %d
        ", $id));
        return intval($count) > 0;
    }

    public static function resetUnitsSold(int $campaign_id): bool {
        global $wpdb;

        return $wpdb->update(
                "{$wpdb->prefix}pwoa_campaigns",
                ['units_sold' => null],
                ['id' => $campaign_id],
                ['%s'],
                ['%d']
            ) !== false;
    }

    // ⚡ NUEVO: Limpiar cache de estrategias
    private static function clearStrategiesCache(): void {
        $objectives = ['basic', 'aov', 'liquidation', 'loyalty', 'urgency'];

        foreach ($objectives as $obj) {
            delete_transient('pwoa_strategies_' . $obj);
        }

        delete_transient('pwoa_attributes');
        delete_transient('pwoa_categories');
    }
}
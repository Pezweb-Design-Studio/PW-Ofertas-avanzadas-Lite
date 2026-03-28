<?php
namespace PW\OfertasAvanzadas\Admin;

defined('ABSPATH') || exit;

final class AdminAssets
{
    public static function register(): void
    {
        add_action('admin_enqueue_scripts', [self::class, 'enqueue'], 20);
    }

    public static function enqueue(string $hook): void
    {
        if (strpos($hook, 'pwoa') === false) {
            return;
        }

        if ($hook === 'toplevel_page_pwoa-dashboard') {
            self::enqueueDashboard();
        } elseif ($hook === 'ofertas_page_pwoa-new-campaign') {
            self::enqueueWizard();
        } elseif ($hook === 'ofertas_page_pwoa-settings') {
            self::enqueueSettings();
        } elseif ($hook === 'ofertas_page_pwoa-shortcodes') {
            self::enqueueShortcodes();
        }
    }

    private static function dashboardStrings(): array
    {
        return [
            'active'                 => __('Active', 'pw-ofertas-avanzadas'),
            'paused'                 => __('Paused', 'pw-ofertas-avanzadas'),
            'unknownError'           => __('Unknown error', 'pw-ofertas-avanzadas'),
            'updateCampaignError'    => __('Could not update the campaign.', 'pw-ofertas-avanzadas'),
            'confirmDeletePrefix'    => __('Are you sure you want to delete the campaign', 'pw-ofertas-avanzadas'),
            'deleteHasStatsNote'     => __('This campaign has associated statistics.', 'pw-ofertas-avanzadas'),
            'deleteHasStatsNoteLite' => __('This campaign has associated statistics. Historical data is kept for reports.', 'pw-ofertas-avanzadas'),
            'deleteError'            => __('Could not delete the campaign.', 'pw-ofertas-avanzadas'),
            'errorPrefix'            => __('Error', 'pw-ofertas-avanzadas'),
            'confirmResetPrefix'     => __('Reset the units-sold counter for', 'pw-ofertas-avanzadas'),
            'confirmResetSuffix'     => __('This will set the counter to zero.', 'pw-ofertas-avanzadas'),
            'confirmResetSuffixLite' => __('This will set the counter for all units sold to zero.', 'pw-ofertas-avanzadas'),
            'resetError'             => __('Could not reset the counter.', 'pw-ofertas-avanzadas'),
        ];
    }

    private static function enqueueDashboard(): void
    {
        $isLite = defined('PWOA_EDITION') && PWOA_EDITION === 'lite';
        $handle = $isLite ? 'pwoa-admin-dashboard-lite' : 'pwoa-admin-dashboard';
        $file   = $isLite ? 'admin-dashboard-lite.js' : 'admin-dashboard.js';

        wp_enqueue_script(
            $handle,
            PWOA_URL . 'assets/js/' . $file,
            ['jquery'],
            PWOA_VERSION,
            true,
        );

        wp_localize_script($handle, 'pwoaAdminDashboard', [
            'nonce'          => wp_create_nonce('pwoa_nonce'),
            'newCampaignUrl' => admin_url('admin.php?page=pwoa-new-campaign'),
            'strings'        => self::dashboardStrings(),
        ]);
    }

    private static function enqueueWizard(): void
    {
        wp_enqueue_style(
            'pwoa-admin-wizard',
            PWOA_URL . 'assets/css/admin-wizard.css',
            [],
            PWOA_VERSION,
        );

        if (defined('PWOA_EDITION') && PWOA_EDITION === 'lite') {
            wp_enqueue_style(
                'pwoa-admin-wizard-lite',
                PWOA_URL . 'assets/css/admin-wizard-lite.css',
                ['pwoa-admin-wizard'],
                PWOA_VERSION,
            );
        }
    }

    private static function enqueueSettings(): void
    {
        wp_enqueue_script(
            'pwoa-admin-settings',
            PWOA_URL . 'assets/js/admin-settings.js',
            ['jquery'],
            PWOA_VERSION,
            true,
        );

        wp_localize_script('pwoa-admin-settings', 'pwoaAdminSettings', [
            'nonce'   => wp_create_nonce('pwoa_nonce'),
            'strings' => [
                'saveError'    => __('Error', 'pw-ofertas-avanzadas'),
                'couldNotSave' => __('Could not save.', 'pw-ofertas-avanzadas'),
                'saveFailed'   => __('Could not save settings.', 'pw-ofertas-avanzadas'),
            ],
        ]);
    }

    private static function enqueueShortcodes(): void
    {
        wp_enqueue_script(
            'pwoa-admin-shortcodes',
            PWOA_URL . 'assets/js/admin-shortcodes.js',
            ['jquery'],
            PWOA_VERSION,
            true,
        );

        wp_localize_script('pwoa-admin-shortcodes', 'pwoaAdminShortcodes', [
            'strings' => [
                'copied'   => __('Copied!', 'pw-ofertas-avanzadas'),
                'copyVerb' => __('Copy', 'pw-ofertas-avanzadas'),
            ],
        ]);
    }
}

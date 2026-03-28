<?php
namespace PW\OfertasAvanzadas\Core;

defined('ABSPATH') || exit;

final class I18n
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        add_action('init', [self::class, 'loadTextdomain'], 0);
    }

    public static function loadTextdomain(): void
    {
        if (!defined('PWOA_PATH') || !defined('PWOA_PLUGIN_FILE')) {
            return;
        }

        $domain = 'pw-ofertas-avanzadas';
        $locale = str_replace('-', '_', determine_locale());
        $mofile = self::resolveMoFile($domain, $locale);

        unload_textdomain($domain, false);

        if ($mofile !== null) {
            /*
             * WP 6.5+ stores translations in WP_Translation_Controller under the $locale passed here.
             * Runtime lookups use determine_locale() (e.g. es_CL). The bundled file is es_ES.mo, but
             * the bucket must match the request locale or translate() never finds entries.
             */
            load_textdomain($domain, $mofile, $locale);
        }

        load_plugin_textdomain(
            $domain,
            false,
            dirname(plugin_basename(PWOA_PLUGIN_FILE)) . '/languages',
        );
    }

    /**
     * Pick an existing .mo on disk. Single shipped pack: es_ES; used for all regional Spanish locales.
     *
     * @return string|null Absolute path to .mo, or null if none readable.
     */
    private static function resolveMoFile(string $domain, string $locale): ?string
    {
        $dir = trailingslashit(PWOA_PATH) . 'languages';
        $prefix = $dir . '/' . $domain . '-';

        $candidates = [$prefix . $locale . '.mo'];

        if (self::isSpanishLocale($locale) && $locale !== 'es_ES') {
            $candidates[] = $prefix . 'es_ES.mo';
        }

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    private static function isSpanishLocale(string $locale): bool
    {
        $locale = strtolower($locale);

        return $locale === 'es' || str_starts_with($locale, 'es_');
    }

    /** Strings for assets/js/wizard.js (English msgids). */
    public static function wizardScriptI18n(): array
    {
        return [
            'discardConfirm'            => __('Discard your changes?', 'pw-ofertas-avanzadas'),
            'buyXPayYInvalid'           => __('Enter valid quantities for “Buy” and “Pay”.', 'pw-ofertas-avanzadas'),
            'buyXPayYMustExceed'        => __('The buy quantity must be greater than the pay quantity.\n\nExample: buy 3, pay 2.', 'pw-ofertas-avanzadas'),
            'unknownError'             => __('Unknown error', 'pw-ofertas-avanzadas'),
            'connectionErrorPrefix'    => __('Connection error:', 'pw-ofertas-avanzadas'),
            'loadCampaignErrorPrefix'  => __('Could not load campaign:', 'pw-ofertas-avanzadas'),
            'strategyNotFound'         => __('Strategy not found.', 'pw-ofertas-avanzadas'),
            'updateCampaign'           => __('Update campaign', 'pw-ofertas-avanzadas'),
            'strategy'                 => __('Strategy', 'pw-ofertas-avanzadas'),
            'configuration'            => __('Configuration', 'pw-ofertas-avanzadas'),
            'whenToUse'                => __('When to use:', 'pw-ofertas-avanzadas'),
            'noExtraConfig'            => __('This strategy has no extra settings.', 'pw-ofertas-avanzadas'),
            'addAnotherProduct'        => __('Add another product', 'pw-ofertas-avanzadas'),
            'addTier'                  => __('Add tier', 'pw-ofertas-avanzadas'),
            'searchPlaceholder'        => __('Search by name, SKU or ID…', 'pw-ofertas-avanzadas'),
            /* translators: %s: one-based product row index in the repeater */
            'productN'                 => __('Product %s', 'pw-ofertas-avanzadas'),
            'removeRow'                => __('Remove', 'pw-ofertas-avanzadas'),
            'duplicateStrong'          => __('Duplicate', 'pw-ofertas-avanzadas'),
            /* translators: %s: one-based row number of the earlier duplicate product */
            'duplicateBody'            => __('Already set on product %s. Both discounts will apply.', 'pw-ofertas-avanzadas'),
            'validating'               => __('Validating…', 'pw-ofertas-avanzadas'),
            'loadFailed'               => __('Could not load', 'pw-ofertas-avanzadas'),
            'noProducts'               => __('No products found', 'pw-ofertas-avanzadas'),
            'skuPrefix'                => __('SKU:', 'pw-ofertas-avanzadas'),
            'stockLabel'               => __('Stock:', 'pw-ofertas-avanzadas'),
            'viewProduct'              => __('View →', 'pw-ofertas-avanzadas'),
            'selectAttribute'          => __('Select an attribute…', 'pw-ofertas-avanzadas'),
            'selectAttributeFirst'     => __('Select an attribute first…', 'pw-ofertas-avanzadas'),
            'loading'                  => __('Loading…', 'pw-ofertas-avanzadas'),
            'noValues'                 => __('No values available', 'pw-ofertas-avanzadas'),
            'buyXPayYInlineError'      => __('The buy quantity must be greater than the pay quantity.', 'pw-ofertas-avanzadas'),
            /* translators: 1: buy quantity, 2: pay quantity, 3: discount percentage */
            'previewBuyPay'            => __('Preview: Buy %1$s pay %2$s = %3$s%% off per set', 'pw-ofertas-avanzadas'),
            /* translators: 1: units per set (buy quantity), 2: phrase like "2 units" (free count + word) */
            'previewEachSet'           => __('Every %1$s units, %2$s free', 'pw-ofertas-avanzadas'),
            'previewUnit'              => __('unit', 'pw-ofertas-avanzadas'),
            'previewUnits'             => __('units', 'pw-ofertas-avanzadas'),
            'errorLabel'               => __('Error:', 'pw-ofertas-avanzadas'),
            'objectiveFallback'        => __('Objective', 'pw-ofertas-avanzadas'),
            'objectives'               => [
                'basic'       => __('Basic', 'pw-ofertas-avanzadas'),
                'aov'         => __('Increase cart value', 'pw-ofertas-avanzadas'),
                'liquidation' => __('Clear inventory', 'pw-ofertas-avanzadas'),
                'loyalty'     => __('Loyalty', 'pw-ofertas-avanzadas'),
                'urgency'     => __('Quick conversion', 'pw-ofertas-avanzadas'),
            ],
            'selectValue'              => __('Select value…', 'pw-ofertas-avanzadas'),
            'previewColon'             => __('Preview:', 'pw-ofertas-avanzadas'),
            /* translators: 1: minimum quantity, 2: attribute term name, 3: discount percentage */
            'attrPreviewMain'          => __('Every %1$s items of "%2$s" = %3$s%% off', 'pw-ofertas-avanzadas'),
            'applications'             => __('applications', 'pw-ofertas-avanzadas'),
            'unlimited'                => __('Unlimited', 'pw-ofertas-avanzadas'),
            /* translators: %s: maximum number of discounted items */
            'maxMatchingItems'         => __('(max %s matching items)', 'pw-ofertas-avanzadas'),
            'errorShort'               => __('Error', 'pw-ofertas-avanzadas'),
            'close'                    => __('Close', 'pw-ofertas-avanzadas'),
            'proOnlyModalTitle'        => __('Pro-only feature', 'pw-ofertas-avanzadas'),
            'proOnlyModalBody'         => __('is available in Pro with six advanced strategy types and full analytics.', 'pw-ofertas-avanzadas'),
            'viewProVersion'           => __('View Pro version →', 'pw-ofertas-avanzadas'),
            'proOnlyObjectiveTitle'    => __('Pro-only feature', 'pw-ofertas-avanzadas'),
            'proOnlyObjectiveBody'     => __('This objective is available in Pro.', 'pw-ofertas-avanzadas'),
            'upgradeToPro'             => __('Upgrade to Pro →', 'pw-ofertas-avanzadas'),
            /* translators: %d: number of Pro-only strategies */
            'proBannerExclusiveOne'    => __('%d Pro-only strategy', 'pw-ofertas-avanzadas'),
            /* translators: %d: number of Pro-only strategies */
            'proBannerExclusiveMany'   => __('%d Pro-only strategies', 'pw-ofertas-avanzadas'),
            /* translators: 1: count of Lite strategies, 2: translated word "strategy" or "strategies" */
            'proBannerLiteLine'        => __('You are viewing %1$d %2$s in Lite. ', 'pw-ofertas-avanzadas'),
            'strategySingular'         => __('strategy', 'pw-ofertas-avanzadas'),
            'strategyPlural'           => __('strategies', 'pw-ofertas-avanzadas'),
            /* translators: 1: number of additional Pro strategies, 2: translated phrase "advanced strategy" or "advanced strategies" */
            'proBannerUnlock'          => __('Upgrade to Pro to unlock %1$d more %2$s.', 'pw-ofertas-avanzadas'),
            'advancedSingular'         => __('advanced strategy', 'pw-ofertas-avanzadas'),
            'advancedPlural'           => __('advanced strategies', 'pw-ofertas-avanzadas'),
            'viewPro'                  => __('View Pro →', 'pw-ofertas-avanzadas'),
        ];
    }
}

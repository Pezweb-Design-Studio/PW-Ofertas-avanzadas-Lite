<?php
// src/Admin/Views/wizard.lite.php — Lite build: copied to wizard.php by build-deploy.sh
defined("ABSPATH") || exit();

use PW\BackendUI\BackendUI;
use PW\OfertasAvanzadas\Core\UpgradeUrl;
use PW\OfertasAvanzadas\Repositories\CampaignRepository;

$is_edit_mode = isset($_GET['edit']) && absint(wp_unslash((string) ($_GET['edit'] ?? ''))) > 0;

$total_campaigns = CampaignRepository::getCount();
$remaining_slots = max(0, 5 - $total_campaigns);

$objectives = [
    "basic" => [
        "title"       => __('Basic', 'pw-ofertas-avanzadas'),
        "desc"        => __('Simple percentage or fixed amount discount on selected products.', 'pw-ofertas-avanzadas'),
        "available"   => true,
    ],
    "aov" => [
        "title"       => __('Increase cart value', 'pw-ofertas-avanzadas'),
        "desc"        => __('Raise average order value with strategic discounts.', 'pw-ofertas-avanzadas'),
        "available"   => true,
    ],
    "liquidation" => [
        "title"       => __('Clear inventory', 'pw-ofertas-avanzadas'),
        "desc"        => __('Move slow-moving or soon-to-expire stock.', 'pw-ofertas-avanzadas'),
        "available"   => true,
    ],
    "loyalty" => [
        "title"       => __('Loyalty', 'pw-ofertas-avanzadas'),
        "desc"        => __('Reward repeat customers and build loyalty.', 'pw-ofertas-avanzadas'),
        "available"   => false,
    ],
    "urgency" => [
        "title"       => __('Quick conversion', 'pw-ofertas-avanzadas'),
        "desc"        => __('Create urgency and drive immediate sales.', 'pw-ofertas-avanzadas'),
        "available"   => false,
    ],
];

$bui = BackendUI::init();
$bui->render_page([
    "title"   => $is_edit_mode ? __('Edit campaign', 'pw-ofertas-avanzadas') : __('New campaign', 'pw-ofertas-avanzadas'),
    "content" => function (BackendUI $bui) use ($is_edit_mode, $objectives, $remaining_slots): void {
        ?>

<div class="pwoa-wizard max-w-5xl mx-auto p-12">

    <?php if (!$is_edit_mode && $remaining_slots <= 2): ?>
        <div class="mb-8 bg-gradient-to-r from-orange-50 to-red-50 border-2 border-orange-200 rounded-lg p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-orange-900 mb-1">
                        <?php if ($remaining_slots === 0): ?>
                            <?php esc_html_e('Campaign limit reached', 'pw-ofertas-avanzadas'); ?>
                        <?php else: ?>
                            <?php
                            echo esc_html(sprintf(
                                /* translators: %d: number of campaign slots left */
                                _n('%d campaign remaining', '%d campaigns remaining', (int) $remaining_slots, 'pw-ofertas-avanzadas'),
                                (int) $remaining_slots
                            ));
                            ?>
                        <?php endif; ?>
                    </h3>
                    <p class="text-sm text-orange-700">
                        <?php
                        echo wp_kses_post(sprintf(
                            /* translators: %s: opening strong tag, %s: closing strong tag */
                            __('Lite: up to 5 campaigns. %1$sUpgrade to Pro%2$s for unlimited campaigns and 6 advanced strategies.', 'pw-ofertas-avanzadas'),
                            '<strong>',
                            '</strong>'
                        ));
                        ?>
                    </p>
                </div>
                <a href="<?php echo esc_url(UpgradeUrl::get()); ?>" target="_blank" rel="noopener noreferrer"
                   class="inline-flex justify-center bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:from-blue-700 hover:to-purple-700 transition whitespace-nowrap">
                    <?php esc_html_e('View Pro →', 'pw-ofertas-avanzadas'); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <nav id="breadcrumb" class="mb-12 pb-6 border-b border-gray-200 hidden">
        <ol class="flex items-center space-x-2 text-sm text-gray-500">
            <li>
                <button type="button" id="crumb-objective" class="hover:text-blue-600 transition">
                    <?php esc_html_e('Objective', 'pw-ofertas-avanzadas'); ?>
                </button>
            </li>
            <li id="crumb-strategy-wrapper" class="hidden">
                <span class="mx-2">/</span>
                <button type="button" id="crumb-strategy" class="hover:text-blue-600 transition">
                    <?php esc_html_e('Strategy', 'pw-ofertas-avanzadas'); ?>
                </button>
            </li>
            <li id="crumb-config-wrapper" class="hidden">
                <span class="mx-2">/</span>
                <span id="crumb-config" class="text-gray-900 font-medium"><?php esc_html_e('Configuration', 'pw-ofertas-avanzadas'); ?></span>
            </li>
        </ol>
    </nav>

    <div id="step-objective" class="<?php echo $is_edit_mode ? 'hidden' : ''; ?>">
        <h1 class="text-4xl font-bold mb-12"><?php esc_html_e('What do you want to achieve?', 'pw-ofertas-avanzadas'); ?></h1>

        <div class="grid grid-cols-2 gap-8">
            <?php foreach ($objectives as $key => $obj):
                $avail = $obj['available'] ?? true;
                ?>
                <button
                    type="button"
                    class="objective-btn <?php echo $avail ? '' : 'pro'; ?> text-left bg-white p-8 rounded-lg shadow hover:shadow-xl transition border-2 border-transparent hover:border-blue-500"
                    data-objective="<?php echo esc_attr($key); ?>"
                    data-title="<?php echo esc_attr($obj['title']); ?>"
                    data-available="<?php echo $avail ? '1' : '0'; ?>">
                    <h3 class="text-2xl font-bold mb-3"><?php echo esc_html($obj['title']); ?></h3>
                    <p class="text-gray-600"><?php echo esc_html($obj['desc']); ?></p>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="step-strategy" class="hidden">
        <h1 class="text-4xl font-bold mb-3" id="selected-objective-title"></h1>
        <p class="text-lg text-gray-500 mb-12"><?php esc_html_e('Choose a strategy', 'pw-ofertas-avanzadas'); ?></p>

        <div id="strategies-list"></div>
    </div>

    <div id="step-config" class="<?php echo $is_edit_mode ? '' : 'hidden'; ?>">
        <h1 class="text-4xl font-bold mb-3" id="selected-strategy-title"></h1>
        <p class="text-lg text-gray-500 mb-12"><?php esc_html_e('Configure your campaign settings', 'pw-ofertas-avanzadas'); ?></p>

        <form id="campaign-form" class="bg-white p-8 rounded-lg shadow space-y-6">

            <div>
                <label class="block text-sm font-bold mb-2"><?php esc_html_e('Campaign name', 'pw-ofertas-avanzadas'); ?></label>
                <input type="text" name="name" id="form-name" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                       placeholder="<?php echo esc_attr(__('e.g. Black Friday 2024 — volume discount', 'pw-ofertas-avanzadas')); ?>">
            </div>

            <div id="dynamic-fields"></div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold mb-2"><?php esc_html_e('Start date (optional)', 'pw-ofertas-avanzadas'); ?></label>
                    <input type="datetime-local" name="start_date" id="form-start-date"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2"><?php esc_html_e('End date (optional)', 'pw-ofertas-avanzadas'); ?></label>
                    <input type="datetime-local" name="end_date" id="form-end-date"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold mb-2">
                    <?php esc_html_e('Application mode', 'pw-ofertas-avanzadas'); ?>
                    <a href="#" id="stacking-help" class="ml-2 text-blue-600 hover:text-blue-800 text-xs font-normal">
                        <?php esc_html_e('What does this mean?', 'pw-ofertas-avanzadas'); ?>
                    </a>
                </label>
                <select name="stacking_mode" id="form-stacking-mode" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="priority"><?php esc_html_e('Priority (best discount wins)', 'pw-ofertas-avanzadas'); ?></option>
                    <option value="stack"><?php esc_html_e('Stack discounts', 'pw-ofertas-avanzadas'); ?></option>
                </select>
                <p class="text-sm text-gray-500 mt-1"><?php esc_html_e('When several campaigns are active, apply only the best one or combine them?', 'pw-ofertas-avanzadas'); ?></p>

                <div id="stacking-tooltip" class="hidden mt-3 p-4 bg-blue-50 border-l-4 border-blue-400 rounded text-sm">
                    <p class="font-bold text-blue-900 mb-2"><?php esc_html_e('Explanation:', 'pw-ofertas-avanzadas'); ?></p>
                    <ul class="space-y-2 text-blue-800">
                        <li><strong><?php esc_html_e('Priority:', 'pw-ofertas-avanzadas'); ?></strong> <?php esc_html_e('Competes with other priority campaigns. Only the highest discount applies.', 'pw-ofertas-avanzadas'); ?></li>
                        <li><strong><?php esc_html_e('Stack:', 'pw-ofertas-avanzadas'); ?></strong> <?php esc_html_e('Adds together with other stackable campaigns when available.', 'pw-ofertas-avanzadas'); ?></li>
                    </ul>
                    <p class="mt-3 text-blue-900">
                        <strong><?php esc_html_e('Note:', 'pw-ofertas-avanzadas'); ?></strong>
                        <?php
                        echo wp_kses_post(sprintf(
                            /* translators: %1$s: opening anchor, %2$s: closing anchor */
                            __('Global stacking rules and advanced options are available in Pro (%1$sview Pro edition%2$s).', 'pw-ofertas-avanzadas'),
                            '<a href="' . esc_url(UpgradeUrl::get()) . '" class="underline font-bold" target="_blank" rel="noopener noreferrer">',
                            '</a>'
                        ));
                        ?>
                    </p>
                </div>
            </div>

            <div id="product-filters-section" class="border-t pt-6 mt-6">
                <h3 class="text-lg font-bold mb-4"><?php esc_html_e('Filter products (optional)', 'pw-ofertas-avanzadas'); ?></h3>
                <p class="text-sm text-gray-600 mb-6"><?php esc_html_e('If you set no filters, the discount applies to all products in the cart.', 'pw-ofertas-avanzadas'); ?></p>

                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2"><?php esc_html_e('Specific products', 'pw-ofertas-avanzadas'); ?></label>
                    <input type="text"
                           id="product-search"
                           placeholder="<?php echo esc_attr(__('Search by name, SKU or ID…', 'pw-ofertas-avanzadas')); ?>"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <div id="product-search-results" class="mt-2 max-h-48 overflow-y-auto border rounded-lg hidden"></div>
                    <div id="selected-products" class="mt-3 flex flex-wrap gap-2"></div>
                    <input type="hidden" id="form-product-ids" name="conditions[product_ids]">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2"><?php esc_html_e('Categories', 'pw-ofertas-avanzadas'); ?></label>
                    <select id="form-categories" multiple class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 min-h-[120px]">
                        <?php
                        $categories = get_terms([
                            'taxonomy'   => 'product_cat',
                            'hide_empty' => false,
                        ]);
                        foreach ($categories as $cat) {
                            echo '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
                        }
                        ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1"><?php esc_html_e('Hold Ctrl/Cmd to select multiple.', 'pw-ofertas-avanzadas'); ?></p>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-bold mb-2"><?php esc_html_e('Minimum price', 'pw-ofertas-avanzadas'); ?></label>
                        <input type="number"
                               id="form-min-price"
                               placeholder="0"
                               step="0.01"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2"><?php esc_html_e('Maximum price', 'pw-ofertas-avanzadas'); ?></label>
                        <input type="number"
                               id="form-max-price"
                               placeholder="999999"
                               step="0.01"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-900">
                        <span class="font-bold"><?php esc_html_e('Products matching criteria:', 'pw-ofertas-avanzadas'); ?></span>
                        <span id="matching-count" class="ml-2 font-mono">-</span>
                    </p>
                    <div class="mt-3">
                        <button type="button"
                                id="btn-show-products"
                                class="text-sm bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            <?php esc_html_e('View filtered products', 'pw-ofertas-avanzadas'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <input type="hidden" name="objective" id="form-objective">
            <input type="hidden" name="strategy" id="form-strategy">
            <input type="hidden" name="priority" id="form-priority" value="10">
            <input type="hidden" name="discount_type" id="form-discount-type">

            <div class="flex gap-4 pt-4">
                <button type="submit" id="submit-btn"
                        class="pwoa-btn-primary px-8 py-3 rounded-lg font-bold">
                    <?php echo $is_edit_mode ? esc_html__('Update campaign', 'pw-ofertas-avanzadas') : esc_html__('Create campaign', 'pw-ofertas-avanzadas'); ?>
                </button>
                <button type="button" id="btn-cancel"
                        class="pwoa-btn-secondary px-8 py-3 rounded-lg font-semibold">
                    <?php esc_html_e('Cancel', 'pw-ofertas-avanzadas'); ?>
                </button>
            </div>

        </form>
    </div>

    <div id="products-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[80vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900"><?php esc_html_e('Products matching the filter', 'pw-ofertas-avanzadas'); ?></h3>
                <button type="button" id="close-modal" class="text-gray-400 hover:text-gray-600 text-2xl font-bold leading-none" aria-label="<?php echo esc_attr(__('Close', 'pw-ofertas-avanzadas')); ?>">×</button>
            </div>

            <div class="p-6 overflow-y-auto flex-1">
                <div id="modal-products-list" class="space-y-2"></div>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                <p class="text-sm text-gray-600">
                    <?php esc_html_e('Total:', 'pw-ofertas-avanzadas'); ?>
                    <span id="modal-count" class="font-bold text-gray-900">0</span>
                    <?php esc_html_e('products', 'pw-ofertas-avanzadas'); ?>
                </p>
                <button type="button" id="close-modal-btn" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700">
                    <?php esc_html_e('Close', 'pw-ofertas-avanzadas'); ?>
                </button>
            </div>
        </div>
    </div>

</div>

        <?php
    },
]);

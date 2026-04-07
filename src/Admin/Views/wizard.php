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
    "title"   => "",
    "content" => function (BackendUI $bui) use ($is_edit_mode, $objectives, $remaining_slots): void {
        ?>

<!-- Título de página -->
<div class="pw-bui-page-title">
    <h1 id="wizard-title" class="pw-bui-page-title__heading">
        <?php echo $is_edit_mode
            ? esc_html__('Edit campaign', 'pw-ofertas-avanzadas')
            : esc_html__('New campaign', 'pw-ofertas-avanzadas'); ?>
    </h1>
</div>

<!-- Action bar sticky — hermano de .pwoa-wizard para que su padre sea <main> (alto) -->
<div id="config-action-bar"
     class="<?php echo $is_edit_mode ? 'flex' : 'hidden'; ?> items-center gap-6 pwoa-sticky-config-header">
    <input type="text"
           id="header-name"
           autocomplete="off"
           placeholder="<?php echo esc_attr(__('Campaign name…', 'pw-ofertas-avanzadas')); ?>"
           class="text-xl font-semibold flex-1 min-w-0 bg-transparent border-0 border-b-2
                  border-transparent focus:border-blue-500 focus:outline-none transition
                  text-gray-900 py-1 px-0">
    <div class="flex gap-3 shrink-0">
        <?php $bui->ui()->button([
            'label'   => __('Cancel', 'pw-ofertas-avanzadas'),
            'type'    => 'button',
            'variant' => 'secondary',
            'attrs'   => ['id' => 'header-cancel-btn'],
        ]); ?>
        <?php $bui->ui()->button([
            'label'   => $is_edit_mode
                ? __('Update campaign', 'pw-ofertas-avanzadas')
                : __('Create campaign', 'pw-ofertas-avanzadas'),
            'type'    => 'submit',
            'variant' => 'primary',
            'attrs'   => ['form' => 'campaign-form'],
        ]); ?>
    </div>
</div>

<!-- Migas debajo de la barra de nombre/botones (flujo normal; no sticky) -->
<nav id="breadcrumb" aria-label="Breadcrumb" class="pwoa-wizard-breadcrumb hidden">
    <ol class="pw-bui-breadcrumbs">
        <li class="pw-bui-breadcrumbs__item">
            <span id="crumb-objective" role="button" tabindex="0"
                  class="cursor-pointer hover:text-blue-600 transition">
                <?php esc_html_e('Objective', 'pw-ofertas-avanzadas'); ?>
            </span>
        </li>
        <li id="crumb-strategy-wrapper" class="pw-bui-breadcrumbs__item hidden">
            <span class="pw-bui-breadcrumbs__sep" aria-hidden="true">/</span>
            <span id="crumb-strategy" role="button" tabindex="0"
                  class="cursor-pointer hover:text-blue-600 transition">
                <?php esc_html_e('Strategy', 'pw-ofertas-avanzadas'); ?>
            </span>
        </li>
        <li id="crumb-config-wrapper" class="pw-bui-breadcrumbs__item pw-bui-breadcrumbs__item--current hidden">
            <span class="pw-bui-breadcrumbs__sep" aria-hidden="true">/</span>
            <span id="crumb-config" aria-current="page"><?php esc_html_e('Configuration', 'pw-ofertas-avanzadas'); ?></span>
        </li>
    </ol>
</nav>

<div class="pwoa-wizard max-w-5xl mx-auto p-12">

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

        <form id="campaign-form" class="space-y-6 max-w-5xl">

            <input type="hidden" name="name" id="form-name">

            <?php $pwoa_ui = $bui->ui(); ?>

            <section id="product-filters-section">
                <?php
                $pwoa_ui->card([
                    "title" => __("Filter products (optional)", "pw-ofertas-avanzadas"),
                    "description" => __(
                        "If you set no filters, the discount applies to all products in the cart.",
                        "pw-ofertas-avanzadas",
                    ),
                    "content" => function (): void {
                        $categories = get_terms([
                            "taxonomy"   => "product_cat",
                            "hide_empty" => false,
                        ]);
                        if (is_wp_error($categories)) {
                            $categories = [];
                        }
                        ?>
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-bold mb-2"><?php esc_html_e('Specific products', 'pw-ofertas-avanzadas'); ?></label>
                        <input type="text"
                               id="product-search"
                               placeholder="<?php echo esc_attr(__('Search by name, SKU or ID…', 'pw-ofertas-avanzadas')); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <div id="product-search-results" class="mt-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg hidden bg-white"></div>
                        <div id="selected-products" class="mt-3 flex flex-wrap gap-2"></div>
                        <input type="hidden" id="form-product-ids" name="conditions[product_ids]">
                    </div>

                    <div>
                        <label class="block text-sm font-bold mb-2"><?php esc_html_e('Categories', 'pw-ofertas-avanzadas'); ?></label>
                        <select id="form-categories" multiple class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 min-h-[120px] bg-white">
                            <?php foreach ($categories as $cat) { ?>
                                <option value="<?php echo esc_attr((string) $cat->term_id); ?>"><?php echo esc_html(
                                    $cat->name,
                                ); ?></option>
                            <?php } ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1"><?php esc_html_e('Hold Ctrl/Cmd to select multiple.', 'pw-ofertas-avanzadas'); ?></p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold mb-2"><?php esc_html_e('Minimum price', 'pw-ofertas-avanzadas'); ?></label>
                            <input type="number"
                                   id="form-min-price"
                                   placeholder="0"
                                   step="0.01"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2"><?php esc_html_e('Maximum price', 'pw-ofertas-avanzadas'); ?></label>
                            <input type="number"
                                   id="form-max-price"
                                   placeholder="999999"
                                   step="0.01"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
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
                                    class="text-sm bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                <?php esc_html_e('View filtered products', 'pw-ofertas-avanzadas'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                        <?php
                    },
                ]);
                ?>
            </section>

            <?php
            $pwoa_ui->card([
                "title" => __("Discount settings", "pw-ofertas-avanzadas"),
                "description" => __(
                    "Set the discount type, value, and options defined by the selected strategy.",
                    "pw-ofertas-avanzadas",
                ),
                "content" => function (): void {
                    echo '<div id="dynamic-fields" class="space-y-6"></div>';
                },
            ]);

            $pwoa_ui->card([
                "title" => __("Schedule", "pw-ofertas-avanzadas"),
                "description" => __("Optional start and end date for this campaign.", "pw-ofertas-avanzadas"),
                "content" => function (): void {
                    ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold mb-2"><?php esc_html_e('Start date (optional)', 'pw-ofertas-avanzadas'); ?></label>
                        <input type="datetime-local" name="start_date" id="form-start-date"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2"><?php esc_html_e('End date (optional)', 'pw-ofertas-avanzadas'); ?></label>
                        <input type="datetime-local" name="end_date" id="form-end-date"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                    <?php
                },
            ]);

            $pwoa_ui->card([
                "title" => __("Application mode", "pw-ofertas-avanzadas"),
                "description" => __(
                    "When several campaigns are active, apply only the best one or combine them?",
                    "pw-ofertas-avanzadas",
                ),
                "content" => function (): void {
                    ?>
                <div>
                    <label class="block text-sm font-bold mb-2" for="form-stacking-mode">
                        <?php esc_html_e('Stacking behavior', 'pw-ofertas-avanzadas'); ?>
                        <a href="#" id="stacking-help" class="ml-2 text-blue-600 hover:text-blue-800 text-xs font-normal"><?php esc_html_e('What does this mean?', 'pw-ofertas-avanzadas'); ?></a>
                    </label>
                    <select name="stacking_mode" id="form-stacking-mode" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="priority"><?php esc_html_e('Priority (best discount wins)', 'pw-ofertas-avanzadas'); ?></option>
                        <option value="stack"><?php esc_html_e('Stack discounts', 'pw-ofertas-avanzadas'); ?></option>
                    </select>

                    <div id="stacking-tooltip" class="hidden mt-4 p-4 bg-blue-50 border-l-4 border-blue-400 rounded-lg text-sm">
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
                                '</a>',
                            ));
                            ?>
                        </p>
                    </div>
                </div>
                    <?php
                },
            ]);
            ?>

            <input type="hidden" name="objective" id="form-objective">
            <input type="hidden" name="strategy" id="form-strategy">
            <input type="hidden" name="priority" id="form-priority" value="10">
            <input type="hidden" name="discount_type" id="form-discount-type">

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

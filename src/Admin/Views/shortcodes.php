<?php
// src/Admin/Views/shortcodes.php
defined("ABSPATH") || exit();

use PW\BackendUI\BackendUI;

/**
 * @var array $campaigns  Campañas activas para el selector del generador.
 */

$campaigns = \PW\OfertasAvanzadas\Repositories\CampaignRepository::getActive();

$base_url  = admin_url("admin.php?page=pwoa-shortcodes");
$tab       = sanitize_key($_GET["tab"] ?? "generador");

$bui = BackendUI::init();

$bui->render_page([
    "title"     => __('Shortcodes', 'pw-ofertas-avanzadas'),
    "tabs"      => [
        ["slug" => "generador",  "label" => __('Generator', 'pw-ofertas-avanzadas'),   "href" => add_query_arg("tab", "generador",  $base_url), "active" => $tab === "generador"],
        ["slug" => "referencia", "label" => __('Reference', 'pw-ofertas-avanzadas'),  "href" => add_query_arg("tab", "referencia", $base_url), "active" => $tab === "referencia"],
        ["slug" => "ejemplos",   "label" => __('Examples', 'pw-ofertas-avanzadas'),    "href" => add_query_arg("tab", "ejemplos",   $base_url), "active" => $tab === "ejemplos"],
    ],
    "tabs_mode" => "url",
    "content"   => function ($bui) use ($campaigns, $tab, $base_url): void {
        $ui = $bui->ui();

        // ── Intro notice ──────────────────────────────────────────────────────
        $ui->notice([
            "type"    => "info",
            "message" => wp_kses_post(
                sprintf(
                    /* translators: %s: shortcode tag */
                    __('Main shortcode: <code>%s</code> — Place it on any page, post, or text widget.', 'pw-ofertas-avanzadas'),
                    '[pwoa_productos_oferta]'
                )
            ),
        ]);

        echo '<div style="margin-bottom:20px;"></div>';

        if ($tab === "generador" || $tab === "") {

            // ── Generator ────────────────────────────────────────────────────
            $ui->card([
                "title"       => __('Shortcode generator', 'pw-ofertas-avanzadas'),
                "description" => __('Set the parameters and copy the result.', 'pw-ofertas-avanzadas'),
                "content"     => function () use ($campaigns): void {
                    echo '<div id="pwoa-generator" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">';

                    // Controls
                    echo '<div style="display:flex;flex-direction:column;gap:14px;">';

                    if (!empty($campaigns)) {
                        echo '<div>';
                        echo '<label style="display:block;font-size:12px;font-weight:600;color:var(--pw-color-fg-default);margin-bottom:6px;">' . esc_html__('Specific campaign', 'pw-ofertas-avanzadas') . ' <span style="color:var(--pw-color-fg-muted);font-weight:400;">(' . esc_html__('optional', 'pw-ofertas-avanzadas') . ')</span></label>';
                        echo '<select id="gen-campaign_id" style="width:100%;border:1px solid var(--pw-color-border-default);border-radius:4px;padding:8px 10px;background:var(--pw-color-bg-canvas);color:var(--pw-color-fg-default);font-size:13px;">';
                        echo '<option value="">' . esc_html__('— All active campaigns —', 'pw-ofertas-avanzadas') . '</option>';
                        foreach ($campaigns as $c) {
                            echo '<option value="' . esc_attr($c->id) . '">' . esc_html($c->name) . ' (ID: ' . (int) $c->id . ')</option>';
                        }
                        echo '</select>';
                        echo '</div>';
                    }

                    $fields = [
                        ["gen-limit",    "number", __('Number of products', 'pw-ofertas-avanzadas'), "12",   "1",  "100"],
                        ["gen-columns",  "select", __('Columns', 'pw-ofertas-avanzadas'),              "4",    null, null],
                        ["gen-orderby",  "select", __('Order by', 'pw-ofertas-avanzadas'),           "date", null, null],
                        ["gen-order",    "select", __('Order', 'pw-ofertas-avanzadas'),                 "DESC", null, null],
                        ["gen-category", "text",   __('Category (slug)', 'pw-ofertas-avanzadas'),      "",     null, null],
                        ["gen-min_price","number", __('Minimum price', 'pw-ofertas-avanzadas'),         "",     null, null],
                        ["gen-max_price","number", __('Maximum price', 'pw-ofertas-avanzadas'),         "",     null, null],
                    ];

                    $select_options = [
                        "gen-columns" => [
                            "2" => "2",
                            "3" => "3",
                            "4" => __('4 (default)', 'pw-ofertas-avanzadas'),
                            "5" => "5",
                            "6" => "6",
                        ],
                        "gen-orderby" => [
                            "date"  => __('Date', 'pw-ofertas-avanzadas'),
                            "price" => __('Price', 'pw-ofertas-avanzadas'),
                            "name"  => __('Name', 'pw-ofertas-avanzadas'),
                            "rand"  => __('Random', 'pw-ofertas-avanzadas'),
                        ],
                        "gen-order"   => [
                            "DESC" => __('Descending', 'pw-ofertas-avanzadas'),
                            "ASC"  => __('Ascending', 'pw-ofertas-avanzadas'),
                        ],
                    ];

                    $input_style = "width:100%;border:1px solid var(--pw-color-border-default);border-radius:4px;padding:8px 10px;"
                        . "background:var(--pw-color-bg-canvas);color:var(--pw-color-fg-default);font-size:13px;box-sizing:border-box;";
                    $label_style = "display:block;font-size:12px;font-weight:600;color:var(--pw-color-fg-default);margin-bottom:6px;";

                    foreach ($fields as [$id, $type, $label, $default]) {
                        echo '<div>';
                        echo '<label for="' . esc_attr($id) . '" style="' . $label_style . '">' . esc_html($label) . '</label>';
                        if ($type === "select") {
                            echo '<select id="' . esc_attr($id) . '" style="' . $input_style . '">';
                            foreach ($select_options[$id] as $val => $lbl) {
                                echo '<option value="' . esc_attr($val) . '"' . ($val == $default ? ' selected' : '') . '>' . esc_html($lbl) . '</option>';
                            }
                            echo '</select>';
                        } else {
                            $extra = $type === "number" ? 'step="1" ' : '';
                            echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($id) . '" value="' . esc_attr($default) . '" ' . $extra . 'style="' . $input_style . '">';
                        }
                        echo '</div>';
                    }

                    // Checkboxes
                    $checks = [
                        ["gen-show_badge",         true,  __('Show discount badge', 'pw-ofertas-avanzadas')],
                        ["gen-show_campaign_name", false, __('Show campaign name', 'pw-ofertas-avanzadas')],
                        ["gen-paginate",           false, __('Enable pagination', 'pw-ofertas-avanzadas')],
                    ];
                    echo '<div style="display:flex;flex-direction:column;gap:8px;">';
                    foreach ($checks as [$id, $checked, $label]) {
                        echo '<label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--pw-color-fg-default);">';
                        echo '<input type="checkbox" id="' . esc_attr($id) . '"' . ($checked ? ' checked' : '') . ' style="width:14px;height:14px;">';
                        echo esc_html($label) . '</label>';
                    }
                    echo '</div>';

                    echo '<div id="gen-per_page-wrap" style="display:none;">';
                    echo '<label for="gen-per_page" style="' . $label_style . '">' . esc_html__('Products per page', 'pw-ofertas-avanzadas') . '</label>';
                    echo '<input type="number" id="gen-per_page" value="12" min="1" max="100" style="' . $input_style . '">';
                    echo '</div>';

                    echo '</div>'; // end controls

                    // Output
                    echo '<div style="display:flex;flex-direction:column;">';
                    echo '<label style="' . $label_style . '">' . esc_html__('Generated shortcode', 'pw-ofertas-avanzadas') . '</label>';
                    echo '<div style="background:#030712;border-radius:6px;padding:20px;display:flex;flex-direction:column;justify-content:space-between;min-height:160px;">';
                    echo '<code id="gen-output" style="color:#fff;font-family:monospace;font-size:13px;word-break:break-all;white-space:pre-wrap;">[pwoa_productos_oferta]</code>';
                    echo '<div style="margin-top:16px;text-align:right;">';
                    echo '<button id="gen-copy-btn" type="button"
                        style="padding:8px 16px;background:var(--pw-color-accent-emphasis);color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:13px;font-weight:600;">';
                    echo '<span id="gen-copy-label">' . esc_html__('Copy', 'pw-ofertas-avanzadas') . '</span></button>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';

                    echo '</div>'; // end grid
                },
            ]);

        } elseif ($tab === "referencia") {

            // ── Parameters reference ──────────────────────────────────────────
            $sections = [
                [
                    "title"  => __('Campaign filters', 'pw-ofertas-avanzadas'),
                    "params" => [
                        ["campaign_id", __('number', 'pw-ofertas-avanzadas'), "—",   __('Filter by campaign ID. If omitted, all active campaigns are used.', 'pw-ofertas-avanzadas')],
                        ["strategy",    __('text', 'pw-ofertas-avanzadas'),  "—",   __('Filter by strategy type: basic_discount, bulk_discount, buy_x_pay_y, etc.', 'pw-ofertas-avanzadas')],
                    ],
                ],
                [
                    "title"  => __('Product filters', 'pw-ofertas-avanzadas'),
                    "params" => [
                        ["category",  __('text', 'pw-ofertas-avanzadas'),  "—", __('Category slug(s), comma-separated.', 'pw-ofertas-avanzadas')],
                        ["tag",       __('text', 'pw-ofertas-avanzadas'),  "—", __('Tag slug(s), comma-separated.', 'pw-ofertas-avanzadas')],
                        ["min_price", __('number', 'pw-ofertas-avanzadas'), "—", __('Minimum product price.', 'pw-ofertas-avanzadas')],
                        ["max_price", __('number', 'pw-ofertas-avanzadas'), "—", __('Maximum product price.', 'pw-ofertas-avanzadas')],
                    ],
                ],
                [
                    "title"  => __('Display', 'pw-ofertas-avanzadas'),
                    "params" => [
                        ["limit",              __('number', 'pw-ofertas-avanzadas'),   "12",    __('Total products when pagination is off.', 'pw-ofertas-avanzadas')],
                        ["columns",            __('number', 'pw-ofertas-avanzadas'),   "4",     __('Grid columns (1–6).', 'pw-ofertas-avanzadas')],
                        ["orderby",            __('text', 'pw-ofertas-avanzadas'),    "date",  __('Sort by: date, price, name, rand.', 'pw-ofertas-avanzadas')],
                        ["order",              __('text', 'pw-ofertas-avanzadas'),    "DESC",  __('ASC or DESC.', 'pw-ofertas-avanzadas')],
                        ["show_badge",         __('boolean', 'pw-ofertas-avanzadas'), "true",  __('Show the discount badge on the image.', 'pw-ofertas-avanzadas')],
                        ["show_campaign_name", __('boolean', 'pw-ofertas-avanzadas'), "false", __('Show the campaign name under the title.', 'pw-ofertas-avanzadas')],
                    ],
                ],
                [
                    "title"  => __('Pagination', 'pw-ofertas-avanzadas'),
                    "params" => [
                        ["paginate", __('boolean', 'pw-ofertas-avanzadas'), "false", __('Enable pagination. Use per_page instead of limit.', 'pw-ofertas-avanzadas')],
                        ["per_page", __('number', 'pw-ofertas-avanzadas'),   "12",    __('Products per page when paginate="true".', 'pw-ofertas-avanzadas')],
                    ],
                ],
            ];

            foreach ($sections as $section) {
                $ui->card([
                    "title"   => $section["title"],
                    "content" => function () use ($section): void {
                        echo '<table class="wp-list-table widefat" style="width:100%;">';
                        echo '<thead><tr><th>' . esc_html__('Parameter', 'pw-ofertas-avanzadas') . '</th><th>' . esc_html__('Type', 'pw-ofertas-avanzadas') . '</th><th>' . esc_html__('Default', 'pw-ofertas-avanzadas') . '</th><th>' . esc_html__('Description', 'pw-ofertas-avanzadas') . '</th></tr></thead><tbody>';
                        foreach ($section["params"] as $p) {
                            echo '<tr>';
                            echo '<td><code>' . esc_html($p[0]) . '</code></td>';
                            echo '<td style="color:var(--pw-color-fg-muted);">' . esc_html($p[1]) . '</td>';
                            echo '<td style="color:var(--pw-color-fg-muted);">' . esc_html($p[2]) . '</td>';
                            echo '<td>' . esc_html($p[3]) . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    },
                ]);
                echo '<div style="margin-bottom:16px;"></div>';
            }

        } elseif ($tab === "ejemplos") {

            // ── Examples ──────────────────────────────────────────────────────
            $examples = [
                [__('All products on sale', 'pw-ofertas-avanzadas'),                                         "[pwoa_productos_oferta]"],
                [__('3-column grid with 6 products', 'pw-ofertas-avanzadas'),                                  "[pwoa_productos_oferta limit=\"6\" columns=\"3\"]"],
                [__('Specific category, price ascending', 'pw-ofertas-avanzadas'),                               "[pwoa_productos_oferta category=\"ropa\" orderby=\"price\" order=\"ASC\" columns=\"4\"]"],
                [__('Campaign name visible', 'pw-ofertas-avanzadas'),                                          "[pwoa_productos_oferta show_campaign_name=\"true\" limit=\"8\" columns=\"4\"]"],
                [__('Pagination, 9 products per page', 'pw-ofertas-avanzadas'),                                 "[pwoa_productos_oferta paginate=\"true\" per_page=\"9\" columns=\"3\"]"],
                [__('Specific campaign by ID', 'pw-ofertas-avanzadas'),                                              "[pwoa_productos_oferta campaign_id=\"1\" columns=\"4\" show_badge=\"true\"]"],
                [__('Price range with campaign label', 'pw-ofertas-avanzadas'),                           "[pwoa_productos_oferta min_price=\"1000\" max_price=\"10000\" show_campaign_name=\"true\" columns=\"3\"]"],
            ];

            $ui->card([
                "title"   => __('Usage examples', 'pw-ofertas-avanzadas'),
                "content" => function () use ($examples): void {
                    echo '<div style="display:flex;flex-direction:column;gap:12px;">';
                    foreach ($examples as $ex) {
                        echo '<div style="border:1px solid var(--pw-color-border-muted);border-radius:4px;overflow:hidden;">';
                        echo '<div style="padding:10px 14px;background:var(--pw-color-bg-subtle);display:flex;justify-content:space-between;align-items:center;">';
                        echo '<span style="font-size:13px;font-weight:600;color:var(--pw-color-fg-default);">' . esc_html($ex[0]) . '</span>';
                        echo '<button class="pwoa-copy-btn" type="button" data-code="' . esc_attr($ex[1]) . '"
                            style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:4px 10px;background:var(--pw-color-bg-canvas);border:1px solid var(--pw-color-border-default);border-radius:3px;cursor:pointer;color:var(--pw-color-fg-default);">' . esc_html__('Copy', 'pw-ofertas-avanzadas') . '</button>';
                        echo '</div>';
                        echo '<div style="padding:10px 14px;background:#030712;">';
                        echo '<code style="color:#fff;font-family:monospace;font-size:13px;">' . esc_html($ex[1]) . '</code>';
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                },
            ]);
        }
    },
]);

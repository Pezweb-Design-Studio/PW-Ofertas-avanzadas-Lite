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
    "title"     => "Shortcodes",
    "tabs"      => [
        ["slug" => "generador",  "label" => "Generador",   "href" => add_query_arg("tab", "generador",  $base_url), "active" => $tab === "generador"],
        ["slug" => "referencia", "label" => "Referencia",  "href" => add_query_arg("tab", "referencia", $base_url), "active" => $tab === "referencia"],
        ["slug" => "ejemplos",   "label" => "Ejemplos",    "href" => add_query_arg("tab", "ejemplos",   $base_url), "active" => $tab === "ejemplos"],
    ],
    "tabs_mode" => "url",
    "content"   => function ($bui) use ($campaigns, $tab, $base_url): void {
        $ui = $bui->ui();

        // ── Intro notice ──────────────────────────────────────────────────────
        $ui->notice([
            "type"    => "info",
            "message" => 'Shortcode principal: <code>[pwoa_productos_oferta]</code> — Insértalo en cualquier página, entrada o widget de texto.',
        ]);

        echo '<div style="margin-bottom:20px;"></div>';

        if ($tab === "generador" || $tab === "") {

            // ── Generator ────────────────────────────────────────────────────
            $ui->card([
                "title"       => "Generador de shortcode",
                "description" => "Configurá los parámetros y copiá el resultado.",
                "content"     => function () use ($campaigns): void {
                    echo '<div id="pwoa-generator" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">';

                    // Controls
                    echo '<div style="display:flex;flex-direction:column;gap:14px;">';

                    if (!empty($campaigns)) {
                        echo '<div>';
                        echo '<label style="display:block;font-size:12px;font-weight:600;color:var(--pw-color-fg-default);margin-bottom:6px;">Campaña específica <span style="color:var(--pw-color-fg-muted);font-weight:400;">(opcional)</span></label>';
                        echo '<select id="gen-campaign_id" style="width:100%;border:1px solid var(--pw-color-border-default);border-radius:4px;padding:8px 10px;background:var(--pw-color-bg-canvas);color:var(--pw-color-fg-default);font-size:13px;">';
                        echo '<option value="">— Todas las campañas activas —</option>';
                        foreach ($campaigns as $c) {
                            echo '<option value="' . esc_attr($c->id) . '">' . esc_html($c->name) . ' (ID: ' . (int) $c->id . ')</option>';
                        }
                        echo '</select>';
                        echo '</div>';
                    }

                    $fields = [
                        ["gen-limit",    "number", "Cantidad de productos", "12",   "1",  "100"],
                        ["gen-columns",  "select", "Columnas",              "4",    null, null],
                        ["gen-orderby",  "select", "Ordenar por",           "date", null, null],
                        ["gen-order",    "select", "Orden",                 "DESC", null, null],
                        ["gen-category", "text",   "Categoría (slug)",      "",     null, null],
                        ["gen-min_price","number", "Precio mínimo",         "",     null, null],
                        ["gen-max_price","number", "Precio máximo",         "",     null, null],
                    ];

                    $select_options = [
                        "gen-columns" => ["2" => "2", "3" => "3", "4" => "4 (default)", "5" => "5", "6" => "6"],
                        "gen-orderby" => ["date" => "Fecha", "price" => "Precio", "name" => "Nombre", "rand" => "Aleatorio"],
                        "gen-order"   => ["DESC" => "Descendente", "ASC" => "Ascendente"],
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
                        ["gen-show_badge",         true,  "Mostrar badge de descuento"],
                        ["gen-show_campaign_name", false, "Mostrar nombre de campaña"],
                        ["gen-paginate",           false, "Activar paginación"],
                    ];
                    echo '<div style="display:flex;flex-direction:column;gap:8px;">';
                    foreach ($checks as [$id, $checked, $label]) {
                        echo '<label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--pw-color-fg-default);">';
                        echo '<input type="checkbox" id="' . esc_attr($id) . '"' . ($checked ? ' checked' : '') . ' style="width:14px;height:14px;">';
                        echo esc_html($label) . '</label>';
                    }
                    echo '</div>';

                    echo '<div id="gen-per_page-wrap" style="display:none;">';
                    echo '<label for="gen-per_page" style="' . $label_style . '">Productos por página</label>';
                    echo '<input type="number" id="gen-per_page" value="12" min="1" max="100" style="' . $input_style . '">';
                    echo '</div>';

                    echo '</div>'; // end controls

                    // Output
                    echo '<div style="display:flex;flex-direction:column;">';
                    echo '<label style="' . $label_style . '">Shortcode generado</label>';
                    echo '<div style="background:#030712;border-radius:6px;padding:20px;display:flex;flex-direction:column;justify-content:space-between;min-height:160px;">';
                    echo '<code id="gen-output" style="color:#fff;font-family:monospace;font-size:13px;word-break:break-all;white-space:pre-wrap;">[pwoa_productos_oferta]</code>';
                    echo '<div style="margin-top:16px;text-align:right;">';
                    echo '<button id="gen-copy-btn" type="button"
                        style="padding:8px 16px;background:var(--pw-color-accent-emphasis);color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:13px;font-weight:600;">';
                    echo '<span id="gen-copy-label">Copiar</span></button>';
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
                    "title"  => "Filtros de campaña",
                    "params" => [
                        ["campaign_id", "número", "—",   "Filtra por ID de campaña. Si se omite, usa todas las campañas activas."],
                        ["strategy",    "texto",  "—",   "Filtra por tipo de estrategia: basic_discount, bulk_discount, buy_x_pay_y, etc."],
                    ],
                ],
                [
                    "title"  => "Filtros de producto",
                    "params" => [
                        ["category",  "texto",  "—", "Slug(s) de categoría separados por coma."],
                        ["tag",       "texto",  "—", "Slug(s) de etiqueta separados por coma."],
                        ["min_price", "número", "—", "Precio mínimo del producto."],
                        ["max_price", "número", "—", "Precio máximo del producto."],
                    ],
                ],
                [
                    "title"  => "Visualización",
                    "params" => [
                        ["limit",              "número",   "12",    "Cantidad total de productos (sin paginación)."],
                        ["columns",            "número",   "4",     "Columnas de la grilla (1–6)."],
                        ["orderby",            "texto",    "date",  "Orden: date, price, name, rand."],
                        ["order",              "texto",    "DESC",  "ASC o DESC."],
                        ["show_badge",         "booleano", "true",  "Muestra el badge de descuento sobre la imagen."],
                        ["show_campaign_name", "booleano", "false", "Muestra el nombre de la campaña bajo el título."],
                    ],
                ],
                [
                    "title"  => "Paginación",
                    "params" => [
                        ["paginate", "booleano", "false", "Activa la paginación. Usa per_page en lugar de limit."],
                        ["per_page", "número",   "12",    "Productos por página cuando paginate=\"true\"."],
                    ],
                ],
            ];

            foreach ($sections as $section) {
                $ui->card([
                    "title"   => $section["title"],
                    "content" => function () use ($section): void {
                        echo '<table class="wp-list-table widefat" style="width:100%;">';
                        echo '<thead><tr><th>Parámetro</th><th>Tipo</th><th>Default</th><th>Descripción</th></tr></thead><tbody>';
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
                ["Todos los productos en oferta",                                         "[pwoa_productos_oferta]"],
                ["Grilla de 3 columnas con 6 productos",                                  "[pwoa_productos_oferta limit=\"6\" columns=\"3\"]"],
                ["Categoría específica, precio ascendente",                               "[pwoa_productos_oferta category=\"ropa\" orderby=\"price\" order=\"ASC\" columns=\"4\"]"],
                ["Con nombre de campaña visible",                                          "[pwoa_productos_oferta show_campaign_name=\"true\" limit=\"8\" columns=\"4\"]"],
                ["Con paginación, 9 productos por página",                                 "[pwoa_productos_oferta paginate=\"true\" per_page=\"9\" columns=\"3\"]"],
                ["Campaña específica por ID",                                              "[pwoa_productos_oferta campaign_id=\"1\" columns=\"4\" show_badge=\"true\"]"],
                ["Por rango de precio con etiqueta de campaña",                           "[pwoa_productos_oferta min_price=\"1000\" max_price=\"10000\" show_campaign_name=\"true\" columns=\"3\"]"],
            ];

            $ui->card([
                "title"   => "Ejemplos de uso",
                "content" => function () use ($examples): void {
                    echo '<div style="display:flex;flex-direction:column;gap:12px;">';
                    foreach ($examples as $ex) {
                        echo '<div style="border:1px solid var(--pw-color-border-muted);border-radius:4px;overflow:hidden;">';
                        echo '<div style="padding:10px 14px;background:var(--pw-color-bg-subtle);display:flex;justify-content:space-between;align-items:center;">';
                        echo '<span style="font-size:13px;font-weight:600;color:var(--pw-color-fg-default);">' . esc_html($ex[0]) . '</span>';
                        echo '<button class="pwoa-copy-btn" type="button" data-code="' . esc_attr($ex[1]) . '"
                            style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:4px 10px;background:var(--pw-color-bg-canvas);border:1px solid var(--pw-color-border-default);border-radius:3px;cursor:pointer;color:var(--pw-color-fg-default);">Copiar</button>';
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
?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Copy examples ──────────────────────────────────────────────────────
    document.querySelectorAll('.pwoa-copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            navigator.clipboard.writeText(btn.dataset.code).then(function () {
                const orig = btn.textContent;
                btn.textContent = '¡Copiado!';
                btn.style.background = 'var(--pw-color-success-subtle)';
                btn.style.borderColor = 'var(--pw-color-success-muted)';
                btn.style.color = 'var(--pw-color-success-fg)';
                setTimeout(function () {
                    btn.textContent = orig;
                    btn.style.background = '';
                    btn.style.borderColor = '';
                    btn.style.color = '';
                }, 1500);
            });
        });
    });

    // ── Generator ──────────────────────────────────────────────────────────
    var output      = document.getElementById('gen-output');
    var copyBtn     = document.getElementById('gen-copy-btn');
    var copyLbl     = document.getElementById('gen-copy-label');
    var paginateChk = document.getElementById('gen-paginate');
    var perPageWrap = document.getElementById('gen-per_page-wrap');

    if (!output) return;

    var defaults = { limit: '12', columns: '4', orderby: 'date', order: 'DESC', show_badge: true, show_campaign_name: false, paginate: false, per_page: '12' };

    function getVal(id) {
        var el = document.getElementById(id);
        if (!el) return null;
        return el.type === 'checkbox' ? el.checked : el.value.trim();
    }

    function build() {
        var sc = '[pwoa_productos_oferta';
        var campaign_id = getVal('gen-campaign_id');
        if (campaign_id) sc += ' campaign_id="' + campaign_id + '"';
        var limit = getVal('gen-limit');
        if (limit && limit !== defaults.limit) sc += ' limit="' + limit + '"';
        var columns = getVal('gen-columns');
        if (columns && columns !== defaults.columns) sc += ' columns="' + columns + '"';
        var orderby = getVal('gen-orderby');
        if (orderby && orderby !== defaults.orderby) sc += ' orderby="' + orderby + '"';
        var order = getVal('gen-order');
        if (order && order !== defaults.order) sc += ' order="' + order + '"';
        var category = getVal('gen-category');
        if (category) sc += ' category="' + category + '"';
        var min_p = getVal('gen-min_price');
        if (min_p) sc += ' min_price="' + min_p + '"';
        var max_p = getVal('gen-max_price');
        if (max_p) sc += ' max_price="' + max_p + '"';
        if (!getVal('gen-show_badge')) sc += ' show_badge="false"';
        if (getVal('gen-show_campaign_name')) sc += ' show_campaign_name="true"';
        if (getVal('gen-paginate')) {
            sc += ' paginate="true"';
            var pp = getVal('gen-per_page');
            if (pp && pp !== defaults.per_page) sc += ' per_page="' + pp + '"';
        }
        return sc + ']';
    }

    function refresh() { if (output) output.textContent = build(); }

    if (paginateChk) {
        paginateChk.addEventListener('change', function () {
            if (perPageWrap) perPageWrap.style.display = paginateChk.checked ? 'block' : 'none';
            refresh();
        });
    }

    var gen = document.getElementById('pwoa-generator');
    if (gen) {
        gen.querySelectorAll('input, select').forEach(function (el) {
            el.addEventListener('change', refresh);
            el.addEventListener('input', refresh);
        });
    }

    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            navigator.clipboard.writeText(output.textContent).then(function () {
                copyLbl.textContent = '¡Copiado!';
                copyBtn.style.background = 'var(--pw-color-success-emphasis)';
                setTimeout(function () {
                    copyLbl.textContent = 'Copiar';
                    copyBtn.style.background = '';
                }, 1500);
            });
        });
    }

    refresh();
});
</script>

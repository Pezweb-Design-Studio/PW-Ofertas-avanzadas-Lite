<?php
if (!defined("ABSPATH")) {
    exit();
}

$campaigns = \PW\OfertasAvanzadas\Repositories\CampaignRepository::getActive();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Shortcodes disponibles</h1>
    <hr class="wp-header-end">

    <div class="max-w-5xl mt-8 space-y-8">

        <!-- Intro -->
        <div class="bg-white rounded-lg shadow p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                <code class="bg-gray-100 text-gray-900 px-3 py-1 rounded text-xl font-mono">[pwoa_productos_oferta]</code>
            </h2>
            <p class="text-gray-600 text-base leading-relaxed mb-6">
                Muestra una grilla de productos WooCommerce vinculados a tus campañas activas. Podés insertarlo en cualquier página, entrada, widget de texto o bloque HTML de Elementor/Divi.
            </p>

            <ul class="space-y-3">
                <li>
                    <span class="font-semibold text-gray-900">Gutenberg</span>
                    <span class="text-gray-600"> — Bloque "Código corto"</span>
                </li>
                <li>
                    <span class="font-semibold text-gray-900">Elementor</span>
                    <span class="text-gray-600"> — Widget "Shortcode"</span>
                </li>
                <li>
                    <span class="font-semibold text-gray-900">Editor clásico</span>
                    <span class="text-gray-600"> — Directamente en el contenido</span>
                </li>
            </ul>
        </div>

        <!-- Generador -->
        <div class="bg-white rounded-lg shadow p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-1">Generador de shortcode</h2>
            <p class="text-gray-500 text-sm mb-6">Configurá los parámetros y copiá el resultado.</p>

            <div class="grid grid-cols-2 gap-6" id="pwoa-generator">

                <!-- Columna izquierda: controles -->
                <div class="space-y-4">

                    <?php if (!empty($campaigns)): ?>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Campaña específica <span class="text-gray-400 font-normal">(opcional)</span></label>
                        <select id="gen-campaign_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                            <option value="">— Todas las campañas activas —</option>
                            <?php foreach ($campaigns as $c): ?>
                            <option value="<?php echo esc_attr($c->id); ?>"><?php echo esc_html($c->name); ?> (ID: <?php echo (int)$c->id; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Cantidad de productos</label>
                            <input type="number" id="gen-limit" value="12" min="1" max="100" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Columnas</label>
                            <select id="gen-columns" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4" selected>4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Ordenar por</label>
                            <select id="gen-orderby" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="date">Fecha</option>
                                <option value="price">Precio</option>
                                <option value="name">Nombre</option>
                                <option value="rand">Aleatorio</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Orden</label>
                            <select id="gen-order" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="DESC">Descendente</option>
                                <option value="ASC">Ascendente</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Categoría <span class="text-gray-400 font-normal">(slug, separado por comas)</span></label>
                        <input type="text" id="gen-category" placeholder="ej: ropa, calzado" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Precio mínimo</label>
                            <input type="number" id="gen-min_price" placeholder="ej: 1000" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Precio máximo</label>
                            <input type="number" id="gen-max_price" placeholder="ej: 50000" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div class="space-y-3 pt-2">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" id="gen-show_badge" checked class="w-4 h-4 accent-blue-600">
                            <span class="text-sm text-gray-700">Mostrar badge de descuento</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" id="gen-show_campaign_name" class="w-4 h-4 accent-blue-600">
                            <span class="text-sm text-gray-700">Mostrar nombre de campaña</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" id="gen-paginate" class="w-4 h-4 accent-blue-600">
                            <span class="text-sm text-gray-700">Activar paginación</span>
                        </label>
                    </div>

                    <div id="gen-per_page-wrap" class="hidden">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Productos por página</label>
                        <input type="number" id="gen-per_page" value="12" min="1" max="100" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                    </div>
                </div>

                <!-- Columna derecha: resultado -->
                <div class="flex flex-col">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Shortcode generado</label>
                    <div class="bg-gray-950 rounded-lg p-6 flex-1 flex flex-col justify-between min-h-32">
                        <code id="gen-output" class="text-white text-sm font-mono break-all leading-relaxed whitespace-pre-wrap">[pwoa_productos_oferta]</code>
                        <button id="gen-copy-btn" class="mt-4 self-end bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition">
                            <span id="gen-copy-label">Copiar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de parámetros -->
        <div class="bg-white rounded-lg shadow p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Parámetros disponibles</h2>

            <!-- Filtros de campaña -->
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3">Filtros de campaña</h3>
            <div class="overflow-x-auto mb-8">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="text-left px-4 py-3 font-semibold text-gray-700 border-b">Parámetro</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700 border-b">Tipo</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700 border-b">Default</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700 border-b">Descripción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><code class="bg-gray-100 text-gray-900 font-mono px-2 py-0.5 rounded text-xs">campaign_id</code></td>
                            <td class="px-4 py-3 text-gray-500">número</td>
                            <td class="px-4 py-3 text-gray-400">—</td>
                            <td class="px-4 py-3 text-gray-700">Muestra solo productos de una campaña específica. Si se omite, usa todas las campañas activas.</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><code class="bg-gray-100 text-gray-900 font-mono px-2 py-0.5 rounded text-xs">strategy</code></td>
                            <td class="px-4 py-3 text-gray-500">texto</td>
                            <td class="px-4 py-3 text-gray-400">—</td>
                            <td class="px-4 py-3 text-gray-700">Filtra por tipo de estrategia: <code class="bg-gray-100 text-gray-900 font-mono px-1 rounded text-xs">basic_discount</code>, <code class="bg-gray-100 text-gray-900 font-mono px-1 rounded text-xs">bulk_discount</code>, <code class="bg-gray-100 text-gray-900 font-mono px-1 rounded text-xs">buy_x_pay_y</code>, <code class="bg-gray-100 text-gray-900 font-mono px-1 rounded text-xs">expiry_based</code>, etc.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Filtros de producto -->
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3">Filtros de producto</h3>
            <div class="overflow-x-auto mb-8">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="text-left px-4 py-3 font-semibold text-gray-700 border-b">Parámetro</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700 border-b">Tipo</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700 border-b">Default</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700 border-b">Descripción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><code class="bg-gray-100 text-gray-900 font-mono px-2 py-0.5 rounded text-xs">category</code></td>
                            <td class="px-4 py-3 text-gray-500">texto</td>
                            <td class="px-4 py-3 text-gray-400">—</td>
                            <td class="px-4 py-3 text-gray-700">Slug(s) de categoría separados por coma. Ej: <code class="bg-gray-100 text-gray-900 font-mono px-1 rounded text-xs">ropa,calzado</code></td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><code class="bg-gray-100 text-gray-900 font-mono px-2 py-0.5 rounded text-xs">tag</code></td>
                            <td class="px-4 py-3 text-gray-500">texto</td>
                            <td class="px-4 py-3 text-gray-400">—</td>
                            <td class="px-4 py-3 text-gray-700">Slug(s) de etiqueta separados por coma.</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><code class="bg-gray-100 text-gray-900 font-mono px-2 py-0.5 rounded text-xs">min_price</code></td>
                            <td class="px-4 py-3 text-gray-500">número</td>
                            <td class="px-4 py-3 text-gray-400">—</td>
                            <td class="px-4 py-3 text-gray-700">Precio mínimo del producto.</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><code class="bg-gray-100 text-gray-900 font-mono px-2 py-0.5 rounded text-xs">max_price</code></td>
                            <td class="px-4 py-3 text-gray-500">número</td>
                            <td class="px-4 py-3 text-gray-400">—</td>
                            <td class="px-4 py-3 text-gray-700">Precio máximo del producto.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Visualización -->
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3">Visualización</h3>
            <div class="overflow-x-auto mb-8">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="text-left px-4 py-3 font-semibold text-gray-700 border-b">Parámetro</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700 border-b">Tipo</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700 border-b">Default</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700 border-b">Descripción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><code class="bg-gray-100 text-gray-900 font-mono px-2 py-0.5 rounded text-xs">limit</code></td>
                            <td class="px-4 py-3 text-gray-500">número</td>
                            <td class="px-4 py-3 text-gray-400">12</td>
                            <td class="px-4 py-3 text-gray-700">Cantidad total de productos a mostrar (cuando no hay paginación).</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><code class="bg-gray-100 text-gray-900 font-mono px-2 py-0.5 rounded text-xs">columns</code></td>
                            <td class="px-4 py-3 text-gray-500">número</td>
                            <td class="px-4 py-3 text-gray-400">4</td>
                            <td class="px-4 py-3 text-gray-700">Columnas de la grilla. Valores: 1 a 6.</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><code class="bg-gray-100 text-gray-900 font-mono px-2 py-0.5 rounded text-xs">orderby</code></td>
                            <td class="px-4 py-3 text-gray-500">texto</td>
                            <td class="px-4 py-3 text-gray-400">date</td>
                            <td class="px-4 py-3 text-gray-700">Ordenamiento: <code class="bg-gray-100 text-gray-900 font-mono px-1 rounded text-xs">date</code>, <code class="bg-gray-100 text-gray-900 font-mono px-1 rounded text-xs">price</code>, <code class="bg-gray-100 text-gray-900 font-mono px-1 rounded text-xs">name</code>, <code class="bg-gray-100 text-gray-900 font-mono px-1 rounded text-xs">rand</code>.</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><code class="bg-gray-100 text-gray-900 font-mono px-2 py-0.5 rounded text-xs">order</code></td>
                            <td class="px-4 py-3 text-gray-500">texto</td>
                            <td class="px-4 py-3 text-gray-400">DESC</td>
                            <td class="px-4 py-3 text-gray-700"><code class="bg-gray-100 text-gray-900 font-mono px-1 rounded text-xs">ASC</code> o <code class="bg-gray-100 text-gray-900 font-mono px-1 rounded text-xs">DESC</code>.</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><code class="bg-gray-100 text-gray-900 font-mono px-2 py-0.5 rounded text-xs">show_badge</code></td>
                            <td class="px-4 py-3 text-gray-500">booleano</td>
                            <td class="px-4 py-3 text-gray-400">true</td>
                            <td class="px-4 py-3 text-gray-700">Muestra el badge de descuento sobre la imagen del producto.</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><code class="bg-gray-100 text-gray-900 font-mono px-2 py-0.5 rounded text-xs">show_campaign_name</code></td>
                            <td class="px-4 py-3 text-gray-500">booleano</td>
                            <td class="px-4 py-3 text-gray-400">false</td>
                            <td class="px-4 py-3 text-gray-700">Muestra el nombre de la campaña asociada debajo del título del producto.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3">Paginación</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="text-left px-4 py-3 font-semibold text-gray-700 border-b">Parámetro</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700 border-b">Tipo</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700 border-b">Default</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700 border-b">Descripción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><code class="bg-gray-100 text-gray-900 font-mono px-2 py-0.5 rounded text-xs">paginate</code></td>
                            <td class="px-4 py-3 text-gray-500">booleano</td>
                            <td class="px-4 py-3 text-gray-400">false</td>
                            <td class="px-4 py-3 text-gray-700">Activa la paginación. Usa <code class="bg-gray-100 text-gray-900 font-mono px-1 rounded text-xs">per_page</code> en lugar de <code class="bg-gray-100 text-gray-900 font-mono px-1 rounded text-xs">limit</code>.</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><code class="bg-gray-100 text-gray-900 font-mono px-2 py-0.5 rounded text-xs">per_page</code></td>
                            <td class="px-4 py-3 text-gray-500">número</td>
                            <td class="px-4 py-3 text-gray-400">12</td>
                            <td class="px-4 py-3 text-gray-700">Productos por página cuando <code class="bg-gray-100 text-gray-900 font-mono px-1 rounded text-xs">paginate="true"</code>.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ejemplos -->
        <div class="bg-white rounded-lg shadow p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Ejemplos de uso</h2>

            <div class="space-y-4">
                <?php
                $examples = [
                    [
                        'label' => 'Todos los productos en oferta (configuración básica)',
                        'code'  => '[pwoa_productos_oferta]',
                    ],
                    [
                        'label' => 'Grilla de 3 columnas con 6 productos',
                        'code'  => '[pwoa_productos_oferta limit="6" columns="3"]',
                    ],
                    [
                        'label' => 'Productos de una categoría, ordenados por precio ascendente',
                        'code'  => '[pwoa_productos_oferta category="ropa" orderby="price" order="ASC" columns="4"]',
                    ],
                    [
                        'label' => 'Con nombre de campaña visible',
                        'code'  => '[pwoa_productos_oferta show_campaign_name="true" limit="8" columns="4"]',
                    ],
                    [
                        'label' => 'Con paginación, 9 productos por página',
                        'code'  => '[pwoa_productos_oferta paginate="true" per_page="9" columns="3"]',
                    ],
                    [
                        'label' => 'Productos de una campaña específica',
                        'code'  => '[pwoa_productos_oferta campaign_id="1" columns="4" show_badge="true"]',
                    ],
                    [
                        'label' => 'Por rango de precio con etiqueta de campaña',
                        'code'  => '[pwoa_productos_oferta min_price="1000" max_price="10000" show_campaign_name="true" columns="3"]',
                    ],
                ];

                foreach ($examples as $i => $example):
                ?>
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-gray-100 px-4 py-3 flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-900"><?php echo esc_html($example['label']); ?></span>
                        <button
                            class="pwoa-copy-btn bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded text-xs font-semibold uppercase tracking-wide cursor-pointer transition"
                            data-code="<?php echo esc_attr($example['code']); ?>">
                            Copiar
                        </button>
                    </div>
                    <div class="bg-gray-950 px-4 py-3">
                        <code class="text-white font-mono text-sm"><?php echo esc_html($example['code']); ?></code>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- /.max-w-5xl -->
</div><!-- /.wrap -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Copiar ejemplos ──────────────────────────────
    document.querySelectorAll('.pwoa-copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const code = btn.getAttribute('data-code');
            navigator.clipboard.writeText(code).then(function () {
                const original = btn.textContent;
                btn.textContent = '¡Copiado!';
                btn.classList.add('bg-green-50', 'border-green-300', 'text-green-700');
                btn.classList.remove('bg-white', 'border-gray-300', 'text-gray-700');
                setTimeout(function () {
                    btn.textContent = original;
                    btn.classList.remove('bg-green-50', 'border-green-300', 'text-green-700');
                    btn.classList.add('bg-white', 'border-gray-300', 'text-gray-700');
                }, 1500);
            });
        });
    });

    // ── Generador ────────────────────────────────────
    const output   = document.getElementById('gen-output');
    const copyBtn  = document.getElementById('gen-copy-btn');
    const copyLbl  = document.getElementById('gen-copy-label');
    const paginateChk = document.getElementById('gen-paginate');
    const perPageWrap = document.getElementById('gen-per_page-wrap');

    if (!output) return;

    function getVal(id) {
        const el = document.getElementById(id);
        if (!el) return null;
        if (el.type === 'checkbox') return el.checked;
        return el.value.trim();
    }

    const defaults = {
        limit:              '12',
        columns:            '4',
        orderby:            'date',
        order:              'DESC',
        show_badge:         true,
        show_campaign_name: false,
        paginate:           false,
        per_page:           '12',
    };

    function buildShortcode() {
        let sc = '[pwoa_productos_oferta';

        const campaign_id = getVal('gen-campaign_id');
        if (campaign_id) sc += ' campaign_id="' + campaign_id + '"';

        const limit = getVal('gen-limit');
        if (limit && limit !== defaults.limit) sc += ' limit="' + limit + '"';

        const columns = getVal('gen-columns');
        if (columns && columns !== defaults.columns) sc += ' columns="' + columns + '"';

        const orderby = getVal('gen-orderby');
        if (orderby && orderby !== defaults.orderby) sc += ' orderby="' + orderby + '"';

        const order = getVal('gen-order');
        if (order && order !== defaults.order) sc += ' order="' + order + '"';

        const category = getVal('gen-category');
        if (category) sc += ' category="' + category + '"';

        const min_price = getVal('gen-min_price');
        if (min_price) sc += ' min_price="' + min_price + '"';

        const max_price = getVal('gen-max_price');
        if (max_price) sc += ' max_price="' + max_price + '"';

        const show_badge = getVal('gen-show_badge');
        if (!show_badge) sc += ' show_badge="false"';

        const show_campaign_name = getVal('gen-show_campaign_name');
        if (show_campaign_name) sc += ' show_campaign_name="true"';

        const paginate = getVal('gen-paginate');
        if (paginate) {
            sc += ' paginate="true"';
            const per_page = getVal('gen-per_page');
            if (per_page && per_page !== defaults.per_page) sc += ' per_page="' + per_page + '"';
        }

        sc += ']';
        return sc;
    }

    function refresh() {
        output.textContent = buildShortcode();
    }

    // Toggle per_page visibility
    if (paginateChk) {
        paginateChk.addEventListener('change', function () {
            perPageWrap.classList.toggle('hidden', !paginateChk.checked);
            refresh();
        });
    }

    // Listen all inputs/selects/checkboxes in the generator
    document.querySelectorAll('#pwoa-generator input, #pwoa-generator select').forEach(function (el) {
        el.addEventListener('change', refresh);
        el.addEventListener('input', refresh);
    });

    // Copy generated shortcode
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            navigator.clipboard.writeText(output.textContent).then(function () {
                copyLbl.textContent = '¡Copiado!';
                copyBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                copyBtn.classList.add('bg-green-600');
                setTimeout(function () {
                    copyLbl.textContent = 'Copiar';
                    copyBtn.classList.remove('bg-green-600');
                    copyBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                }, 1500);
            });
        });
    }

    refresh();
});
</script>

<style>
.max-w-5xl { max-width: 64rem; }
.space-y-8 > * + * { margin-top: 2rem; }
.space-y-4 > * + * { margin-top: 1rem; }
.space-y-3 > * + * { margin-top: 0.75rem; }
.divide-y > * + * { border-top: 1px solid #f3f4f6; }
.pwoa-pagination { margin-top: 2rem; text-align: center; }
.pwoa-campaign-label { display: block; font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; }
.bg-gray-950 { background-color: #030712; }
</style>

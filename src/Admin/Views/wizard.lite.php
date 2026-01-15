<?php
if (!defined('ABSPATH')) exit;

// ⚡ Detectar modo edición
$is_edit_mode = isset($_GET['edit']) && !empty($_GET['edit']);

// ⚠️ LITE: Mostrar banner de campañas restantes
$total_campaigns = \PW\OfertasAvanzadas\Repositories\CampaignRepository::getCount();
$remaining_slots = max(0, 5 - $total_campaigns);

$objectives = [
    'basic' => [
        'title' => 'Básico',
        'desc' => 'Descuento simple por porcentaje o monto fijo a productos seleccionados',
        'available' => true
    ],
    'aov' => [
        'title' => 'Aumentar Valor del Carrito',
        'desc' => 'Incrementa el ticket promedio con descuentos estratégicos',
        'available' => true
    ],
    'liquidation' => [
        'title' => 'Liquidar Inventario',
        'desc' => 'Mueve stock que no rota o está próximo a vencer',
        'available' => true
    ],
    'loyalty' => [
        'title' => 'Fidelización',
        'desc' => 'Recompensa clientes recurrentes y genera lealtad',
        'available' => false // ⚠️ LITE: Bloqueado
    ],
    'urgency' => [
        'title' => 'Conversión Rápida',
        'desc' => 'Genera urgencia y aumenta ventas inmediatas',
        'available' => false // ⚠️ LITE: Bloqueado
    ]
];
?>

<style>
    .pwoa-wizard input[type="text"],
    .pwoa-wizard input[type="number"],
    .pwoa-wizard input[type="datetime-local"],
    .pwoa-wizard select {
        height: 48px !important;
        font-size: 15px !important;
    }

    .pwoa-btn-primary {
        background-color: #3b82f6 !important;
        color: white !important;
        transition: all 0.2s ease !important;
    }
    .pwoa-btn-primary:hover {
        background-color: #2563eb !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4) !important;
    }

    .pwoa-btn-secondary {
        background-color: #e5e7eb !important;
        color: #374151 !important;
        transition: all 0.2s ease !important;
    }
    .pwoa-btn-secondary:hover {
        background-color: #d1d5db !important;
    }

    /* ⚠️ LITE: Estilos para objectives bloqueados */
    .objective-btn.locked {
        opacity: 0.6;
        cursor: not-allowed;
        position: relative;
    }
    .objective-btn.locked::after {
        content: '🔒 PRO';
        position: absolute;
        top: 8px;
        right: 8px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: bold;
    }
</style>

<div class="pwoa-wizard max-w-5xl mx-auto p-12">

    <!-- ⚠️ LITE: Banner de campañas restantes -->
    <?php if (!$is_edit_mode && $remaining_slots <= 2): ?>
        <div class="mb-8 bg-gradient-to-r from-orange-50 to-red-50 border-2 border-orange-200 rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-orange-900 mb-1">
                        <?php if ($remaining_slots == 0): ?>
                            ⚠️ Límite alcanzado
                        <?php else: ?>
                            ⏰ <?php echo $remaining_slots; ?> <?php echo $remaining_slots == 1 ? 'campaña restante' : 'campañas restantes'; ?>
                        <?php endif; ?>
                    </h3>
                    <p class="text-sm text-orange-700">
                        Versión Lite: máximo 5 campañas.
                        <strong>Actualiza a Pro</strong> para campañas ilimitadas + 6 estrategias avanzadas.
                    </p>
                </div>
                <a href="https://tu-sitio.com/pro" target="_blank"
                   class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:from-blue-700 hover:to-purple-700 transition whitespace-nowrap">
                    Ver Pro →
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Breadcrumb -->
    <nav id="breadcrumb" class="mb-12 pb-6 border-b border-gray-200 hidden">
        <ol class="flex items-center space-x-2 text-sm text-gray-500">
            <li>
                <button type="button" id="crumb-objective" class="hover:text-blue-600 transition">
                    Objetivo
                </button>
            </li>
            <li id="crumb-strategy-wrapper" class="hidden">
                <span class="mx-2">/</span>
                <button type="button" id="crumb-strategy" class="hover:text-blue-600 transition">
                    Estrategia
                </button>
            </li>
            <li id="crumb-config-wrapper" class="hidden">
                <span class="mx-2">/</span>
                <span id="crumb-config" class="text-gray-900 font-medium">Configuración</span>
            </li>
        </ol>
    </nav>

    <!-- Step 1: Objetivo -->
    <div id="step-objective" class="<?php echo $is_edit_mode ? 'hidden' : ''; ?>">
        <h1 class="text-4xl font-bold mb-12">¿Qué quieres lograr?</h1>

        <div class="grid grid-cols-2 gap-8">
            <?php foreach ($objectives as $key => $obj): ?>
                <button
                    type="button"
                    class="objective-btn text-left bg-white p-8 rounded-lg shadow hover:shadow-xl transition border-2 border-transparent hover:border-blue-500 <?php echo !$obj['available'] ? 'locked' : ''; ?>"
                    data-objective="<?php echo esc_attr($key); ?>"
                    data-title="<?php echo esc_attr($obj['title']); ?>"
                    data-available="<?php echo $obj['available'] ? '1' : '0'; ?>">
                    <h3 class="text-2xl font-bold mb-3"><?php echo esc_html($obj['title']); ?></h3>
                    <p class="text-gray-600"><?php echo esc_html($obj['desc']); ?></p>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Step 2: Estrategia -->
    <div id="step-strategy" class="hidden">
        <h1 class="text-4xl font-bold mb-3" id="selected-objective-title"></h1>
        <p class="text-lg text-gray-500 mb-12">Selecciona una estrategia</p>

        <div id="strategies-list"></div>
    </div>

    <!-- Step 3: Configuración -->
    <div id="step-config" class="<?php echo $is_edit_mode ? '' : 'hidden'; ?>">
        <h1 class="text-4xl font-bold mb-3" id="selected-strategy-title"></h1>
        <p class="text-lg text-gray-500 mb-12">Configura los parámetros de tu campaña</p>

        <form id="campaign-form" class="bg-white p-8 rounded-lg shadow space-y-6">

            <div>
                <label class="block text-sm font-bold mb-2">Nombre de la campaña</label>
                <input type="text" name="name" id="form-name" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                       placeholder="Ej: Black Friday 2024 - Descuento por volumen">
            </div>

            <div id="dynamic-fields"></div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold mb-2">Fecha de inicio (opcional)</label>
                    <input type="datetime-local" name="start_date" id="form-start-date"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Fecha de fin (opcional)</label>
                    <input type="datetime-local" name="end_date" id="form-end-date"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold mb-2">Modo de aplicación</label>
                <select name="stacking_mode" id="form-stacking-mode" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="priority">Prioridad (mejor descuento)</option>
                    <option value="stack">Apilar descuentos</option>
                </select>
                <p class="text-sm text-gray-500 mt-1">Si hay múltiples campañas activas, ¿aplicar solo la mejor o sumarlas?</p>
            </div>

            <!-- FILTRADO DE PRODUCTOS -->
            <div id="product-filters-section" class="border-t pt-6 mt-6">
                <h3 class="text-lg font-bold mb-4">Filtrar productos (opcional)</h3>
                <p class="text-sm text-gray-600 mb-6">Si no configuras filtros, el descuento se aplicará a todos los productos del carrito</p>

                <!-- Búsqueda de productos -->
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Productos específicos</label>
                    <input type="text"
                           id="product-search"
                           placeholder="Buscar por nombre, SKU o ID..."
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <div id="product-search-results" class="mt-2 max-h-48 overflow-y-auto border rounded-lg hidden"></div>
                    <div id="selected-products" class="mt-3 flex flex-wrap gap-2"></div>
                    <input type="hidden" id="form-product-ids" name="conditions[product_ids]">
                </div>

                <!-- Categorías -->
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Categorías</label>
                    <select id="form-categories" multiple class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" style="height: 120px;">
                        <?php
                        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
                        foreach ($categories as $cat) {
                            echo '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
                        }
                        ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Mantén presionado Ctrl/Cmd para seleccionar múltiples</p>
                </div>

                <!-- Rango de precio -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-bold mb-2">Precio mínimo</label>
                        <input type="number"
                               id="form-min-price"
                               placeholder="0"
                               step="0.01"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Precio máximo</label>
                        <input type="number"
                               id="form-max-price"
                               placeholder="999999"
                               step="0.01"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Validación de productos -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-900">
                        <span class="font-bold">Productos que cumplen criterios:</span>
                        <span id="matching-count" class="ml-2 font-mono">-</span>
                    </p>
                    <div class="mt-3">
                        <button type="button"
                                id="btn-show-products"
                                class="text-sm bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            Ver productos filtrados
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
                    Crear Campaña
                </button>
                <button type="button" id="btn-cancel"
                        class="pwoa-btn-secondary px-8 py-3 rounded-lg font-semibold">
                    Cancelar
                </button>
            </div>

        </form>
    </div>


    <!-- Modal de productos coincidentes -->
    <div id="products-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[80vh] flex flex-col">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900">Productos que coinciden con el filtro</h3>
                <button type="button" id="close-modal" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">×</button>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto flex-1">
                <div id="modal-products-list" class="space-y-2">
                    <!-- Los productos se cargan aquí -->
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                <p class="text-sm text-gray-600">
                    Total: <span id="modal-count" class="font-bold text-gray-900">0</span> productos
                </p>
                <button type="button" id="close-modal-btn" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

</div>
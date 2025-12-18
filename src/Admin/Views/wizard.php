<?php
if (!defined('ABSPATH')) exit;

$objectives = [
        'aov' => [
                'title' => 'Aumentar Valor del Carrito',
                'desc' => 'Incrementa el ticket promedio con descuentos estratégicos'
        ],
        'liquidation' => [
                'title' => 'Liquidar Inventario',
                'desc' => 'Mueve stock que no rota o está próximo a vencer'
        ],
        'loyalty' => [
                'title' => 'Fidelización',
                'desc' => 'Recompensa clientes recurrentes y genera lealtad'
        ],
        'urgency' => [
                'title' => 'Conversión Rápida',
                'desc' => 'Genera urgencia y aumenta ventas inmediatas'
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
</style>

<div class="pwoa-wizard max-w-5xl mx-auto p-12">

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
    <div id="step-objective">
        <h1 class="text-4xl font-bold mb-12">¿Qué quieres lograr?</h1>

        <div class="grid grid-cols-2 gap-8">
            <?php foreach ($objectives as $key => $obj): ?>
                <button
                        type="button"
                        class="objective-btn text-left bg-white p-8 rounded-lg shadow hover:shadow-xl transition border-2 border-transparent hover:border-blue-500"
                        data-objective="<?php echo esc_attr($key); ?>"
                        data-title="<?php echo esc_attr($obj['title']); ?>">
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
    <div id="step-config" class="hidden">
        <h1 class="text-4xl font-bold mb-3" id="selected-strategy-title"></h1>
        <p class="text-lg text-gray-500 mb-12">Configura los parámetros de tu campaña</p>

        <form id="campaign-form" class="bg-white p-8 rounded-lg shadow space-y-6">

            <div>
                <label class="block text-sm font-bold mb-2">Nombre de la campaña</label>
                <input type="text" name="name" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                       placeholder="Ej: Black Friday 2024 - Descuento por volumen">
            </div>

            <div id="dynamic-fields"></div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold mb-2">Fecha de inicio (opcional)</label>
                    <input type="datetime-local" name="start_date"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Fecha de fin (opcional)</label>
                    <input type="datetime-local" name="end_date"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold mb-2">Modo de aplicación</label>
                <select name="stacking_mode" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="priority">Prioridad (mejor descuento)</option>
                    <option value="stack">Apilar descuentos</option>
                </select>
                <p class="text-sm text-gray-500 mt-1">Si hay múltiples campañas activas, ¿aplicar solo la mejor o sumarlas?</p>
            </div>

            <div>
                <label class="block text-sm font-bold mb-2">Prioridad</label>
                <input type="number" name="priority" value="10" min="1" max="100"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                <p class="text-sm text-gray-500 mt-1">Mayor número = mayor prioridad (1-100)</p>
            </div>

            <input type="hidden" name="objective" id="form-objective">
            <input type="hidden" name="strategy" id="form-strategy">
            <input type="hidden" name="discount_type" id="form-discount-type">

            <div class="flex gap-4 pt-4">
                <button type="submit"
                        class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 font-bold">
                    Crear Campaña
                </button>
                <button type="button" id="btn-cancel"
                        class="bg-gray-200 text-gray-700 px-8 py-3 rounded-lg hover:bg-gray-300">
                    Cancelar
                </button>
            </div>

        </form>
    </div>

</div>
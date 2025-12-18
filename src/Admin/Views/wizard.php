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

<div class="max-w-5xl mx-auto p-12">

    <div id="step-objective">
        <h2 class="text-4xl font-bold mb-12">¿Qué quieres lograr?</h2>

        <div class="grid grid-cols-2 gap-8">
            <?php foreach ($objectives as $key => $obj): ?>
                <button
                        type="button"
                        class="objective-btn text-left bg-white p-8 rounded-lg shadow hover:shadow-xl transition border-2 border-transparent hover:border-blue-500"
                        data-objective="<?php echo esc_attr($key); ?>">
                    <h3 class="text-2xl font-bold mb-3"><?php echo esc_html($obj['title']); ?></h3>
                    <p class="text-gray-600"><?php echo esc_html($obj['desc']); ?></p>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="step-strategy" class="hidden">
        <button type="button" id="btn-back" class="text-blue-600 mb-8 hover:text-blue-800">← Volver</button>
        <h2 class="text-4xl font-bold mb-12">Estrategias Recomendadas</h2>
        <div id="strategies-list"></div>
    </div>

    <div id="step-config" class="hidden">
        <button type="button" id="btn-back-config" class="text-blue-600 mb-8 hover:text-blue-800">← Volver</button>
        <h2 class="text-4xl font-bold mb-12">Configurar Campaña</h2>

        <form id="campaign-form" class="bg-white p-8 rounded-lg shadow space-y-6">

            <div>
                <label class="block text-sm font-bold mb-2">Nombre de la campaña</label>
                <input type="text" name="name" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
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
            </div>

            <div>
                <label class="block text-sm font-bold mb-2">Prioridad</label>
                <input type="number" name="priority" value="10" min="1" max="100"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                <p class="text-sm text-gray-500 mt-1">Mayor número = mayor prioridad</p>
            </div>

            <input type="hidden" name="objective" id="form-objective">
            <input type="hidden" name="strategy" id="form-strategy">
            <input type="hidden" name="discount_type" id="form-discount-type">

            <div class="flex gap-4">
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
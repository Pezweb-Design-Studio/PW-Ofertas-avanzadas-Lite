<?php
if (!defined("ABSPATH")) {
    exit();
}

$current_behavior = $stacking_behavior ?? "priority_first";
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Ajustes de Ofertas Avanzadas</h1>
    <hr class="wp-header-end">

    <div class="max-w-4xl mt-8">
        <div class="bg-white rounded-lg shadow">
            <div class="p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Comportamiento de Descuentos Múltiples</h2>
                <p class="text-gray-600 mb-8">Define cómo se aplicarán los descuentos cuando hay múltiples campañas activas simultáneamente.</p>

                <form id="settings-form" class="space-y-6">
                    <?php wp_nonce_field("pwoa_nonce", "pwoa_nonce"); ?>

                    <!-- Opción 1: Priority First -->
                    <label class="flex items-start p-6 border-2 rounded-lg cursor-pointer transition <?php echo $current_behavior ===
                    "priority_first"
                        ? "border-blue-500 bg-blue-50"
                        : "border-gray-200 hover:border-gray-300"; ?>">
                        <input type="radio"
                               name="stacking_behavior"
                               value="priority_first"
                               class="mt-1 mr-4"
                               <?php checked(
                                   $current_behavior,
                                   "priority_first",
                               ); ?>>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-bold text-gray-900">Prioridad primero</h3>
                                <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Recomendado</span>
                            </div>
                            <p class="text-gray-700 mb-3">Las campañas marcadas como "Prioritarias" siempre tienen precedencia. Si existen campañas prioritarias disponibles, se aplicará la de mayor descuento. Las campañas "Apilables" solo se usarán cuando NO haya campañas prioritarias aplicables.</p>
                            <div class="bg-gray-50 border-l-4 border-gray-400 p-3 mt-3">
                                <p class="text-sm text-gray-700"><strong>Caso de uso:</strong> Ideal si quieres tener control total sobre qué descuento tiene más importancia. Útil para ofertas especiales que deben predominar sobre promociones generales.</p>
                            </div>
                        </div>
                    </label>

                    <!-- Opción 2: Stack First -->
                    <label class="flex items-start p-6 border-2 rounded-lg cursor-pointer transition <?php echo $current_behavior ===
                    "stack_first"
                        ? "border-blue-500 bg-blue-50"
                        : "border-gray-200 hover:border-gray-300"; ?>">
                        <input type="radio"
                               name="stacking_behavior"
                               value="stack_first"
                               class="mt-1 mr-4"
                               <?php checked(
                                   $current_behavior,
                                   "stack_first",
                               ); ?>>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-bold text-gray-900">Solo apilables (Clásico)</h3>
                            </div>
                            <p class="text-gray-700 mb-3">Si existe AL MENOS una campaña "Apilable", se sumarán TODAS las campañas apilables disponibles e ignorará las prioritarias. Solo aplicará campañas prioritarias si no hay ninguna apilable aplicable.</p>
                            <div class="bg-gray-50 border-l-4 border-gray-400 p-3 mt-3">
                                <p class="text-sm text-gray-700"><strong>Caso de uso:</strong> Comportamiento tradicional. Útil si prefieres que los descuentos se acumulen cuando sea posible.</p>
                            </div>
                        </div>
                    </label>

                    <!-- Opción 3: Max Discount -->
                    <label class="flex items-start p-6 border-2 rounded-lg cursor-pointer transition <?php echo $current_behavior ===
                    "max_discount"
                        ? "border-blue-500 bg-blue-50"
                        : "border-gray-200 hover:border-gray-300"; ?>">
                        <input type="radio"
                               name="stacking_behavior"
                               value="max_discount"
                               class="mt-1 mr-4"
                               <?php checked(
                                   $current_behavior,
                                   "max_discount",
                               ); ?>>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-bold text-gray-900">Siempre el mejor descuento</h3>
                            </div>
                            <p class="text-gray-700 mb-3">El sistema calcula AMBOS escenarios (suma de apilables vs mejor prioritario) y aplica automáticamente el que genere mayor ahorro para el cliente.</p>
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mt-3">
                                <p class="text-sm text-yellow-900"><strong>⚠️ Precaución:</strong> Este modo puede generar descuentos totales mayores de los esperados si no configuras límites claros en tus campañas. Úsalo con cuidado.</p>
                            </div>
                            <div class="bg-gray-50 border-l-4 border-gray-400 p-3 mt-3">
                                <p class="text-sm text-gray-700"><strong>Caso de uso:</strong> Máxima generosidad con el cliente. El sistema siempre elige el escenario más favorable para el comprador.</p>
                            </div>
                        </div>
                    </label>

                    <div class="pt-4">
                        <button type="submit"
                                class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('settings-form');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        formData.append('action', 'pwoa_save_settings');
        formData.append('nonce', '<?php echo wp_create_nonce(
            "pwoa_nonce",
        ); ?>');

        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Mostrar mensaje de éxito
                const notice = document.createElement('div');
                notice.className = 'notice notice-success is-dismissible';
                notice.innerHTML = '<p>' + data.data.message + '</p>';
                document.querySelector('.wrap').insertBefore(notice, document.querySelector('.wrap').firstChild);

                // Scroll al top
                window.scrollTo({ top: 0, behavior: 'smooth' });

                // Auto-hide después de 3 segundos
                setTimeout(() => notice.remove(), 3000);
            } else {
                alert('Error: ' + (data.data || 'No se pudo guardar'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al guardar la configuración');
        }
    });
});
</script>

<style>
.max-w-4xl {
    max-width: 56rem;
}
</style>

// ⚠️ LITE: Agregar al inicio del archivo wizard.js existente

// Detectar si es LITE o PRO
const IS_LITE = typeof PWOA_EDITION !== 'undefined' && PWOA_EDITION === 'lite';

// Override del handleClick para manejar objectives bloqueados
const originalHandleClick = PWOAWizard.handleClick;
PWOAWizard.handleClick = function(e) {
    const t = e.target;

    if (t.closest('.objective-btn')) {
        const btn = t.closest('.objective-btn');
        const available = btn.dataset.available === '1';

        // ⚠️ LITE: Bloquear objectives PRO
        if (IS_LITE && !available) {
            e.preventDefault();
            e.stopPropagation();

            const objective = btn.dataset.title;
            this.showProModal(objective);
            return;
        }
    }

    // Llamar al handler original
    return originalHandleClick.call(this, e);
};

// ⚠️ LITE: Modal para mostrar upgrade a PRO
PWOAWizard.showProModal = function(featureName) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
    modal.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-8">
            <div class="text-center">
                <div class="text-6xl mb-4">🔒</div>
                <h2 class="text-2xl font-bold mb-3">Función exclusiva de PRO</h2>
                <p class="text-gray-600 mb-6">
                    <strong>${featureName}</strong> está disponible en la versión Pro junto con 6 estrategias avanzadas adicionales y analytics completos.
                </p>
                <div class="space-y-3">
                    <a href="https://tu-sitio.com/pro" target="_blank" 
                       class="block bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:from-blue-700 hover:to-purple-700 transition">
                        Ver versión PRO →
                    </a>
                    <button type="button" class="block w-full bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 transition close-pro-modal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    modal.querySelector('.close-pro-modal').addEventListener('click', () => {
        modal.remove();
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
};

// ⚠️ LITE: Override para agregar banners en strategies
const originalRenderStrategies = PWOAWizard.renderStrategies;
PWOAWizard.renderStrategies = function(strategies) {
    if (!IS_LITE || strategies.length > 0) {
        return originalRenderStrategies.call(this, strategies);
    }

    // Si está vacío en LITE, mostrar banner de PRO
    const html = `
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 border-2 border-blue-200 rounded-lg p-12 text-center">
            <div class="text-6xl mb-4">✨</div>
            <h3 class="text-3xl font-bold mb-4">Función exclusiva de PRO</h3>
            <p class="text-gray-700 text-lg mb-8">
                Este objetivo está disponible en la versión Pro.
            </p>
            <a href="https://tu-sitio.com/pro" target="_blank" 
               class="inline-block bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-4 rounded-lg font-bold text-lg hover:from-blue-700 hover:to-purple-700 transition">
                Actualizar a Pro →
            </a>
        </div>
    `;

    document.getElementById('strategies-list').innerHTML = html;
};
// ⚠️ LITE ADDON - Bloqueo de strategies Pro

console.log("🔍 [ADDON] wizard.lite-addon.js cargando...");

// ⚠️ CRITICAL: Leer edition desde pwoaData (localized por Plugin.php)
const IS_LITE = typeof pwoaData !== "undefined" && pwoaData.edition === "lite";

console.log(
  "🔍 [ADDON] pwoaData:",
  typeof pwoaData !== "undefined" ? pwoaData : "UNDEFINED",
);
console.log(
  "🔍 [ADDON] Edition:",
  typeof pwoaData !== "undefined" ? pwoaData.edition : "UNDEFINED",
);
console.log("🔍 [ADDON] IS_LITE:", IS_LITE);

if (!IS_LITE) {
  console.log("⚠️ [ADDON] No es Lite, addon desactivado");
}

// ⚡ Interceptor en CAPTURING PHASE (se ejecuta ANTES del bubbling)
document.addEventListener(
  "click",
  function (e) {
    if (!IS_LITE) return; // Solo en Lite

    const t = e.target;

    // ⚠️ LITE: NO bloquear objectives Pro - dejar que entren para ver strategies bloqueadas
    // Solo bloquear strategies Pro

    // ⚡ Bloquear strategies PRO
    if (t.closest(".strategy-card")) {
      console.log("🔍 [ADDON] Click en strategy-card");
      const card = t.closest(".strategy-card");

      try {
        const strategyData = JSON.parse(card.dataset.strategy);

        console.log("🔍 [ADDON] Strategy:", {
          name: strategyData.name,
          available: strategyData.available,
        });

        if (strategyData.available === false) {
          console.log("🔴 [ADDON] BLOQUEANDO strategy PRO:", strategyData.name);
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();

          PWOAWizard.showProModal(strategyData.name);
          return false;
        } else {
          console.log("✅ [ADDON] Strategy Lite, permitiendo");
        }
      } catch (err) {
        console.error("❌ [ADDON] Error parsing strategy data:", err);
      }
    }
  },
  true,
); // ⚡ true = CAPTURING phase

console.log("✅ [ADDON] Event listener registrado en capturing phase");

// ⚠️ LITE: Modal para mostrar upgrade a PRO
PWOAWizard.showProModal = function (featureName) {
  console.log("🟣 [MODAL] Mostrando modal para:", featureName);

  const modal = document.createElement("div");
  modal.className =
    "fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center";
  modal.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-8">
            <div class="text-center">
                <div class="text-6xl mb-4">🔒</div>
                <h2 class="text-2xl font-bold mb-3">Función exclusiva de PRO</h2>
                <p class="text-gray-600 mb-6">
                    <strong>${featureName}</strong> está disponible en la versión Pro junto con 6 estrategias avanzadas adicionales y analytics completos.
                </p>
                <div class="space-y-3">
                    <a href="https://pezweb.com/" target="_blank"
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

  modal.querySelector(".close-pro-modal").addEventListener("click", () => {
    console.log("🟣 [MODAL] Cerrando modal");
    modal.remove();
  });

  modal.addEventListener("click", (e) => {
    if (e.target === modal) {
      console.log("🟣 [MODAL] Cerrando modal (click en background)");
      modal.remove();
    }
  });
};

console.log("✅ [ADDON] showProModal definido");

// ⚠️ LITE: Override para renderizar strategies con candadito
const originalRenderStrategies = PWOAWizard.renderStrategies;

console.log("🔍 [ADDON] Guardando referencia a renderStrategies original");

PWOAWizard.renderStrategies = function (strategies) {
  console.log(
    "🟢 [RENDER] renderStrategies llamado con",
    strategies.length,
    "strategies",
  );
  console.log("🟢 [RENDER] IS_LITE:", IS_LITE);

  if (!IS_LITE) {
    console.log("🟢 [RENDER] No es Lite, usando original");
    return originalRenderStrategies.call(this, strategies);
  }

  // Si está vacío, mostrar banner de PRO
  if (strategies.length === 0) {
    console.log("🟢 [RENDER] Sin strategies, mostrando banner PRO");
    const html = `
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 border-2 border-blue-200 rounded-lg p-12 text-center">
                <div class="text-6xl mb-4">✨</div>
                <h3 class="text-3xl font-bold mb-4">Función exclusiva de PRO</h3>
                <p class="text-gray-700 text-lg mb-8">
                    Este objetivo está disponible en la versión Pro.
                </p>
                <a href="https://pezweb.com/" target="_blank"
                   class="inline-block bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-4 rounded-lg font-bold text-lg hover:from-blue-700 hover:to-purple-700 transition">
                    Actualizar a Pro →
                </a>
            </div>
        `;
    document.getElementById("strategies-list").innerHTML = html;
    return;
  }

  const liteCount = strategies.filter((s) => s.available !== false).length;
  const proCount = strategies.filter((s) => s.available === false).length;

  console.log("🟢 [RENDER] Renderizando:", {
    total: strategies.length,
    lite: liteCount,
    pro: proCount,
  });

  // ⚠️ Si hay strategies Pro, mostrar banner informativo
  let proBanner = "";
  if (proCount > 0) {
    proBanner = `
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 border-2 border-purple-200 rounded-lg p-6 mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-purple-900 mb-2">
                            🔒 ${proCount} ${proCount === 1 ? "estrategia exclusiva" : "estrategias exclusivas"} de PRO
                        </h3>
                        <p class="text-sm text-purple-700">
                            Estás viendo ${liteCount} ${liteCount === 1 ? "estrategia disponible" : "estrategias disponibles"} en Lite.
                            Actualiza a Pro para desbloquear ${proCount} ${proCount === 1 ? "estrategia avanzada adicional" : "estrategias avanzadas adicionales"}.
                        </p>
                    </div>
                    <a href="https://pezweb.com/" target="_blank"
                       class="ml-4 bg-gradient-to-r from-blue-600 to-purple-600 !text-white px-6 py-3 rounded-lg font-bold hover:from-blue-700 hover:to-purple-700 transition whitespace-nowrap">
                        Ver Pro →
                    </a>
                </div>
            </div>
        `;
  }

  // Renderizar strategies con locked class para Pro
  const html = strategies
    .map((s) => {
      const isLocked = s.available === false;
      const lockedClass = isLocked ? "locked" : "";
      const lockedBadge = isLocked
        ? '<span class="pro-badge">🔒 PRO</span>'
        : "";

      if (isLocked) {
        console.log("🔒 [RENDER] Strategy PRO:", s.name);
      }

      return `
            <div class="strategy-card ${lockedClass} bg-white p-8 rounded-lg shadow mb-6 cursor-pointer hover:shadow-xl transition border-2 border-transparent hover:border-blue-500"
                 data-strategy='${JSON.stringify(s)}'>
                ${lockedBadge}
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-2xl font-bold">${s.name}</h3>
                    <span class="text-yellow-500 text-xl">${"★".repeat(s.effectiveness)}</span>
                </div>
                <p class="text-gray-600 mb-6 leading-relaxed">${s.description}</p>
                <div class="bg-blue-50 p-4 rounded">
                    <strong class="text-blue-900">Cuándo usar:</strong>
                    <span class="text-blue-800">${s.when_to_use}</span>
                </div>
            </div>
        `;
    })
    .join("");

  document.getElementById("strategies-list").innerHTML = proBanner + html;
  console.log("✅ [RENDER] Strategies renderizadas");
};

console.log("✅ [ADDON] renderStrategies overridden");
console.log("✅ [ADDON] wizard.lite-addon.js cargado completamente");

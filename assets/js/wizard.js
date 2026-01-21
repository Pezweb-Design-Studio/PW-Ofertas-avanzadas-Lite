const PWOAWizard = {
  state: {
    objective: null,
    strategy: null,
    strategyData: null,
    editMode: false,
    editId: null,
    selectedProducts: [],
    needsValidation: false,
    cachedData: null,
  },

  init() {
    const editId = new URLSearchParams(location.search).get("edit");

    if (editId) {
      this.state.editMode = true;
      this.state.editId = editId;
      this.loadCampaignOptimized(editId);
    }

    document.addEventListener("click", (e) => this.handleClick(e));
    document.addEventListener("input", (e) => this.handleInput(e));
    document.addEventListener("blur", (e) => this.handleBlur(e), true);
    document.addEventListener("submit", (e) => this.handleSubmit(e));

    document.addEventListener("click", (e) => {
      if (!e.target.closest(".repeater-product-search")) {
        document
          .querySelectorAll(".repeater-search-results")
          .forEach((d) => d.classList.add("hidden"));
      }
    });
  },

  handleClick(e) {
    const t = e.target;

    if (t.closest(".objective-btn")) {
      const btn = t.closest(".objective-btn");
      this.selectObjective(btn.dataset.objective, btn.dataset.title);
    } else if (t.closest(".strategy-card")) {
      const data = JSON.parse(t.closest(".strategy-card").dataset.strategy);
      this.selectStrategy(data);
    } else if (t.id === "crumb-objective") this.goToStep("objective");
    else if (t.id === "crumb-strategy") this.goToStep("strategy");
    else if (t.id === "btn-back") this.goToStep("objective");
    else if (t.id === "btn-back-config") this.goToStep("strategy");
    else if (t.id === "btn-cancel" && confirm("¿Descartar cambios?"))
      location.href = "?page=pwoa-dashboard";
    else if (t.closest(".add-repeater")) {
      const key = t.closest(".add-repeater").dataset.fieldKey;
      this.addRepeaterRow(key);
    } else if (t.closest(".remove-repeater")) {
      e.stopPropagation();
      const row = t.closest(".repeater-row");
      const key = row.closest('[id^="repeater-"]').id.replace("repeater-", "");
      row.remove();
      this.checkDuplicates(key);
    } else if (t.closest(".repeater-header")) {
      if (t.closest(".remove-repeater")) return;
      const row = t.closest(".repeater-row");
      const content = row.querySelector(".repeater-content");
      const icon = row.querySelector(".accordion-icon");
      const isOpen = content.style.display !== "none";
      content.style.display = isOpen ? "none" : "block";
      icon.style.transform = isOpen ? "rotate(-90deg)" : "rotate(0deg)";
    } else if (t.closest(".repeater-product-result")) {
      const el = t.closest(".repeater-product-result");
      const input = el
        .closest(".repeater-content")
        .querySelector(".repeater-product-search");
      this.selectProduct(el, input);
    } else if (t.closest(".remove-product")) {
      const id = t.closest(".remove-product").dataset.id;
      this.state.selectedProducts = this.state.selectedProducts.filter(
        (p) => p.id !== id,
      );
      this.renderSelectedProducts();
    } else if (t.id === "btn-show-products") this.showMatchingProducts();
    else if (
      t.id === "close-modal" ||
      t.id === "close-modal-btn" ||
      t.id === "products-modal"
    ) {
      document.getElementById("products-modal").classList.add("hidden");
    } else if (t.id === "stacking-help") {
      e.preventDefault();
      const tooltip = document.getElementById("stacking-tooltip");
      tooltip.classList.toggle("hidden");
    }
  },

  handleInput(e) {
    const t = e.target;

    if (t.classList.contains("repeater-product-search")) {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => this.searchInRepeater(t), 300);
    } else if (t.id === "product-search") {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => this.searchProducts(t.value), 300);
    } else if (
      t.name === "config[buy_quantity]" ||
      t.name === "config[pay_quantity]"
    ) {
      this.validateBuyXPayY();
    } else if (t.name === "config[attribute_slug]") {
      this.loadAttributeTerms(t.value);
      this.state.needsValidation = true;
    } else if (
      t.name === "config[min_quantity]" ||
      t.name === "config[discount_value]" ||
      t.name === "config[max_applications]"
    ) {
      if (this.state.strategy === "attribute_quantity_discount") {
        this.previewAttributeDiscount();
      }
    } else if (
      t.id === "form-categories" ||
      t.id === "form-min-price" ||
      t.id === "form-max-price" ||
      t.name === "config[attribute_value]"
    ) {
      this.state.needsValidation = true;
    }
  },

  // ⚡ NUEVO: Solo validar cuando usuario sale del campo
  handleBlur(e) {
    const t = e.target;

    if (
      this.state.needsValidation &&
      (t.id === "form-categories" ||
        t.id === "form-min-price" ||
        t.id === "form-max-price" ||
        t.name === "config[attribute_value]")
    ) {
      this.validateFilters();
      this.state.needsValidation = false;
    }
  },

  async handleSubmit(e) {
    if (e.target.id !== "campaign-form") return;
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    if (this.state.strategy === "buy_x_pay_y") {
      const buy = parseInt(formData.get("config[buy_quantity]")) || 0;
      const pay = parseInt(formData.get("config[pay_quantity]")) || 0;

      if (buy <= 0 || pay <= 0) {
        alert(
          'Error: Debes especificar cantidades válidas para "Llevas" y "Pagas"',
        );
        return;
      }

      if (buy <= pay) {
        alert(
          "Error: La cantidad a llevar debe ser mayor que la cantidad a pagar.\n\nEjemplo válido: Lleva 3, Paga 2",
        );
        return;
      }
    }

    const config = {};
    const repeaters = {};

    formData.forEach((value, key) => {
      const match = key.match(/config\[(\w+)\](?:\[(\d+)\]\[(\w+)\])?/);
      if (!match) return;

      const [, field, idx, subKey] = match;

      if (idx !== undefined) {
        if (!repeaters[field]) repeaters[field] = [];
        if (!repeaters[field][idx]) repeaters[field][idx] = {};
        repeaters[field][idx][subKey] = value;
      } else {
        config[field] = value;
      }
    });

    Object.assign(config, repeaters);

    const data = {
      action: this.state.editMode
        ? "pwoa_update_campaign"
        : "pwoa_save_campaign",
      nonce: pwoaData.nonce,
      name: formData.get("name"),
      objective: formData.get("objective"),
      strategy: formData.get("strategy"),
      discount_type: formData.get("discount_type") || config.discount_type,
      config: JSON.stringify(config),
      conditions: JSON.stringify(this.buildConditions()),
      stacking_mode: formData.get("stacking_mode"),
      priority: 10,
      start_date: formData.get("start_date"),
      end_date: formData.get("end_date"),
    };

    if (this.state.editMode) data.campaign_id = this.state.editId;

    try {
      const res = await fetch(pwoaData.ajaxUrl, {
        method: "POST",
        body: new URLSearchParams(data),
      });

      const result = await res.json();

      if (result.success) {
        alert("✓ " + result.data.message);
        location.href = "?page=pwoa-dashboard";
      } else {
        alert("✗ Error: " + (result.data || "Error desconocido"));
      }
    } catch (error) {
      alert("✗ Error de conexión: " + error.message);
    }
  },

  // ⚡ OPTIMIZADO: 1 fetch con toda la data
  async loadCampaignOptimized(id) {
    try {
      const res = await fetch(pwoaData.ajaxUrl, {
        method: "POST",
        body: new URLSearchParams({
          action: "pwoa_get_wizard_data",
          campaign_id: id,
          nonce: pwoaData.nonce,
        }),
      });

      const result = await res.json();

      if (!result.success) {
        alert("Error al cargar campaña: " + result.data);
        location.href = "?page=pwoa-dashboard";
        return;
      }

      const data = result.data;
      this.state.cachedData = data;

      const c = data.campaign;
      this.state.objective = c.objective;
      this.state.strategy = c.strategy;

      this.state.strategyData = data.strategies.find(
        (s) => this.getStrategyKey(s.name) === c.strategy,
      );

      if (!this.state.strategyData) {
        alert("Error: Estrategia no encontrada");
        return;
      }

      document.getElementById("selected-strategy-title").textContent =
        this.state.strategyData.name;

      const fragment = this.renderConfigFieldsOptimized(
        this.state.strategyData.config_fields || [],
      );
      const container = document.getElementById("dynamic-fields");
      container.replaceChildren(fragment);

      document.getElementById("form-objective").value = c.objective;
      document.getElementById("form-strategy").value = c.strategy;

      this.hideSteps();
      document.getElementById("step-config").classList.remove("hidden");
      this.toggleProductFilters();

      // ⚡ Cargar todos los datos en paralelo
      await Promise.all([
        this.populateFormFields(c),
        this.loadRepeaters(c.config),
        this.loadConditions(c.conditions),
      ]);

      // ⚡ Validar UNA sola vez al final
      this.validateFilters();
    } catch (error) {
      alert("Error al cargar campaña: " + error.message);
      location.href = "?page=pwoa-dashboard";
    }
  },

  // ⚡ NUEVO: Poblar campos del form
  async populateFormFields(campaign) {
    document.getElementById("form-name").value = campaign.name;
    document.getElementById("form-stacking-mode").value =
      campaign.stacking_mode;

    if (campaign.start_date && campaign.start_date !== "0000-00-00 00:00:00") {
      document.getElementById("form-start-date").value = campaign.start_date
        .replace(" ", "T")
        .substring(0, 16);
    }
    if (campaign.end_date && campaign.end_date !== "0000-00-00 00:00:00") {
      document.getElementById("form-end-date").value = campaign.end_date
        .replace(" ", "T")
        .substring(0, 16);
    }

    for (let key in campaign.config) {
      const input = document.querySelector(`[name="config[${key}]"]`);
      if (input) input.value = campaign.config[key];
    }

    document.getElementById("submit-btn").textContent = "Actualizar Campaña";
  },

  async selectObjective(objective, title) {
    this.state.objective = objective;
    document.getElementById("selected-objective-title").textContent = title;

    // ⚡ Usar endpoint unificado
    const strategies = await this.fetchWizardData({ objective });
    this.renderStrategies(strategies);
    this.goToStep("strategy");
  },

  // ⚡ NUEVO: Endpoint unificado
  async fetchWizardData(params) {
    const res = await fetch(pwoaData.ajaxUrl, {
      method: "POST",
      body: new URLSearchParams({
        action: "pwoa_get_wizard_data",
        ...params,
        nonce: pwoaData.nonce,
      }),
    });

    const data = await res.json();

    if (data.success) {
      this.state.cachedData = data.data;
      return data.data.strategies || [];
    }

    return [];
  },

  selectStrategy(data) {
    this.state.strategyData = data;
    this.state.strategy = this.getStrategyKey(data.name);

    document.getElementById("selected-strategy-title").textContent = data.name;

    const fragment = this.renderConfigFieldsOptimized(data.config_fields || []);
    const container = document.getElementById("dynamic-fields");
    container.replaceChildren(fragment);

    document.getElementById("form-objective").value = this.state.objective;
    document.getElementById("form-strategy").value = this.state.strategy;

    this.goToStep("config");
    this.toggleProductFilters();

    // Validar una vez al entrar al step
    setTimeout(() => this.validateFilters(), 300);
  },

  goToStep(step) {
    this.hideSteps();
    document.getElementById("step-" + step).classList.remove("hidden");
    this.updateBreadcrumb(step);
  },

  hideSteps() {
    ["objective", "strategy", "config"].forEach((s) => {
      document.getElementById("step-" + s).classList.add("hidden");
    });
  },

  updateBreadcrumb(step) {
    const breadcrumb = document.getElementById("breadcrumb");

    if (step === "objective") {
      breadcrumb.classList.add("hidden");
      return;
    }

    breadcrumb.classList.remove("hidden");

    ["crumb-objective", "crumb-strategy", "crumb-config"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) {
        el.classList.remove(
          "text-blue-600",
          "font-semibold",
          "text-gray-900",
          "font-medium",
        );
        el.classList.add("text-gray-500");
      }
    });

    if (step === "strategy") {
      document.getElementById("crumb-objective").textContent =
        this.getObjectiveTitle();
      document
        .getElementById("crumb-objective")
        .classList.remove("text-gray-500");
      document
        .getElementById("crumb-objective")
        .classList.add("text-blue-600", "font-semibold");
      document.getElementById("crumb-strategy-wrapper").classList.add("hidden");
      document.getElementById("crumb-config-wrapper").classList.add("hidden");
    }

    if (step === "config") {
      document.getElementById("crumb-objective").textContent =
        this.getObjectiveTitle();
      document.getElementById("crumb-strategy").textContent =
        this.state.strategyData?.name || "Estrategia";
      document.getElementById("crumb-config").textContent = "Configuración";
      document.getElementById("crumb-config").classList.remove("text-gray-500");
      document
        .getElementById("crumb-config")
        .classList.add("text-gray-900", "font-medium");
      document
        .getElementById("crumb-strategy-wrapper")
        .classList.remove("hidden");
      document
        .getElementById("crumb-config-wrapper")
        .classList.remove("hidden");
    }
  },

  getObjectiveTitle() {
    const map = {
      basic: "Básico",
      aov: "Aumentar Valor del Carrito",
      liquidation: "Liquidar Inventario",
      loyalty: "Fidelización",
      urgency: "Conversión Rápida",
    };
    return map[this.state.objective] || "Objetivo";
  },

  toggleProductFilters() {
    const section = document.getElementById("product-filters-section");
    if (section) {
      section.classList.toggle(
        "hidden",
        this.state.strategy === "bulk_discount",
      );
    }
  },

  renderStrategies(strategies) {
    const html = strategies
      .map(
        (s) => `
            <div class="strategy-card bg-white p-8 rounded-lg shadow mb-6 cursor-pointer hover:shadow-xl transition border-2 border-transparent hover:border-blue-500"
                 data-strategy='${JSON.stringify(s)}'>
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
        `,
      )
      .join("");

    document.getElementById("strategies-list").innerHTML = html;
  },

  // ⚡ OPTIMIZADO: Document Fragment (1 solo reflow)
  renderConfigFieldsOptimized(fields) {
    const fragment = document.createDocumentFragment();

    if (!fields.length) {
      const p = document.createElement("p");
      p.className = "text-gray-500";
      p.textContent = "Esta estrategia no requiere configuración adicional.";
      fragment.appendChild(p);
      return fragment;
    }

    const isBuyXPayY = fields.some((f) => f.key === "buy_quantity");

    if (isBuyXPayY) {
      const buyField = fields.find((f) => f.key === "buy_quantity");
      const payField = fields.find((f) => f.key === "pay_quantity");
      const maxField = fields.find((f) => f.key === "max_sets");

      const grid = document.createElement("div");
      grid.className = "grid grid-cols-2 gap-6 mb-6";
      grid.appendChild(this.createFieldElement(buyField));
      grid.appendChild(this.createFieldElement(payField));
      fragment.appendChild(grid);

      if (maxField) {
        fragment.appendChild(this.createFieldElement(maxField));
      }
    } else if (fields.some((f) => f.type === "attribute_select")) {
      this.renderAttributeFieldsOptimized(fields, fragment);
    } else {
      fields.forEach((f) => {
        fragment.appendChild(
          f.type === "repeater"
            ? this.createRepeaterElement(f)
            : this.createFieldElement(f),
        );
      });
    }

    return fragment;
  },

  // ⚡ NUEVO: Crear elemento de campo (en vez de innerHTML)
  createFieldElement(f) {
    const div = document.createElement("div");
    div.className = "mb-6";

    const label = document.createElement("label");
    label.className = "block text-sm font-bold mb-2";
    label.textContent = f.label;
    div.appendChild(label);

    let input;

    switch (f.type) {
      case "select":
        input = document.createElement("select");
        input.name = `config[${f.key}]`;
        input.className =
          "w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500";
        if (f.required) input.required = true;

        Object.entries(f.options || {}).forEach(([v, l]) => {
          const opt = document.createElement("option");
          opt.value = v;
          opt.textContent = l;
          input.appendChild(opt);
        });
        break;

      case "number":
        input = document.createElement("input");
        input.type = "number";
        input.name = `config[${f.key}]`;
        input.className =
          "w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500";
        if (f.required) input.required = true;
        if (f.default) input.value = f.default;
        break;

      case "datetime":
        input = document.createElement("input");
        input.type = "datetime-local";
        input.name = `config[${f.key}]`;
        input.className =
          "w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500";
        if (f.required) input.required = true;
        break;

      default:
        input = document.createElement("input");
        input.type = "text";
        input.name = `config[${f.key}]`;
        input.className =
          "w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500";
        if (f.required) input.required = true;
    }

    div.appendChild(input);

    if (f.description) {
      const desc = document.createElement("p");
      desc.className = "text-sm text-gray-500 mt-1";
      desc.textContent = f.description;
      div.appendChild(desc);
    }

    return div;
  },

  // ⚡ NUEVO: Crear elemento repeater
  createRepeaterElement(f) {
    const container = document.createElement("div");
    container.className = "mb-8";

    const h3 = document.createElement("h3");
    h3.className = "text-xl font-bold mb-2";
    h3.textContent = f.label;
    container.appendChild(h3);

    if (f.description) {
      const desc = document.createElement("p");
      desc.className = "text-sm text-gray-600 mb-6";
      desc.textContent = f.description;
      container.appendChild(desc);
    }

    const repeaterDiv = document.createElement("div");
    repeaterDiv.id = `repeater-${f.key}`;
    repeaterDiv.dataset.fieldKey = f.key;
    repeaterDiv.innerHTML = this.renderRepeaterRow(f, 0);
    container.appendChild(repeaterDiv);

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className =
      "add-repeater mt-6 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors inline-flex items-center gap-2";
    btn.dataset.fieldKey = f.key;
    btn.innerHTML = `<span class="text-xl">+</span><span>${f.key === "bulk_items" ? "Agregar otro producto" : "Agregar nivel"}</span>`;
    container.appendChild(btn);

    return container;
  },

  renderRepeaterRow(field, idx) {
    const key = field.key;
    let productSearch = "";
    let otherFields = "";

    field.fields.forEach((sf) => {
      const req = sf.required ? "required" : "";
      const desc = sf.description
        ? `<p class="text-xs text-gray-500 mt-1">${sf.description}</p>`
        : "";

      if (sf.type === "product_search") {
        productSearch = `
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2">${sf.label}</label>
                        <div class="relative">
                            <input type="text" placeholder="Buscar por nombre, SKU o ID..."
                                   class="repeater-product-search w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                   data-row-index="${idx}" data-field-key="${key}" data-subfield-key="${sf.key}">
                            <div class="repeater-search-results hidden absolute z-50 w-full bg-white border rounded-lg shadow-xl mt-1 max-h-48 overflow-y-auto"></div>
                        </div>
                        <input type="hidden" name="config[${key}][${idx}][${sf.key}]" class="selected-product-id" ${req}>
                        <div class="selected-product-display mt-2 text-sm font-medium text-green-700"></div>
                    </div>
                `;
      } else {
        let input = "";

        if (sf.type === "select") {
          const opts = Object.entries(sf.options || {})
            .map(([v, l]) => `<option value="${v}">${l}</option>`)
            .join("");
          input = `<select name="config[${key}][${idx}][${sf.key}]" ${req} class="w-full px-3 py-2 border rounded-lg text-sm">${opts}</select>`;
        } else if (sf.type === "text") {
          const ph = sf.key === "badge_text" ? 'Ej: "Oferta única"' : "";
          input = `<input type="text" name="config[${key}][${idx}][${sf.key}]" ${req} placeholder="${ph}" class="w-full px-3 py-2 border rounded-lg text-sm">`;
        } else {
          input = `<input type="number" name="config[${key}][${idx}][${sf.key}]" ${req} step="0.01" class="w-full px-3 py-2 border rounded-lg text-sm">`;
        }

        otherFields += `<div><label class="block text-xs font-semibold mb-1.5 text-gray-700">${sf.label}</label>${input}${desc}</div>`;
      }
    });

    return `
            <div class="repeater-row bg-white border-2 border-gray-200 rounded-lg mb-3 overflow-hidden">
                <div class="repeater-header flex justify-between items-center p-4 cursor-pointer hover:bg-gray-50">
                    <div class="flex items-center gap-3">
                        <svg class="accordion-icon w-5 h-5 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        <h4 class="text-base font-bold text-gray-800">Producto ${idx + 1}</h4>
                        <span class="product-name-preview text-sm text-gray-500 italic"></span>
                    </div>
                    <button type="button" class="remove-repeater bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-sm">× Eliminar</button>
                </div>
                <div class="repeater-content p-4 pt-0 border-t border-gray-100">
                    ${productSearch}
                    <div class="grid grid-cols-4 gap-4">${otherFields}</div>
                </div>
            </div>
        `;
  },

  addRepeaterRow(key) {
    const container = document.getElementById("repeater-" + key);
    const field = this.state.strategyData.config_fields.find(
      (f) => f.key === key,
    );
    const idx = container.querySelectorAll(".repeater-row").length;

    container.insertAdjacentHTML(
      "beforeend",
      this.renderRepeaterRow(field, idx),
    );
  },

  async searchInRepeater(input) {
    const query = input.value;
    const resultsDiv = input.nextElementSibling;

    if (query.length < 2) {
      resultsDiv.classList.add("hidden");
      return;
    }

    const res = await fetch(pwoaData.ajaxUrl, {
      method: "POST",
      body: new URLSearchParams({
        action: "pwoa_search_products",
        search: query,
        nonce: pwoaData.nonce,
      }),
    });

    const data = await res.json();

    if (!data.success || !data.data.length) {
      resultsDiv.classList.add("hidden");
      return;
    }

    resultsDiv.innerHTML = data.data
      .map(
        (p) => `
            <div class="repeater-product-result p-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0"
                 data-id="${p.id}" data-name="${p.name}">
                <div class="font-semibold text-sm">${p.name}</div>
                <div class="text-xs text-gray-500">ID: ${p.id} | ${p.formatted_price}</div>
            </div>
        `,
      )
      .join("");

    resultsDiv.classList.remove("hidden");
  },

  selectProduct(el, input) {
    const id = el.dataset.id;
    const name = el.dataset.name;
    const row = input.closest(".repeater-row");

    const hidden = input.parentElement.nextElementSibling;
    hidden.value = id;

    const display = hidden.nextElementSibling;
    display.innerHTML = `<strong>✓</strong> ${name} <span class="text-gray-500">(ID: ${id})</span>`;

    const preview = row.querySelector(".product-name-preview");
    if (preview) preview.textContent = `- ${name}`;

    input.value = "";
    input.nextElementSibling.classList.add("hidden");

    const key = input.dataset.fieldKey;
    this.checkDuplicates(key);
  },

  checkDuplicates(key) {
    const container = document.getElementById("repeater-" + key);
    if (!container) return;

    const rows = container.querySelectorAll(".repeater-row");
    const ids = {};

    rows.forEach((row, i) => {
      const hidden = row.querySelector(".selected-product-id");
      if (!hidden?.value) return;

      const id = hidden.value;
      const warn = row.querySelector(".duplicate-warning");

      if (ids[id]) {
        row.classList.remove("border-gray-200");
        row.classList.add("border-yellow-400", "bg-yellow-50");

        if (!warn) {
          const content = row.querySelector(".repeater-content");
          const w = document.createElement("div");
          w.className =
            "duplicate-warning bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 px-4 py-2 rounded text-sm mb-3";
          w.innerHTML = `⚠️ <strong>Duplicado</strong> - Ya configurado en Producto ${ids[id] + 1}. Ambas se aplicarán.`;
          content.insertBefore(w, content.firstChild);
        }
      } else {
        row.classList.remove("border-yellow-400", "bg-yellow-50");
        row.classList.add("border-gray-200");
        if (warn) warn.remove();
        ids[id] = i;
      }
    });
  },

  async loadRepeaters(config) {
    if (!this.state.strategyData?.config_fields) return;

    const promises = this.state.strategyData.config_fields
      .filter((f) => f.type === "repeater" && config[f.key]?.length)
      .map((f) => this.loadRepeaterField(f, config));

    await Promise.all(promises);
  },

  async loadRepeaterField(field, config) {
    const container = document.getElementById("repeater-" + field.key);
    if (!container) return;

    container.innerHTML = "";

    const rowPromises = config[field.key].map((rowData, i) => {
      container.insertAdjacentHTML(
        "beforeend",
        this.renderRepeaterRow(field, i),
      );

      return Promise.all(
        field.fields.map((sf) =>
          this.loadRepeaterSubField(sf, field.key, i, rowData),
        ),
      );
    });

    await Promise.all(rowPromises);
    this.checkDuplicates(field.key);
  },

  async loadRepeaterSubField(sf, key, idx, rowData) {
    if (sf.type === "product_search" && rowData[sf.key]) {
      const hidden = document.querySelector(
        `[name="config[${key}][${idx}][${sf.key}]"]`,
      );
      if (hidden) {
        hidden.value = rowData[sf.key];
        await this.loadProductName(rowData[sf.key], hidden);
      }
    } else {
      const input = document.querySelector(
        `[name="config[${key}][${idx}][${sf.key}]"]`,
      );
      if (input && rowData[sf.key] !== undefined) input.value = rowData[sf.key];
    }
  },

  async loadProductName(id, hidden) {
    const res = await fetch(pwoaData.ajaxUrl, {
      method: "POST",
      body: new URLSearchParams({
        action: "pwoa_search_products",
        search: id,
        nonce: pwoaData.nonce,
      }),
    });

    const data = await res.json();
    if (data.success && data.data[0]) {
      const p = data.data[0];
      const display = hidden.nextElementSibling;
      if (display?.classList.contains("selected-product-display")) {
        display.innerHTML = `<strong>✓</strong> ${p.name} <span class="text-gray-500">(ID: ${p.id})</span>`;

        const row = hidden.closest(".repeater-row");
        const preview = row?.querySelector(".product-name-preview");
        if (preview) preview.textContent = `- ${p.name}`;
      }
    }
  },

  async loadConditions(cond) {
    if (!cond) return;

    if (cond.product_ids?.length) {
      const promises = cond.product_ids.map(async (id) => {
        const res = await fetch(pwoaData.ajaxUrl, {
          method: "POST",
          body: new URLSearchParams({
            action: "pwoa_search_products",
            search: id,
            nonce: pwoaData.nonce,
          }),
        });

        const data = await res.json();
        if (data.success && data.data[0]) {
          const p = data.data[0];
          this.state.selectedProducts.push({
            id: p.id.toString(),
            name: p.name,
            sku: p.sku || "",
          });
        }
      });

      await Promise.all(promises);
      this.renderSelectedProducts();
    }

    if (cond.category_ids?.length) {
      const select = document.getElementById("form-categories");
      if (select) {
        cond.category_ids.forEach((id) => {
          const opt = select.querySelector(`option[value="${id}"]`);
          if (opt) opt.selected = true;
        });
      }
    }

    if (cond.min_price) {
      const input = document.getElementById("form-min-price");
      if (input) input.value = cond.min_price;
    }
    if (cond.max_price) {
      const input = document.getElementById("form-max-price");
      if (input) input.value = cond.max_price;
    }
  },

  async searchProducts(query) {
    if (query.length < 2) {
      document.getElementById("product-search-results").classList.add("hidden");
      return;
    }

    const res = await fetch(pwoaData.ajaxUrl, {
      method: "POST",
      body: new URLSearchParams({
        action: "pwoa_search_products",
        search: query,
        nonce: pwoaData.nonce,
      }),
    });

    const data = await res.json();
    if (!data.success) return;

    const container = document.getElementById("product-search-results");

    if (!data.data.length) {
      container.classList.add("hidden");
      return;
    }

    container.innerHTML = data.data
      .map(
        (p) => `
            <div class="p-3 hover:bg-gray-50 cursor-pointer border-b product-result"
                 data-id="${p.id}" data-name="${p.name}" data-sku="${p.sku || ""}">
                <div class="font-semibold text-sm">${p.name}</div>
                <div class="text-xs text-gray-500">${p.sku ? "SKU: " + p.sku + " | " : ""}ID: ${p.id} | ${p.formatted_price}</div>
            </div>
        `,
      )
      .join("");

    container.classList.remove("hidden");

    container.querySelectorAll(".product-result").forEach((el) => {
      el.addEventListener("click", () => {
        const prod = {
          id: el.dataset.id,
          name: el.dataset.name,
          sku: el.dataset.sku,
        };
        if (!this.state.selectedProducts.some((p) => p.id === prod.id)) {
          this.state.selectedProducts.push(prod);
          this.renderSelectedProducts();
        }
        container.classList.add("hidden");
        document.getElementById("product-search").value = "";
      });
    });
  },

  renderSelectedProducts() {
    const container = document.getElementById("selected-products");
    container.innerHTML = this.state.selectedProducts
      .map(
        (p) => `
            <div class="bg-blue-50 border border-blue-200 rounded px-3 py-1 flex items-center gap-2">
                <span class="text-sm">${p.name}</span>
                <button type="button" class="text-blue-600 hover:text-blue-800 font-bold remove-product" data-id="${p.id}">×</button>
            </div>
        `,
      )
      .join("");

    document.getElementById("form-product-ids").value =
      this.state.selectedProducts.map((p) => p.id).join(",");

    this.state.needsValidation = true;
  },

  buildConditions() {
    if (this.state.strategy === "bulk_discount") return {};

    const cond = {};

    if (this.state.strategy === "attribute_quantity_discount") {
      const attrSlug = document.querySelector(
        '[name="config[attribute_slug]"]',
      )?.value;
      const attrValue = document.querySelector(
        '[name="config[attribute_value]"]',
      )?.value;

      if (attrSlug && attrValue) {
        cond.attribute_slug = attrSlug;
        cond.attribute_value = attrValue;
      }
    }

    if (this.state.selectedProducts.length) {
      cond.product_ids = this.state.selectedProducts.map((p) => parseInt(p.id));
    }

    const cats = document.getElementById("form-categories");
    if (cats) {
      const selected = Array.from(cats.selectedOptions).map((o) =>
        parseInt(o.value),
      );
      if (selected.length) cond.category_ids = selected;
    }

    const minPrice = document.getElementById("form-min-price");
    if (minPrice?.value) cond.min_price = parseFloat(minPrice.value);

    const maxPrice = document.getElementById("form-max-price");
    if (maxPrice?.value) cond.max_price = parseFloat(maxPrice.value);

    return cond;
  },

  async validateFilters() {
    const span = document.getElementById("matching-count");
    if (!span) return;

    const cond = this.buildConditions();
    span.textContent = "Validando...";

    const res = await fetch(pwoaData.ajaxUrl, {
      method: "POST",
      body: new URLSearchParams({
        action: "pwoa_validate_conditions",
        conditions: JSON.stringify(cond),
        nonce: pwoaData.nonce,
      }),
    });

    const data = await res.json();
    span.textContent = data.success ? data.data.count : "Error";
  },

  async showMatchingProducts() {
    const modal = document.getElementById("products-modal");
    const list = document.getElementById("modal-products-list");

    list.innerHTML =
      '<p class="text-center text-gray-500 py-8">Cargando...</p>';
    modal.classList.remove("hidden");

    const cond = this.buildConditions();
    const res = await fetch(pwoaData.ajaxUrl, {
      method: "POST",
      body: new URLSearchParams({
        action: "pwoa_get_matching_products",
        conditions: JSON.stringify(cond),
        nonce: pwoaData.nonce,
      }),
    });

    const data = await res.json();

    if (!data.success) {
      list.innerHTML =
        '<p class="text-center text-red-500 py-8">Error al cargar</p>';
      return;
    }

    document.getElementById("modal-count").textContent = data.data.count;
    document.getElementById("matching-count").textContent = data.data.count;

    if (!data.data.products.length) {
      list.innerHTML =
        '<p class="text-center text-gray-500 py-8">No hay productos</p>';
      return;
    }

    list.innerHTML = data.data.products
      .map(
        (p) => `
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                <div class="flex-1">
                    <p class="font-semibold text-gray-900">${p.name}</p>
                    <p class="text-sm text-gray-500">${p.sku ? "SKU: " + p.sku + " | " : ""}ID: ${p.id}</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-gray-900">${p.formatted_price}</p>
                    ${p.stock ? '<p class="text-xs text-gray-500">Stock: ' + p.stock + "</p>" : ""}
                    <a href="${pwoaData.adminUrl}post.php?post=${p.id}&action=edit" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 mt-1 inline-block">Ver →</a>
                </div>
            </div>
        `,
      )
      .join("");
  },

  getStrategyKey(name) {
    const map = {
      "Descuento Básico por Productos": "basic_discount",
      "Descuento por Monto Mínimo": "min_amount",
      "Envío Gratis sobre Monto Mínimo": "free_shipping",
      "Descuento Escalonado por Cantidad": "tiered_discount",
      "Descuentos por Volumen (Bulk)": "bulk_discount",
      "Lleva X Paga Y": "buy_x_pay_y",
      "Descuento por Atributos": "attribute_quantity_discount",
      "Descuento por Fecha de Vencimiento": "expiry_based",
      "Descuento por Stock Bajo": "low_stock",
      "Descuento por Compras Recurrentes": "recurring_purchase",
      "Flash Sale (Oferta Relámpago)": "flash_sale",
    };
    return map[name] || "";
  },

  validateBuyXPayY() {
    const buyInput = document.querySelector('[name="config[buy_quantity]"]');
    const payInput = document.querySelector('[name="config[pay_quantity]"]');

    if (!buyInput || !payInput) return;

    const buy = parseInt(buyInput.value) || 0;
    const pay = parseInt(payInput.value) || 0;

    const gridContainer = buyInput.closest(".grid");

    let preview = document.getElementById("buy-x-pay-y-preview");
    if (preview) preview.remove();

    let error = document.getElementById("buy-x-pay-y-error");
    if (error) error.remove();

    if (buy <= 0 || pay <= 0) return;

    if (buy <= pay) {
      buyInput.classList.add("border-red-500");
      payInput.classList.add("border-red-500");

      const err = document.createElement("div");
      err.id = "buy-x-pay-y-error";
      err.className =
        "bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6";
      err.innerHTML =
        "<strong>Error:</strong> La cantidad a llevar debe ser mayor que la cantidad a pagar";

      gridContainer.insertAdjacentElement("afterend", err);
      return;
    }

    buyInput.classList.remove("border-red-500");
    payInput.classList.remove("border-red-500");

    const discount = (((buy - pay) / buy) * 100).toFixed(2);

    const prev = document.createElement("div");
    prev.id = "buy-x-pay-y-preview";
    prev.className =
      "bg-blue-50 border-l-4 border-blue-500 text-blue-800 px-4 py-3 rounded mb-6";
    prev.innerHTML = `
            <div class="flex items-center gap-3">
                <div>
                    <div class="text-sm"><strong>Vista previa:</strong> Lleva ${buy} Paga ${pay} = <strong>${discount}% OFF</strong> por set</div>
                    <div class="text-xs text-blue-600 mt-1">Cada ${buy} unidades, ${buy - pay} ${buy - pay === 1 ? "es" : "son"} gratis</div>
                </div>
            </div>
        `;

    gridContainer.insertAdjacentElement("afterend", prev);
  },

  async renderAttributeFieldsOptimized(fields, fragment) {
    const attributes =
      this.state.cachedData?.attributes || (await this.fetchAttributes());

    for (const field of fields) {
      if (field.type === "attribute_select") {
        const div = document.createElement("div");
        div.className = "mb-6";

        const label = document.createElement("label");
        label.className = "block text-sm font-bold mb-2";
        label.textContent = field.label;
        div.appendChild(label);

        const select = document.createElement("select");
        select.name = `config[${field.key}]`;
        select.required = true;
        select.className =
          "w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500";

        const defaultOpt = document.createElement("option");
        defaultOpt.value = "";
        defaultOpt.textContent = "Seleccionar atributo...";
        select.appendChild(defaultOpt);

        attributes.forEach((a) => {
          const opt = document.createElement("option");
          opt.value = a.slug;
          opt.textContent = a.name;
          select.appendChild(opt);
        });

        div.appendChild(select);

        if (field.description) {
          const desc = document.createElement("p");
          desc.className = "text-sm text-gray-500 mt-1";
          desc.textContent = field.description;
          div.appendChild(desc);
        }

        fragment.appendChild(div);
      } else if (field.type === "attribute_value_select") {
        const div = document.createElement("div");
        div.className = "mb-6";

        const label = document.createElement("label");
        label.className = "block text-sm font-bold mb-2";
        label.textContent = field.label;
        div.appendChild(label);

        const select = document.createElement("select");
        select.name = `config[${field.key}]`;
        select.required = true;
        select.id = "attribute-value-select";
        select.className =
          "w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500";

        const defaultOpt = document.createElement("option");
        defaultOpt.value = "";
        defaultOpt.textContent = "Primero selecciona un atributo...";
        select.appendChild(defaultOpt);

        div.appendChild(select);

        if (field.description) {
          const desc = document.createElement("p");
          desc.className = "text-sm text-gray-500 mt-1";
          desc.textContent = field.description;
          div.appendChild(desc);
        }

        fragment.appendChild(div);
      } else if (
        field.key === "discount_type" ||
        field.key === "discount_value"
      ) {
        if (field.key === "discount_type") {
          const valueField = fields.find((f) => f.key === "discount_value");
          const grid = document.createElement("div");
          grid.className = "grid grid-cols-2 gap-6 mb-6";
          grid.appendChild(this.createFieldElement(field));
          grid.appendChild(this.createFieldElement(valueField));
          fragment.appendChild(grid);
        }
      } else if (field.key !== "discount_value") {
        fragment.appendChild(this.createFieldElement(field));
      }
    }
  },

  async fetchAttributes() {
    const res = await fetch(pwoaData.ajaxUrl, {
      method: "POST",
      body: new URLSearchParams({
        action: "pwoa_get_attributes",
        nonce: pwoaData.nonce,
      }),
    });

    const data = await res.json();
    return data.success ? data.data : [];
  },

  async loadAttributeTerms(attributeSlug) {
    const select = document.getElementById("attribute-value-select");
    if (!select || !attributeSlug) return;

    select.innerHTML = '<option value="">Cargando...</option>';

    const res = await fetch(pwoaData.ajaxUrl, {
      method: "POST",
      body: new URLSearchParams({
        action: "pwoa_get_attribute_terms",
        attribute_slug: attributeSlug,
        nonce: pwoaData.nonce,
      }),
    });

    const data = await res.json();

    if (data.success && data.data.length) {
      select.innerHTML =
        '<option value="">Seleccionar valor...</option>' +
        data.data
          .map((t) => `<option value="${t.slug}">${t.name}</option>`)
          .join("");
    } else {
      select.innerHTML = '<option value="">No hay valores disponibles</option>';
    }

    this.previewAttributeDiscount();
  },

  previewAttributeDiscount() {
    const attrSlug = document.querySelector(
      '[name="config[attribute_slug]"]',
    )?.value;
    const attrValue = document.querySelector(
      '[name="config[attribute_value]"]',
    )?.value;
    const minQty =
      parseInt(
        document.querySelector('[name="config[min_quantity]"]')?.value,
      ) || 0;
    const discValue =
      parseFloat(
        document.querySelector('[name="config[discount_value]"]')?.value,
      ) || 0;
    const maxApps =
      parseInt(
        document.querySelector('[name="config[max_applications]"]')?.value,
      ) || 0;

    let preview = document.getElementById("attr-discount-preview");
    if (preview) preview.remove();

    if (!attrSlug || !attrValue || minQty <= 0 || discValue <= 0) return;

    const attrName =
      document.querySelector('[name="config[attribute_slug]"] option:checked')
        ?.text || "";
    const valueName =
      document.querySelector('[name="config[attribute_value]"] option:checked')
        ?.text || "";

    const prev = document.createElement("div");
    prev.id = "attr-discount-preview";
    prev.className =
      "bg-blue-50 border-l-4 border-blue-500 text-blue-800 px-4 py-3 rounded mb-6";

    let maxText = maxApps > 0 ? ` (máximo ${maxApps * minQty} productos)` : "";

    prev.innerHTML = `
            <div>
                <div class="text-sm"><strong>Vista previa:</strong> Cada ${minQty} productos de "${valueName}" = <strong>${discValue}% OFF</strong></div>
                <div class="text-xs text-blue-600 mt-1">Máximo ${maxApps > 0 ? maxApps + " aplicaciones" : "ilimitado"}${maxText}</div>
            </div>
        `;

    const maxField = document.querySelector(
      '[name="config[max_applications]"]',
    );
    if (maxField) {
      maxField.closest(".mb-6").insertAdjacentElement("afterend", prev);
    }
  },
};

document.addEventListener("DOMContentLoaded", () => PWOAWizard.init());

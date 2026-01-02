const PWOAWizard = {
    state: {
        objective: null,
        strategy: null,
        strategyData: null,
        editMode: false,
        editId: null,
        selectedProducts: []
    },

    init() {
        const editId = new URLSearchParams(location.search).get('edit');

        if (editId) {
            this.state.editMode = true;
            this.state.editId = editId;
            this.loadCampaign(editId);
        }

        // Event delegation - UN solo listener para todo
        document.addEventListener('click', e => this.handleClick(e));
        document.addEventListener('input', e => this.handleInput(e));
        document.addEventListener('submit', e => this.handleSubmit(e));

        // Click outside para cerrar dropdowns
        document.addEventListener('click', e => {
            if (!e.target.closest('.repeater-product-search')) {
                document.querySelectorAll('.repeater-search-results').forEach(d => d.classList.add('hidden'));
            }
        });
    },

    handleClick(e) {
        const t = e.target;

        // Objectives
        if (t.closest('.objective-btn')) {
            const btn = t.closest('.objective-btn');
            this.selectObjective(btn.dataset.objective, btn.dataset.title);
        }

        // Strategies
        else if (t.closest('.strategy-card')) {
            const data = JSON.parse(t.closest('.strategy-card').dataset.strategy);
            this.selectStrategy(data);
        }

        // Breadcrumb
        else if (t.id === 'crumb-objective') this.goToStep('objective');
        else if (t.id === 'crumb-strategy') this.goToStep('strategy');

        // Back buttons
        else if (t.id === 'btn-back') this.goToStep('objective');
        else if (t.id === 'btn-back-config') this.goToStep('strategy');
        else if (t.id === 'btn-cancel' && confirm('¿Descartar cambios?')) location.href = '?page=pwoa-dashboard';

        // Repeater actions
        else if (t.closest('.add-repeater')) {
            const key = t.closest('.add-repeater').dataset.fieldKey;
            this.addRepeaterRow(key);
        }
        else if (t.closest('.remove-repeater')) {
            e.stopPropagation();
            const row = t.closest('.repeater-row');
            const key = row.closest('[id^="repeater-"]').id.replace('repeater-', '');
            row.remove();
            this.checkDuplicates(key);
        }

        // Accordion
        else if (t.closest('.repeater-header')) {
            if (t.closest('.remove-repeater')) return;
            const row = t.closest('.repeater-row');
            const content = row.querySelector('.repeater-content');
            const icon = row.querySelector('.accordion-icon');
            const isOpen = content.style.display !== 'none';
            content.style.display = isOpen ? 'none' : 'block';
            icon.style.transform = isOpen ? 'rotate(-90deg)' : 'rotate(0deg)';
        }

        // Product search results
        else if (t.closest('.repeater-product-result')) {
            const el = t.closest('.repeater-product-result');
            const input = el.closest('.repeater-content').querySelector('.repeater-product-search');
            this.selectProduct(el, input);
        }

        // Remove selected product (filters section)
        else if (t.closest('.remove-product')) {
            const id = t.closest('.remove-product').dataset.id;
            this.state.selectedProducts = this.state.selectedProducts.filter(p => p.id !== id);
            this.renderSelectedProducts();
        }

        // Validate/show products
        else if (t.id === 'btn-validate-filters') this.validateFilters();
        else if (t.id === 'btn-show-products') this.showMatchingProducts();

        // Modal
        else if (t.id === 'close-modal' || t.id === 'close-modal-btn' || t.id === 'products-modal') {
            document.getElementById('products-modal').classList.add('hidden');
        }
    },

    handleInput(e) {
        const t = e.target;

        // Product search en repeater
        if (t.classList.contains('repeater-product-search')) {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => this.searchInRepeater(t), 300);
        }

        // Product search en filtros
        else if (t.id === 'product-search') {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => this.searchProducts(t.value), 300);
        }
    },

    async handleSubmit(e) {
        if (e.target.id !== 'campaign-form') return;
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);
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
            action: this.state.editMode ? 'pwoa_update_campaign' : 'pwoa_save_campaign',
            nonce: pwoaData.nonce,
            name: formData.get('name'),
            objective: formData.get('objective'),
            strategy: formData.get('strategy'),
            discount_type: formData.get('discount_type') || config.discount_type,
            config: JSON.stringify(config),
            conditions: JSON.stringify(this.buildConditions()),
            stacking_mode: formData.get('stacking_mode'),
            priority: formData.get('priority'),
            start_date: formData.get('start_date'),
            end_date: formData.get('end_date')
        };

        if (this.state.editMode) data.campaign_id = this.state.editId;

        try {
            const res = await fetch(pwoaData.ajaxUrl, {
                method: 'POST',
                body: new URLSearchParams(data)
            });

            const result = await res.json();

            if (result.success) {
                alert('✓ ' + result.data.message);
                location.href = '?page=pwoa-dashboard';
            } else {
                alert('✗ Error: ' + (result.data || 'Error desconocido'));
            }
        } catch (error) {
            alert('✗ Error de conexión: ' + error.message);
        }
    },

    async loadCampaign(id) {
        const res = await fetch(pwoaData.ajaxUrl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'pwoa_get_campaign',
                campaign_id: id,
                nonce: pwoaData.nonce
            })
        });

        const data = await res.json();
        if (!data.success) {
            alert('Error al cargar campaña: ' + data.data);
            location.href = '?page=pwoa-dashboard';
            return;
        }

        const c = data.data;
        this.state.objective = c.objective;
        this.state.strategy = c.strategy;

        const strategies = await this.fetchStrategies(c.objective);
        this.state.strategyData = strategies.find(s => this.getStrategyKey(s.name) === c.strategy);

        if (!this.state.strategyData) return;

        document.getElementById('selected-strategy-title').textContent = this.state.strategyData.name;
        this.renderConfigFields(this.state.strategyData.config_fields || []);

        document.getElementById('form-objective').value = c.objective;
        document.getElementById('form-strategy').value = c.strategy;

        this.hideSteps();
        document.getElementById('step-config').classList.remove('hidden');
        this.toggleProductFilters();

        setTimeout(() => {
            document.getElementById('form-name').value = c.name;
            document.getElementById('form-priority').value = c.priority;
            document.getElementById('form-stacking-mode').value = c.stacking_mode;

            if (c.start_date && c.start_date !== '0000-00-00 00:00:00') {
                document.getElementById('form-start-date').value = c.start_date.replace(' ', 'T').substring(0, 16);
            }
            if (c.end_date && c.end_date !== '0000-00-00 00:00:00') {
                document.getElementById('form-end-date').value = c.end_date.replace(' ', 'T').substring(0, 16);
            }

            for (let key in c.config) {
                const input = document.querySelector(`[name="config[${key}]"]`);
                if (input) input.value = c.config[key];
            }

            document.getElementById('submit-btn').textContent = 'Actualizar Campaña';

            setTimeout(() => {
                this.loadRepeaters(c.config);
                this.loadConditions(c.conditions);
            }, 50);
        }, 100);
    },

    async selectObjective(objective, title) {
        this.state.objective = objective;
        document.getElementById('selected-objective-title').textContent = title;

        const strategies = await this.fetchStrategies(objective);
        this.renderStrategies(strategies);
        this.goToStep('strategy');
    },

    async fetchStrategies(objective) {
        const res = await fetch(pwoaData.ajaxUrl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'pwoa_get_strategies',
                objective: objective,
                nonce: pwoaData.nonce
            })
        });

        const data = await res.json();
        return data.success ? data.data : [];
    },

    selectStrategy(data) {
        this.state.strategyData = data;
        this.state.strategy = this.getStrategyKey(data.name);

        document.getElementById('selected-strategy-title').textContent = data.name;
        this.renderConfigFields(data.config_fields || []);

        document.getElementById('form-objective').value = this.state.objective;
        document.getElementById('form-strategy').value = this.state.strategy;

        this.goToStep('config');
        this.toggleProductFilters();
    },

    goToStep(step) {
        this.hideSteps();
        document.getElementById('step-' + step).classList.remove('hidden');
        this.updateBreadcrumb(step);
    },

    hideSteps() {
        ['objective', 'strategy', 'config'].forEach(s => {
            document.getElementById('step-' + s).classList.add('hidden');
        });
    },

    updateBreadcrumb(step) {
        const breadcrumb = document.getElementById('breadcrumb');

        if (step === 'objective') {
            breadcrumb.classList.add('hidden');
            return;
        }

        breadcrumb.classList.remove('hidden');

        // Reset classes
        ['crumb-objective', 'crumb-strategy', 'crumb-config'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.classList.remove('text-blue-600', 'font-semibold', 'text-gray-900', 'font-medium');
                el.classList.add('text-gray-500');
            }
        });

        if (step === 'strategy') {
            document.getElementById('crumb-objective').textContent = this.getObjectiveTitle();
            document.getElementById('crumb-objective').classList.remove('text-gray-500');
            document.getElementById('crumb-objective').classList.add('text-blue-600', 'font-semibold');
            document.getElementById('crumb-strategy-wrapper').classList.add('hidden');
            document.getElementById('crumb-config-wrapper').classList.add('hidden');
        }

        if (step === 'config') {
            document.getElementById('crumb-objective').textContent = this.getObjectiveTitle();
            document.getElementById('crumb-strategy').textContent = this.state.strategyData?.name || 'Estrategia';
            document.getElementById('crumb-config').textContent = 'Configuración';
            document.getElementById('crumb-config').classList.remove('text-gray-500');
            document.getElementById('crumb-config').classList.add('text-gray-900', 'font-medium');
            document.getElementById('crumb-strategy-wrapper').classList.remove('hidden');
            document.getElementById('crumb-config-wrapper').classList.remove('hidden');
        }
    },

    getObjectiveTitle() {
        const map = {
            basic: 'Básico',
            aov: 'Aumentar Valor del Carrito',
            liquidation: 'Liquidar Inventario',
            loyalty: 'Fidelización',
            urgency: 'Conversión Rápida'
        };
        return map[this.state.objective] || 'Objetivo';
    },

    toggleProductFilters() {
        const section = document.getElementById('product-filters-section');
        if (section) {
            section.classList.toggle('hidden', this.state.strategy === 'bulk_discount');
        }
    },

    renderStrategies(strategies) {
        const html = strategies.map(s => `
            <div class="strategy-card bg-white p-8 rounded-lg shadow mb-6 cursor-pointer hover:shadow-xl transition border-2 border-transparent hover:border-blue-500"
                 data-strategy='${JSON.stringify(s)}'>
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-2xl font-bold">${s.name}</h3>
                    <span class="text-yellow-500 text-xl">${'★'.repeat(s.effectiveness)}</span>
                </div>
                <p class="text-gray-600 mb-6 leading-relaxed">${s.description}</p>
                <div class="bg-blue-50 p-4 rounded">
                    <strong class="text-blue-900">Cuándo usar:</strong> 
                    <span class="text-blue-800">${s.when_to_use}</span>
                </div>
            </div>
        `).join('');

        document.getElementById('strategies-list').innerHTML = html;
    },

    renderConfigFields(fields) {
        if (!fields.length) {
            document.getElementById('dynamic-fields').innerHTML = '<p class="text-gray-500">Esta estrategia no requiere configuración adicional.</p>';
            return;
        }

        const html = fields.map(f => f.type === 'repeater' ? this.renderRepeaterField(f) : this.renderField(f)).join('');
        document.getElementById('dynamic-fields').innerHTML = html;
    },

    renderField(f) {
        const req = f.required ? 'required' : '';
        const desc = f.description ? `<p class="text-sm text-gray-500 mt-1">${f.description}</p>` : '';
        let input = '';

        switch(f.type) {
            case 'select':
                const opts = Object.entries(f.options || {}).map(([v, l]) => `<option value="${v}">${l}</option>`).join('');
                input = `<select name="config[${f.key}]" ${req} class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">${opts}</select>`;
                break;
            case 'number':
                input = `<input type="number" name="config[${f.key}]" ${req} value="${f.default || ''}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">`;
                break;
            case 'datetime':
                input = `<input type="datetime-local" name="config[${f.key}]" ${req} class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">`;
                break;
            default:
                input = `<input type="text" name="config[${f.key}]" ${req} class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">`;
        }

        return `<div class="mb-6"><label class="block text-sm font-bold mb-2">${f.label}</label>${input}${desc}</div>`;
    },

    renderRepeaterField(f) {
        const btnText = f.key === 'bulk_items' ? 'Agregar otro producto' : 'Agregar nivel';

        return `
            <div class="mb-8">
                <h3 class="text-xl font-bold mb-2">${f.label}</h3>
                ${f.description ? `<p class="text-sm text-gray-600 mb-6">${f.description}</p>` : ''}
                <div id="repeater-${f.key}" data-field-key="${f.key}">
                    ${this.renderRepeaterRow(f, 0)}
                </div>
                <button type="button" class="add-repeater mt-6 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors inline-flex items-center gap-2" data-field-key="${f.key}">
                    <span class="text-xl">+</span>
                    <span>${btnText}</span>
                </button>
            </div>
        `;
    },

    renderRepeaterRow(field, idx) {
        const key = field.key;
        let productSearch = '';
        let otherFields = '';

        field.fields.forEach(sf => {
            const req = sf.required ? 'required' : '';
            const desc = sf.description ? `<p class="text-xs text-gray-500 mt-1">${sf.description}</p>` : '';

            if (sf.type === 'product_search') {
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
                let input = '';

                if (sf.type === 'select') {
                    const opts = Object.entries(sf.options || {}).map(([v, l]) => `<option value="${v}">${l}</option>`).join('');
                    input = `<select name="config[${key}][${idx}][${sf.key}]" ${req} class="w-full px-3 py-2 border rounded-lg text-sm">${opts}</select>`;
                } else if (sf.type === 'text') {
                    const ph = sf.key === 'badge_text' ? 'Ej: "Oferta única"' : '';
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
        const container = document.getElementById('repeater-' + key);
        const field = this.state.strategyData.config_fields.find(f => f.key === key);
        const idx = container.querySelectorAll('.repeater-row').length;

        container.insertAdjacentHTML('beforeend', this.renderRepeaterRow(field, idx));
    },

    async searchInRepeater(input) {
        const query = input.value;
        const resultsDiv = input.nextElementSibling;

        if (query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }

        const res = await fetch(pwoaData.ajaxUrl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'pwoa_search_products',
                search: query,
                nonce: pwoaData.nonce
            })
        });

        const data = await res.json();

        if (!data.success || !data.data.length) {
            resultsDiv.classList.add('hidden');
            return;
        }

        resultsDiv.innerHTML = data.data.map(p => `
            <div class="repeater-product-result p-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0"
                 data-id="${p.id}" data-name="${p.name}">
                <div class="font-semibold text-sm">${p.name}</div>
                <div class="text-xs text-gray-500">ID: ${p.id} | ${p.formatted_price}</div>
            </div>
        `).join('');

        resultsDiv.classList.remove('hidden');
    },

    selectProduct(el, input) {
        const id = el.dataset.id;
        const name = el.dataset.name;
        const row = input.closest('.repeater-row');

        // Set hidden input
        const hidden = input.parentElement.nextElementSibling;
        hidden.value = id;

        // Show selected
        const display = hidden.nextElementSibling;
        display.innerHTML = `<strong>✓</strong> ${name} <span class="text-gray-500">(ID: ${id})</span>`;

        // Update header preview
        const preview = row.querySelector('.product-name-preview');
        if (preview) preview.textContent = `- ${name}`;

        // Clear search
        input.value = '';
        input.nextElementSibling.classList.add('hidden');

        // Check duplicates
        const key = input.dataset.fieldKey;
        this.checkDuplicates(key);
    },

    checkDuplicates(key) {
        const container = document.getElementById('repeater-' + key);
        if (!container) return;

        const rows = container.querySelectorAll('.repeater-row');
        const ids = {};

        rows.forEach((row, i) => {
            const hidden = row.querySelector('.selected-product-id');
            if (!hidden?.value) return;

            const id = hidden.value;
            const warn = row.querySelector('.duplicate-warning');

            if (ids[id]) {
                row.classList.remove('border-gray-200');
                row.classList.add('border-yellow-400', 'bg-yellow-50');

                if (!warn) {
                    const content = row.querySelector('.repeater-content');
                    const w = document.createElement('div');
                    w.className = 'duplicate-warning bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 px-4 py-2 rounded text-sm mb-3';
                    w.innerHTML = `⚠️ <strong>Duplicado</strong> - Ya configurado en Producto ${ids[id] + 1}. Ambas se aplicarán.`;
                    content.insertBefore(w, content.firstChild);
                }
            } else {
                row.classList.remove('border-yellow-400', 'bg-yellow-50');
                row.classList.add('border-gray-200');
                if (warn) warn.remove();
                ids[id] = i;
            }
        });
    },

    loadRepeaters(config) {
        if (!this.state.strategyData?.config_fields) return;

        this.state.strategyData.config_fields.forEach(f => {
            if (f.type !== 'repeater' || !config[f.key]?.length) return;

            const container = document.getElementById('repeater-' + f.key);
            if (!container) return;

            container.innerHTML = '';

            config[f.key].forEach((rowData, i) => {
                container.insertAdjacentHTML('beforeend', this.renderRepeaterRow(f, i));

                f.fields.forEach(sf => {
                    if (sf.type === 'product_search' && rowData[sf.key]) {
                        const hidden = document.querySelector(`[name="config[${f.key}][${i}][${sf.key}]"]`);
                        if (hidden) {
                            hidden.value = rowData[sf.key];
                            this.loadProductName(rowData[sf.key], hidden);
                        }
                    } else {
                        const input = document.querySelector(`[name="config[${f.key}][${i}][${sf.key}]"]`);
                        if (input && rowData[sf.key] !== undefined) input.value = rowData[sf.key];
                    }
                });
            });

            this.checkDuplicates(f.key);
        });
    },

    async loadProductName(id, hidden) {
        const res = await fetch(pwoaData.ajaxUrl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'pwoa_search_products',
                search: id,
                nonce: pwoaData.nonce
            })
        });

        const data = await res.json();
        if (data.success && data.data[0]) {
            const p = data.data[0];
            const display = hidden.nextElementSibling;
            if (display?.classList.contains('selected-product-display')) {
                display.innerHTML = `<strong>✓</strong> ${p.name} <span class="text-gray-500">(ID: ${p.id})</span>`;

                const row = hidden.closest('.repeater-row');
                const preview = row?.querySelector('.product-name-preview');
                if (preview) preview.textContent = `- ${p.name}`;
            }
        }
    },

    loadConditions(cond) {
        if (!cond) return;

        // Load selected products
        if (cond.product_ids?.length) {
            cond.product_ids.forEach(async id => {
                const res = await fetch(pwoaData.ajaxUrl, {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'pwoa_search_products',
                        search: id,
                        nonce: pwoaData.nonce
                    })
                });

                const data = await res.json();
                if (data.success && data.data[0]) {
                    const p = data.data[0];
                    this.state.selectedProducts.push({ id: p.id.toString(), name: p.name, sku: p.sku || '' });
                    this.renderSelectedProducts();
                }
            });
        }

        // Load categories
        if (cond.category_ids?.length) {
            const select = document.getElementById('form-categories');
            if (select) {
                cond.category_ids.forEach(id => {
                    const opt = select.querySelector(`option[value="${id}"]`);
                    if (opt) opt.selected = true;
                });
            }
        }

        // Load prices
        if (cond.min_price) {
            const input = document.getElementById('form-min-price');
            if (input) input.value = cond.min_price;
        }
        if (cond.max_price) {
            const input = document.getElementById('form-max-price');
            if (input) input.value = cond.max_price;
        }
    },

    async searchProducts(query) {
        if (query.length < 2) {
            document.getElementById('product-search-results').classList.add('hidden');
            return;
        }

        const res = await fetch(pwoaData.ajaxUrl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'pwoa_search_products',
                search: query,
                nonce: pwoaData.nonce
            })
        });

        const data = await res.json();
        if (!data.success) return;

        const container = document.getElementById('product-search-results');

        if (!data.data.length) {
            container.classList.add('hidden');
            return;
        }

        container.innerHTML = data.data.map(p => `
            <div class="p-3 hover:bg-gray-50 cursor-pointer border-b product-result"
                 data-id="${p.id}" data-name="${p.name}" data-sku="${p.sku || ''}">
                <div class="font-semibold text-sm">${p.name}</div>
                <div class="text-xs text-gray-500">${p.sku ? 'SKU: ' + p.sku + ' | ' : ''}ID: ${p.id} | ${p.formatted_price}</div>
            </div>
        `).join('');

        container.classList.remove('hidden');

        // Bind click
        container.querySelectorAll('.product-result').forEach(el => {
            el.addEventListener('click', () => {
                const prod = { id: el.dataset.id, name: el.dataset.name, sku: el.dataset.sku };
                if (!this.state.selectedProducts.some(p => p.id === prod.id)) {
                    this.state.selectedProducts.push(prod);
                    this.renderSelectedProducts();
                }
                container.classList.add('hidden');
                document.getElementById('product-search').value = '';
            });
        });
    },

    renderSelectedProducts() {
        const container = document.getElementById('selected-products');
        container.innerHTML = this.state.selectedProducts.map(p => `
            <div class="bg-blue-50 border border-blue-200 rounded px-3 py-1 flex items-center gap-2">
                <span class="text-sm">${p.name}</span>
                <button type="button" class="text-blue-600 hover:text-blue-800 font-bold remove-product" data-id="${p.id}">×</button>
            </div>
        `).join('');

        document.getElementById('form-product-ids').value = this.state.selectedProducts.map(p => p.id).join(',');
    },

    buildConditions() {
        if (this.state.strategy === 'bulk_discount') return {};

        const cond = {};

        if (this.state.selectedProducts.length) {
            cond.product_ids = this.state.selectedProducts.map(p => parseInt(p.id));
        }

        const cats = document.getElementById('form-categories');
        if (cats) {
            const selected = Array.from(cats.selectedOptions).map(o => parseInt(o.value));
            if (selected.length) cond.category_ids = selected;
        }

        const minPrice = document.getElementById('form-min-price');
        if (minPrice?.value) cond.min_price = parseFloat(minPrice.value);

        const maxPrice = document.getElementById('form-max-price');
        if (maxPrice?.value) cond.max_price = parseFloat(maxPrice.value);

        return cond;
    },

    async validateFilters() {
        const cond = this.buildConditions();
        const span = document.getElementById('matching-count');
        span.textContent = 'Validando...';

        const res = await fetch(pwoaData.ajaxUrl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'pwoa_validate_conditions',
                conditions: JSON.stringify(cond),
                nonce: pwoaData.nonce
            })
        });

        const data = await res.json();
        span.textContent = data.success ? data.data.count : 'Error';
    },

    async showMatchingProducts() {
        const modal = document.getElementById('products-modal');
        const list = document.getElementById('modal-products-list');

        list.innerHTML = '<p class="text-center text-gray-500 py-8">Cargando...</p>';
        modal.classList.remove('hidden');

        const cond = this.buildConditions();
        const res = await fetch(pwoaData.ajaxUrl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'pwoa_get_matching_products',
                conditions: JSON.stringify(cond),
                nonce: pwoaData.nonce
            })
        });

        const data = await res.json();

        if (!data.success) {
            list.innerHTML = '<p class="text-center text-red-500 py-8">Error al cargar</p>';
            return;
        }

        document.getElementById('modal-count').textContent = data.data.count;
        document.getElementById('matching-count').textContent = data.data.count;

        if (!data.data.products.length) {
            list.innerHTML = '<p class="text-center text-gray-500 py-8">No hay productos</p>';
            return;
        }

        list.innerHTML = data.data.products.map(p => `
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                <div class="flex-1">
                    <p class="font-semibold text-gray-900">${p.name}</p>
                    <p class="text-sm text-gray-500">${p.sku ? 'SKU: ' + p.sku + ' | ' : ''}ID: ${p.id}</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-gray-900">${p.formatted_price}</p>
                    ${p.stock ? '<p class="text-xs text-gray-500">Stock: ' + p.stock + '</p>' : ''}
                    <a href="${pwoaData.adminUrl}post.php?post=${p.id}&action=edit" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 mt-1 inline-block">Ver →</a>
                </div>
            </div>
        `).join('');
    },

    getStrategyKey(name) {
        const map = {
            'Descuento Básico por Productos': 'basic_discount',
            'Descuento por Monto Mínimo': 'min_amount',
            'Envío Gratis sobre Monto Mínimo': 'free_shipping',
            'Descuento Escalonado por Cantidad': 'tiered_discount',
            'Descuentos por Volumen (Bulk)': 'bulk_discount',
            'Descuento por Fecha de Vencimiento': 'expiry_based',
            'Descuento por Stock Bajo': 'low_stock',
            'Descuento por Compras Recurrentes': 'recurring_purchase',
            'Flash Sale (Oferta Relámpago)': 'flash_sale'
        };
        return map[name] || '';
    }
};

document.addEventListener('DOMContentLoaded', () => PWOAWizard.init());
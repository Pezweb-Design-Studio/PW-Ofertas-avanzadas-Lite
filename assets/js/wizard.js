const PWOAWizard = {

    currentObjective: null,
    currentObjectiveTitle: null,
    currentStrategy: null,
    currentStrategyTitle: null,
    strategyData: null,
    editMode: false,
    editCampaignId: null,
    selectedProducts: [],

    init() {
        const urlParams = new URLSearchParams(window.location.search);
        const editId = urlParams.get('edit');

        if (editId) {
            this.editMode = true;
            this.editCampaignId = editId;
            this.loadCampaignForEdit(editId);
        } else {
            this.bindObjectiveButtons();
        }

        this.bindBackButtons();
        this.bindBreadcrumb();
        this.bindForm();
        this.bindModal();
    },

    async loadCampaignForEdit(campaignId) {
        try {
            const response = await fetch(pwoaData.ajaxUrl, {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'pwoa_get_campaign',
                    campaign_id: campaignId,
                    nonce: pwoaData.nonce
                })
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.data || 'Error al cargar campaña');
            }

            const campaign = result.data;

            this.currentObjective = campaign.objective;
            this.currentStrategy = campaign.strategy;

            const response2 = await fetch(pwoaData.ajaxUrl, {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'pwoa_get_strategies',
                    objective: campaign.objective,
                    nonce: pwoaData.nonce
                })
            });

            const strategies = await response2.json();

            if (strategies.success) {
                const strategyData = strategies.data.find(s => this.getStrategyKey(s.name) === campaign.strategy);

                if (strategyData) {
                    this.strategyData = strategyData;
                    this.currentStrategyTitle = strategyData.name;

                    document.getElementById('selected-strategy-title').textContent = strategyData.name;
                    this.renderConfigFields(strategyData.config_fields || []);

                    document.getElementById('form-objective').value = campaign.objective;
                    document.getElementById('form-strategy').value = campaign.strategy;

                    document.getElementById('step-objective').classList.add('hidden');
                    document.getElementById('step-strategy').classList.add('hidden');
                    document.getElementById('step-config').classList.remove('hidden');

                    // Bind de filtros de productos
                    setTimeout(() => {
                        this.bindProductFilters();
                    }, 50);

                    setTimeout(() => {
                        document.getElementById('form-name').value = campaign.name;
                        document.getElementById('form-priority').value = campaign.priority;
                        document.getElementById('form-stacking-mode').value = campaign.stacking_mode;

                        if (campaign.start_date && campaign.start_date !== '0000-00-00 00:00:00') {
                            const startFormatted = campaign.start_date.replace(' ', 'T').substring(0, 16);
                            document.getElementById('form-start-date').value = startFormatted;
                        }

                        if (campaign.end_date && campaign.end_date !== '0000-00-00 00:00:00') {
                            const endFormatted = campaign.end_date.replace(' ', 'T').substring(0, 16);
                            document.getElementById('form-end-date').value = endFormatted;
                        }

                        for (let key in campaign.config) {
                            const input = document.querySelector(`[name="config[${key}]"]`);
                            if (input) {
                                input.value = campaign.config[key];
                            }
                        }

                        document.getElementById('submit-btn').textContent = 'Actualizar Campaña';

                        // Cargar repeaters después de un pequeño delay para asegurar que el DOM esté listo
                        setTimeout(() => {
                            this.loadRepeaters(campaign.config);
                            this.loadConditions(campaign.conditions);
                        }, 50);
                    }, 100);
                }
            }

        } catch (error) {
            alert('Error al cargar campaña: ' + error.message);
            window.location.href = '?page=pwoa-dashboard';
        }
    },

    loadRepeaters(config) {
        // Buscar todos los repeaters en la estrategia actual
        if (!this.strategyData || !this.strategyData.config_fields) return;

        this.strategyData.config_fields.forEach(field => {
            if (field.type === 'repeater' && config[field.key]) {
                const repeaterData = config[field.key];

                // Verificar que sea un array con datos
                if (!Array.isArray(repeaterData) || repeaterData.length === 0) return;

                const container = document.getElementById(`repeater-${field.key}`);
                if (!container) return;

                // Limpiar el repeater (remover la fila vacía inicial)
                container.innerHTML = '';

                // Renderizar cada fila con sus datos
                repeaterData.forEach((rowData, index) => {
                    const row = this.renderRepeaterRow(field, index);
                    container.insertAdjacentHTML('beforeend', row);

                    // Rellenar los valores de cada campo en la fila
                    field.fields.forEach(subField => {
                        const input = document.querySelector(`[name="config[${field.key}][${index}][${subField.key}]"]`);
                        if (input && rowData[subField.key] !== undefined) {
                            input.value = rowData[subField.key];
                        }
                    });
                });

                // Re-bind de los botones de eliminar
                this.bindRepeaterButtons();
            }
        });
    },

    loadConditions(conditions) {
        if (!conditions) return;

        console.log('PWOA Debug - Cargando conditions:', conditions);

        // Cargar productos seleccionados
        if (conditions.product_ids && conditions.product_ids.length > 0) {
            // Buscar los productos por sus IDs
            conditions.product_ids.forEach(async (productId) => {
                const response = await fetch(pwoaData.ajaxUrl, {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'pwoa_search_products',
                        search: productId.toString(),
                        nonce: pwoaData.nonce
                    })
                });

                const data = await response.json();
                if (data.success && data.data.length > 0) {
                    const product = data.data[0];
                    this.addSelectedProduct({
                        id: product.id.toString(),
                        name: product.name,
                        sku: product.sku || ''
                    });
                }
            });
        }

        // Cargar categorías seleccionadas
        if (conditions.category_ids && conditions.category_ids.length > 0) {
            const categoriesSelect = document.getElementById('form-categories');
            if (categoriesSelect) {
                conditions.category_ids.forEach(catId => {
                    const option = categoriesSelect.querySelector(`option[value="${catId}"]`);
                    if (option) {
                        option.selected = true;
                    }
                });
            }
        }

        // Cargar precio mínimo
        if (conditions.min_price) {
            const minPriceInput = document.getElementById('form-min-price');
            if (minPriceInput) {
                minPriceInput.value = conditions.min_price;
            }
        }

        // Cargar precio máximo
        if (conditions.max_price) {
            const maxPriceInput = document.getElementById('form-max-price');
            if (maxPriceInput) {
                maxPriceInput.value = conditions.max_price;
            }
        }
    },

    bindObjectiveButtons() {
        document.querySelectorAll('.objective-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const objective = e.currentTarget.dataset.objective;
                const title = e.currentTarget.dataset.title;
                this.selectObjective(objective, title);
            });
        });
    },

    bindBackButtons() {
        const btnBack = document.getElementById('btn-back');
        const btnBackConfig = document.getElementById('btn-back-config');
        const btnCancel = document.getElementById('btn-cancel');

        if (btnBack) {
            btnBack.addEventListener('click', () => this.goToStep('objective'));
        }

        if (btnBackConfig) {
            btnBackConfig.addEventListener('click', () => this.goToStep('strategy'));
        }

        if (btnCancel) {
            btnCancel.addEventListener('click', () => {
                if (confirm('¿Descartar cambios?')) {
                    window.location.href = '?page=pwoa-dashboard';
                }
            });
        }
    },

    bindBreadcrumb() {
        const crumbObjective = document.getElementById('crumb-objective');
        const crumbStrategy = document.getElementById('crumb-strategy');

        if (crumbObjective) {
            crumbObjective.addEventListener('click', () => this.goToStep('objective'));
        }

        if (crumbStrategy) {
            crumbStrategy.addEventListener('click', () => this.goToStep('strategy'));
        }
    },

    bindForm() {
        const form = document.getElementById('campaign-form');
        if (form) {
            form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
    },

    bindModal() {
        document.addEventListener('click', (e) => {
            if (e.target.id === 'close-modal' || e.target.id === 'close-modal-btn' || e.target.id === 'products-modal') {
                this.closeProductsModal();
            }
        });
    },

    goToStep(step) {
        document.getElementById('step-objective').classList.add('hidden');
        document.getElementById('step-strategy').classList.add('hidden');
        document.getElementById('step-config').classList.add('hidden');

        document.getElementById('step-' + step).classList.remove('hidden');

        this.updateBreadcrumb(step);
    },

    updateBreadcrumb(step) {
        const breadcrumb = document.getElementById('breadcrumb');
        const crumbObjective = document.getElementById('crumb-objective');
        const crumbStrategyWrapper = document.getElementById('crumb-strategy-wrapper');
        const crumbStrategy = document.getElementById('crumb-strategy');
        const crumbConfigWrapper = document.getElementById('crumb-config-wrapper');
        const crumbConfig = document.getElementById('crumb-config');

        if (step === 'objective') {
            breadcrumb.classList.add('hidden');
            return;
        }

        breadcrumb.classList.remove('hidden');

        crumbObjective.classList.remove('text-blue-600', 'font-semibold');
        crumbObjective.classList.add('text-gray-500');
        crumbStrategy.classList.remove('text-blue-600', 'font-semibold');
        crumbStrategy.classList.add('text-gray-500');
        crumbConfig.classList.remove('text-gray-900', 'font-medium');
        crumbConfig.classList.add('text-gray-500');

        if (step === 'strategy') {
            crumbObjective.textContent = this.currentObjectiveTitle || 'Objetivo';
            crumbObjective.classList.remove('text-gray-500');
            crumbObjective.classList.add('text-blue-600', 'font-semibold');
            crumbStrategyWrapper.classList.add('hidden');
            crumbConfigWrapper.classList.add('hidden');
        }

        if (step === 'config') {
            crumbObjective.textContent = this.currentObjectiveTitle || 'Objetivo';
            crumbStrategy.textContent = this.currentStrategyTitle || 'Estrategia';
            crumbConfig.textContent = 'Configuración';

            crumbObjective.classList.remove('text-gray-500');
            crumbObjective.classList.add('text-gray-500');
            crumbStrategy.classList.remove('text-gray-500');
            crumbStrategy.classList.add('text-gray-500');
            crumbConfig.classList.remove('text-gray-500');
            crumbConfig.classList.add('text-gray-900', 'font-medium');

            crumbStrategyWrapper.classList.remove('hidden');
            crumbConfigWrapper.classList.remove('hidden');
        }
    },

    async selectObjective(objective, title) {
        this.currentObjective = objective;
        this.currentObjectiveTitle = title;

        const response = await fetch(pwoaData.ajaxUrl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'pwoa_get_strategies',
                objective: objective,
                nonce: pwoaData.nonce
            })
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('selected-objective-title').textContent = title;
            this.renderStrategies(data.data);
            this.goToStep('strategy');
        } else {
            alert('Error al cargar estrategias: ' + (data.data || 'Error desconocido'));
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

        document.querySelectorAll('.strategy-card').forEach(card => {
            card.addEventListener('click', (e) => {
                const strategyData = JSON.parse(e.currentTarget.dataset.strategy);
                this.selectStrategy(strategyData);
            });
        });
    },

    selectStrategy(strategyData) {
        this.strategyData = strategyData;
        this.currentStrategy = this.getStrategyKey(strategyData.name);
        this.currentStrategyTitle = strategyData.name;

        document.getElementById('selected-strategy-title').textContent = strategyData.name;

        this.renderConfigFields(strategyData.config_fields || []);

        document.getElementById('form-objective').value = this.currentObjective;
        document.getElementById('form-strategy').value = this.currentStrategy;

        this.goToStep('config');

        // Bind de filtros de productos después de mostrar el step
        this.bindProductFilters();
    },

    getStrategyKey(name) {
        const map = {
            'Descuento Básico por Productos': 'basic_discount',
            'Descuento por Monto Mínimo': 'min_amount',
            'Envío Gratis sobre Monto Mínimo': 'free_shipping',
            'Descuento Escalonado por Cantidad': 'tiered_discount',
            'Descuento por Fecha de Vencimiento': 'expiry_based',
            'Descuento por Stock Bajo': 'low_stock',
            'Descuento por Compras Recurrentes': 'recurring_purchase',
            'Flash Sale (Oferta Relámpago)': 'flash_sale'
        };
        return map[name] || '';
    },

    renderConfigFields(fields) {
        if (!fields || fields.length === 0) {
            document.getElementById('dynamic-fields').innerHTML = '<p class="text-gray-500">Esta estrategia no requiere configuración adicional.</p>';
            return;
        }

        const html = fields.map(field => {
            if (field.type === 'repeater') {
                return this.renderRepeaterField(field);
            }
            return this.renderField(field);
        }).join('');

        document.getElementById('dynamic-fields').innerHTML = html;

        this.bindRepeaterButtons();
    },

    renderField(field) {
        const required = field.required ? 'required' : '';
        const description = field.description ? `<p class="text-sm text-gray-500 mt-1">${field.description}</p>` : '';

        let input = '';

        switch(field.type) {
            case 'select':
                const options = Object.entries(field.options || {})
                    .map(([value, label]) => `<option value="${value}">${label}</option>`)
                    .join('');
                input = `<select name="config[${field.key}]" ${required} class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">${options}</select>`;
                break;

            case 'number':
                input = `<input type="number" name="config[${field.key}]" ${required} value="${field.default || ''}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">`;
                break;

            case 'datetime':
                input = `<input type="datetime-local" name="config[${field.key}]" ${required} class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">`;
                break;

            default:
                input = `<input type="text" name="config[${field.key}]" ${required} class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">`;
        }

        return `
            <div class="mb-6">
                <label class="block text-sm font-bold mb-2">${field.label}</label>
                ${input}
                ${description}
            </div>
        `;
    },

    renderRepeaterField(field) {
        return `
            <div class="mb-6">
                <label class="block text-sm font-bold mb-4">${field.label}</label>
                ${field.description ? `<p class="text-sm text-gray-600 mb-4">${field.description}</p>` : ''}
                <div id="repeater-${field.key}" class="space-y-4" data-field-key="${field.key}">
                    ${this.renderRepeaterRow(field, 0)}
                </div>
                <button type="button" class="add-repeater mt-4 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700" data-field-key="${field.key}">
                    + Agregar nivel
                </button>
            </div>
        `;
    },

    renderRepeaterRow(field, index) {
        const fields = field.fields.map(subField => {
            const required = subField.required ? 'required' : '';
            const desc = subField.description ? `<p class="text-xs text-gray-500 mt-1">${subField.description}</p>` : '';
            return `
                <div>
                    <label class="block text-xs font-bold mb-1">${subField.label}</label>
                    <input type="number" name="config[${field.key}][${index}][${subField.key}]" ${required}
                           class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                    ${desc}
                </div>
            `;
        }).join('');

        return `
            <div class="repeater-row bg-gray-50 p-4 rounded border grid grid-cols-${field.fields.length + 1} gap-4">
                ${fields}
                <div class="flex items-end">
                    <button type="button" class="remove-repeater bg-red-600 text-white px-3 py-2 rounded hover:bg-red-700 text-sm">
                        Eliminar
                    </button>
                </div>
            </div>
        `;
    },

    bindRepeaterButtons() {
        document.querySelectorAll('.add-repeater').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const fieldKey = e.target.dataset.fieldKey;
                const container = document.getElementById('repeater-' + fieldKey);
                const field = this.strategyData.config_fields.find(f => f.key === fieldKey);
                const currentRows = container.querySelectorAll('.repeater-row').length;

                container.insertAdjacentHTML('beforeend', this.renderRepeaterRow(field, currentRows));
                this.bindRepeaterButtons();
            });
        });

        document.querySelectorAll('.remove-repeater').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.repeater-row').remove();
            });
        });
    },

    async handleSubmit(e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);

        const config = {};
        const repeaters = {};

        formData.forEach((value, key) => {
            if (key.includes('config[') && key.includes('][')) {
                const matches = key.match(/config\[(\w+)\]\[(\d+)\]\[(\w+)\]/);
                if (matches) {
                    const [, fieldKey, index, subKey] = matches;
                    if (!repeaters[fieldKey]) repeaters[fieldKey] = [];
                    if (!repeaters[fieldKey][index]) repeaters[fieldKey][index] = {};
                    repeaters[fieldKey][index][subKey] = value;
                }
            } else if (key.startsWith('config[')) {
                const fieldKey = key.match(/config\[(\w+)\]/)[1];
                config[fieldKey] = value;
            }
        });

        Object.assign(config, repeaters);

        const data = {
            action: this.editMode ? 'pwoa_update_campaign' : 'pwoa_save_campaign',
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

        if (this.editMode) {
            data.campaign_id = this.editCampaignId;
        }

        try {
            const response = await fetch(pwoaData.ajaxUrl, {
                method: 'POST',
                body: new URLSearchParams(data)
            });

            const result = await response.json();

            if (result.success) {
                alert('✓ ' + result.data.message);
                window.location.href = '?page=pwoa-dashboard';
            } else {
                alert('✗ Error: ' + (result.data || 'Error desconocido'));
            }
        } catch (error) {
            alert('✗ Error de conexión: ' + error.message);
        }
    },
    bindProductFilters() {
        const searchInput = document.getElementById('product-search');
        const validateBtn = document.getElementById('btn-validate-filters');
        const showProductsBtn = document.getElementById('btn-show-products');

        if (!searchInput || !validateBtn || !showProductsBtn) {
            console.log('PWOA Debug - Elementos de filtros:', {
                searchInput: !!searchInput,
                validateBtn: !!validateBtn,
                showProductsBtn: !!showProductsBtn
            });
            return;
        }

        console.log('PWOA Debug - Filtros bindeados correctamente');

        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => this.searchProducts(e.target.value), 300);
        });

        validateBtn.addEventListener('click', () => this.validateFilters());
        showProductsBtn.addEventListener('click', () => this.showMatchingProducts());
    },

    async searchProducts(query) {
        if (query.length < 2) {
            document.getElementById('product-search-results').classList.add('hidden');
            return;
        }

        const response = await fetch(pwoaData.ajaxUrl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'pwoa_search_products',
                search: query,
                nonce: pwoaData.nonce
            })
        });

        const data = await response.json();

        if (data.success) {
            this.renderProductResults(data.data);
        }
    },

    renderProductResults(products) {
        const container = document.getElementById('product-search-results');

        if (products.length === 0) {
            container.classList.add('hidden');
            return;
        }

        const html = products.map(p => `
            <div class="p-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0 product-result"
                 data-id="${p.id}"
                 data-name="${p.name}"
                 data-sku="${p.sku || ''}">
                <div class="font-semibold text-sm">${p.name}</div>
                <div class="text-xs text-gray-500">
                    ${p.sku ? 'SKU: ' + p.sku + ' | ' : ''}ID: ${p.id} | ${p.formatted_price}
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
        container.classList.remove('hidden');

        container.querySelectorAll('.product-result').forEach(el => {
            el.addEventListener('click', () => {
                this.addSelectedProduct({
                    id: el.dataset.id,
                    name: el.dataset.name,
                    sku: el.dataset.sku
                });
                container.classList.add('hidden');
                document.getElementById('product-search').value = '';
            });
        });
    },

    addSelectedProduct(product) {
        if (this.selectedProducts.some(p => p.id === product.id)) return;

        this.selectedProducts.push(product);
        this.renderSelectedProducts();
    },

    renderSelectedProducts() {
        const container = document.getElementById('selected-products');

        const html = this.selectedProducts.map(p => `
            <div class="bg-blue-50 border border-blue-200 rounded px-3 py-1 flex items-center gap-2">
                <span class="text-sm">${p.name}</span>
                <button type="button" 
                        class="text-blue-600 hover:text-blue-800 font-bold remove-product"
                        data-id="${p.id}">×</button>
            </div>
        `).join('');

        container.innerHTML = html;

        container.querySelectorAll('.remove-product').forEach(btn => {
            btn.addEventListener('click', () => {
                this.selectedProducts = this.selectedProducts.filter(p => p.id !== btn.dataset.id);
                this.renderSelectedProducts();
            });
        });

        document.getElementById('form-product-ids').value = this.selectedProducts.map(p => p.id).join(',');
    },

    buildConditions() {
        const conditions = {};

        if (this.selectedProducts.length > 0) {
            conditions.product_ids = this.selectedProducts.map(p => parseInt(p.id));
        }

        const categoriesSelect = document.getElementById('form-categories');
        if (categoriesSelect) {
            const categories = Array.from(categoriesSelect.selectedOptions)
                .map(o => parseInt(o.value));
            if (categories.length > 0) {
                conditions.category_ids = categories;
            }
        }

        const minPriceInput = document.getElementById('form-min-price');
        if (minPriceInput && minPriceInput.value) {
            conditions.min_price = parseFloat(minPriceInput.value);
        }

        const maxPriceInput = document.getElementById('form-max-price');
        if (maxPriceInput && maxPriceInput.value) {
            conditions.max_price = parseFloat(maxPriceInput.value);
        }

        return conditions;
    },

    async validateFilters() {
        console.log('PWOA Debug - validateFilters llamado');
        const conditions = this.buildConditions();
        console.log('PWOA Debug - Conditions:', conditions);
        const countSpan = document.getElementById('matching-count');

        countSpan.textContent = 'Validando...';

        const response = await fetch(pwoaData.ajaxUrl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'pwoa_validate_conditions',
                conditions: JSON.stringify(conditions),
                nonce: pwoaData.nonce
            })
        });

        const data = await response.json();

        if (data.success) {
            countSpan.textContent = data.data.count;
        } else {
            countSpan.textContent = 'Error';
        }
    },

    async showMatchingProducts() {
        const conditions = this.buildConditions();
        const countSpan = document.getElementById('matching-count');

        // Mostrar loading
        const modal = document.getElementById('products-modal');
        const productsList = document.getElementById('modal-products-list');
        productsList.innerHTML = '<p class="text-center text-gray-500 py-8">Cargando productos...</p>';
        modal.classList.remove('hidden');

        const response = await fetch(pwoaData.ajaxUrl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'pwoa_get_matching_products',
                conditions: JSON.stringify(conditions),
                nonce: pwoaData.nonce
            })
        });

        const data = await response.json();

        if (data.success) {
            countSpan.textContent = data.data.count;
            this.renderProductsInModal(data.data.products, data.data.count);
        } else {
            productsList.innerHTML = '<p class="text-center text-red-500 py-8">Error al cargar productos</p>';
        }
    },

    renderProductsInModal(products, count) {
        const productsList = document.getElementById('modal-products-list');
        const modalCount = document.getElementById('modal-count');

        modalCount.textContent = count;

        if (products.length === 0) {
            productsList.innerHTML = '<p class="text-center text-gray-500 py-8">No hay productos que cumplan los criterios</p>';
        } else {
            const html = products.map(p => `
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900">${p.name}</p>
                        <p class="text-sm text-gray-500">
                            ${p.sku ? 'SKU: ' + p.sku + ' | ' : ''}ID: ${p.id}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-900">${p.formatted_price}</p>
                        ${p.stock ? '<p class="text-xs text-gray-500">Stock: ' + p.stock + '</p>' : ''}
                        <a href="${pwoaData.adminUrl}post.php?post=${p.id}&action=edit" 
                           target="_blank" 
                           class="text-xs text-blue-600 hover:text-blue-800 mt-1 inline-block">
                            Ver producto →
                        </a>
                    </div>
                </div>
            `).join('');

            productsList.innerHTML = html;
        }
    },

    closeProductsModal() {
        const modal = document.getElementById('products-modal');
        modal.classList.add('hidden');
    }
};

document.addEventListener('DOMContentLoaded', () => PWOAWizard.init());
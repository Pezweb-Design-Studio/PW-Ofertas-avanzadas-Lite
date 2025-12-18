const PWOAWizard = {

    currentObjective: null,
    currentStrategy: null,
    strategyData: null,

    init() {
        this.bindObjectiveButtons();
        this.bindBackButtons();
        this.bindForm();
    },

    bindObjectiveButtons() {
        document.querySelectorAll('.objective-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const objective = e.currentTarget.dataset.objective;
                this.selectObjective(objective);
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
                    this.goToStep('objective');
                }
            });
        }
    },

    bindForm() {
        const form = document.getElementById('campaign-form');
        if (form) {
            form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
    },

    goToStep(step) {
        document.getElementById('step-objective').classList.add('hidden');
        document.getElementById('step-strategy').classList.add('hidden');
        document.getElementById('step-config').classList.add('hidden');

        document.getElementById('step-' + step).classList.remove('hidden');
    },

    async selectObjective(objective) {
        this.currentObjective = objective;

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

        // Bind click events
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

        this.renderConfigFields(strategyData.config_fields || []);

        document.getElementById('form-objective').value = this.currentObjective;
        document.getElementById('form-strategy').value = this.currentStrategy;

        this.goToStep('config');
    },

    getStrategyKey(name) {
        const map = {
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

        // Bind repeater events
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
            return `
                <div>
                    <label class="block text-xs font-bold mb-1">${subField.label}</label>
                    <input type="number" name="config[${field.key}][${index}][${subField.key}]" ${required}
                           class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                </div>
            `;
        }).join('');

        return `
            <div class="repeater-row bg-gray-50 p-4 rounded border grid grid-cols-${field.fields.length} gap-4">
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

        // Procesar repeaters manualmente
        const config = {};
        const repeaters = {};

        formData.forEach((value, key) => {
            if (key.includes('config[') && key.includes('][')) {
                // Es un repeater
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

        // Merge repeaters into config
        Object.assign(config, repeaters);

        const data = {
            action: 'pwoa_save_campaign',
            nonce: pwoaData.nonce,
            name: formData.get('name'),
            objective: formData.get('objective'),
            strategy: formData.get('strategy'),
            discount_type: formData.get('discount_type') || config.discount_type,
            config: JSON.stringify(config),
            conditions: JSON.stringify({}),
            stacking_mode: formData.get('stacking_mode'),
            priority: formData.get('priority'),
            start_date: formData.get('start_date'),
            end_date: formData.get('end_date')
        };

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
    }
};

document.addEventListener('DOMContentLoaded', () => PWOAWizard.init());
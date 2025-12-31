const PWOAAnalytics = {

    chart: null,

    init() {
        this.loadChartData();
        this.bindFilters();
    },

    bindFilters() {
        const periodFilter = document.getElementById('analytics-period');
        if (periodFilter) {
            periodFilter.addEventListener('change', () => this.loadChartData());
        }
    },

    async loadChartData() {
        const period = document.getElementById('analytics-period')?.value || 30;

        const response = await fetch(pwoaData.ajaxUrl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'pwoa_get_analytics',
                period: period,
                nonce: pwoaData.nonce
            })
        });

        const data = await response.json();

        if (data.success) {
            this.renderChart(data.data);
            this.updateMetrics(data.data.metrics);
        }
    },

    renderChart(data) {
        const ctx = document.getElementById('analytics-chart');
        if (!ctx) return;

        if (this.chart) {
            this.chart.destroy();
        }

        // ImplementaciÃ³n simple sin dependencias externas
        // En producciÃ³n podrÃ­as usar Chart.js
        this.renderSimpleChart(ctx, data.trends);
    },

    renderSimpleChart(canvas, trends) {
        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;

        ctx.clearRect(0, 0, width, height);

        if (!trends || trends.length === 0) {
            ctx.fillStyle = '#999';
            ctx.textAlign = 'center';
            ctx.fillText('Sin datos disponibles', width / 2, height / 2);
            return;
        }

        // Dibujar lÃ­neas bÃ¡sicas
        ctx.strokeStyle = '#3B82F6';
        ctx.lineWidth = 2;
        ctx.beginPath();

        const maxValue = Math.max(...trends.map(t => t.total_discount));
        const stepX = width / (trends.length - 1);
        const stepY = height / maxValue;

        trends.forEach((point, index) => {
            const x = index * stepX;
            const y = height - (point.total_discount * stepY);

            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });

        ctx.stroke();
    },

    updateMetrics(metrics) {
        document.querySelectorAll('[data-metric]').forEach(el => {
            const metric = el.dataset.metric;
            if (metrics[metric] !== undefined) {
                el.textContent = this.formatMetric(metrics[metric], metric);
            }
        });
    },

    formatMetric(value, type) {
        switch(type) {
            case 'total_discounted':
            case 'avg_discount':
            case 'total_revenue':
                // WooCommerce formateará en PHP, aquí solo mostramos el número
                return parseFloat(value).toLocaleString();
            case 'conversion_rate':
            case 'roi':
                return parseFloat(value).toFixed(2) + '%';
            default:
                return value;
        }
    },

    exportData() {
        window.location.href = pwoaData.ajaxUrl + '?action=pwoa_export_analytics&nonce=' + pwoaData.nonce;
    }
};

document.addEventListener('DOMContentLoaded', () => PWOAAnalytics.init());
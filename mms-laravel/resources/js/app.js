import './bootstrap';
import Alpine from 'alpinejs';
import morph from '@alpinejs/morph';

Alpine.plugin(morph);
window.Alpine = Alpine;
Alpine.start();

// ----------------------------------------------------------------
// Global chart defaults (dark theme)
// ----------------------------------------------------------------
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

Chart.defaults.color = '#7b8094';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family = "'Space Grotesk', sans-serif";
Chart.defaults.font.size = 12;

window.Chart = Chart;

// ----------------------------------------------------------------
// Dashboard KPI chart initializer
// ----------------------------------------------------------------
window.initDashboardCharts = function(statusData, trendData) {
    // Status donut
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData).map(s => s.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: ['#4f8ef7','#a78bfa','#f7944f','#3dd68c','#ef5a5a','#6b7280'],
                    borderWidth: 0,
                    hoverOffset: 6,
                }],
            },
            options: {
                cutout: '72%',
                plugins: {
                    legend: { position: 'right', labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8 } },
                },
            },
        });
    }

    // 30-day trend line
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: Object.keys(trendData),
                datasets: [{
                    label: 'Completed',
                    data: Object.values(trendData),
                    borderColor: '#4f8ef7',
                    backgroundColor: 'rgba(79,142,247,0.08)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                }],
            },
            options: {
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                },
                plugins: { legend: { display: false } },
            },
        });
    }
};

// ----------------------------------------------------------------
// Asset utilization bar chart
// ----------------------------------------------------------------
window.initAssetChart = function(categories, counts) {
    const ctx = document.getElementById('assetChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: categories,
            datasets: [{
                label: 'Work Orders',
                data: counts,
                backgroundColor: 'rgba(79,142,247,0.6)',
                borderRadius: 6,
                borderSkipped: false,
            }],
        },
        options: {
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
            },
            plugins: { legend: { display: false } },
        },
    });
};

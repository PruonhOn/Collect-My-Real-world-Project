import Chart from 'chart.js/auto';

function isDark() {
    return document.documentElement.getAttribute('data-bs-theme') === 'dark';
}

function gridColor() {
    return isDark() ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.04)';
}

function tickColor() {
    return isDark() ? '#9a8d9c' : '#8c7f8b';
}

let salesChart = null;
let paymentChart = null;

function buildSalesChart() {
    const canvas = document.getElementById('salesChart');
    if (!canvas || !window.SalesChartData) return;
    if (salesChart) salesChart.destroy();

    salesChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: window.SalesChartData.map(d => d.day),
            datasets: [{
                label: 'Revenue',
                data: window.SalesChartData.map(d => d.revenue),
                backgroundColor: 'rgba(166, 77, 121, 0.75)',
                borderRadius: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { color: tickColor() }, grid: { color: gridColor() } },
                x: { grid: { display: false }, ticks: { color: tickColor() } },
            },
        },
    });
}

function buildPaymentChart() {
    const canvas = document.getElementById('paymentChart');
    if (!canvas || !window.PaymentChartData) return;
    if (paymentChart) paymentChart.destroy();

    const palette = ['#a64d79', '#3d9970', '#d98e04', '#4a7db4', '#8c7f8b'];
    const labels = Object.keys(window.PaymentChartData);
    const values = Object.values(window.PaymentChartData);

    paymentChart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: labels.map((_, i) => palette[i % palette.length]),
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16, color: tickColor() } },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${Number(ctx.parsed).toLocaleString('en-US', { style: 'currency', currency: 'USD' })}`,
                    },
                },
            },
        },
    });
}

document.addEventListener('DOMContentLoaded', () => {
    buildSalesChart();
    buildPaymentChart();

    document.addEventListener('themechange', () => {
        buildSalesChart();
        buildPaymentChart();
    });
});

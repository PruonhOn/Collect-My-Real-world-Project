import Chart from 'chart.js/auto';

function isDark() {
    return document.documentElement.getAttribute('data-bs-theme') === 'dark';
}

function gridColor() {
    return isDark() ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.04)';
}

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('revenueChart');
    if (!canvas || !window.RevenueChartData) return;

    let chart = null;

    function buildChart(data) {
        if (chart) chart.destroy();
        chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Revenue',
                    data: data.revenue,
                    borderColor: '#a64d79',
                    backgroundColor: 'rgba(166, 77, 121, 0.12)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${Number(ctx.parsed.y).toLocaleString('en-US', { style: 'currency', currency: 'USD' })}`,
                        },
                    },
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: gridColor() } },
                    x: { grid: { display: false } },
                },
            },
        });
    }

    buildChart(window.RevenueChartData);

    document.addEventListener('themechange', () => buildChart(window.RevenueChartData));

    document.querySelectorAll('.chart-range').forEach(button => {
        button.addEventListener('click', () => {
            const days = parseInt(button.dataset.days, 10);
            document.querySelectorAll('.chart-range').forEach(b => b.classList.remove('active'));
            button.classList.add('active');

            fetch('/dashboard/chart?days=' + days, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
                .then(res => res.json())
                .then(data => {
                    window.RevenueChartData = data;
                    buildChart(data);
                })
                .catch(() => window.showToast('Could not update chart.', 'error'));
        });
    });
});

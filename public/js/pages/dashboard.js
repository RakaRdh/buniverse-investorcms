let dashboardChart = null;

const chartDatasets = window.serverChartDatasets || {
    today: {
        categories: ['06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'],
        series: [{
            name: 'Published Articles',
            data: [0, 0, 0, 0, 0, 0, 0, 0, 0]
        }, {
            name: 'Drafts Created',
            data: [0, 0, 0, 0, 0, 0, 0, 0, 0]
        }]
    },
    '7d': {
        categories: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
        series: [{
            name: 'Published Articles',
            data: [0, 0, 0, 0, 0, 0, 0]
        }, {
            name: 'Drafts Created',
            data: [0, 0, 0, 0, 0, 0, 0]
        }]
    },
    '30d': {
        categories: ['W1', 'W2', 'W3', 'W4', 'W5'],
        series: [{
            name: 'Published Articles',
            data: [0, 0, 0, 0, 0]
        }, {
            name: 'Drafts Created',
            data: [0, 0, 0, 0, 0]
        }]
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const chartEl = document.querySelector("#publishingChart");
    if (chartEl && window.ApexCharts) {
        const options = {
            series: chartDatasets.today.series,
            chart: {
                type: 'area',
                height: 280,
                toolbar: { show: false },
                fontFamily: 'Inter, sans-serif',
                animations: { enabled: true, easing: 'easeinout', speed: 400 }
            },
            colors: ['#1F4E79', '#3991CF'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.40,
                    opacityTo: 0.05,
                    stops: [20, 100]
                }
            },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2.5 },
            xaxis: {
                categories: chartDatasets.today.categories,
                labels: { style: { colors: '#94A3B8', fontSize: '11px', fontFamily: 'Inter, sans-serif' } }
            },
            yaxis: {
                labels: { style: { colors: '#94A3B8', fontSize: '11px', fontFamily: 'Inter, sans-serif' } }
            },
            grid: { borderColor: document.documentElement.classList.contains('dark') ? '#1F293D' : '#F1F5F9' },
            theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
        };

        dashboardChart = new ApexCharts(chartEl, options);
        dashboardChart.render();

        window.addEventListener('newsroom_theme_change', (e) => {
            const isDark = e.detail.theme === 'dark';
            if (dashboardChart) {
                dashboardChart.updateOptions({
                    theme: { mode: isDark ? 'dark' : 'light' },
                    grid: { borderColor: isDark ? '#1F293D' : '#F1F5F9' },
                    colors: isDark ? ['#60A5FA', '#38BDF8'] : ['#1F4E79', '#3991CF']
                });
            }
        });
    }
});

function switchDashboardPeriod(periodKey) {
    if (!chartDatasets[periodKey] || !dashboardChart) return;

    // Update active button styles
    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.classList.remove('bg-background', 'text-foreground', 'shadow-xs', 'font-semibold');
        btn.classList.add('text-muted-foreground');
    });

    const activeBtnId = periodKey === 'today' ? 'btnPeriodToday' : (periodKey === '7d' ? 'btnPeriod7d' : 'btnPeriod30d');
    const activeBtn = document.getElementById(activeBtnId);
    if (activeBtn) {
        activeBtn.classList.add('bg-background', 'text-foreground', 'shadow-xs', 'font-semibold');
        activeBtn.classList.remove('text-muted-foreground');
    }

    // Update chart data & categories
    const selected = chartDatasets[periodKey];
    dashboardChart.updateOptions({
        xaxis: { categories: selected.categories }
    });
    dashboardChart.updateSeries(selected.series);
}

let dashboardChart = null;

const chartDatasets = {
    today: {
        categories: ['06:00', '07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'],
        series: [{
            name: 'Published Articles',
            data: [4, 8, 14, 26, 18, 11, 22, 38, 29, 16, 19, 24]
        }, {
            name: 'Drafts Created',
            data: [2, 5, 8, 12, 7, 4, 9, 11, 6, 5, 8, 9]
        }]
    },
    '7d': {
        categories: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
        series: [{
            name: 'Published Articles',
            data: [34, 42, 38, 48, 52, 28, 22]
        }, {
            name: 'Drafts Created',
            data: [12, 16, 14, 19, 18, 9, 7]
        }]
    },
    '30d': {
        categories: ['W1 (1-7)', 'W2 (8-14)', 'W3 (15-21)', 'W4 (22-28)', 'W5 (29-31)'],
        series: [{
            name: 'Published Articles',
            data: [210, 245, 230, 285, 140]
        }, {
            name: 'Drafts Created',
            data: [75, 88, 82, 94, 48]
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
            grid: { borderColor: '#F1F5F9' }
        };

        dashboardChart = new ApexCharts(chartEl, options);
        dashboardChart.render();
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

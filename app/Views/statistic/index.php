<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- PageHeader Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Audience Statistics &amp; Traffic Analytics</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Ikhtisar metrik audiens, momentum kanal berita, dan distribusi performa platform Investor.id.</p>
        </div>
        <div class="flex items-center gap-1.5 rounded-lg border border-border bg-muted/40 p-1 text-xs font-medium self-start sm:self-auto">
            <button type="button" id="btnStatToday" onclick="switchStatisticPeriod('today')" class="stat-period-btn rounded-md bg-background px-2.5 py-1 text-foreground shadow-xs transition-all font-semibold">Today</button>
            <button type="button" id="btnStat7d" onclick="switchStatisticPeriod('7d')" class="stat-period-btn rounded-md px-2.5 py-1 text-muted-foreground hover:text-foreground transition-all">Last 7 Days</button>
            <button type="button" id="btnStatMtd" onclick="switchStatisticPeriod('mtd')" class="stat-period-btn rounded-md px-2.5 py-1 text-muted-foreground hover:text-foreground transition-all">Month to Date</button>
        </div>
    </div>
</div>

<!-- Metrics Cards Grid (2x2 on Mobile, 4 Cols on Desktop) -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4 mb-6 sm:mb-8">
    <div class="surface-card p-3 sm:p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between gap-1.5">
            <span class="text-[10px] sm:text-xs font-semibold tracking-wider text-muted-foreground uppercase truncate">Pageviews</span>
            <span class="grid size-6.5 sm:size-8 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary">
                <i data-lucide="eye" class="size-3.5 sm:size-[18px]"></i>
            </span>
        </div>
        <p class="mt-2 sm:mt-3 text-lg sm:text-2xl lg:text-3xl font-bold tracking-tight text-foreground truncate"><?= esc($metrics['totalPageviews']) ?></p>
        <p class="mt-1 sm:mt-2 inline-flex items-center gap-1 text-[10px] sm:text-xs font-medium text-success truncate">
            <i data-lucide="arrow-up-right" class="size-3"></i>
            <span>+14.2%</span>
            <span class="hidden sm:inline text-muted-foreground">vs last week</span>
        </p>
    </div>

    <div class="surface-card p-3 sm:p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between gap-1.5">
            <span class="text-[10px] sm:text-xs font-semibold tracking-wider text-muted-foreground uppercase truncate">Read Time</span>
            <span class="grid size-6.5 sm:size-8 shrink-0 place-items-center rounded-lg bg-accent/10 text-accent">
                <i data-lucide="clock" class="size-3.5 sm:size-[18px]"></i>
            </span>
        </div>
        <p class="mt-2 sm:mt-3 text-lg sm:text-2xl lg:text-3xl font-bold tracking-tight text-foreground truncate"><?= esc($metrics['avgReadTime']) ?></p>
        <p class="mt-1 sm:mt-2 inline-flex items-center gap-1 text-[10px] sm:text-xs font-medium text-success truncate">
            <i data-lucide="arrow-up-right" class="size-3"></i>
            <span>+8.5%</span>
            <span class="hidden sm:inline text-muted-foreground">retention</span>
        </p>
    </div>

    <div class="surface-card p-3 sm:p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between gap-1.5">
            <span class="text-[10px] sm:text-xs font-semibold tracking-wider text-muted-foreground uppercase truncate">Subscribers</span>
            <span class="grid size-6.5 sm:size-8 shrink-0 place-items-center rounded-lg bg-warning/15 text-warning">
                <i data-lucide="star" class="size-3.5 sm:size-[18px]"></i>
            </span>
        </div>
        <p class="mt-2 sm:mt-3 text-lg sm:text-2xl lg:text-3xl font-bold tracking-tight text-foreground truncate"><?= esc($metrics['premiumSubscribers']) ?></p>
        <p class="mt-1 sm:mt-2 inline-flex items-center gap-1 text-[10px] sm:text-xs font-medium text-success truncate">
            <i data-lucide="arrow-up-right" class="size-3"></i>
            <span><?= esc($metrics['premiumArticles']) ?></span>
            <span class="text-muted-foreground truncate">articles</span>
        </p>
    </div>

    <div class="surface-card p-3 sm:p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between gap-1.5">
            <span class="text-[10px] sm:text-xs font-semibold tracking-wider text-muted-foreground uppercase truncate">Artikel Tayang</span>
            <span class="grid size-6.5 sm:size-8 shrink-0 place-items-center rounded-lg bg-emerald-500/10 text-emerald-600">
                <i data-lucide="check-circle-2" class="size-3.5 sm:size-[18px]"></i>
            </span>
        </div>
        <p class="mt-2 sm:mt-3 text-lg sm:text-2xl lg:text-3xl font-bold tracking-tight text-foreground truncate"><?= esc($metrics['publishedCount']) ?></p>
        <p class="mt-1 sm:mt-2 text-[10px] sm:text-xs text-muted-foreground truncate">Total Published</p>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="surface-card lg:col-span-2 p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-foreground">Audience Traffic Breakdown</h2>
            <span class="text-[11px] text-muted-foreground">Hourly & Daily Visitor Trends</span>
        </div>
        <div id="trafficChart" class="min-h-[280px]"></div>
    </div>

    <div class="surface-card p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-foreground">Kanal Distribution</h2>
            <span class="text-[11px] text-muted-foreground">Top 5 Kanals</span>
        </div>
        <div id="categoryChart" class="min-h-[280px]"></div>
    </div>
</div>

<!-- Additional Platform Insights Card Grid -->
<div class="grid grid-cols-1 gap-6 mb-8">
    <div class="surface-card p-5 space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Laporan Kinerja Redaksi</h3>
            <i data-lucide="clipboard-list" class="size-4 text-primary"></i>
        </div>
        <p class="text-xs text-muted-foreground leading-relaxed">
            Untuk melihat detail performa harian jurnalis atau editor per tanggal dalam rentang 30 hari, silakan buka menu Laporan Tim Editorial.
        </p>
        <div class="pt-1">
            <a href="<?= base_url('statistic/report') ?>" class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-xs font-bold text-primary-foreground shadow hover:bg-primary/90 transition-colors">
                <i data-lucide="arrow-right" class="size-3.5"></i>
                <span>Buka Performa Tim Editorial</span>
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
window.serverTrafficDatasets = <?= json_encode($trafficDatasets ?? null) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
let trafficChart = null;

const statTrafficDatasets = window.serverTrafficDatasets || {
    today: {
        categories: ['00:00', '03:00', '06:00', '09:00', '12:00', '15:00', '18:00', '21:00'],
        series: [{
            name: 'Direct & App Visitors',
            data: [0, 0, 0, 0, 0, 0, 0, 0]
        }, {
            name: 'Search & Syndication',
            data: [0, 0, 0, 0, 0, 0, 0, 0]
        }]
    },
    '7d': {
        categories: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
        series: [{
            name: 'Direct & App Visitors',
            data: [0, 0, 0, 0, 0, 0, 0]
        }, {
            name: 'Search & Syndication',
            data: [0, 0, 0, 0, 0, 0, 0]
        }]
    },
    mtd: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        series: [{
            name: 'Direct & App Visitors',
            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
        }, {
            name: 'Search & Syndication',
            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
        }]
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (window.ApexCharts) {
        const isDark = document.documentElement.classList.contains('dark');
        trafficChart = new ApexCharts(document.querySelector("#trafficChart"), {
            series: statTrafficDatasets.today.series,
            chart: { 
                type: 'line', 
                height: 280, 
                toolbar: { show: false }, 
                fontFamily: 'Inter, sans-serif',
                animations: { enabled: true, easing: 'easeinout', speed: 400 }
            },
            colors: isDark ? ['#60A5FA', '#38BDF8'] : ['#1F4E79', '#3991CF'],
            stroke: { curve: 'smooth', width: 2.5 },
            xaxis: { 
                categories: statTrafficDatasets.today.categories,
                labels: { style: { colors: '#94A3B8', fontSize: '11px', fontFamily: 'Inter, sans-serif' } }
            },
            yaxis: {
                labels: { style: { colors: '#94A3B8', fontSize: '11px', fontFamily: 'Inter, sans-serif' } }
            },
            grid: { borderColor: isDark ? '#1F293D' : '#F1F5F9' },
            theme: { mode: isDark ? 'dark' : 'light' }
        });
        trafficChart.render();

        const catChart = new ApexCharts(document.querySelector("#categoryChart"), {
            series: <?= json_encode($chartData['catSeries']) ?>,
            labels: <?= json_encode($chartData['catLabels']) ?>,
            chart: { type: 'donut', height: 280, fontFamily: 'Inter, sans-serif' },
            colors: ['#3B82F6', '#60A5FA', '#F59E0B', '#10B981', '#A855F7'],
            theme: { mode: isDark ? 'dark' : 'light' },
            legend: { 
                position: 'bottom',
                labels: { colors: isDark ? '#F1F5F9' : '#1E293B' }
            }
        });
        catChart.render();

        window.addEventListener('newsroom_theme_change', (e) => {
            const dark = e.detail.theme === 'dark';
            if (trafficChart) {
                trafficChart.updateOptions({
                    theme: { mode: dark ? 'dark' : 'light' },
                    grid: { borderColor: dark ? '#1F293D' : '#F1F5F9' },
                    colors: dark ? ['#60A5FA', '#38BDF8'] : ['#1F4E79', '#3991CF']
                });
            }
            if (catChart) {
                catChart.updateOptions({
                    theme: { mode: dark ? 'dark' : 'light' },
                    legend: { labels: { colors: dark ? '#F1F5F9' : '#1E293B' } }
                });
            }
        });
    }
});

function switchStatisticPeriod(periodKey) {
    if (!statTrafficDatasets[periodKey] || !trafficChart) return;

    document.querySelectorAll('.stat-period-btn').forEach(btn => {
        btn.classList.remove('bg-background', 'text-foreground', 'shadow-xs', 'font-semibold');
        btn.classList.add('text-muted-foreground');
    });

    const activeBtnId = periodKey === 'today' ? 'btnStatToday' : (periodKey === '7d' ? 'btnStat7d' : 'btnStatMtd');
    const activeBtn = document.getElementById(activeBtnId);
    if (activeBtn) {
        activeBtn.classList.add('bg-background', 'text-foreground', 'shadow-xs', 'font-semibold');
        activeBtn.classList.remove('text-muted-foreground');
    }

    const selected = statTrafficDatasets[periodKey];
    trafficChart.updateOptions({
        xaxis: { categories: selected.categories }
    });
    trafficChart.updateSeries(selected.series);
}
</script>
<?= $this->endSection() ?>

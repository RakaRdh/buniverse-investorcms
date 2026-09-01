<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Editorial Statistics & Audience Analytics</h1>
            <p class="mt-1 text-xs text-muted-foreground">Granular data insights on reader behavior, traffic channels, category momentum, and editorial throughput.</p>
        </div>
        <div class="flex items-center gap-1.5 rounded-lg border border-border bg-muted/40 p-1 text-xs font-medium">
            <button type="button" id="btnStatToday" onclick="switchStatisticPeriod('today')" class="stat-period-btn rounded-md bg-background px-2.5 py-1 text-foreground shadow-xs transition-all font-semibold">Today</button>
            <button type="button" id="btnStat7d" onclick="switchStatisticPeriod('7d')" class="stat-period-btn rounded-md px-2.5 py-1 text-muted-foreground hover:text-foreground transition-all">Last 7 Days</button>
            <button type="button" id="btnStatMtd" onclick="switchStatisticPeriod('mtd')" class="stat-period-btn rounded-md px-2.5 py-1 text-muted-foreground hover:text-foreground transition-all">Month to Date</button>
        </div>
    </div>
</div>

<!-- Metrics Cards Grid -->
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-8">
    <div class="surface-card p-5">
        <div class="flex items-center justify-between gap-3">
            <span class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Total Pageviews</span>
            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-primary/8 text-primary">
                <i data-lucide="eye" class="size-[18px]"></i>
            </span>
        </div>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-foreground"><?= esc($metrics['totalPageviews']) ?></p>
        <p class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-success">
            <i data-lucide="arrow-up-right" class="size-3.5"></i>
            <span>+14.2%</span>
            <span class="text-muted-foreground">vs last week</span>
        </p>
    </div>

    <div class="surface-card p-5">
        <div class="flex items-center justify-between gap-3">
            <span class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Avg. Read Time</span>
            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-accent/10 text-accent">
                <i data-lucide="clock" class="size-[18px]"></i>
            </span>
        </div>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-foreground"><?= esc($metrics['avgReadTime']) ?></p>
        <p class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-success">
            <i data-lucide="arrow-up-right" class="size-3.5"></i>
            <span>+8.5%</span>
            <span class="text-muted-foreground">retention</span>
        </p>
    </div>

    <div class="surface-card p-5">
        <div class="flex items-center justify-between gap-3">
            <span class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Premium Subscribers</span>
            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-warning/15 text-warning">
                <i data-lucide="star" class="size-[18px]"></i>
            </span>
        </div>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-foreground"><?= esc($metrics['premiumSubscribers']) ?></p>
        <p class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-success">
            <i data-lucide="arrow-up-right" class="size-3.5"></i>
            <span><?= esc($metrics['premiumArticles']) ?> articles</span>
            <span class="text-muted-foreground">in paywall</span>
        </p>
    </div>

    <div class="surface-card p-5">
        <div class="flex items-center justify-between gap-3">
            <span class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Social & Referrals</span>
            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-success/10 text-success">
                <i data-lucide="share-2" class="size-[18px]"></i>
            </span>
        </div>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-foreground"><?= esc($metrics['socialReferralPct']) ?></p>
        <p class="mt-2 text-xs text-muted-foreground">Google Discover & IDX Feed</p>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="surface-card lg:col-span-2 p-5 space-y-4">
        <h2 class="text-sm font-semibold text-foreground">Audience Traffic Breakdown</h2>
        <div id="trafficChart" class="min-h-[280px]"></div>
    </div>

    <div class="surface-card p-5 space-y-4">
        <h2 class="text-sm font-semibold text-foreground">Category Distribution</h2>
        <div id="categoryChart" class="min-h-[280px]"></div>
    </div>
</div>

<!-- Author Table -->
<div class="surface-card overflow-hidden">
    <div class="px-5 py-4 border-b border-border">
        <h2 class="text-sm font-semibold text-foreground">Journalist & Author Performance</h2>
    </div>
    <div class="relative w-full overflow-auto">
        <table class="w-full caption-bottom text-sm">
            <thead class="[&_tr]:border-b border-border bg-muted/40">
                <tr class="text-left font-bold text-foreground text-xs">
                    <th class="h-10 px-4">Journalist</th>
                    <th class="h-10 px-4">Role</th>
                    <th class="h-10 px-4">Articles Published</th>
                    <th class="h-10 px-4">Total Views</th>
                    <th class="h-10 px-4">Avg. Engagement</th>
                    <th class="h-10 px-4">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php foreach ($authorStats as $st): ?>
                <?php
                    $u = $st['user'];
                    $uParts = explode(' ', $u['name']);
                    $uInitials = strtoupper(substr($uParts[0], 0, 1) . (isset($uParts[1]) ? substr($uParts[1], 0, 1) : ''));
                ?>
                <tr class="hover:bg-muted/40 transition-colors">
                    <td class="p-4">
                        <div class="flex items-center gap-3">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-primary/10 text-primary font-bold text-xs border border-primary/20">
                                <?= esc($uInitials) ?>
                            </span>
                            <div>
                                <span class="font-bold text-foreground text-xs block"><?= esc($u['name']) ?></span>
                                <span class="text-[11px] text-muted-foreground"><?= esc($u['email']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="p-4">
                        <?php
                            $roleClass = match($u['role']) {
                                'Admin' => 'border-primary/20 bg-primary/10 text-primary',
                                'Editor' => 'border-accent/25 bg-accent/12 text-accent',
                                default => 'border-muted text-muted-foreground bg-muted'
                            };
                        ?>
                        <span class="inline-flex rounded-md border px-2 py-0.5 text-xs font-medium <?= $roleClass ?>"><?= esc($u['role']) ?></span>
                    </td>
                    <td class="p-4 font-bold text-xs text-foreground"><?= esc($st['published_count']) ?> artikel</td>
                    <td class="p-4 text-xs font-semibold text-foreground"><?= number_format($st['total_views']) ?></td>
                    <td class="p-4 text-xs font-bold text-success"><?= esc($st['engagement']) ?></td>
                    <td class="p-4">
                        <span class="inline-flex items-center gap-1.5 rounded-md border border-success/25 bg-success/12 px-2 py-0.5 text-xs font-medium text-success capitalize">
                            <span class="size-1.5 rounded-full bg-current"></span>
                            Active
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
let trafficChart = null;

const statTrafficDatasets = {
    today: {
        categories: ['00:00', '03:00', '06:00', '09:00', '12:00', '15:00', '18:00', '21:00'],
        series: [{
            name: 'Direct & App Visitors',
            data: [12, 8, 28, 64, 82, 95, 76, 45]
        }, {
            name: 'Search & Syndication',
            data: [8, 5, 18, 42, 60, 72, 58, 30]
        }]
    },
    '7d': {
        categories: ['Sen (25/8)', 'Sel (26/8)', 'Rab (27/8)', 'Kam (28/8)', 'Jum (29/8)', 'Sab (30/8)', 'Min (31/8)'],
        series: [{
            name: 'Direct & App Visitors',
            data: [110, 125, 140, 135, 155, 92, 84]
        }, {
            name: 'Search & Syndication',
            data: [85, 95, 115, 105, 120, 68, 60]
        }]
    },
    mtd: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        series: [{
            name: 'Direct & App Visitors',
            data: [45, 52, 68, 74, 92, 110, 125, 140, 135, 120, 95, 88]
        }, {
            name: 'Search & Syndication',
            data: [30, 40, 45, 55, 70, 85, 95, 115, 105, 90, 75, 65]
        }]
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (window.ApexCharts) {
        trafficChart = new ApexCharts(document.querySelector("#trafficChart"), {
            series: statTrafficDatasets.today.series,
            chart: { 
                type: 'line', 
                height: 280, 
                toolbar: { show: false }, 
                fontFamily: 'Inter, sans-serif',
                animations: { enabled: true, easing: 'easeinout', speed: 400 }
            },
            colors: ['#1F4E79', '#3991CF'],
            stroke: { curve: 'smooth', width: 2.5 },
            xaxis: { 
                categories: statTrafficDatasets.today.categories,
                labels: { style: { colors: '#94A3B8', fontSize: '11px', fontFamily: 'Inter, sans-serif' } }
            },
            yaxis: {
                labels: { style: { colors: '#94A3B8', fontSize: '11px', fontFamily: 'Inter, sans-serif' } }
            },
            grid: { borderColor: '#F1F5F9' }
        });
        trafficChart.render();

        new ApexCharts(document.querySelector("#categoryChart"), {
            series: <?= json_encode($chartData['catSeries']) ?>,
            labels: <?= json_encode($chartData['catLabels']) ?>,
            chart: { type: 'donut', height: 280, fontFamily: 'Inter, sans-serif' },
            colors: ['#1F4E79', '#3991CF', '#F79009', '#12B76A', '#9E77ED'],
            legend: { position: 'bottom' }
        }).render();
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

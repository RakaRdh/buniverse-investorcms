<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<?= $this->endSection() ?>

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
        <h2 class="text-sm font-semibold text-foreground">Kanal Distribution</h2>
        <div id="categoryChart" class="min-h-[280px]"></div>
    </div>
</div>

<!-- Editorial Performance & Production Section (Interactive Dropdown Role Filter) -->
<div class="surface-card overflow-hidden mb-8" id="editorialReportSection">
    
    <!-- Section Toolbar Header with Dropdown & Date Range -->
    <div class="px-5 py-4 border-b border-border bg-muted/20 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2.5">
            <span class="grid size-8 place-items-center rounded-lg bg-primary/10 text-primary">
                <i data-lucide="clipboard-check" class="size-4"></i>
            </span>
            <div>
                <h2 class="text-sm font-bold text-foreground">Editorial Team Performance & Article Output</h2>
                <p class="text-[11px] text-muted-foreground">Lihat data kinerja berdasarkan peran jurnalis (Author), editor penyunting, kontributor, atau rekap rilis harian.</p>
            </div>
        </div>

        <!-- Role Select Dropdown & Date Filter Form -->
        <form method="get" action="<?= base_url('statistic') ?>#editorialReportSection" class="flex flex-wrap items-center gap-2">
            <!-- View Type Dropdown -->
            <div class="min-w-[170px]">
                <select name="view_type" class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1 text-xs font-semibold text-foreground shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" onchange="this.form.submit()">
                    <option value="all" <?= $viewType === 'all' ? 'selected' : '' ?>>Tampilkan: Semua Role</option>
                    <option value="author" <?= $viewType === 'author' ? 'selected' : '' ?>>Khusus: Author (Jurnalis)</option>
                    <option value="editor" <?= $viewType === 'editor' ? 'selected' : '' ?>>Khusus: Editor Redaksi</option>
                    <option value="contributor" <?= $viewType === 'contributor' ? 'selected' : '' ?>>Khusus: Contributor</option>
                    <option value="daily" <?= $viewType === 'daily' ? 'selected' : '' ?>>Khusus: Artikel Rilis Harian</option>
                </select>
            </div>

            <!-- Date Range Inputs (Flatpickr) -->
            <div class="flex items-center gap-1.5 rounded-md border border-input bg-background px-2 py-0.5 shadow-xs">
                <i data-lucide="calendar" class="size-3.5 text-primary"></i>
                <input type="text" name="from" id="statDateFrom" value="<?= esc($fromDate) ?>" class="h-6 w-20 bg-transparent text-[11px] font-semibold text-foreground focus:outline-none cursor-pointer" placeholder="YYYY-MM-DD">
                <span class="text-[11px] text-muted-foreground font-medium">s/d</span>
                <input type="text" name="to" id="statDateTo" value="<?= esc($toDate) ?>" class="h-6 w-20 bg-transparent text-[11px] font-semibold text-foreground focus:outline-none cursor-pointer" placeholder="YYYY-MM-DD">
            </div>

            <button type="submit" class="inline-flex h-8 items-center justify-center gap-1 rounded-md bg-primary px-3 text-xs font-bold text-primary-foreground shadow hover:bg-primary/90 transition-colors">
                <i data-lucide="filter" class="size-3"></i>
                <span>Filter</span>
            </button>
        </form>
    </div>

    <!-- Active Period Summary Note -->
    <div class="flex items-center justify-between px-5 py-2.5 border-b border-border bg-primary/5 text-xs">
        <div class="flex items-center gap-2">
            <span class="inline-flex size-2 rounded-full bg-primary animate-pulse"></span>
            <span class="text-muted-foreground">Periode Data:</span>
            <strong class="text-foreground font-semibold"><?= date('d M Y', strtotime($fromDate)) ?> — <?= date('d M Y', strtotime($toDate)) ?></strong>
            <span class="text-muted-foreground text-[11px]">(Maks. 30 hari)</span>
        </div>
        <div class="text-muted-foreground text-[11px]">
            Total Naskah: <strong class="text-primary font-bold"><?= $totalFilteredArticles ?></strong> artikel
        </div>
    </div>

    <!-- 1. AUTHOR PERFORMANCE TABLE (Shown if view_type == 'all' or 'author') -->
    <?php if ($viewType === 'all' || $viewType === 'author'): ?>
    <div class="p-4 border-b border-border">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-foreground flex items-center gap-1.5">
                <i data-lucide="pen-tool" class="size-3.5 text-primary"></i>
                <span>Performa Author (Jurnalis)</span>
            </h3>
            <span class="text-[11px] font-semibold text-muted-foreground"><?= count($authorStats) ?> Authors</span>
        </div>
        
        <div class="relative w-full overflow-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-border bg-muted/40 font-bold text-muted-foreground uppercase">
                    <tr>
                        <th class="py-2.5 px-3">Journalist</th>
                        <th class="py-2.5 px-3">Role</th>
                        <th class="py-2.5 px-3 text-center">Artikel Published</th>
                        <th class="py-2.5 px-3 text-right">Total Views</th>
                        <th class="py-2.5 px-3 text-right">Avg. Views</th>
                        <th class="py-2.5 px-3 text-center">Engagement</th>
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
                        <td class="py-2.5 px-3">
                            <div class="flex items-center gap-2.5">
                                <span class="grid size-7 shrink-0 place-items-center rounded-full bg-primary/10 text-primary font-bold text-[10px] border border-primary/20">
                                    <?= esc($uInitials) ?>
                                </span>
                                <div>
                                    <span class="font-bold text-foreground text-xs block"><?= esc($u['name']) ?></span>
                                    <span class="text-[10px] text-muted-foreground"><?= esc($u['email']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="py-2.5 px-3">
                            <span class="inline-flex rounded-md border border-muted px-2 py-0.5 text-[10px] font-medium bg-muted text-muted-foreground"><?= esc($u['role']) ?></span>
                        </td>
                        <td class="py-2.5 px-3 text-center font-bold text-success">
                            <span class="inline-flex rounded bg-success/10 border border-success/25 px-2 py-0.5 text-[11px]">
                                <?= esc($st['published_count']) ?> artikel
                            </span>
                        </td>
                        <td class="py-2.5 px-3 text-right font-semibold text-foreground"><?= number_format($st['total_views']) ?></td>
                        <td class="py-2.5 px-3 text-right font-mono text-muted-foreground"><?= number_format($st['avg_views']) ?></td>
                        <td class="py-2.5 px-3 text-center font-bold text-primary"><?= esc($st['engagement']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- 2. EDITOR PERFORMANCE TABLE (Shown if view_type == 'all' or 'editor') -->
    <?php if ($viewType === 'all' || $viewType === 'editor'): ?>
    <div class="p-4 border-b border-border">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-foreground flex items-center gap-1.5">
                <i data-lucide="check-check" class="size-3.5 text-accent"></i>
                <span>Performa Editor Redaksi</span>
            </h3>
            <span class="text-[11px] font-semibold text-muted-foreground"><?= count($editorStats) ?> Editors</span>
        </div>

        <div class="relative w-full overflow-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-border bg-muted/40 font-bold text-muted-foreground uppercase">
                    <tr>
                        <th class="py-2.5 px-3">Nama Editor</th>
                        <th class="py-2.5 px-3">Role Akun</th>
                        <th class="py-2.5 px-3 text-center">Artikel Dipublikasikan</th>
                        <th class="py-2.5 px-3 text-center">Total Naskah Disunting</th>
                        <th class="py-2.5 px-3 text-right">Akumulasi Views</th>
                        <th class="py-2.5 px-3 text-center">Performa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php foreach ($editorStats as $st): ?>
                    <?php
                        $u = $st['user'];
                        $uParts = explode(' ', $u['name']);
                        $uInitials = strtoupper(substr($uParts[0], 0, 1) . (isset($uParts[1]) ? substr($uParts[1], 0, 1) : ''));
                    ?>
                    <tr class="hover:bg-muted/40 transition-colors">
                        <td class="py-2.5 px-3">
                            <div class="flex items-center gap-2.5">
                                <span class="grid size-7 shrink-0 place-items-center rounded-full bg-accent/10 text-accent font-bold text-[10px] border border-accent/20">
                                    <?= esc($uInitials) ?>
                                </span>
                                <div>
                                    <span class="font-bold text-foreground text-xs block"><?= esc($u['name']) ?></span>
                                    <span class="text-[10px] text-muted-foreground">@<?= esc($u['username']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="py-2.5 px-3">
                            <span class="inline-flex rounded-md border border-accent/25 bg-accent/10 px-2 py-0.5 text-[10px] font-bold text-accent"><?= esc($u['role']) ?></span>
                        </td>
                        <td class="py-2.5 px-3 text-center font-bold text-primary">
                            <span class="inline-flex rounded bg-primary/10 border border-primary/25 px-2 py-0.5 text-[11px]">
                                <?= esc($st['published_count']) ?> artikel
                            </span>
                        </td>
                        <td class="py-2.5 px-3 text-center font-medium text-muted-foreground"><?= esc($st['total_posts']) ?> naskah</td>
                        <td class="py-2.5 px-3 text-right font-semibold text-foreground"><?= number_format($st['total_views']) ?></td>
                        <td class="py-2.5 px-3 text-center font-bold text-success"><?= esc($st['engagement']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- 3. CONTRIBUTOR PERFORMANCE (Shown if view_type == 'all' or 'contributor') -->
    <?php if ($viewType === 'all' || $viewType === 'contributor'): ?>
    <div class="p-4 border-b border-border">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-foreground flex items-center gap-1.5">
                <i data-lucide="users" class="size-3.5 text-amber-600"></i>
                <span>Laporan Contributor</span>
            </h3>
            <span class="text-[11px] font-semibold text-muted-foreground"><?= count($contribStats) ?> Contributors</span>
        </div>

        <div class="relative w-full overflow-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-border bg-muted/40 font-bold text-muted-foreground uppercase">
                    <tr>
                        <th class="py-2.5 px-3">Nama Contributor</th>
                        <th class="py-2.5 px-3 text-center">Published</th>
                        <th class="py-2.5 px-3 text-center">Draft / Review</th>
                        <th class="py-2.5 px-3 text-right">Total Naskah</th>
                        <th class="py-2.5 px-3 text-right">Total Views</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php foreach ($contribStats as $st): ?>
                    <tr class="hover:bg-muted/40 transition-colors">
                        <td class="py-2.5 px-3 font-bold text-foreground">
                            <?= esc($st['user']['name']) ?>
                            <span class="block text-[10px] text-muted-foreground font-normal"><?= esc($st['user']['email']) ?></span>
                        </td>
                        <td class="py-2.5 px-3 text-center font-bold text-success"><?= $st['published_count'] ?></td>
                        <td class="py-2.5 px-3 text-center font-bold text-warning"><?= $st['pending_count'] ?></td>
                        <td class="py-2.5 px-3 text-right font-bold text-foreground"><?= $st['total_posts'] ?></td>
                        <td class="py-2.5 px-3 text-right font-mono text-muted-foreground"><?= number_format($st['total_views']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- 4. DAILY ARTICLE RELEASES TABLE (Shown if view_type == 'all' or 'daily') -->
    <?php if ($viewType === 'all' || $viewType === 'daily'): ?>
    <div class="p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-foreground flex items-center gap-1.5">
                <i data-lucide="bar-chart-2" class="size-3.5 text-emerald-600"></i>
                <span>Jumlah Artikel Dirilis Per Hari</span>
            </h3>
            <span class="text-[11px] font-mono text-muted-foreground"><?= count($dailyReleases) ?> Hari</span>
        </div>

        <div class="relative w-full overflow-auto max-h-[360px]">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-border bg-muted/40 font-bold text-muted-foreground uppercase sticky top-0 bg-background">
                    <tr>
                        <th class="py-2.5 px-3">Tanggal Rilis</th>
                        <th class="py-2.5 px-3">Hari</th>
                        <th class="py-2.5 px-3 text-center">Volume Artikel Terbit</th>
                        <th class="py-2.5 px-3 text-right">Total Akumulasi Views</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php foreach ($dailyReleases as $d): ?>
                    <tr class="hover:bg-muted/40 transition-colors">
                        <td class="py-2 px-3 font-bold text-foreground"><?= esc($d['date_formatted']) ?></td>
                        <td class="py-2 px-3 text-muted-foreground"><?= esc($d['day_name']) ?></td>
                        <td class="py-2 px-3 text-center">
                            <?php if ($d['count'] > 0): ?>
                                <span class="inline-flex items-center justify-center min-w-6 rounded bg-emerald-500/15 px-2 py-0.5 text-xs font-bold text-emerald-700">
                                    <?= $d['count'] ?> artikel
                                </span>
                            <?php else: ?>
                                <span class="text-muted-foreground/50 font-mono">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3 text-right font-mono text-muted-foreground"><?= number_format($d['views']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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
    if (window.flatpickr) {
        flatpickr("#statDateFrom", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M Y",
            maxDate: "today"
        });
        flatpickr("#statDateTo", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M Y",
            maxDate: "today"
        });
    }

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

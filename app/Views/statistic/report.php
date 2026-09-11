<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.matrix-table th, .matrix-table td {
    white-space: nowrap;
}
.matrix-cell-active {
    background-color: hsl(var(--primary) / 0.12);
    color: hsl(var(--primary));
    font-weight: 700;
}
.report-toolbar-item {
    height: 38px !important;
    max-height: 38px !important;
    box-sizing: border-box;
}
.report-date-capsule input.form-control,
.report-date-capsule input {
    height: 100% !important;
    line-height: 36px !important;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    outline: none !important;
    padding: 0 !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    cursor: pointer !important;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header with Tabs Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Performa &amp; Analitik Redaksi</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Matriks sebaran artikel harian, produktivitas personil, serta heatmap jam terbit berita pasar modal.</p>
        </div>

        <!-- Role & Heatmap Tab Filter -->
        <div class="flex flex-wrap items-center gap-1 rounded-lg border border-border bg-muted/40 p-1 text-xs font-semibold self-start sm:self-auto">
            <a href="<?= base_url('statistic/report?tab=author&from=' . $fromDate . '&to=' . $toDate) ?>" class="rounded-md px-2.5 py-1 sm:px-3 sm:py-1.5 transition-all <?= $tab === 'author' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground' ?>">
                Author (Jurnalis)
            </a>
            <a href="<?= base_url('statistic/report?tab=editor&from=' . $fromDate . '&to=' . $toDate) ?>" class="rounded-md px-2.5 py-1 sm:px-3 sm:py-1.5 transition-all <?= $tab === 'editor' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground' ?>">
                Editor Redaksi
            </a>
            <a href="<?= base_url('statistic/report?tab=heatmap&from=' . $fromDate . '&to=' . $toDate) ?>" class="rounded-md px-2.5 py-1 sm:px-3 sm:py-1.5 transition-all <?= $tab === 'heatmap' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground' ?>">
                <span class="inline-flex items-center gap-1.5">
                    <i data-lucide="flame" class="size-3.5 text-amber-500"></i>
                    <span>Hourly Heatmap (24 Jam)</span>
                </span>
            </a>
        </div>
    </div>
</div>

<!-- Date Filter Toolbar -->
<div class="surface-card p-3 sm:p-4 mb-6">
    <!-- Date Filter Form -->
    <form method="get" action="<?= base_url('statistic/report') ?>" class="flex flex-wrap items-center gap-2.5 sm:gap-3">
        <input type="hidden" name="tab" value="<?= esc($tab) ?>">

        <?php if ($tab !== 'heatmap'): ?>
        <!-- Search Name Filter (Lengthened to flex-1 to fill the space smoothly) -->
        <div class="relative flex-1 min-w-[200px]">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none"></i>
            <input type="text" id="reportSearchName" placeholder="Cari nama personil..." class="report-toolbar-item w-full rounded-md border border-input bg-background pl-9 pr-3 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
        </div>
        <?php endif; ?>

        <!-- Compact Date Inputs Capsule (Uniform 38px height) -->
        <div class="report-toolbar-item report-date-capsule inline-flex items-center gap-1.5 rounded-md border border-input bg-background px-3 text-xs shadow-xs shrink-0">
            <i data-lucide="calendar" class="size-4 text-primary shrink-0"></i>
            <input type="text" name="from" id="reportDateFrom" value="<?= esc($fromDate) ?>" class="w-22 sm:w-24 bg-transparent text-xs font-semibold text-foreground focus:outline-none cursor-pointer" placeholder="YYYY-MM-DD">
            <span class="text-muted-foreground font-medium text-xs px-1">s/d</span>
            <input type="text" name="to" id="reportDateTo" value="<?= esc($toDate) ?>" class="w-22 sm:w-24 bg-transparent text-xs font-semibold text-foreground focus:outline-none cursor-pointer" placeholder="YYYY-MM-DD">
        </div>

        <!-- Apply Button -->
        <button type="submit" class="report-toolbar-item inline-flex items-center justify-center gap-1.5 rounded-md bg-[#2B6CB0] px-4 text-xs font-bold text-white shadow hover:bg-[#2B6CB0]/90 transition-colors shrink-0">
            <i data-lucide="filter" class="size-3.5"></i>
            <span>Terapkan</span>
        </button>

        <?php if ($tab !== 'heatmap'): ?>
        <!-- Export CSV Button -->
        <button type="button" id="exportReportCsvBtn" class="report-toolbar-item inline-flex items-center justify-center gap-1.5 rounded-md border border-border bg-background px-3.5 text-xs font-semibold text-foreground shadow-xs hover:bg-muted transition-colors shrink-0">
            <i data-lucide="download" class="size-3.5 text-primary"></i>
            <span>Export CSV</span>
        </button>
        <?php endif; ?>
    </form>
</div>

<?php if ($tab === 'heatmap'): ?>
<!-- 24-Hour Productivity Heatmap View -->
<div class="surface-card overflow-hidden mb-8">
    <div class="px-5 py-3.5 border-b border-border bg-muted/20 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <i data-lucide="flame" class="size-4 text-amber-500"></i>
            <h2 class="text-sm font-bold text-foreground">Distribusi Jam Penerbitan Berita (00:00 — 23:00 WIB)</h2>
        </div>
        <div class="flex items-center gap-2 text-xs">
            <span class="text-muted-foreground text-[11px]">Intensitas Berita:</span>
            <div class="flex items-center gap-1">
                <span class="inline-flex size-4 items-center justify-center rounded bg-muted/60 text-[9px] text-muted-foreground">0</span>
                <span class="inline-flex size-4 items-center justify-center rounded bg-emerald-500/20 text-[9px] font-bold text-emerald-700 dark:text-emerald-300">1-2</span>
                <span class="inline-flex size-4 items-center justify-center rounded bg-emerald-500/40 text-[9px] font-bold text-emerald-800 dark:text-emerald-200">3-5</span>
                <span class="inline-flex size-4 items-center justify-center rounded bg-emerald-600 text-[9px] font-bold text-white">6+</span>
            </div>
        </div>
    </div>

    <div class="relative w-full overflow-x-auto custom-sidebar-scroll">
        <table class="w-full text-left text-xs matrix-table">
            <thead class="border-b border-border bg-muted/50 font-bold text-muted-foreground uppercase text-[11px] sticky top-0">
                <tr>
                    <th class="py-3 px-3.5 sticky left-0 z-20 bg-background/95 backdrop-blur-xs border-r border-border min-w-[160px]">Tanggal</th>
                    <?php for ($h = 0; $h < 24; $h++): ?>
                    <th class="py-3 px-1.5 text-center border-r border-border/60 min-w-[36px]">
                        <span class="text-foreground text-[10px]"><?= sprintf('%02d', $h) ?></span>
                    </th>
                    <?php endfor; ?>
                    <th class="py-3 px-3 text-center border-l border-border min-w-[80px] bg-primary/5 text-primary font-bold">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php if (empty($heatmapData)): ?>
                <tr>
                    <td colspan="26" class="p-8 text-center text-muted-foreground text-xs">
                        Tidak ada riwayat publikasi berita pada rentang tanggal ini.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($heatmapData as $dateStr => $hourlyCounts): 
                        $dayTotal = array_sum($hourlyCounts);
                    ?>
                    <tr class="hover:bg-muted/30 transition-colors">
                        <td class="py-2.5 px-3.5 sticky left-0 z-10 bg-background/95 backdrop-blur-xs border-r border-border font-medium text-foreground">
                            <span class="block font-semibold"><?= date2ID($dateStr, 'day_date') ?></span>
                        </td>
                        <?php for ($h = 0; $h < 24; $h++): 
                            $count = $hourlyCounts[$h] ?? 0;
                        ?>
                        <td class="py-2 px-1 text-center border-r border-border/40">
                            <?php if ($count === 0): ?>
                                <span class="text-muted-foreground/30 text-[11px]">-</span>
                            <?php elseif ($count <= 2): ?>
                                <span class="inline-flex size-6 items-center justify-center rounded bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 font-bold text-[11px]" title="<?= $count ?> artikel pada jam <?= sprintf('%02d:00', $h) ?>">
                                    <?= $count ?>
                                </span>
                            <?php elseif ($count <= 5): ?>
                                <span class="inline-flex size-6 items-center justify-center rounded bg-emerald-500/35 text-emerald-900 dark:text-emerald-100 font-bold text-[11px]" title="<?= $count ?> artikel pada jam <?= sprintf('%02d:00', $h) ?>">
                                    <?= $count ?>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex size-6 items-center justify-center rounded bg-emerald-600 text-white font-bold text-[11px] shadow-xs" title="<?= $count ?> artikel pada jam <?= sprintf('%02d:00', $h) ?>">
                                    <?= $count ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <?php endfor; ?>
                        <td class="py-2 px-3 text-center border-l border-border bg-primary/5 font-bold text-primary font-mono">
                            <?= number_format($dayTotal) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<!-- Author / Editor Matrix View -->
<?php
$activeMatrix = match($tab) {
    'editor' => $editorMatrix,
    default => $authorMatrix,
};
$roleLabel = match($tab) {
    'editor' => 'Editor Redaksi',
    default => 'Author (Jurnalis)',
};
?>

<div class="surface-card overflow-hidden mb-8">
    <div class="px-5 py-3.5 border-b border-border bg-muted/20 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i data-lucide="table-2" class="size-4 text-primary"></i>
            <h2 class="text-sm font-bold text-foreground">Matriks Performa: <?= esc($roleLabel) ?></h2>
        </div>
        <span id="reportPersonnelCount" class="text-xs font-medium text-muted-foreground"><?= isset($fullPersonnelCount) ? esc($fullPersonnelCount) : count($activeMatrix) ?> Personil Terdaftar</span>
    </div>

    <div class="relative w-full overflow-x-auto custom-sidebar-scroll">
        <table class="w-full text-left text-xs matrix-table">
            <thead class="border-b border-border bg-muted/50 font-bold text-muted-foreground uppercase text-[11px] sticky top-0">
                <tr>
                    <th class="py-3 px-3.5 sticky left-0 z-20 bg-background/95 backdrop-blur-xs border-r border-border min-w-[180px]">Nama</th>
                    <th class="py-3 px-3 text-center border-r border-border min-w-[90px]">Role</th>
                    
                    <!-- Date Columns -->
                    <?php foreach ($dateColumns as $col): ?>
                    <th class="py-3 px-2 text-center border-r border-border/60 min-w-[42px]" title="<?= esc($col['full_date']) ?>">
                        <span class="block text-foreground text-xs"><?= esc($col['short_label']) ?></span>
                        <span class="block text-[9px] text-muted-foreground/70 font-normal"><?= esc($col['day_name']) ?></span>
                    </th>
                    <?php endforeach; ?>

                    <th class="py-3 px-3.5 text-center border-r border-border min-w-[90px] bg-primary/5 text-primary font-bold">Total Terbit</th>
                    <th class="py-3 px-3.5 text-right min-w-[110px] bg-primary/5 text-primary font-bold">Views</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border" id="reportMatrixTbody">
                <?php if (empty($activeMatrix)): ?>
                <tr>
                    <td colspan="<?= count($dateColumns) + 4 ?>" class="p-8 text-center text-muted-foreground text-xs">
                        Tidak ada data performa untuk rentang waktu dan peran yang dipilih.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($activeMatrix as $row): ?>
                    <tr class="hover:bg-muted/40 transition-colors report-matrix-row">
                        <td class="py-2.5 px-3.5 sticky left-0 z-10 bg-background/95 backdrop-blur-xs border-r border-border font-medium text-foreground">
                            <span class="block font-semibold text-foreground truncate max-w-[170px]"><?= esc($row['name']) ?></span>
                            <span class="block text-[10px] text-muted-foreground">@<?= esc($row['user']['username'] ?? '') ?></span>
                        </td>
                        <td class="py-2.5 px-3 text-center border-r border-border">
                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium bg-muted text-muted-foreground uppercase">
                                <?= esc($row['role']) ?>
                            </span>
                        </td>

                        <?php foreach ($dateColumns as $col): 
                            $count = $row['daily_counts'][$col['full_date']] ?? 0;
                        ?>
                        <td class="py-2 px-1 text-center border-r border-border/60 <?= $count > 0 ? 'matrix-cell-active' : 'text-muted-foreground/40' ?>">
                            <?= $count > 0 ? $count : '-' ?>
                        </td>
                        <?php endforeach; ?>

                        <td class="py-2.5 px-3.5 text-center border-r border-border bg-primary/5 font-bold text-primary">
                            <?= number_format($row['published_count']) ?>
                        </td>
                        <td class="py-2.5 px-3.5 text-right bg-primary/5 font-bold text-foreground">
                            <?= number_format($row['total_views']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination for Matrix -->
    <?php if (!empty($pagination) && $pagination['totalPages'] > 1): ?>
        <?php
        $queryParams = $_GET;
        $buildReportPageUrl = function($pageNum) use ($queryParams) {
            $params = $queryParams;
            $params['page'] = $pageNum;
            return base_url('statistic/report?' . http_build_query($params));
        };
        ?>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 border-t border-border bg-muted/10 text-xs">
            <span class="text-muted-foreground text-center sm:text-left">
                Menampilkan <span class="font-medium text-foreground"><?= $pagination['from'] ?></span> s/d <span class="font-medium text-foreground"><?= $pagination['to'] ?></span> dari <span class="font-medium text-foreground"><?= $pagination['totalItems'] ?></span> personil
            </span>

            <div class="flex items-center gap-1">
                <?php if ($pagination['page'] > 1): ?>
                    <a href="<?= $buildReportPageUrl($pagination['page'] - 1) ?>" class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-foreground hover:bg-muted transition-colors">Prev</a>
                <?php else: ?>
                    <span class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-muted-foreground opacity-50 cursor-not-allowed">Prev</span>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $pagination['totalPages']; $p++): ?>
                    <?php if ($p === $pagination['page']): ?>
                        <span class="rounded-md bg-primary px-2.5 py-1 font-bold text-primary-foreground"><?= $p ?></span>
                    <?php else: ?>
                        <a href="<?= $buildReportPageUrl($p) ?>" class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-foreground hover:bg-muted transition-colors"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                    <a href="<?= $buildReportPageUrl($pagination['page'] + 1) ?>" class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-foreground hover:bg-muted transition-colors">Next</a>
                <?php else: ?>
                    <span class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-muted-foreground opacity-50 cursor-not-allowed">Next</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="/js/pages/report.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.flatpickr) {
        flatpickr("#reportDateFrom", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M Y",
            maxDate: "today"
        });
        flatpickr("#reportDateTo", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M Y",
            maxDate: "today"
        });
    }
});
</script>
<?= $this->endSection() ?>

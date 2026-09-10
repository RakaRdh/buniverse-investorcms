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
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header with Tabs Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Performa Tim Editorial</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Matriks sebaran artikel per hari (format tanggal h/b) dan akumulasi views tim redaksi (maksimal rentang 30 hari).</p>
        </div>

        <!-- Role Tab Filter -->
        <div class="flex flex-wrap items-center gap-1 rounded-lg border border-border bg-muted/40 p-1 text-xs font-semibold self-start sm:self-auto">
            <a href="<?= base_url('statistic/report?tab=author&from=' . $fromDate . '&to=' . $toDate) ?>" class="rounded-md px-2.5 py-1 sm:px-3 sm:py-1.5 transition-all <?= $tab === 'author' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground' ?>">
                Author (Jurnalis)
            </a>
            <a href="<?= base_url('statistic/report?tab=editor&from=' . $fromDate . '&to=' . $toDate) ?>" class="rounded-md px-2.5 py-1 sm:px-3 sm:py-1.5 transition-all <?= $tab === 'editor' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground' ?>">
                Editor Redaksi
            </a>
        </div>
    </div>
</div>

<!-- Date Filter & Summary Toolbar Responsive -->
<div class="surface-card p-4 mb-6">
    <form method="get" action="<?= base_url('statistic/report') ?>" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <input type="hidden" name="tab" value="<?= esc($tab) ?>">

        <div class="flex items-center gap-2 text-xs">
            <span class="inline-flex size-2 shrink-0 rounded-full bg-primary animate-pulse"></span>
            <div class="min-w-0">
                <span class="text-muted-foreground font-medium">Periode:</span>
                <span class="font-bold text-foreground"><?= date('d M Y', strtotime($fromDate)) ?> — <?= date('d M Y', strtotime($toDate)) ?></span>
                <span class="text-[11px] text-muted-foreground">(<?= count($dateColumns) ?> hari)</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <!-- Search Name Filter (Full width on mobile, w-48 on desktop) -->
            <div class="relative w-full sm:w-48">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 size-3.5 text-muted-foreground pointer-events-none"></i>
                <input type="text" id="reportSearchName" placeholder="Cari nama personil..." class="h-8 w-full rounded-md border border-input bg-background pl-9 pr-3 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>

            <!-- Date Inputs -->
            <div class="flex items-center gap-1 rounded-md border border-input bg-background px-2.5 py-1 text-xs shadow-xs flex-1 sm:flex-initial">
                <i data-lucide="calendar" class="size-3.5 text-primary shrink-0"></i>
                <input type="text" name="from" id="reportDateFrom" value="<?= esc($fromDate) ?>" class="h-6 w-24 bg-transparent text-xs font-semibold text-foreground focus:outline-none cursor-pointer" placeholder="YYYY-MM-DD">
                <span class="text-muted-foreground font-medium px-0.5">s/d</span>
                <input type="text" name="to" id="reportDateTo" value="<?= esc($toDate) ?>" class="h-6 w-24 bg-transparent text-xs font-semibold text-foreground focus:outline-none cursor-pointer" placeholder="YYYY-MM-DD">
            </div>

            <button type="submit" class="inline-flex h-8 items-center justify-center gap-1.5 rounded-md bg-[#2B6CB0] px-3.5 text-xs font-bold text-white shadow hover:bg-[#2B6CB0]/90 transition-colors">
                <i data-lucide="filter" class="size-3"></i>
                <span>Terapkan Rentang</span>
            </button>

            <!-- Export CSV Button Inline -->
            <button type="button" id="exportReportCsvBtn" class="inline-flex h-8 items-center justify-center gap-1.5 rounded-md border border-border bg-background px-3 text-xs font-semibold text-foreground shadow-xs hover:bg-muted transition-colors">
                <i data-lucide="download" class="size-3 text-primary"></i>
                <span>Export CSV</span>
            </button>
        </div>
    </form>
</div>

<?php
// Pick current dataset based on tab
$activeMatrix = match($tab) {
    'editor' => $editorMatrix,
    default => $authorMatrix,
};
$roleLabel = match($tab) {
    'editor' => 'Editor Redaksi',
    default => 'Author (Jurnalis)',
};
?>

<!-- Matrix Table Container -->
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
                    
                    <!-- Date Columns: Format '3/9', '4/9', etc. -->
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
            <tbody class="divide-y divide-border">
                <?php if (empty($activeMatrix)): ?>
                <tr>
                    <td colspan="<?= count($dateColumns) + 4 ?>" class="py-8 text-center text-muted-foreground">
                        Tidak ada data personil pada role ini dalam rentang waktu yang dipilih.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($activeMatrix as $row): ?>
                    <tr class="hover:bg-muted/30 transition-colors">
                        <!-- Column 1: Nama (Sticky Left) -->
                        <td class="py-2.5 px-3.5 sticky left-0 z-10 bg-background border-r border-border font-bold text-foreground">
                            <div class="flex items-center gap-2">
                                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-primary/10 text-primary text-[10px] font-bold">
                                    <?= esc(substr($row['name'], 0, 1)) ?>
                                </span>
                                <span class="truncate"><?= esc($row['name']) ?></span>
                            </div>
                        </td>

                        <!-- Column 2: Role -->
                        <td class="py-2.5 px-3 text-center border-r border-border">
                            <span class="inline-flex rounded-md border border-border px-2 py-0.5 text-[10px] font-medium bg-muted text-muted-foreground">
                                <?= esc($row['role']) ?>
                            </span>
                        </td>

                        <!-- Column 3 to XX: Tanggal (format '3/9') -->
                        <?php foreach ($dateColumns as $col): ?>
                        <?php 
                            $c = $row['daily_counts'][$col['full_date']] ?? 0;
                            $hasArticle = $c > 0;
                        ?>
                        <td class="py-2 px-1 text-center border-r border-border/50 text-xs <?= $hasArticle ? 'matrix-cell-active' : 'text-muted-foreground/40 font-mono' ?>">
                            <?= $hasArticle ? esc($c) : '-' ?>
                        </td>
                        <?php endforeach; ?>

                        <!-- Total Terbit Column -->
                        <td class="py-2.5 px-3 text-center font-bold text-primary border-r border-border bg-primary/5">
                            <?= esc($row['published_count']) ?>
                        </td>

                        <!-- Total Views Column -->
                        <td class="py-2.5 px-3.5 text-right font-mono font-bold text-foreground bg-primary/5">
                            <?= number_format($row['total_views']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
        </table>
    </div>

    <!-- Pagination Footer -->
    <?php if (isset($pagination) && $pagination['totalItems'] > 0): ?>
        <?php
            $reportQueryParams = $_GET;
            $buildReportPageUrl = function($p) use ($reportQueryParams) {
                $params = $reportQueryParams;
                $params['page'] = $p;
                return base_url('statistic/report') . '?' . http_build_query($params);
            };
        ?>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-border px-4 py-3 text-xs text-muted-foreground bg-muted/20">
            <div>
                Menampilkan <span class="font-semibold text-foreground"><?= $pagination['from'] ?></span> - <span class="font-semibold text-foreground"><?= $pagination['to'] ?></span> dari <span class="font-semibold text-foreground"><?= number_format($pagination['totalItems']) ?></span> personil
            </div>
            <div class="flex items-center gap-1">
                <?php if ($pagination['page'] > 1): ?>
                    <a href="<?= $buildReportPageUrl($pagination['page'] - 1) ?>" class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-foreground hover:bg-muted transition-colors">Prev</a>
                <?php else: ?>
                    <span class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-muted-foreground opacity-50 cursor-not-allowed">Prev</span>
                <?php endif; ?>

                <?php for ($p = max(1, $pagination['page'] - 2); $p <= min($pagination['totalPages'], $pagination['page'] + 2); $p++): ?>
                    <?php if ($p == $pagination['page']): ?>
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

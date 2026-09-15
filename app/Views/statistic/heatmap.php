<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.matrix-table th, .matrix-table td {
    white-space: nowrap;
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

<!-- Page Header Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Heatmap Distribusi Jam Terbit (24 Jam)</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Analisis matriks kepadatan waktu publikasi berita 24 jam untuk optimasi jadwal tayang redaksi.</p>
        </div>
    </div>
</div>

<!-- Mode Selection & Filter Toolbar -->
<div class="surface-card p-3 sm:p-4 mb-6">
    <div class="flex flex-col gap-4">
        <!-- Mode Switcher Tabs (Per Bulan vs Custom Rentang Waktu) -->
        <div class="flex items-center gap-2 border-b border-border/60 pb-3">
            <span class="text-xs font-semibold text-muted-foreground">Mode Filter:</span>
            <div class="inline-flex rounded-lg border border-border bg-muted/40 p-1 text-xs font-medium">
                <button type="button" id="btnModeMonth" onclick="switchHeatmapMode('month')" class="rounded-md px-3 py-1 transition-all <?= $mode === 'month' ? 'bg-background text-foreground shadow-xs font-semibold' : 'text-muted-foreground hover:text-foreground' ?>">
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="calendar-days" class="size-3.5"></i>
                        <span>Pilihan Bulan</span>
                    </span>
                </button>
                <button type="button" id="btnModeRange" onclick="switchHeatmapMode('range')" class="rounded-md px-3 py-1 transition-all <?= $mode === 'range' ? 'bg-background text-foreground shadow-xs font-semibold' : 'text-muted-foreground hover:text-foreground' ?>">
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="calendar-range" class="size-3.5"></i>
                        <span>Custom Rentang Waktu</span>
                    </span>
                </button>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="get" action="<?= base_url('statistic/heatmap') ?>" id="heatmapFilterForm" class="flex flex-wrap items-center gap-2.5 sm:gap-3">
            <input type="hidden" name="mode" id="heatmapModeInput" value="<?= esc($mode) ?>">

            <!-- Month Selectors (Visible when mode === 'month') -->
            <div id="monthSelectorBox" class="flex flex-wrap items-center gap-2 <?= $mode === 'month' ? '' : 'hidden' ?>">
                <div class="inline-flex items-center gap-1.5 rounded-md border border-input bg-background px-3 text-xs shadow-xs report-toolbar-item">
                    <i data-lucide="calendar" class="size-4 text-primary shrink-0"></i>
                    <select name="month" id="heatmapMonthSelect" class="bg-transparent text-xs font-semibold text-foreground focus:outline-none cursor-pointer py-1">
                        <?php 
                        $monthNames = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                        foreach ($monthNames as $mNum => $mName): ?>
                            <option value="<?= $mNum ?>" <?= (int)$month === $mNum ? 'selected' : '' ?>><?= $mName ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="year" id="heatmapYearSelect" class="bg-transparent text-xs font-semibold text-foreground focus:outline-none cursor-pointer py-1 pl-1 border-l border-border">
                        <?php 
                        $curY = (int)date('Y');
                        for ($y = $curY; $y >= $curY - 5; $y--): ?>
                            <option value="<?= $y ?>" <?= (int)$year === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <!-- Custom Range Inputs (Visible when mode === 'range') -->
            <div id="rangeSelectorBox" class="flex flex-wrap items-center gap-2 <?= $mode === 'range' ? '' : 'hidden' ?>">
                <div class="report-toolbar-item report-date-capsule inline-flex items-center gap-1.5 rounded-md border border-input bg-background px-3 text-xs shadow-xs shrink-0">
                    <i data-lucide="calendar-range" class="size-4 text-primary shrink-0"></i>
                    <input type="text" name="from" id="heatmapDateFrom" value="<?= esc($fromDate) ?>" class="w-22 sm:w-24 bg-transparent text-xs font-semibold text-foreground focus:outline-none cursor-pointer" placeholder="YYYY-MM-DD">
                    <span class="text-muted-foreground font-medium text-xs px-1">s/d</span>
                    <input type="text" name="to" id="heatmapDateTo" value="<?= esc($toDate) ?>" class="w-22 sm:w-24 bg-transparent text-xs font-semibold text-foreground focus:outline-none cursor-pointer" placeholder="YYYY-MM-DD">
                </div>
            </div>

            <!-- Apply Button -->
            <button type="submit" class="report-toolbar-item inline-flex items-center justify-center gap-1.5 rounded-md bg-[#2B6CB0] px-4 text-xs font-bold text-white shadow hover:bg-[#2B6CB0]/90 transition-colors shrink-0">
                <i data-lucide="filter" class="size-3.5"></i>
                <span>Terapkan</span>
            </button>

            <!-- Export Matrix Button -->
            <button type="button" id="exportHeatmapCsvBtn" class="report-toolbar-item inline-flex items-center justify-center gap-1.5 rounded-md border border-border bg-background px-3.5 text-xs font-semibold text-foreground shadow-xs hover:bg-muted transition-colors shrink-0">
                <i data-lucide="download" class="size-3.5 text-primary"></i>
                <span>Export CSV Heatmap</span>
            </button>
        </form>
    </div>
</div>

<!-- 24-Hour Productivity Heatmap View -->
<div class="surface-card overflow-hidden mb-8">
    <div class="px-5 py-3.5 border-b border-border bg-muted/20 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <i data-lucide="flame" class="size-4 text-amber-500"></i>
            <h2 class="text-sm font-bold text-foreground">
                Distribusi Jam Penerbitan Berita 
                <span class="text-xs font-normal text-muted-foreground">(<?= esc($fromDate) ?> s/d <?= esc($toDate) ?>)</span>
            </h2>
        </div>
        <div class="flex items-center gap-2 text-xs">
            <span class="text-muted-foreground text-[11px]">Intensitas:</span>
            <div class="flex items-center gap-1">
                <span class="inline-flex size-4 items-center justify-center rounded bg-muted/60 text-[9px] text-muted-foreground">0</span>
                <span class="inline-flex size-4 items-center justify-center rounded bg-emerald-500/20 text-[9px] font-bold text-emerald-700 dark:text-emerald-300">1-2</span>
                <span class="inline-flex size-4 items-center justify-center rounded bg-emerald-500/40 text-[9px] font-bold text-emerald-800 dark:text-emerald-200">3-5</span>
                <span class="inline-flex size-4 items-center justify-center rounded bg-emerald-600 text-[9px] font-bold text-white">6+</span>
            </div>
        </div>
    </div>

    <div class="relative w-full overflow-x-auto custom-sidebar-scroll">
        <table class="w-full text-left text-xs matrix-table" id="heatmapTable">
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
                    <?php 
                    $totalAllArticles = 0;
                    $hourlyGrandTotals = array_fill(0, 24, 0);

                    foreach ($heatmapData as $dateStr => $hourlyCounts): 
                        $dayTotal = array_sum($hourlyCounts);
                        $totalAllArticles += $dayTotal;
                    ?>
                    <tr class="hover:bg-muted/30 transition-colors">
                        <td class="py-2.5 px-3.5 sticky left-0 z-10 bg-background/95 backdrop-blur-xs border-r border-border font-medium text-foreground">
                            <span class="block font-semibold"><?= date2ID($dateStr, 'day_date') ?></span>
                        </td>
                        <?php for ($h = 0; $h < 24; $h++): 
                            $count = $hourlyCounts[$h] ?? 0;
                            $hourlyGrandTotals[$h] += $count;
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
            <?php if (!empty($heatmapData)): ?>
            <tfoot class="border-t-2 border-border bg-muted/60 font-bold text-xs sticky bottom-0">
                <tr>
                    <td class="py-3 px-3.5 sticky left-0 z-20 bg-background/95 backdrop-blur-xs border-r border-border text-foreground">
                        Ringkasan Total
                    </td>
                    <?php for ($h = 0; $h < 24; $h++): ?>
                    <td class="py-2 px-1 text-center border-r border-border/60 font-mono text-[11px] text-foreground">
                        <?= $hourlyGrandTotals[$h] > 0 ? number_format($hourlyGrandTotals[$h]) : '-' ?>
                    </td>
                    <?php endfor; ?>
                    <td class="py-2 px-3 text-center border-l border-border bg-primary/10 font-mono text-primary text-sm font-extrabold">
                        <?= number_format($totalAllArticles) ?>
                    </td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
function switchHeatmapMode(mode) {
    const modeInput = document.getElementById('heatmapModeInput');
    const monthBox = document.getElementById('monthSelectorBox');
    const rangeBox = document.getElementById('rangeSelectorBox');
    const btnMonth = document.getElementById('btnModeMonth');
    const btnRange = document.getElementById('btnModeRange');

    modeInput.value = mode;

    if (mode === 'month') {
        monthBox.classList.remove('hidden');
        rangeBox.classList.add('hidden');

        btnMonth.className = 'rounded-md px-3 py-1 transition-all bg-background text-foreground shadow-xs font-semibold';
        btnRange.className = 'rounded-md px-3 py-1 transition-all text-muted-foreground hover:text-foreground';
    } else {
        monthBox.classList.add('hidden');
        rangeBox.classList.remove('hidden');

        btnRange.className = 'rounded-md px-3 py-1 transition-all bg-background text-foreground shadow-xs font-semibold';
        btnMonth.className = 'rounded-md px-3 py-1 transition-all text-muted-foreground hover:text-foreground';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.flatpickr) {
        flatpickr("#heatmapDateFrom", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M Y",
            maxDate: "today"
        });
        flatpickr("#heatmapDateTo", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M Y",
            maxDate: "today"
        });
    }

    // Export CSV Heatmap
    const exportBtn = document.getElementById('exportHeatmapCsvBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            const table = document.getElementById('heatmapTable');
            if (!table) return;

            const rows = [];
            table.querySelectorAll('tr').forEach((tr) => {
                const rowData = [];
                tr.querySelectorAll('th, td').forEach((cell) => {
                    let text = cell.innerText.replace(/[\n\r]+/g, ' ').replace(/\s+/g, ' ').trim();
                    rowData.push('"' + text.replace(/"/g, '""') + '"');
                });
                if (rowData.length > 0) {
                    rows.push(rowData.join(','));
                }
            });

            if (rows.length <= 1) {
                alert('Tidak ada data heatmap untuk diexport.');
                return;
            }

            const csvContent = '\uFEFF' + rows.join('\r\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const downloadLink = document.createElement('a');
            downloadLink.href = url;
            downloadLink.setAttribute('download', `editorial_heatmap_<?= $fromDate ?>_<?= $toDate ?>.csv`);
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            URL.revokeObjectURL(url);
        });
    }
});
</script>
<?= $this->endSection() ?>

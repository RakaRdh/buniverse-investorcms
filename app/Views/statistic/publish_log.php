<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
.matrix-table th, .matrix-table td {
    white-space: nowrap;
}
.report-toolbar-item {
    height: 38px !important;
    max-height: 38px !important;
    box-sizing: border-box;
}
.publish-cell {
    transition: background-color 0.15s ease-in-out;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header Responsive (Clean without sub-navigation tabs) -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Publish Log</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Matriks sebaran jumlah artikel terbit per bulan dan tanggal sepanjang tahun.</p>
        </div>
    </div>
</div>

<!-- Year Selection Toolbar -->
<div class="surface-card p-3 sm:p-4 mb-6">
    <form method="get" action="<?= base_url('statistic/publish-log') ?>" class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-muted-foreground">Tahun:</span>
            <div class="inline-flex items-center gap-1.5 rounded-md border border-input bg-background px-3 text-xs shadow-xs report-toolbar-item">
                <i data-lucide="calendar" class="size-4 text-primary shrink-0"></i>
                <select name="year" id="publishLogYearSelect" class="bg-transparent text-xs font-semibold text-foreground focus:outline-none cursor-pointer py-1">
                    <?php 
                    $curY = (int)date('Y');
                    for ($y = $curY; $y >= $curY - 6; $y--): ?>
                        <option value="<?= $y ?>" <?= (int)$year === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <button type="submit" class="report-toolbar-item inline-flex items-center justify-center gap-1.5 rounded-md bg-[#2B6CB0] px-4 text-xs font-bold text-white shadow hover:bg-[#2B6CB0]/90 transition-colors shrink-0">
                <i data-lucide="filter" class="size-3.5"></i>
                <span>Terapkan</span>
            </button>
        </div>

        <!-- Export CSV Button -->
        <button type="button" id="exportPublishLogCsvBtn" class="report-toolbar-item inline-flex items-center justify-center gap-1.5 rounded-md border border-border bg-background px-3.5 text-xs font-semibold text-foreground shadow-xs hover:bg-muted transition-colors shrink-0">
            <i data-lucide="download" class="size-3.5 text-primary"></i>
            <span>Export CSV Publish Log</span>
        </button>
    </form>
</div>

<!-- Heatmap Matrix Table: Vertical Months x Horizontal Days (1-31) -->
<div class="surface-card overflow-hidden mb-8">
    <div class="px-5 py-3.5 border-b border-border bg-muted/20 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <i data-lucide="calendar-check" class="size-4 text-primary"></i>
            <h2 class="text-sm font-bold text-foreground">
                Matriks Publikasi Artikel Tahun <?= esc($year) ?>
            </h2>
        </div>
        <div class="flex items-center gap-2 text-xs">
            <span class="text-muted-foreground text-[11px]">Intensitas Terbit:</span>
            <div class="flex items-center gap-1">
                <span class="inline-flex size-4 items-center justify-center rounded bg-muted/60 text-[9px] text-muted-foreground">0</span>
                <span class="inline-flex size-4 items-center justify-center rounded bg-emerald-500/20 text-[9px] font-bold text-emerald-700 dark:text-emerald-300">1-2</span>
                <span class="inline-flex size-4 items-center justify-center rounded bg-emerald-500/40 text-[9px] font-bold text-emerald-800 dark:text-emerald-200">3-5</span>
                <span class="inline-flex size-4 items-center justify-center rounded bg-emerald-600 text-[9px] font-bold text-white">6+</span>
            </div>
        </div>
    </div>

    <div class="relative w-full overflow-x-auto custom-sidebar-scroll">
        <table class="w-full text-left text-xs matrix-table" id="publishLogMatrixTable">
            <thead class="border-b border-border bg-muted/50 font-bold text-muted-foreground uppercase text-[11px] sticky top-0">
                <tr>
                    <th class="py-3 px-3.5 sticky left-0 z-20 bg-background/95 backdrop-blur-xs border-r border-border min-w-[130px]">Bulan</th>
                    <?php for ($d = 1; $d <= 31; $d++): ?>
                    <th class="py-3 px-1 text-center border-r border-border/60 min-w-[36px]">
                        <span class="text-foreground text-[10px]"><?= sprintf('%02d', $d) ?></span>
                    </th>
                    <?php endfor; ?>
                    <th class="py-3 px-3 text-center border-l border-border min-w-[80px] bg-primary/5 text-primary font-bold">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php 
                $grandTotalArticles = 0;
                $dailyGrandTotals = array_fill(1, 31, 0);

                foreach ($matrix as $mNum => $mRow): 
                    $grandTotalArticles += $mRow['month_total'];
                ?>
                <tr class="hover:bg-muted/30 transition-colors">
                    <td class="py-2.5 px-3.5 sticky left-0 z-10 bg-background/95 backdrop-blur-xs border-r border-border font-medium text-foreground">
                        <span class="block font-semibold"><?= esc($mRow['month_name']) ?></span>
                    </td>

                    <?php for ($d = 1; $d <= 31; $d++): 
                        $dayData = $mRow['days'][$d];
                        $isValid = $dayData['valid'];
                        $count = $dayData['count'];
                        if ($isValid) {
                            $dailyGrandTotals[$d] += $count;
                        }
                    ?>
                    <td class="py-2 px-1 text-center border-r border-border/40 publish-cell <?= !$isValid ? 'bg-muted/30' : '' ?>">
                        <?php if (!$isValid): ?>
                            <span class="text-muted-foreground/20 text-[10px] select-none">✕</span>
                        <?php elseif ($count === 0): ?>
                            <span class="text-muted-foreground/35 text-[11px]">-</span>
                        <?php elseif ($count <= 2): ?>
                            <span class="inline-flex size-6 items-center justify-center rounded bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 font-bold text-[11px]" title="<?= $count ?> artikel terbit (<?= esc($dayData['date']) ?>)">
                                <?= $count ?>
                            </span>
                        <?php elseif ($count <= 5): ?>
                            <span class="inline-flex size-6 items-center justify-center rounded bg-emerald-500/35 text-emerald-900 dark:text-emerald-100 font-bold text-[11px]" title="<?= $count ?> artikel terbit (<?= esc($dayData['date']) ?>)">
                                <?= $count ?>
                            </span>
                        <?php else: ?>
                            <span class="inline-flex size-6 items-center justify-center rounded bg-emerald-600 text-white font-bold text-[11px] shadow-xs" title="<?= $count ?> artikel terbit (<?= esc($dayData['date']) ?>)">
                                <?= $count ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <?php endfor; ?>

                    <td class="py-2 px-3 text-center border-l border-border bg-primary/5 font-bold text-primary font-mono">
                        <?= number_format($mRow['month_total']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="border-t-2 border-border bg-muted/60 font-bold text-xs sticky bottom-0">
                <tr>
                    <td class="py-3 px-3.5 sticky left-0 z-20 bg-background/95 backdrop-blur-xs border-r border-border text-foreground">
                        Total Pertanggal
                    </td>
                    <?php for ($d = 1; $d <= 31; $d++): ?>
                    <td class="py-2 px-1 text-center border-r border-border/60 font-mono text-[10px] text-foreground">
                        <?= $dailyGrandTotals[$d] > 0 ? number_format($dailyGrandTotals[$d]) : '-' ?>
                    </td>
                    <?php endfor; ?>
                    <td class="py-2 px-3 text-center border-l border-border bg-primary/10 font-mono text-primary text-sm font-extrabold">
                        <?= number_format($grandTotalArticles) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Export CSV Publish Log Matrix
    const exportBtn = document.getElementById('exportPublishLogCsvBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            const table = document.getElementById('publishLogMatrixTable');
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
                alert('Tidak ada data publish log untuk diexport.');
                return;
            }

            const csvContent = '\uFEFF' + rows.join('\r\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const downloadLink = document.createElement('a');
            downloadLink.href = url;
            downloadLink.setAttribute('download', `publish_log_matrix_<?= $year ?>.csv`);
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            URL.revokeObjectURL(url);
        });
    }
});
</script>
<?= $this->endSection() ?>

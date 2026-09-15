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
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Artikel Terpopuler</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Peringkat artikel dengan impresi dan pembaca tertinggi berdasarkan akumulasi pageviews.</p>
        </div>
    </div>
</div>

<!-- Date Filter Toolbar -->
<div class="surface-card p-3 sm:p-4 mb-6">
    <form method="get" action="<?= base_url('statistic/popular') ?>" class="flex flex-wrap items-center gap-2.5 sm:gap-3">
        <!-- Quick Client Filter -->
        <div class="relative flex-1 min-w-[200px]">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none"></i>
            <input type="text" id="popularSearchInput" placeholder="Cari judul artikel..." class="report-toolbar-item w-full rounded-md border border-input bg-background pl-9 pr-3 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
        </div>

        <!-- Date Range Capsule -->
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

        <!-- Export Spreadsheet -->
        <a href="<?= base_url('statistic/export?tab=popular&from=' . $fromDate . '&to=' . $toDate) ?>" class="report-toolbar-item inline-flex items-center justify-center gap-1.5 rounded-md border border-border bg-background px-3.5 text-xs font-semibold text-foreground shadow-xs hover:bg-muted transition-colors shrink-0">
            <i data-lucide="download" class="size-3.5 text-primary"></i>
            <span>Export Spreadsheet</span>
        </a>
    </form>
</div>

<!-- Popular Articles View -->
<div class="surface-card overflow-hidden mb-8">
    <div class="px-5 py-3.5 border-b border-border bg-muted/20 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i data-lucide="trending-up" class="size-4 text-emerald-500"></i>
            <h2 class="text-sm font-bold text-foreground">Peringkat Artikel Terpopuler</h2>
        </div>
        <span id="popularCount" class="text-xs font-medium text-muted-foreground">Top <?= count($popularArticles) ?> Artikel</span>
    </div>

    <div class="relative w-full overflow-x-auto custom-sidebar-scroll">
        <table class="w-full text-left text-xs matrix-table" id="popularArticlesTable">
            <thead class="border-b border-border bg-muted/50 font-bold text-muted-foreground uppercase text-[11px] sticky top-0">
                <tr>
                    <th class="py-3 px-3 text-center min-w-[50px]">#</th>
                    <th class="py-3 px-3.5 min-w-[80px]">ID</th>
                    <th class="py-3 px-3.5 min-w-[320px]">Judul Berita</th>
                    <th class="py-3 px-3 text-center min-w-[110px]">Kanal</th>
                    <th class="py-3 px-3 text-center min-w-[130px]">Tanggal Terbit</th>
                    <th class="py-3 px-3.5 min-w-[140px]">Editor</th>
                    <th class="py-3 px-3.5 text-right min-w-[110px] bg-primary/5 text-primary font-bold">Total Views</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php if (empty($popularArticles)): ?>
                <tr>
                    <td colspan="7" class="p-8 text-center text-muted-foreground text-xs">
                        Tidak ada data artikel untuk rentang waktu ini.
                    </td>
                </tr>
                <?php else: ?>
                    <?php $rank = 1; foreach ($popularArticles as $art): ?>
                    <tr class="hover:bg-muted/40 transition-colors popular-row">
                        <td class="py-2.5 px-3 text-center font-mono font-bold <?= $rank <= 3 ? 'text-amber-500 font-extrabold' : 'text-muted-foreground' ?>">
                            <?= $rank++ ?>
                        </td>
                        <td class="py-2.5 px-3.5 font-mono text-muted-foreground">#<?= esc($art['articleID']) ?></td>
                        <td class="py-2.5 px-3.5 font-medium text-foreground">
                            <a href="<?= base_url('post/edit/' . $art['articleID']) ?>" target="_blank" class="hover:text-primary transition-colors line-clamp-1">
                                <?= esc($art['articleTitle']) ?>
                            </a>
                        </td>
                        <td class="py-2.5 px-3 text-center">
                            <span class="inline-flex items-center rounded px-2 py-0.5 text-[10px] font-semibold bg-primary/10 text-primary">
                                <?= esc($art['kanal_title'] ?? 'Market') ?>
                            </span>
                        </td>
                        <td class="py-2.5 px-3 text-center font-mono text-[11px] text-muted-foreground">
                            <?= date('d/m/Y H:i', strtotime($art['articleDate'])) ?>
                        </td>
                        <td class="py-2.5 px-3.5 text-foreground truncate max-w-[140px]">
                            <?= esc($art['editor_name'] ?? '-') ?>
                        </td>
                        <td class="py-2.5 px-3.5 text-right font-mono font-bold text-foreground bg-primary/5">
                            <?= number_format($art['viewed'] ?? 0) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

    const searchInput = document.getElementById('popularSearchInput');
    const tableBody = document.querySelector('#popularArticlesTable tbody');
    const countBadge = document.getElementById('popularCount');

    if (searchInput && tableBody) {
        const rows = tableBody.querySelectorAll('tr.popular-row');
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            let visible = 0;
            rows.forEach((row) => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                    visible++;
                } else {
                    row.style.display = 'none';
                }
            });
            if (countBadge) {
                countBadge.textContent = visible + ' Ditampilkan';
            }
        });
    }
});
</script>
<?= $this->endSection() ?>

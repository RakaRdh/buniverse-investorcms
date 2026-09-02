<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header with Date Filter -->
<div class="mb-6">
    <div class="grid grid-cols-[minmax(0,1fr)_auto] items-start gap-4 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Editorial Production & Productivity Report</h1>
            <p class="mt-1 text-xs text-muted-foreground">Laporan ringkas volume penerbitan artikel per author, editor, contributor, dan rekap harian (maksimal rentang 30 hari).</p>
        </div>

        <!-- Date Filter Toolbar -->
        <form method="get" action="<?= base_url('statistic/report') ?>" id="reportFilterForm" class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-1.5 rounded-lg border border-border bg-background p-1.5 shadow-xs">
                <i data-lucide="calendar" class="size-4 text-primary ml-1"></i>
                <input type="text" name="from" id="reportDateFrom" value="<?= esc($fromDate) ?>" class="h-7 w-28 bg-transparent px-1 text-xs font-semibold text-foreground focus:outline-none cursor-pointer" placeholder="Dari Tanggal">
                <span class="text-xs text-muted-foreground font-medium">s/d</span>
                <input type="text" name="to" id="reportDateTo" value="<?= esc($toDate) ?>" class="h-7 w-28 bg-transparent px-1 text-xs font-semibold text-foreground focus:outline-none cursor-pointer" placeholder="Sampai Tanggal">
            </div>
            
            <button type="submit" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md bg-primary px-3 text-xs font-bold text-primary-foreground shadow hover:bg-primary/90 transition-colors">
                <i data-lucide="filter" class="size-3.5"></i>
                <span>Terapkan Filter</span>
            </button>

            <a href="<?= base_url('statistic/report') ?>" class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-3 text-xs font-medium text-muted-foreground hover:bg-muted hover:text-foreground transition-colors" title="Reset ke 30 Hari Terakhir">
                <i data-lucide="rotate-ccw" class="size-3.5"></i>
            </a>
        </form>
    </div>
</div>

<!-- Active Period Badge Note -->
<div class="flex items-center justify-between mb-6 p-3 rounded-lg border border-primary/20 bg-primary/5 text-xs">
    <div class="flex items-center gap-2">
        <span class="inline-flex size-2 rounded-full bg-primary animate-pulse"></span>
        <span class="text-muted-foreground">Periode Terpilih:</span>
        <strong class="text-foreground font-semibold"><?= date('d M Y', strtotime($fromDate)) ?> — <?= date('d M Y', strtotime($toDate)) ?></strong>
        <span class="text-muted-foreground">(Maksimal 30 hari kalender)</span>
    </div>
    <div class="text-muted-foreground">
        Total Artikel dalam Rentang: <strong class="text-primary font-bold"><?= $totalFilteredArticles ?></strong>
    </div>
</div>

<div class="space-y-8">
    
    <!-- 1. Author Report Section -->
    <div class="surface-card overflow-hidden">
        <div class="px-5 py-4 border-b border-border flex items-center justify-between bg-muted/20">
            <div class="flex items-center gap-2.5">
                <span class="grid size-7 place-items-center rounded-md bg-primary/10 text-primary">
                    <i data-lucide="pen-tool" class="size-4"></i>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-foreground">1. Laporan Produktivitas Author (Jurnalis)</h2>
                    <p class="text-[11px] text-muted-foreground">Jumlah artikel yang ditulis dan diterbitkan dalam rentang tanggal terpilih.</p>
                </div>
            </div>
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20">
                <?= count($authorReport) ?> Authors
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-border bg-muted/40 text-muted-foreground uppercase font-bold">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">Nama Author</th>
                        <th class="py-3 px-4">Role & Email</th>
                        <th class="py-3 px-4 text-center">Artikel Diterbitkan</th>
                        <th class="py-3 px-4 text-right">Total Views</th>
                        <th class="py-3 px-4 text-right">Rata-rata Views</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php if (empty($authorReport)): ?>
                        <tr><td colspan="6" class="py-6 text-center text-muted-foreground">Tidak ada data author pada periode ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($authorReport as $idx => $row): ?>
                        <tr class="hover:bg-muted/40 transition-colors">
                            <td class="py-3 px-4 text-center font-mono text-muted-foreground"><?= $idx + 1 ?></td>
                            <td class="py-3 px-4 font-bold text-foreground"><?= esc($row['user']['name']) ?></td>
                            <td class="py-3 px-4 text-muted-foreground">
                                <span class="inline-flex rounded bg-muted px-1.5 py-0.5 text-[10px] font-medium mr-1.5"><?= esc($row['user']['role']) ?></span>
                                <?= esc($row['user']['email']) ?>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex rounded-md bg-success/15 px-2.5 py-0.5 text-xs font-bold text-success border border-success/30">
                                    <?= $row['published_articles'] ?> Artikel
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-semibold text-foreground"><?= number_format($row['total_views']) ?></td>
                            <td class="py-3 px-4 text-right font-mono text-muted-foreground"><?= number_format($row['avg_views']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. Editor Report Section -->
    <div class="surface-card overflow-hidden">
        <div class="px-5 py-4 border-b border-border flex items-center justify-between bg-muted/20">
            <div class="flex items-center gap-2.5">
                <span class="grid size-7 place-items-center rounded-md bg-accent/10 text-accent">
                    <i data-lucide="check-check" class="size-4"></i>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-foreground">2. Laporan Kinerja Editor Redaksi</h2>
                    <p class="text-[11px] text-muted-foreground">Jumlah artikel yang disunting, direview, dan dipublikasikan oleh masing-masing editor.</p>
                </div>
            </div>
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-accent/10 text-accent border border-accent/20">
                <?= count($editorReport) ?> Editors
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-border bg-muted/40 text-muted-foreground uppercase font-bold">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">Nama Editor</th>
                        <th class="py-3 px-4">Role Akun</th>
                        <th class="py-3 px-4 text-center">Artikel Dipublikasikan</th>
                        <th class="py-3 px-4 text-center">Total Artikel Disunting</th>
                        <th class="py-3 px-4 text-right">Total Akumulasi Views</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php if (empty($editorReport)): ?>
                        <tr><td colspan="6" class="py-6 text-center text-muted-foreground">Tidak ada data editor pada periode ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($editorReport as $idx => $row): ?>
                        <tr class="hover:bg-muted/40 transition-colors">
                            <td class="py-3 px-4 text-center font-mono text-muted-foreground"><?= $idx + 1 ?></td>
                            <td class="py-3 px-4 font-bold text-foreground"><?= esc($row['user']['name']) ?></td>
                            <td class="py-3 px-4">
                                <span class="inline-flex rounded border border-accent/30 bg-accent/10 px-2 py-0.5 text-[11px] font-bold text-accent"><?= esc($row['user']['role']) ?></span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex rounded-md bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary border border-primary/25">
                                    <?= $row['published_articles'] ?> Artikel
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center font-semibold text-muted-foreground"><?= $row['total_reviewed'] ?> naskah</td>
                            <td class="py-3 px-4 text-right font-semibold text-foreground"><?= number_format($row['total_views']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. Contributor & Daily Articles Row (2-Grid Side by Side) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        
        <!-- 3a. Contributor Section -->
        <div class="surface-card overflow-hidden">
            <div class="px-5 py-4 border-b border-border flex items-center justify-between bg-muted/20">
                <div class="flex items-center gap-2.5">
                    <span class="grid size-7 place-items-center rounded-md bg-amber-500/10 text-amber-600">
                        <i data-lucide="users" class="size-4"></i>
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-foreground">3. Laporan Contributor</h2>
                        <p class="text-[11px] text-muted-foreground">Penerbitan naskah kontributor luar / desk.</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto max-h-[420px]">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-border bg-muted/40 text-muted-foreground uppercase font-bold sticky top-0 bg-background">
                        <tr>
                            <th class="py-3 px-4">Nama Contributor</th>
                            <th class="py-3 px-4 text-center">Published</th>
                            <th class="py-3 px-4 text-center">Draft/Review</th>
                            <th class="py-3 px-4 text-right">Total Naskah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <?php if (empty($contribReport)): ?>
                            <tr><td colspan="4" class="py-6 text-center text-muted-foreground">Tidak ada data contributor.</td></tr>
                        <?php else: ?>
                            <?php foreach ($contribReport as $row): ?>
                            <tr class="hover:bg-muted/40 transition-colors">
                                <td class="py-3 px-4 font-bold text-foreground">
                                    <?= esc($row['user']['name']) ?>
                                    <span class="block text-[10px] text-muted-foreground font-normal"><?= esc($row['user']['email']) ?></span>
                                </td>
                                <td class="py-3 px-4 text-center font-semibold text-success"><?= $row['published_count'] ?></td>
                                <td class="py-3 px-4 text-center font-semibold text-warning"><?= $row['pending_count'] ?></td>
                                <td class="py-3 px-4 text-right font-bold text-foreground"><?= $row['total_articles'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3b. Daily Releases Table Section -->
        <div class="surface-card overflow-hidden">
            <div class="px-5 py-4 border-b border-border flex items-center justify-between bg-muted/20">
                <div class="flex items-center gap-2.5">
                    <span class="grid size-7 place-items-center rounded-md bg-emerald-500/10 text-emerald-600">
                        <i data-lucide="bar-chart-2" class="size-4"></i>
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-foreground">4. Jumlah Artikel Dirilis Per Hari</h2>
                        <p class="text-[11px] text-muted-foreground">Distribusi output berita harian portal.</p>
                    </div>
                </div>
                <span class="text-xs font-mono text-muted-foreground">
                    <?= count($dailyReleases) ?> Hari
                </span>
            </div>

            <div class="overflow-x-auto max-h-[420px]">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-border bg-muted/40 text-muted-foreground uppercase font-bold sticky top-0 bg-background">
                        <tr>
                            <th class="py-3 px-4">Tanggal Rilis</th>
                            <th class="py-3 px-4">Hari</th>
                            <th class="py-3 px-4 text-center">Volume Artikel</th>
                            <th class="py-3 px-4 text-right">Total Views</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <?php if (empty($dailyReleases)): ?>
                            <tr><td colspan="4" class="py-6 text-center text-muted-foreground">Tidak ada rilis artikel dalam rentang ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($dailyReleases as $d): ?>
                            <tr class="hover:bg-muted/40 transition-colors">
                                <td class="py-2.5 px-4 font-bold text-foreground"><?= esc($d['date_formatted']) ?></td>
                                <td class="py-2.5 px-4 text-muted-foreground"><?= esc($d['day_name']) ?></td>
                                <td class="py-2.5 px-4 text-center">
                                    <?php if ($d['count'] > 0): ?>
                                        <span class="inline-flex items-center justify-center min-w-6 rounded bg-emerald-500/15 px-2 py-0.5 text-xs font-bold text-emerald-700">
                                            <?= $d['count'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted-foreground/60 font-mono">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2.5 px-4 text-right font-mono text-muted-foreground"><?= number_format($d['views']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

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

    const form = document.getElementById('reportFilterForm');
    if (form) {
        form.addEventListener('submit', (e) => {
            const from = document.getElementById('reportDateFrom').value;
            const to = document.getElementById('reportDateTo').value;
            if (from && to) {
                const diffTime = Math.abs(new Date(to) - new Date(from));
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                if (diffDays > 30) {
                    alert('Rentang tanggal maksimal adalah 30 hari. Sistem akan otomatis menyesuaikan rentang.');
                }
            }
        });
    }
});
</script>
<?= $this->endSection() ?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Page Header Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="truncate text-lg sm:text-2xl font-bold tracking-tight text-foreground leading-tight">Custom Data Charts</h1>
                <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-0.5 text-xs font-semibold text-emerald-600 border border-emerald-500/20">Custom Data</span>
            </div>
            <p class="mt-0.5 text-xs sm:text-sm text-muted-foreground truncate">Kelola grafik custom editorial, atur konfigurasi nilai sumbu X &amp; Y, dan gunakan di dalam artikel redaksi.</p>
        </div>
        <div class="flex items-center gap-2 self-stretch sm:self-auto flex-wrap">
            <a href="<?= base_url('embed-chart/template') ?>" class="inline-flex flex-1 sm:flex-initial items-center justify-center gap-1.5 rounded-md border border-input bg-background px-3.5 py-2 text-xs font-semibold text-foreground hover:bg-muted transition-colors shadow-xs">
                <i data-lucide="layout-grid" class="size-4 text-muted-foreground"></i>
                <span>Master Template</span>
            </a>
            <a href="<?= base_url('embed-chart/custom/create') ?>" class="inline-flex flex-1 sm:flex-initial items-center justify-center gap-1.5 rounded-md bg-[#2B6CB0] px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90 transition-colors">
                <i data-lucide="plus" class="size-4"></i>
                <span>Add Custom Chart</span>
            </a>
        </div>
    </div>
</div>

<!-- Search & Filter Controls -->
<div class="mb-5 flex flex-col sm:flex-row items-center justify-between gap-3">
    <div class="relative w-full sm:w-80">
        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground"></i>
        <input type="text" 
               id="searchCustomInput" 
               placeholder="Cari judul chart, tipe visual..." 
               class="flex h-9 w-full rounded-lg border border-input bg-card pl-9 pr-3 py-1.5 text-xs shadow-2xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
    </div>
    <div class="flex items-center gap-2 text-xs text-muted-foreground w-full sm:w-auto justify-between sm:justify-end">
        <span>Total: <strong class="text-foreground"><?= count($charts) ?></strong> chart custom</span>
    </div>
</div>

<!-- Custom Charts Table -->
<div class="surface-card overflow-hidden rounded-xl border border-border shadow-xs">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs" id="customChartsTable" style="table-layout: auto;">
            <thead class="border-b border-border bg-muted/40 font-semibold text-muted-foreground uppercase text-[10px] tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-center" style="width: 48px;">No</th>
                    <th class="px-4 py-3" style="min-width: 240px;">Nama Grafik / Chart</th>
                    <th class="px-4 py-3" style="width: 140px;">Tipe Visual</th>
                    <th class="px-4 py-3" style="min-width: 220px;">Konfigurasi Axis (X & Y)</th>
                    <th class="px-4 py-3" style="width: 120px;">Dibuat Oleh</th>
                    <th class="px-4 py-3" style="width: 130px;">Terakhir Update</th>
                    <th class="px-4 py-3 text-right" style="width: 90px;">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border" id="customChartsTableBody">
                <?php if (empty($charts)): ?>
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-muted-foreground">
                        <div class="flex flex-col items-center justify-center space-y-3">
                            <div class="rounded-full bg-muted/80 p-3">
                                <i data-lucide="bar-chart-2" class="size-6 text-muted-foreground"></i>
                            </div>
                            <p class="font-medium text-sm">Belum ada custom chart yang dibuat.</p>
                            <a href="<?= base_url('embed-chart/custom/create') ?>" class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3.5 py-1.5 text-xs font-semibold text-primary-foreground shadow">
                                <i data-lucide="plus" class="size-3.5"></i>
                                Buat Custom Chart Pertama
                            </a>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($charts as $c): ?>
                    <tr class="hover:bg-muted/30 transition-colors custom-chart-row" 
                        data-search="<?= strtolower(esc($c['title'] . ' ' . $c['type'] . ' ' . $c['symbol'])) ?>">
                        <td class="px-4 py-3.5 text-center font-mono text-muted-foreground"><?= $no++ ?></td>
                        
                        <!-- Nama Grafik: tidak bold, font-normal, text-xs sm:text-sm -->
                        <td class="px-4 py-3.5">
                            <a href="<?= base_url('embed-chart/custom/edit/' . $c['id']) ?>" 
                               class="font-normal text-xs sm:text-sm text-foreground hover:text-primary transition-colors leading-relaxed block">
                                <?= esc($c['title']) ?>
                            </a>
                        </td>
                        
                        <!-- Tipe Visual: badge rapi whitespace-nowrap -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <?php
                                $badgeStyles = [
                                    'bar-vertical'   => 'bg-blue-500/10 text-blue-600 border-blue-500/20 dark:text-blue-400',
                                    'bar-horizontal' => 'bg-teal-500/10 text-teal-600 border-teal-500/20 dark:text-teal-400',
                                    'line'           => 'bg-amber-500/10 text-amber-600 border-amber-500/20 dark:text-amber-400',
                                    'pie'            => 'bg-purple-500/10 text-purple-600 border-purple-500/20 dark:text-purple-400',
                                ];
                                $styleClass = $badgeStyles[$c['type']] ?? 'bg-muted text-muted-foreground';
                            ?>
                            <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[11px] font-medium capitalize <?= $styleClass ?>">
                                <?= esc($c['type']) ?>
                            </span>
                        </td>
                        
                        <!-- Konfigurasi Axis (X & Y): rapi tanpa tumpang tindih -->
                        <td class="px-4 py-3.5">
                            <div class="text-xs text-foreground flex items-center gap-1.5 flex-wrap">
                                <span class="font-bold text-[10px] text-muted-foreground uppercase">X:</span>
                                <span><?= esc($c['symbol']) ?></span>
                                <span class="rounded bg-muted/70 px-1.5 py-0.2 text-[10px] text-muted-foreground border border-border whitespace-nowrap">
                                    <?= count($c['labels'] ?? []) ?> data
                                </span>
                            </div>
                            <div class="text-[11px] text-muted-foreground flex items-center gap-1.5 mt-1">
                                <span class="font-bold text-[10px] text-muted-foreground uppercase">Y:</span>
                                <span><?= esc($c['yAxisTitle'] ?? 'Nilai') ?></span>
                            </div>
                        </td>

                        <!-- Dibuat Oleh -->
                        <td class="px-4 py-3.5 text-muted-foreground whitespace-nowrap">
                            <?= esc($c['author'] ?: 'Redaksi') ?>
                        </td>

                        <!-- Terakhir Update -->
                        <td class="px-4 py-3.5 text-[11px] text-muted-foreground whitespace-nowrap font-mono">
                            <?= esc(date('d M Y', strtotime($c['updated']))) ?><br>
                            <span class="text-[10px] text-muted-foreground/70"><?= esc(date('H:i', strtotime($c['updated']))) ?> WIB</span>
                        </td>

                        <!-- Aksi: Edit & Delete only -->
                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5 justify-end">
                                <a href="<?= base_url('embed-chart/custom/edit/' . $c['id']) ?>" 
                                   class="inline-flex items-center gap-1 rounded-md border border-input bg-background px-2.5 py-1 text-xs font-medium text-foreground hover:text-primary hover:bg-muted shadow-2xs transition-colors"
                                   title="Edit Custom Chart">
                                    <i data-lucide="edit-3" class="size-3.5 text-primary"></i>
                                    <span>Edit</span>
                                </a>
                                <a href="<?= base_url('embed-chart/delete/' . $c['id']) ?>" 
                                   class="inline-flex size-7 items-center justify-center rounded-md border border-input bg-background text-destructive hover:bg-destructive/10 shadow-2xs transition-colors"
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus custom chart ini?')"
                                   title="Hapus Chart">
                                    <i data-lucide="trash-2" class="size-3.5"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->section('scripts') ?>
<script>
document.getElementById('searchCustomInput')?.addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase().trim();
    const rows = document.querySelectorAll('.custom-chart-row');
    rows.forEach(row => {
        const text = row.getAttribute('data-search') || '';
        if (text.includes(term)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>

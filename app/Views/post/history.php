<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Riwayat Revisi Naskah</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Log audit trail perubahan naskah berita, pergantian judul, dan status editorial.</p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <a href="<?= base_url('post') ?>" class="inline-flex items-center justify-center gap-1.5 rounded-md border border-input bg-background px-3 py-2 text-xs sm:text-sm font-medium text-foreground shadow-sm hover:bg-muted transition-colors">
                <i data-lucide="arrow-left" class="size-4"></i>
                <span>Kembali ke Artikel</span>
            </a>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="surface-card p-4 mb-6 rounded-xl border border-border">
    <form method="get" action="<?= base_url('post/history') ?>" id="historyFilterForm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <!-- Search Keyword -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-foreground uppercase tracking-wider mb-1">Cari Judul / Editor</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground"></i>
                    <input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>" class="flex h-9 w-full rounded-md border border-input bg-background pl-9 pr-3 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Ketik kata kunci judul artikel...">
                </div>
            </div>

            <!-- Editor Select -->
            <div>
                <label class="block text-[11px] font-bold text-foreground uppercase tracking-wider mb-1">Editor</label>
                <select name="editor_id" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="all">Semua Editor</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ((string)($filters['editor_id'] ?? '') === (string)$u['id']) ? 'selected' : '' ?>>
                            <?= esc($u['name'] ?: $u['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-[11px] font-bold text-foreground uppercase tracking-wider mb-1">Dari Tanggal</label>
                <input type="text" name="date_from" id="filterHistoryDateFrom" value="<?= esc($filters['date_from'] ?? '') ?>" placeholder="YYYY-MM-DD" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>

            <!-- Date To & Submit -->
            <div class="flex items-center gap-2">
                <div class="flex-1">
                    <label class="block text-[11px] font-bold text-foreground uppercase tracking-wider mb-1">Sampai</label>
                    <input type="text" name="date_to" id="filterHistoryDateTo" value="<?= esc($filters['date_to'] ?? '') ?>" placeholder="YYYY-MM-DD" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                </div>
                <div class="flex items-end gap-1.5 pt-5">
                    <button type="submit" class="inline-flex h-9 items-center justify-center gap-1 rounded-md bg-[#2B6CB0] px-3.5 text-xs font-bold text-white shadow-xs hover:bg-[#2B6CB0]/90 transition-colors">
                        <i data-lucide="filter" class="size-3.5"></i>
                        <span>Filter</span>
                    </button>
                    <?php if (!empty($filters['q']) || ($filters['editor_id'] ?? 'all') !== 'all' || !empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['article_id'])): ?>
                        <a href="<?= base_url('post/history') ?>" class="inline-flex h-9 size-9 items-center justify-center rounded-md border border-destructive/30 bg-destructive/10 text-destructive hover:bg-destructive/20 transition-colors" title="Reset Filter">
                            <i data-lucide="rotate-ccw" class="size-3.5"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Header Top Counter -->
<div class="flex flex-wrap items-center justify-between gap-1 mb-3 px-1 text-xs text-muted-foreground">
    <div>
        Menampilkan <strong class="text-foreground"><?= $pagination['from'] ?>–<?= $pagination['to'] ?></strong> dari <strong class="text-foreground"><?= $pagination['totalItems'] ?></strong> log revisi
    </div>
</div>

<!-- Table Card -->
<div class="surface-card rounded-xl border border-border shadow-sm overflow-hidden">
    <!-- Desktop Table View -->
    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
            <thead class="bg-muted/60 text-muted-foreground uppercase text-[10px] font-bold tracking-wider border-b border-border">
                <tr>
                    <th class="py-3 px-3 w-[14%]">Waktu Revisi</th>
                    <th class="py-3 px-3 w-[30%]">Artikel / Naskah</th>
                    <th class="py-3 px-3 w-[15%]">Editor</th>
                    <th class="py-3 px-3 w-[18%]">Perubahan Judul</th>
                    <th class="py-3 px-3 w-[13%]">Perubahan Status</th>
                    <th class="py-3 px-3 w-[10%] text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php if (empty($histories)): ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-muted-foreground">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <i data-lucide="history" class="size-8 text-muted-foreground/60"></i>
                                <p class="text-sm font-medium text-foreground">Belum ada riwayat revisi</p>
                                <p class="text-xs text-muted-foreground">Log revisi tercatat otomatis saat editor memperbarui artikel.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($histories as $h): ?>
                    <tr class="transition-colors hover:bg-muted/40">
                        <!-- Waktu -->
                        <td class="py-3 px-3 whitespace-nowrap text-muted-foreground font-mono text-[11px]">
                            <div class="font-medium text-foreground"><?= date('d M Y', strtotime($h['timestamp'])) ?></div>
                            <div class="text-[10px] text-muted-foreground"><?= date('H:i:s', strtotime($h['timestamp'])) ?> WIB</div>
                        </td>

                        <!-- Artikel -->
                        <td class="py-3 px-3">
                            <div class="font-medium text-foreground line-clamp-1">
                                <?= esc($h['title_after'] ?: ($h['current_title'] ?: 'Artikel #' . $h['articleID'])) ?>
                            </div>
                            <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-muted-foreground">
                                <span class="font-mono">ID: #<?= (int)$h['articleID'] ?></span>
                                <?php if (!empty($h['articleID'])): ?>
                                    &bull;
                                    <a href="<?= base_url('post/edit/' . $h['articleID']) ?>" class="text-primary hover:underline" target="_blank">Edit Naskah</a>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Editor -->
                        <td class="py-3 px-3">
                            <div class="font-semibold text-foreground"><?= esc($h['editor_name'] ?: ($h['editor_username'] ?: 'Editor #' . $h['editorID'])) ?></div>
                            <div class="text-[10px] text-muted-foreground">@<?= esc($h['editor_username'] ?: 'editor') ?></div>
                        </td>

                        <!-- Judul Before vs After -->
                        <td class="py-3 px-3">
                            <?php if ($h['title_prev'] !== $h['title_after']): ?>
                                <div class="text-[11px] text-rose-600 line-through truncate max-w-[220px]" title="Sebelum: <?= esc($h['title_prev']) ?>">
                                    <?= esc($h['title_prev']) ?>
                                </div>
                                <div class="text-[11px] text-emerald-600 font-medium truncate max-w-[220px]" title="Sesudah: <?= esc($h['title_after']) ?>">
                                    <?= esc($h['title_after']) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted-foreground italic text-[11px]">Judul tidak berubah</span>
                            <?php endif; ?>
                        </td>

                        <!-- Status Before vs After -->
                        <td class="py-3 px-3">
                            <div class="flex items-center gap-1 text-[11px]">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-muted border border-border">
                                    <?= esc($h['status_prev'] ?: 'Draft') ?>
                                </span>
                                <i data-lucide="arrow-right" class="size-3 text-muted-foreground"></i>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-primary/10 text-primary border border-primary/20">
                                    <?= esc($h['status_after'] ?: 'Draft') ?>
                                </span>
                            </div>
                        </td>

                        <!-- Aksi -->
                        <td class="py-3 px-3 text-right">
                            <a href="<?= base_url('post/history/' . $h['id']) ?>" class="inline-flex items-center gap-1 rounded-md border border-input bg-background px-2.5 py-1 text-xs font-semibold text-primary hover:bg-primary hover:text-white transition-colors shadow-2xs">
                                <i data-lucide="git-compare" class="size-3"></i>
                                <span>Diff</span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <?php
        $queryParams = $_GET;
        $buildPageUrl = function($pageNum) use ($queryParams) {
            $params = $queryParams;
            $params['page'] = $pageNum;
            return base_url('post/history') . '?' . http_build_query($params);
        };
    ?>
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 border-t border-border text-xs text-muted-foreground bg-muted/20">
        <div>
            Halaman <strong class="text-foreground"><?= $pagination['page'] ?></strong> dari <strong class="text-foreground"><?= $pagination['totalPages'] ?></strong>
        </div>
        <div class="flex items-center gap-1">
            <?php if ($pagination['page'] > 1): ?>
                <a href="<?= $buildPageUrl($pagination['page'] - 1) ?>" class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-foreground hover:bg-muted transition-colors">Prev</a>
            <?php else: ?>
                <span class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-muted-foreground opacity-50 cursor-not-allowed">Prev</span>
            <?php endif; ?>

            <?php for ($p = max(1, $pagination['page'] - 2); $p <= min($pagination['totalPages'], $pagination['page'] + 2); $p++): ?>
                <?php if ($p == $pagination['page']): ?>
                    <span class="rounded-md bg-primary px-2.5 py-1 font-bold text-primary-foreground"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= $buildPageUrl($p) ?>" class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-foreground hover:bg-muted transition-colors"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                <a href="<?= $buildPageUrl($pagination['page'] + 1) ?>" class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-foreground hover:bg-muted transition-colors">Next</a>
            <?php else: ?>
                <span class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-muted-foreground opacity-50 cursor-not-allowed">Next</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
flatpickr('#filterHistoryDateFrom', { dateFormat: 'Y-m-d' });
flatpickr('#filterHistoryDateTo', { dateFormat: 'Y-m-d' });
</script>
<?= $this->endSection() ?>

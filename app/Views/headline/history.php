<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Riwayat Log Headline</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Audit trail kronologis aktivitas headline: penambahan, perubahan urutan (reorder), dan penghapusan artikel.</p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <a href="<?= base_url('headline') ?>" class="inline-flex items-center justify-center gap-1.5 rounded-md border border-input bg-background px-3 py-2 text-xs sm:text-sm font-medium text-foreground shadow-sm hover:bg-muted transition-colors">
                <i data-lucide="arrow-left" class="size-4"></i>
                <span>Kembali ke Atur Headline</span>
            </a>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="surface-card p-4 mb-6 rounded-xl border border-border">
    <form method="get" action="<?= base_url('headline/history') ?>" id="headlineHistoryFilterForm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
            <!-- Search Keyword -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-foreground uppercase tracking-wider mb-1">Cari Judul / User</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground"></i>
                    <input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>" class="flex h-9 w-full rounded-md border border-input bg-background pl-9 pr-3 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Ketik kata kunci judul artikel / username...">
                </div>
            </div>

            <!-- Kategori / Category Select -->
            <div>
                <label class="block text-[11px] font-bold text-foreground uppercase tracking-wider mb-1">Kategori</label>
                <select name="category" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="all" <?= ($filters['category'] ?? 'all') === 'all' ? 'selected' : '' ?>>Semua Kategori</option>
                    <option value="headline" <?= ($filters['category'] ?? '') === 'headline' ? 'selected' : '' ?>>Headline (Home)</option>
                    <option value="topical" <?= ($filters['category'] ?? '') === 'topical' ? 'selected' : '' ?>>Topical</option>
                    <option value="trending" <?= ($filters['category'] ?? '') === 'trending' ? 'selected' : '' ?>>Trending</option>
                    <option value="newsletter" <?= ($filters['category'] ?? '') === 'newsletter' ? 'selected' : '' ?>>Newsletter</option>
                </select>
            </div>

            <!-- Action Select -->
            <div>
                <label class="block text-[11px] font-bold text-foreground uppercase tracking-wider mb-1">Aksi</label>
                <select name="action" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="all" <?= ($filters['action'] ?? 'all') === 'all' ? 'selected' : '' ?>>Semua Aksi</option>
                    <option value="ADD" <?= strtoupper($filters['action'] ?? '') === 'ADD' ? 'selected' : '' ?>>Tambah (ADD)</option>
                    <option value="REMOVE" <?= strtoupper($filters['action'] ?? '') === 'REMOVE' ? 'selected' : '' ?>>Hapus (REMOVE)</option>
                    <option value="REORDER" <?= strtoupper($filters['action'] ?? '') === 'REORDER' ? 'selected' : '' ?>>Urutan (REORDER)</option>
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
                    <?php if (!empty($filters['q']) || ($filters['category'] ?? 'all') !== 'all' || ($filters['action'] ?? 'all') !== 'all' || !empty($filters['date_from']) || !empty($filters['date_to'])): ?>
                        <a href="<?= base_url('headline/history') ?>" class="inline-flex h-9 size-9 items-center justify-center rounded-md border border-destructive/30 bg-destructive/10 text-destructive hover:bg-destructive/20 transition-colors" title="Reset Filter">
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
        Menampilkan <strong class="text-foreground"><?= $pagination['from'] ?>–<?= $pagination['to'] ?></strong> dari <strong class="text-foreground"><?= $pagination['totalItems'] ?></strong> riwayat log
    </div>
</div>

<!-- Table Card -->
<div class="surface-card rounded-xl border border-border shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
            <thead class="bg-muted/60 text-muted-foreground uppercase text-[10px] font-bold tracking-wider border-b border-border">
                <tr>
                    <th class="py-3 px-3 w-[15%]">Waktu Aktivitas</th>
                    <th class="py-3 px-3 w-[10%]">Aksi</th>
                    <th class="py-3 px-3 w-[12%]">Kategori</th>
                    <th class="py-3 px-3 w-[33%]">Artikel Terkait</th>
                    <th class="py-3 px-3 w-[8%] text-center">Posisi</th>
                    <th class="py-3 px-3 w-[12%]">Pelaksana</th>
                    <th class="py-3 px-3 w-[10%] text-right">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center text-muted-foreground">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <i data-lucide="inbox" class="size-8 text-muted-foreground/40"></i>
                                <p class="text-sm font-medium">Belum ada riwayat audit log headline.</p>
                                <p class="text-[11px] text-muted-foreground">Setiap aksi penambahan, perubahan urutan, atau penghapusan headline akan otomatis tercatat di sini.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-muted/30 transition-colors">
                            <!-- Waktu Log -->
                            <td class="py-3 px-3 text-muted-foreground whitespace-nowrap">
                                <div class="font-medium text-foreground"><?= date('d M Y', strtotime($log['timestamp'])) ?></div>
                                <div class="text-[10px] text-muted-foreground/80"><?= date('H:i:s', strtotime($log['timestamp'])) ?> WIB</div>
                            </td>

                            <!-- Aksi Badge -->
                            <td class="py-3 px-3">
                                <?php 
                                    $action = strtoupper($log['action'] ?? '');
                                    if ($action === 'ADD'): 
                                ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                        <i data-lucide="plus-circle" class="size-3"></i> ADD
                                    </span>
                                <?php elseif ($action === 'REMOVE'): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                        <i data-lucide="trash-2" class="size-3"></i> REMOVE
                                    </span>
                                <?php elseif ($action === 'REORDER'): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">
                                        <i data-lucide="move" class="size-3"></i> REORDER
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-muted text-muted-foreground border border-border">
                                        <?= esc($action) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Kanal / Kategori -->
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-primary/10 text-primary border border-primary/20 capitalize">
                                    <?= esc($log['category']) ?>
                                </span>
                            </td>

                            <!-- Artikel Terkait -->
                            <td class="py-3 px-3">
                                <div class="flex flex-col gap-0.5">
                                    <?php if (!empty($log['article_title'])): ?>
                                        <a href="<?= base_url('post/edit/' . $log['articleID']) ?>" target="_blank" class="font-medium text-foreground hover:text-primary transition-colors line-clamp-1 group" title="<?= esc($log['article_title']) ?>">
                                            <span><?= esc($log['article_title']) ?></span>
                                            <i data-lucide="external-link" class="inline size-3 opacity-0 group-hover:opacity-100 transition-opacity ml-1"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted-foreground italic text-xs">Artikel #<?= esc($log['articleID']) ?> (Dihapus / Tidak Ditemukan)</span>
                                    <?php endif; ?>
                                    <div class="flex items-center gap-2 text-[10px] text-muted-foreground">
                                        <span>ID: <strong class="text-foreground">#<?= esc($log['articleID']) ?></strong></span>
                                        <?php if (!empty($log['notes'])): ?>
                                            <span>•</span>
                                            <span class="truncate max-w-[200px]" title="<?= esc($log['notes']) ?>"><?= esc($log['notes']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <!-- Posisi -->
                            <td class="py-3 px-3 text-center">
                                <?php if (!empty($log['position'])): ?>
                                    <span class="inline-flex items-center justify-center font-bold px-2 py-0.5 rounded bg-muted text-foreground border border-border text-[11px]">
                                        #<?= esc($log['position']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted-foreground text-[11px]">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- Pelaksana (User) -->
                            <td class="py-3 px-3">
                                <div class="flex items-center gap-1.5">
                                    <div class="size-5 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-[9px] shrink-0">
                                        <?= strtoupper(substr($log['actor_name'] ?: ($log['user'] ?: 'U'), 0, 1)) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-medium text-foreground truncate text-[11px]">
                                            <?= esc($log['actor_name'] ?: $log['user']) ?>
                                        </div>
                                        <div class="text-[9px] text-muted-foreground font-mono truncate">
                                            @<?= esc($log['user']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Detail / Tautan -->
                            <td class="py-3 px-3 text-right">
                                <a href="<?= base_url('headline?target_category=' . urlencode(strtolower($log['category']))) ?>" class="inline-flex items-center gap-1 rounded-md border border-input bg-background px-2.5 py-1 text-xs font-semibold text-foreground hover:bg-muted hover:text-primary transition-colors shadow-2xs" title="Lihat susunan headline saat ini">
                                    <i data-lucide="layout-list" class="size-3"></i>
                                    <span>Lihat</span>
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
            return base_url('headline/history') . '?' . http_build_query($params);
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

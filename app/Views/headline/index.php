<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/css/pages/headline.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- PageHeader Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Headline &amp; Cover Curation</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Kelola urutan dan penetapan artikel yang tampil sebagai headline utama di portal Investor.id.</p>
        </div>
    </div>
</div>

<!-- Filtering Bar -->
<div class="surface-card p-4 mb-6">
    <form method="get" action="<?= base_url('headline') ?>" class="flex flex-wrap items-center gap-3">
        <span class="text-xs font-bold text-foreground">Filtering:</span>
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="q" value="<?= esc($filters['q']) ?>" placeholder="Cari artikel..." class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs sm:text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
        </div>
        <div class="w-44">
            <select name="category" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs sm:text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                <option value="all">Category: All</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= esc($cat['name']) ?>" <?= ($filters['category'] === $cat['name']) ? 'selected' : '' ?>>
                        <?= esc($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-[#2B6CB0] px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow-sm hover:bg-[#2B6CB0]/90">
            SEARCH POST
        </button>
        <?php if (!empty($filters['q']) || $filters['category'] !== 'all'): ?>
            <a href="<?= base_url('headline') ?>" class="inline-flex items-center justify-center rounded-md border border-input bg-background px-3 py-2 text-xs font-medium text-muted-foreground hover:text-foreground">
                Reset
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- MOBILE ONLY TOP SECTION: Curated Headline (Hanya Muncul di Mobile) -->
<div class="block sm:hidden surface-card p-4 mb-6 border border-primary/30 shadow-sm bg-card">
    <form method="post" action="<?= base_url('headline/save') ?>" class="space-y-3.5">
        <div class="flex items-center justify-between border-b border-border pb-2.5">
            <div class="flex items-center gap-2">
                <i data-lucide="sparkles" class="size-4 text-primary"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-foreground">Headline Terpilih (Mobile)</h2>
            </div>
            <button type="submit" class="inline-flex items-center gap-1 rounded bg-[#2B6CB0] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#2B6CB0]/90 shadow-2xs">
                <i data-lucide="save" class="size-3.5"></i>
                <span>Simpan</span>
            </button>
        </div>

        <select name="target_category" onchange="onTargetCategoryChange(this.value)" class="flex h-8.5 w-full rounded-md border border-input bg-background px-2.5 py-1 text-xs font-semibold shadow-sm focus-visible:outline-none">
            <option value="Headline" <?= ($targetCategory === 'Headline') ? 'selected' : '' ?>>Kanal: Headline (Cover)</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= esc($cat['name']) ?>" <?= ($targetCategory === $cat['name']) ? 'selected' : '' ?>>
                    Kanal: <?= esc($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="space-y-2">
            <?php if (empty($headlines)): ?>
                <div class="py-4 text-center text-xs text-muted-foreground">Belum ada headline terpilih.</div>
            <?php else: ?>
                <?php $pos = 1; foreach ($headlines as $hl): ?>
                <div class="flex items-start gap-2.5 p-2.5 rounded-md border border-border bg-muted/40">
                    <span class="grid size-6 shrink-0 place-items-center rounded bg-primary text-primary-foreground font-bold text-[11px]">#<?= $pos++ ?></span>
                    <span class="text-xs font-medium text-foreground flex-1 line-clamp-2"><?= esc($hl['title']) ?></span>
                    <button type="button" onclick="confirmRemoveHeadline(<?= $hl['id'] ?>, '<?= esc(addslashes($hl['title'])) ?>')" class="text-destructive hover:opacity-75 p-0.5">
                        <i data-lucide="x" class="size-3.5"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- 2-Panel Layout Matching Original Desktop UI (Left: Published List, Right: Headline Queue) -->
<div class="headline-grid-layout">
    <!-- Left Column: Published Articles List -->
    <div class="surface-card overflow-hidden">
        <!-- Mobile Cards View for Articles -->
        <div class="divide-y divide-border block sm:hidden">
            <div class="p-3.5 bg-muted/30 border-b border-border font-bold text-xs uppercase tracking-wider text-foreground">
                Semua Artikel Published
            </div>
            <?php if (empty($availablePosts)): ?>
                <div class="p-8 text-center text-muted-foreground text-xs">No published articles available.</div>
            <?php else: ?>
                <?php foreach ($availablePosts as $post): ?>
                <div class="p-3.5 flex items-start justify-between gap-3">
                    <div class="space-y-1 min-w-0 flex-1">
                        <span class="block font-medium text-foreground text-xs leading-snug">
                            <?php if ($post['is_premium']): ?>
                                <span class="inline-flex items-center rounded-sm bg-amber-500/15 px-1 py-0.2 text-[9px] font-bold text-amber-700 uppercase mr-1">[Premium]</span>
                            <?php endif; ?>
                            <?= esc($post['title']) ?>
                        </span>
                        <div class="flex items-center gap-2 text-[11px] text-muted-foreground">
                            <span><?= esc($post['category']) ?></span>
                            <span>&bull;</span>
                            <span><?= esc($post['publish_date']) ?></span>
                        </div>
                    </div>
                    <button type="button" 
                            onclick="confirmAddHeadline(<?= $post['id'] ?>, '<?= esc(addslashes($post['title'])) ?>')"
                            class="inline-flex shrink-0 size-8 items-center justify-center rounded-md bg-[#2B6CB0] text-white hover:bg-[#2B6CB0]/90 transition-colors shadow-xs" 
                            title="Pilih / Jadikan Headline">
                        <i data-lucide="corner-down-right" class="size-3.5"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Desktop Table View -->
        <div class="relative w-full overflow-auto hidden sm:block">
            <table class="w-full caption-bottom text-sm">
                <thead class="[&_tr]:border-b border-border bg-muted/40">
                    <tr class="text-left font-bold text-foreground text-xs">
                        <th class="h-10 px-4 font-bold text-foreground">Title</th>
                        <th class="h-10 px-4 font-bold text-foreground w-40">Article Date</th>
                        <th class="h-10 px-4 font-bold text-foreground text-center w-20">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php if (empty($availablePosts)): ?>
                        <tr>
                            <td colspan="3" class="p-8 text-center text-muted-foreground text-xs">No published articles available.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($availablePosts as $post): ?>
                        <tr class="transition-colors hover:bg-muted/50">
                            <td class="p-4">
                                <div>
                                    <div class="flex items-center gap-1.5 flex-wrap mb-0.5">
                                        <?php if ($post['is_premium']): ?>
                                            <span class="inline-flex items-center rounded-sm bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 uppercase">[Premium]</span>
                                        <?php endif; ?>
                                        <span class="inline-flex items-center rounded-sm bg-success/15 px-1.5 py-0.5 text-[10px] font-bold text-success uppercase">
                                            <?= esc($post['status'] ?? 'Published') ?>
                                        </span>
                                    </div>
                                    <span class="block font-medium text-foreground text-xs leading-relaxed">
                                        <?= esc($post['title']) ?>
                                    </span>
                                    <span class="text-[11px] text-muted-foreground"><?= esc($post['category']) ?> &bull; By <?= esc($post['author']) ?></span>
                                </div>
                            </td>
                            <td class="p-4 text-xs text-muted-foreground whitespace-nowrap">
                                <?= esc($post['publish_date']) ?>
                            </td>
                            <td class="p-4 text-center">
                                <button type="button" 
                                        onclick="confirmAddHeadline(<?= $post['id'] ?>, '<?= esc(addslashes($post['title'])) ?>')"
                                        class="inline-flex size-7 items-center justify-center rounded-md bg-[#2B6CB0] text-white hover:bg-[#2B6CB0]/90 transition-colors shadow-xs" 
                                        title="Pilih / Jadikan Headline">
                                    <i data-lucide="corner-down-right" class="size-3.5"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Left Column Pagination -->
        <?php if (!empty($pagination)): ?>
            <?= view('layouts/partials/pagination', ['pagination' => $pagination, 'baseUrl' => base_url('headline'), 'extraParams' => ['target_category' => $targetCategory]]) ?>
        <?php endif; ?>
    </div>

    <!-- Right Column: Dropdown Category & Target Headline Queue (Desktop View) -->
    <div class="space-y-4 hidden sm:block headline-sticky-sidebar">
        <form method="post" action="<?= base_url('headline/save') ?>" class="surface-card p-5 space-y-4">
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="targetCategorySelect" class="block text-xs font-bold text-foreground">Select Category / Kanal</label>
                    <span class="text-[11px] font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded">
                        <?= count($headlines) ?> Artikel
                    </span>
                </div>
                <select name="target_category" id="targetCategorySelect" onchange="onTargetCategoryChange(this.value)" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs sm:text-sm font-semibold shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="Headline" <?= ($targetCategory === 'Headline') ? 'selected' : '' ?>>★ Headline Utama (Cover / Carousel)</option>
                    <optgroup label="── Kanal Kategori ──">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= esc($cat['name']) ?>" <?= ($targetCategory === $cat['name']) ? 'selected' : '' ?>>
                                Kanal: <?= esc($cat['name']) ?>
                            </option>
                            <?php if (!empty($cat['sub_kanals'])): ?>
                                <?php foreach ($cat['sub_kanals'] as $sub): ?>
                                    <option value="<?= esc($sub['name']) ?>" <?= ($targetCategory === $sub['name']) ? 'selected' : '' ?>>
                                        &nbsp;&nbsp;↳ <?= esc($sub['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>

            <div class="surface-card overflow-hidden border border-border">
                <table class="w-full text-xs">
                    <thead class="bg-muted/50 border-b border-border">
                        <tr class="text-left font-bold text-foreground">
                            <th class="h-8 px-3 w-14">Pos</th>
                            <th class="h-8 px-3">Title &amp; Meta</th>
                            <th class="h-8 px-2 text-center w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <?php if (empty($headlines)): ?>
                            <tr>
                                <td colspan="3" class="p-8 text-center text-muted-foreground text-xs">
                                    <div class="flex flex-col items-center justify-center gap-1.5 py-4">
                                        <i data-lucide="inbox" class="size-6 text-muted-foreground/40"></i>
                                        <span class="font-bold text-foreground">Belum ada artikel headline untuk "<?= esc($targetCategory) ?>"</span>
                                        <span class="text-[11px] text-muted-foreground">Pilih artikel dari daftar sebelah kiri dan klik tombol panah untuk menambahkan.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $pos = 1; foreach ($headlines as $hl): ?>
                            <tr class="hover:bg-muted/40 transition-colors">
                                <td class="px-3 py-2.5 font-mono font-bold text-primary text-center">#<?= $pos++ ?></td>
                                <td class="px-3 py-2.5">
                                    <span class="block font-medium text-foreground line-clamp-2 leading-snug" title="<?= esc($hl['title']) ?>">
                                        <?= esc($hl['title']) ?>
                                    </span>
                                    <div class="flex items-center gap-2 text-[10px] text-muted-foreground mt-1">
                                        <span class="font-bold text-primary bg-primary/10 px-1.5 py-0.2 rounded"><?= esc($hl['category'] ?? $targetCategory) ?></span>
                                        <span>&bull;</span>
                                        <span><?= esc($hl['publish_date'] ?? date('d M Y')) ?></span>
                                    </div>
                                </td>
                                <td class="px-2 py-2.5 text-center">
                                    <button type="button" 
                                            onclick="confirmRemoveHeadline(<?= $hl['id'] ?>, '<?= esc(addslashes($hl['title'])) ?>', '<?= esc(addslashes($targetCategory)) ?>')"
                                            class="text-destructive hover:opacity-75 transition-opacity p-1 rounded hover:bg-destructive/10" 
                                            title="Hapus dari headline <?= esc($targetCategory) ?>">
                                        <i data-lucide="x" class="size-3.5"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-[#2B6CB0] px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90 transition-colors">
                <i data-lucide="save" class="size-4"></i>
                <span>SIMPAN URUTAN HEADLINE</span>
            </button>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Tambah ke Headline -->
<div id="confirmAddModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="surface-card w-full max-w-md p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center gap-3 text-[#2B6CB0]">
            <div class="grid size-10 place-items-center rounded-full bg-[#2B6CB0]/10 shrink-0">
                <i data-lucide="newspaper" class="size-5"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-foreground">Jadikan Headline <span class="text-primary font-black"><?= esc($targetCategory) ?></span>?</h3>
                <p class="text-xs text-muted-foreground">Artikel ini akan dimasukkan ke urutan headline kanal <?= esc($targetCategory) ?>.</p>
            </div>
        </div>
        <div class="p-3 rounded-lg bg-muted/60 border border-border">
            <p id="addArticleTitle" class="text-xs font-medium text-foreground line-clamp-2"></p>
        </div>
        <form method="post" action="<?= base_url('headline/add') ?>" id="confirmAddForm">
            <input type="hidden" name="article_id" id="addArticleIdInput" value="">
            <input type="hidden" name="target_category" value="<?= esc($targetCategory) ?>">
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeConfirmModals()" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted transition-colors">
                    Batal
                </button>
                <button type="submit" class="rounded-md bg-[#2B6CB0] px-4 py-2 text-xs font-bold uppercase text-white hover:bg-[#2B6CB0]/90 transition-colors shadow-xs">
                    Ya, Jadikan Headline
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Disable / Hapus dari Headline -->
<div id="confirmRemoveModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="surface-card w-full max-w-md p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center gap-3 text-destructive">
            <div class="grid size-10 place-items-center rounded-full bg-destructive/10 shrink-0">
                <i data-lucide="alert-triangle" class="size-5"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-foreground">Hapus dari Headline?</h3>
                <p class="text-xs text-muted-foreground">Artikel ini akan dinonaktifkan dari tampilan headline portal.</p>
            </div>
        </div>
        <div class="p-3 rounded-lg bg-muted/60 border border-border">
            <p id="removeArticleTitle" class="text-xs font-medium text-foreground line-clamp-2"></p>
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" onclick="closeConfirmModals()" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted transition-colors">
                Batal
            </button>
            <a href="#" id="confirmRemoveLink" class="inline-flex items-center justify-center rounded-md bg-destructive px-4 py-2 text-xs font-bold uppercase text-destructive-foreground hover:bg-destructive/90 transition-colors shadow-xs">
                Ya, Hapus Headline
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/pages/headline.js"></script>
<?= $this->endSection() ?>

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

<!-- Flash Alerts Banner -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="mb-5 flex items-center justify-between rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs sm:text-sm font-medium text-emerald-700 dark:text-emerald-400">
        <div class="flex items-center gap-2.5">
            <i data-lucide="check-circle" class="size-4 shrink-0"></i>
            <span><?= esc(session()->getFlashdata('success')) ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700/60 hover:text-emerald-700 dark:text-emerald-400/60 dark:hover:text-emerald-400">
            <i data-lucide="x" class="size-4"></i>
        </button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="mb-5 flex items-center justify-between rounded-lg border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-xs sm:text-sm font-medium text-rose-700 dark:text-rose-400">
        <div class="flex items-center gap-2.5">
            <i data-lucide="alert-circle" class="size-4 shrink-0"></i>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-700/60 hover:text-rose-700 dark:text-rose-400/60 dark:hover:text-rose-400">
            <i data-lucide="x" class="size-4"></i>
        </button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('info')): ?>
    <div class="mb-5 flex items-center justify-between rounded-lg border border-sky-500/20 bg-sky-500/10 px-4 py-3 text-xs sm:text-sm font-medium text-sky-700 dark:text-sky-400">
        <div class="flex items-center gap-2.5">
            <i data-lucide="info" class="size-4 shrink-0"></i>
            <span><?= esc(session()->getFlashdata('info')) ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-sky-700/60 hover:text-sky-700 dark:text-sky-400/60 dark:hover:text-sky-400">
            <i data-lucide="x" class="size-4"></i>
        </button>
    </div>
<?php endif; ?>

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
            <option value="home" <?= ($targetCategory === 'home') ? 'selected' : '' ?>>Headline Utama (Home)</option>
            <option value="topical" <?= ($targetCategory === 'topical') ? 'selected' : '' ?>>Topical</option>
            <option value="trending" <?= ($targetCategory === 'trending') ? 'selected' : '' ?>>Trending</option>
            <option value="newsletter" <?= ($targetCategory === 'newsletter') ? 'selected' : '' ?>>Newsletter</option>
        </select>

        <!-- Category explanation text without icons -->
        <div class="text-[11px] text-muted-foreground bg-muted/40 p-2 rounded border border-border/60">
            <?php if ($targetCategory === 'headline' || $targetCategory === 'home'): ?>
                Headline Utama (Maksimal 5 artikel untuk carousel & hero banner depan)
            <?php elseif ($targetCategory === 'topical'): ?>
                Isu & Tema Khusus (Maksimal 5 artikel tematik pilihan redaksi)
            <?php elseif ($targetCategory === 'trending'): ?>
                Berita Trending Terpopuler (Maksimal 5 artikel berita terpopuler pilihan redaksi)
            <?php elseif ($targetCategory === 'newsletter'): ?>
                Newsletter Morning Briefing (Maksimal 5 artikel: 1 utama + 4 artikel list)
            <?php endif; ?>
        </div>

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
                <?php 
                    $isFuture = !empty($post['publish_date']) && strtotime($post['publish_date']) > time(); 
                    $isAlreadyHeadline = isset($activeHeadlineMap[(int)$post['id']]);
                    $currentHeadlinePos = $isAlreadyHeadline ? $activeHeadlineMap[(int)$post['id']] : null;
                ?>
                <div class="p-3.5 flex items-center justify-between gap-3 hover:bg-muted/40 transition-colors">
                    <div class="space-y-1 min-w-0 flex-1">
                        <span class="block font-medium text-foreground text-xs leading-snug">
                            <?php if ($post['is_premium']): ?>
                                <span class="inline-flex items-center rounded-sm bg-amber-500/15 px-1 py-0.2 text-[9px] font-bold text-amber-700 uppercase mr-1">[Premium]</span>
                            <?php endif; ?>
                            <?php if ($isAlreadyHeadline): ?>
                                <span class="inline-flex items-center rounded-sm bg-primary/15 px-1.5 py-0.2 text-[9px] font-bold text-primary uppercase mr-1">Aktif di Headline #<?= $currentHeadlinePos ?></span>
                            <?php endif; ?>
                            <?= esc($post['title']) ?>
                        </span>
                        <div class="flex items-center gap-2 text-[11px] text-muted-foreground flex-wrap">
                            <span><?= esc($post['category']) ?></span>
                            <span>&bull;</span>
                            <span class="<?= $isFuture ? 'text-amber-600 font-semibold' : '' ?>"><?= esc($post['publish_date']) ?></span>
                            <?php if ($isFuture): ?>
                                <span class="inline-flex items-center rounded bg-amber-500/15 px-1.5 py-0.2 text-[9px] font-bold text-amber-700">Belum Tayang</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($isFuture): ?>
                        <span class="inline-flex items-center rounded bg-muted border border-border px-2 py-1 text-[10px] font-semibold text-muted-foreground" title="Artikel belum mencapai waktu tayang">
                            Tertunda
                        </span>
                    <?php elseif ($isAlreadyHeadline): ?>
                        <button type="button" 
                                onclick="openAlreadyHeadlineModal('<?= esc(addslashes($post['title'])) ?>', '<?= esc(addslashes($targetCategory)) ?>', <?= $currentHeadlinePos ?>)"
                                class="inline-flex shrink-0 size-8 items-center justify-center rounded-md border border-primary/40 bg-primary/10 text-primary hover:bg-primary/20 transition-colors shadow-xs" 
                                title="Artikel sudah menjadi headline di posisi #<?= $currentHeadlinePos ?>">
                            <i data-lucide="check" class="size-3.5"></i>
                        </button>
                    <?php else: ?>
                        <button type="button" 
                                onclick="checkAndConfirmAddHeadline(<?= $post['id'] ?>, '<?= esc(addslashes($post['title'])) ?>', <?= count($headlines) ?>, '<?= esc(addslashes($targetCategory)) ?>')"
                                class="inline-flex shrink-0 size-8 items-center justify-center rounded-md bg-[#2B6CB0] text-white hover:bg-[#2B6CB0]/90 transition-colors shadow-xs" 
                                title="Pilih / Jadikan Headline">
                            <i data-lucide="corner-down-right" class="size-3.5"></i>
                        </button>
                    <?php endif; ?>
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
                        <th class="h-10 px-4 font-bold text-foreground w-44">Article Date</th>
                        <th class="h-10 px-4 font-bold text-foreground text-center w-24">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php if (empty($availablePosts)): ?>
                        <tr>
                            <td colspan="3" class="p-8 text-center text-muted-foreground text-xs">No published articles available.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($availablePosts as $post): ?>
                        <?php 
                            $isFuture = !empty($post['publish_date']) && strtotime($post['publish_date']) > time(); 
                            $isAlreadyHeadline = isset($activeHeadlineMap[(int)$post['id']]);
                            $currentHeadlinePos = $isAlreadyHeadline ? $activeHeadlineMap[(int)$post['id']] : null;
                        ?>
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
                                        <?php if ($isAlreadyHeadline): ?>
                                            <span class="inline-flex items-center rounded-sm bg-primary/15 px-1.5 py-0.5 text-[10px] font-bold text-primary uppercase">Aktif di Headline #<?= $currentHeadlinePos ?></span>
                                        <?php endif; ?>
                                        <?php if ($isFuture): ?>
                                            <span class="inline-flex items-center rounded-sm bg-amber-500/15 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 uppercase">Belum Tayang</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="block font-medium text-foreground text-xs leading-relaxed">
                                        <?= esc($post['title']) ?>
                                    </span>
                                    <span class="text-[11px] text-muted-foreground"><?= esc($post['category']) ?> &bull; By <?= esc($post['author']) ?></span>
                                </div>
                            </td>
                            <td class="p-4 text-xs <?= $isFuture ? 'text-amber-600 font-semibold' : 'text-muted-foreground' ?> whitespace-nowrap">
                                <?= esc($post['publish_date']) ?>
                            </td>
                            <td class="p-4 text-center">
                                <?php if ($isFuture): ?>
                                    <span class="inline-flex items-center rounded bg-muted border border-border px-2 py-1 text-[10px] font-semibold text-muted-foreground" title="Artikel belum mencapai waktu tayang">
                                        Tertunda
                                    </span>
                                <?php elseif ($isAlreadyHeadline): ?>
                                    <button type="button" 
                                            onclick="openAlreadyHeadlineModal('<?= esc(addslashes($post['title'])) ?>', '<?= esc(addslashes($targetCategory)) ?>', <?= $currentHeadlinePos ?>)"
                                            class="inline-flex size-7 items-center justify-center rounded-md border border-primary/40 bg-primary/10 text-primary hover:bg-primary/20 transition-colors shadow-xs" 
                                            title="Artikel sudah menjadi headline di posisi #<?= $currentHeadlinePos ?>">
                                        <i data-lucide="check" class="size-3.5"></i>
                                    </button>
                                <?php else: ?>
                                    <button type="button" 
                                            onclick="checkAndConfirmAddHeadline(<?= $post['id'] ?>, '<?= esc(addslashes($post['title'])) ?>', <?= count($headlines) ?>, '<?= esc(addslashes($targetCategory)) ?>')"
                                            class="inline-flex size-7 items-center justify-center rounded-md bg-[#2B6CB0] text-white hover:bg-[#2B6CB0]/90 transition-colors shadow-xs" 
                                            title="Pilih / Jadikan Headline">
                                        <i data-lucide="corner-down-right" class="size-3.5"></i>
                                    </button>
                                <?php endif; ?>
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
                    <label for="targetCategorySelect" class="block text-xs font-bold text-foreground">Pilih Kategori (Category)</label>
                    <span class="text-[11px] font-bold <?= count($headlines) >= 5 ? 'text-amber-600 bg-amber-500/10' : 'text-primary bg-primary/10' ?> px-2 py-0.5 rounded">
                        <?= count($headlines) ?> / 5 Artikel Terisi
                    </span>
                </div>
                <select name="target_category" id="targetCategorySelect" onchange="onTargetCategoryChange(this.value)" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs sm:text-sm font-semibold shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="home" <?= ($targetCategory === 'home') ? 'selected' : '' ?>>Headline Utama (Home)</option>
                    <option value="topical" <?= ($targetCategory === 'topical') ? 'selected' : '' ?>>Topical</option>
                    <option value="trending" <?= ($targetCategory === 'trending') ? 'selected' : '' ?>>Trending</option>
                    <option value="newsletter" <?= ($targetCategory === 'newsletter') ? 'selected' : '' ?>>Newsletter</option>
                </select>

                <!-- Category explanation text without icons -->
                <div class="mt-2 text-[11px] text-muted-foreground bg-muted/40 p-2 rounded border border-border/60 leading-relaxed">
                    <?php if ($targetCategory === 'headline' || $targetCategory === 'home'): ?>
                        Headline Utama (Maksimal 5 artikel untuk carousel & hero banner depan)
                    <?php elseif ($targetCategory === 'topical'): ?>
                        Isu & Tema Khusus (Maksimal 5 artikel tematik pilihan redaksi)
                    <?php elseif ($targetCategory === 'trending'): ?>
                        Berita Trending Terpopuler (Maksimal 5 artikel berita terpopuler pilihan redaksi)
                    <?php elseif ($targetCategory === 'newsletter'): ?>
                        Newsletter Morning Briefing (Maksimal 5 artikel: 1 utama + 4 artikel list)
                    <?php endif; ?>
                </div>
            </div>

            <div class="surface-card overflow-hidden border border-border">
                <table class="w-full text-xs">
                    <thead class="bg-muted/50 border-b border-border">
                        <tr class="text-left font-bold text-foreground">
                            <th class="h-8 px-2 w-16 text-center">Urutan</th>
                            <th class="h-8 px-3">Title &amp; Meta</th>
                            <th class="h-8 px-2 text-center w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="headlineQueueTableBody" class="divide-y divide-border">
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
                            <tr class="headline-draggable-row hover:bg-muted/50 transition-all cursor-grab active:cursor-grabbing select-none" 
                                draggable="true" 
                                data-article-id="<?= (int)$hl['id'] ?>">
                                <td class="px-2 py-2.5 text-center">
                                    <div class="inline-flex items-center justify-center gap-1">
                                        <span class="text-muted-foreground/50 hover:text-foreground cursor-grab">
                                            <i data-lucide="grip-vertical" class="size-3.5"></i>
                                        </span>
                                        <span class="headline-pos-badge font-mono font-bold text-primary text-xs">#<?= $pos++ ?></span>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="block font-medium text-foreground line-clamp-2 leading-snug" title="<?= esc($hl['title']) ?>">
                                        <?= esc($hl['title']) ?>
                                    </span>
                                    <div class="flex items-center gap-2 text-[10px] text-muted-foreground mt-1 flex-wrap">
                                        <span class="font-bold text-primary bg-primary/10 px-1.5 py-0.2 rounded"><?= esc($hl['category'] ?? $targetCategory) ?></span>
                                        <span>&bull;</span>
                                        <span><?= esc($hl['publish_date'] ?? date('d M Y')) ?></span>
                                        <?php if (!empty($hl['set_by'])): ?>
                                            <span>&bull;</span>
                                            <span class="font-mono text-[9px] text-foreground/80 bg-muted px-1.5 py-0.2 rounded">Set by: <strong><?= esc($hl['set_by']) ?></strong></span>
                                        <?php endif; ?>
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

            <button type="submit" id="saveHeadlineOrderBtn" class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-[#2B6CB0] px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90 transition-colors">
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
                <p class="text-xs text-muted-foreground">Artikel ini akan dimasukkan ke urutan headline kategori <?= esc($targetCategory) ?>.</p>
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

<!-- Modal Peringatan: Artikel Sudah Terdaftar di Headline (No Icon, Tulisan Aja) -->
<div id="alreadyHeadlineModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="surface-card w-full max-w-md p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <div>
            <h3 class="text-sm font-bold text-foreground">Artikel Sudah Menjadi Headline</h3>
            <p class="text-xs text-muted-foreground mt-0.5">Artikel ini sudah aktif terdaftar di antrean headline kategori <span id="alreadyHeadlineCategoryText" class="font-bold text-primary"></span>.</p>
        </div>
        <div class="p-3.5 rounded-lg bg-muted/60 border border-border space-y-1.5">
            <p id="alreadyHeadlineTitle" class="text-xs font-semibold text-foreground line-clamp-2"></p>
            <div class="text-[11px] text-muted-foreground">
                Saat ini berada di urutan: <span id="alreadyHeadlinePosBadge" class="font-bold text-primary"></span>
            </div>
        </div>
        <p class="text-xs text-muted-foreground leading-relaxed">
            Anda tidak dapat menambahkan artikel yang sama lebih dari satu kali ke dalam antrean headline kategori ini.
        </p>
        <div class="flex justify-end pt-1">
            <button type="button" onclick="closeConfirmModals()" class="rounded-md bg-[#2B6CB0] px-5 py-2 text-xs font-bold uppercase tracking-wider text-white hover:bg-[#2B6CB0]/90 transition-colors shadow-xs">
                Mengerti
            </button>
        </div>
    </div>
</div>

<!-- Modal Peringatan: Kuota Maksimal 5 Artikel Tercapai (No Icon, Tulisan Aja) -->
<div id="maxQuotaModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="surface-card w-full max-w-md p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <div>
            <h3 class="text-sm font-bold text-foreground">Batas Maksimal Headline Tercapai</h3>
            <p class="text-xs text-muted-foreground mt-0.5">Kategori <span id="maxQuotaCategoryText" class="font-bold text-primary"></span> sudah terisi penuh (5 dari 5 artikel).</p>
        </div>
        <div class="p-3.5 rounded-lg bg-muted/60 border border-border">
            <p class="text-xs text-muted-foreground leading-relaxed">
                Sesuai konfigurasi sistem, maksimal artikel headline untuk setiap kategori adalah <strong>5 artikel</strong>. Silakan hapus atau gantikan artikel yang ada di daftar headline terlebih dahulu sebelum menambahkan artikel baru.
            </p>
        </div>
        <div class="flex justify-end pt-1">
            <button type="button" onclick="closeConfirmModals()" class="rounded-md bg-[#2B6CB0] px-5 py-2 text-xs font-bold uppercase tracking-wider text-white hover:bg-[#2B6CB0]/90 transition-colors shadow-xs">
                Tutup
            </button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/pages/headline.js?v=<?= time() ?>"></script>
<?= $this->endSection() ?>

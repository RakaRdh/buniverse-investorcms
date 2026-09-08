<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/css/pages/media.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Header & Toolbar Matching Prompt 3 (Card based layout with search and upload new) -->
<div class="surface-card p-4 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <a href="<?= base_url('media/new') ?>" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-md bg-[#ED8936] px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow-sm hover:bg-[#ED8936]/90 transition-colors">
                <i data-lucide="upload" class="size-4"></i>
                <span>Upload New</span>
            </a>
        </div>

        <form method="get" action="<?= base_url('media') ?>" class="flex flex-wrap items-center gap-2 flex-1 max-w-md justify-start sm:justify-end">
            <span class="text-xs font-bold text-foreground">Pencarian:</span>
            <input type="text" name="q" value="<?= esc($keyword) ?>" placeholder="Cari media, caption, atau user..." class="flex h-9 flex-1 sm:w-64 rounded-md border border-input bg-background px-3 py-1 text-xs sm:text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-[#2B6CB0] px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow-sm hover:bg-[#2B6CB0]/90">
                Search
            </button>
            <?php if (!empty($keyword)): ?>
                <a href="<?= base_url('media') ?>" class="inline-flex items-center justify-center rounded-md border border-input bg-background px-3 py-2 text-xs font-medium text-muted-foreground hover:text-foreground">
                    Reset
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Media Cards Grid Layout (Prompt: "di media, basenya card, jangan tabel") -->
<?php if (empty($media)): ?>
    <div class="surface-card p-12 text-center">
        <i data-lucide="image-off" class="size-10 text-muted-foreground mx-auto mb-2"></i>
        <p class="text-sm font-medium text-foreground">Tidak ada media ditemukan</p>
        <p class="text-xs text-muted-foreground mt-1">Coba kata kunci pencarian lain atau upload aset baru.</p>
    </div>
<?php else: ?>
    <div class="space-y-3">
        <?php foreach ($media as $item): ?>
        <div class="surface-card p-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 transition-all duration-150 hover:shadow-raised hover:border-primary/40">
            <!-- Left Side: Image Preview + Information -->
            <div class="flex items-start gap-4 min-w-0 flex-1">
                <div class="size-24 rounded-lg overflow-hidden border border-border shrink-0 bg-slate-900">
                    <img src="<?= esc($item['image']) ?>" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover" onerror="this.onerror=null;this.src='/img/investor_placeholder.jpg';">
                </div>
                <div class="space-y-1.5 min-w-0 flex-1">
                    <a href="<?= base_url('media/edit/' . $item['id']) ?>" class="font-bold text-sm text-[#2B6CB0] hover:underline block leading-snug">
                        <?= esc($item['title']) ?>
                    </a>
                    <p class="text-xs text-muted-foreground leading-relaxed">
                        <?= esc($item['caption']) ?>
                    </p>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 pt-1 text-[11px] text-muted-foreground">
                        <span><strong>Source:</strong> <?= esc($item['source']) ?></span>
                        <span><strong>Date:</strong> <?= esc($item['date']) ?></span>
                        <span><strong>User:</strong> <span class="font-mono text-foreground"><?= esc($item['user']) ?></span></span>
                    </div>
                </div>
            </div>

            <!-- Right Side: Action Buttons -->
            <div class="flex items-center gap-2 shrink-0 self-end md:self-center">
                <a href="<?= base_url('media/edit/' . $item['id']) ?>" class="inline-flex size-8 items-center justify-center rounded-md bg-[#2B6CB0] text-white hover:bg-[#2B6CB0]/90 shadow-xs transition-colors" title="Edit Media">
                    <i data-lucide="edit-3" class="size-4"></i>
                </a>
                <a href="<?= base_url('media/delete/' . $item['id']) ?>" class="inline-flex size-8 items-center justify-center rounded-md bg-destructive text-destructive-foreground hover:bg-destructive/90 shadow-xs transition-colors" title="Delete Media" onclick="return confirm('Delete this media asset?')">
                    <i data-lucide="trash-2" class="size-4"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Media Pagination Footer -->
    <?php if (!empty($pagination) && $pagination['totalPages'] > 1): ?>
        <div class="surface-card mt-6">
            <?= view('layouts/partials/pagination', ['pagination' => $pagination, 'baseUrl' => base_url('media')]) ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/pages/media.js"></script>
<?= $this->endSection() ?>

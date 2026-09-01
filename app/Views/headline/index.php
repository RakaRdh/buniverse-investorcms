<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/css/pages/headline.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Filtering Bar (No Title column, search and category only) -->
<div class="surface-card p-4 mb-6">
    <form method="get" action="<?= base_url('headline') ?>" class="flex flex-wrap items-center gap-3">
        <span class="text-xs font-bold text-foreground">Filtering:</span>
        <div class="flex-1 min-w-[220px]">
            <input type="text" name="q" value="<?= esc($filters['q']) ?>" placeholder="Cari artikel..." class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
        </div>
        <div class="w-48">
            <select name="category" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
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

<!-- 2-Panel Layout Matching Prompt & Gambar 2 -->
<div class="headline-grid-layout">
    <!-- Left Column: Published Articles List (Tabel List Published) -->
    <div class="surface-card overflow-hidden">
        <div class="relative w-full overflow-auto">
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
                                    <span class="block font-medium text-foreground text-xs leading-relaxed">
                                        <?php if ($post['is_premium']): ?>
                                            <span class="inline-flex items-center rounded-sm bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 uppercase mr-1">[Premium]</span>
                                        <?php endif; ?>
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
    </div>

    <!-- Right Column: Dropdown Category & Target Headline Queue -->
    <div class="space-y-4">
        <form method="post" action="<?= base_url('headline/save') ?>" class="surface-card p-5 space-y-4">
            <div>
                <label class="block text-xs font-bold text-foreground mb-1.5">Select Category</label>
                <select name="target_category" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="Headline" selected>Headline</option>
                    <option value="Trending">Trending</option>
                    <option value="Special Report">Special Report</option>
                    <option value="Market Flash">Market Flash</option>
                </select>
            </div>

            <div class="surface-card overflow-hidden border border-border">
                <table class="w-full text-xs">
                    <thead class="bg-muted/50 border-b border-border">
                        <tr class="text-left font-bold text-foreground">
                            <th class="h-8 px-3 w-20">ArticleID</th>
                            <th class="h-8 px-3">Title</th>
                            <th class="h-8 px-2 text-center w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <?php if (empty($headlines)): ?>
                            <tr>
                                <td colspan="3" class="p-6 text-center text-muted-foreground">No data available in table</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($headlines as $hl): ?>
                            <tr class="hover:bg-muted/40">
                                <td class="px-3 py-2 font-mono font-bold text-primary">#<?= esc($hl['id']) ?></td>
                                <td class="px-3 py-2">
                                    <span class="block truncate max-w-[180px] font-medium text-foreground" title="<?= esc($hl['title']) ?>">
                                        <?= esc($hl['title']) ?>
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <button type="button" 
                                            onclick="confirmRemoveHeadline(<?= $hl['id'] ?>, '<?= esc(addslashes($hl['title'])) ?>')"
                                            class="text-destructive hover:opacity-75 transition-opacity" 
                                            title="Disable / Hapus dari headline">
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
                <span>SAVE HEADLINE</span>
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
                <h3 class="text-sm font-bold text-foreground">Jadikan Sebagai Headline?</h3>
                <p class="text-xs text-muted-foreground">Artikel ini akan dimasukkan ke antrean kurasi headline utama.</p>
            </div>
        </div>
        <div class="p-3 rounded-lg bg-muted/60 border border-border">
            <p id="addArticleTitle" class="text-xs font-medium text-foreground line-clamp-2"></p>
        </div>
        <form method="post" action="<?= base_url('headline/add') ?>" id="confirmAddForm">
            <input type="hidden" name="article_id" id="addArticleIdInput" value="">
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

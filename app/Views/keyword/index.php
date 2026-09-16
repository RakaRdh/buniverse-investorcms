<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Page Header Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Master Keyword Management</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Kelola kata kunci / topik berita terpusat untuk integrasi SEO, pencarian artikel, dan tag berita.</p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <button type="button" onclick="openAddKeywordModal()" class="inline-flex items-center justify-center gap-1.5 rounded-md bg-primary px-3.5 py-2 text-xs sm:text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                <i data-lucide="plus" class="size-4"></i>
                <span>Tambah Keyword</span>
            </button>
        </div>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="surface-card p-4 mb-6 rounded-xl border border-border">
    <form method="get" action="<?= base_url('keyword') ?>" class="flex flex-col sm:flex-row items-center gap-3">
        <div class="relative flex-1 w-full">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground"></i>
            <input type="text" name="q" value="<?= esc($q ?? '') ?>" class="flex h-9 w-full rounded-md border border-input bg-background pl-9 pr-3 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Cari keyword atau slug...">
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <button type="submit" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 rounded-md bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors h-9">
                <i data-lucide="filter" class="size-3.5"></i>
                <span>Filter</span>
            </button>
            <?php if (!empty($q)): ?>
                <a href="<?= base_url('keyword') ?>" class="inline-flex items-center justify-center rounded-md border border-input bg-background px-3 py-2 text-xs font-medium text-muted-foreground hover:text-foreground h-9">
                    Reset
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="surface-card rounded-xl border border-border overflow-hidden shadow-xs">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-foreground">
            <thead class="bg-muted/50 border-b border-border text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 w-16 text-center">No</th>
                    <th class="px-4 py-3">Nama Keyword</th>
                    <th class="px-4 py-3">Slug (URL Friendly)</th>
                    <th class="px-4 py-3 text-center">Jumlah Artikel Terkait</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php if (empty($keywords)): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-muted-foreground">
                            <i data-lucide="tag" class="mx-auto size-8 text-muted-foreground/50 mb-2"></i>
                            <p class="text-sm font-medium">Belum ada data keyword yang sesuai.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = $pagination['from'];
                    foreach ($keywords as $kw): 
                    ?>
                        <tr class="hover:bg-muted/30 transition-colors">
                            <td class="px-4 py-3 text-center text-muted-foreground font-mono"><?= $no++ ?></td>
                            <td class="px-4 py-3">
                                <span class="font-bold text-foreground text-sm"><?= esc($kw['keyword']) ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <code class="px-2 py-0.5 rounded bg-muted text-muted-foreground text-[11px] font-mono"><?= esc($kw['slug']) ?></code>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ((int)$kw['article_count'] > 0): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                        <?= number_format($kw['article_count']) ?> artikel
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs text-muted-foreground bg-muted">
                                        0
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" onclick='openEditKeywordModal(<?= json_encode($kw) ?>)' class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border border-input bg-background hover:bg-muted text-foreground transition-colors font-medium text-xs" title="Edit Keyword">
                                        <i data-lucide="pencil" class="size-3.5 text-blue-600"></i>
                                        <span>Edit</span>
                                    </button>
                                    <a href="<?= base_url('keyword/delete/' . $kw['id']) ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus keyword \'<?= esc($kw['keyword']) ?>\'? Relasi ke <?= $kw['article_count'] ?> artikel akan dilepas.')" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border border-input bg-background hover:bg-rose-50 dark:hover:bg-rose-950/20 text-rose-600 transition-colors font-medium text-xs" title="Hapus Keyword">
                                        <i data-lucide="trash-2" class="size-3.5"></i>
                                        <span>Hapus</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <?php if ($pagination['totalPages'] > 1): ?>
        <?= view('layouts/partials/pagination', ['pagination' => $pagination, 'baseUrl' => base_url('keyword')]) ?>
    <?php endif; ?>
</div>

<!-- Modal Form Add/Edit Keyword -->
<div id="keywordModal" class="fixed inset-0 z-50 hidden bg-background/80 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-card border border-border rounded-xl shadow-2xl p-6 text-foreground animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-border">
            <h3 class="text-base font-bold text-foreground" id="modalTitle">Tambah Keyword Baru</h3>
            <button type="button" onclick="closeKeywordModal()" class="text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="size-5"></i>
            </button>
        </div>

        <form method="post" action="<?= base_url('keyword/save') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="keywordIdInput" value="">

            <div>
                <label class="block text-xs font-semibold text-foreground mb-1">Nama Keyword <span class="text-rose-500">*</span></label>
                <input type="text" name="keyword" id="keywordTextInput" required placeholder="Contoh: IHSG Hari Ini" oninput="autoSlug(this.value)" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>

            <div>
                <label class="block text-xs font-semibold text-foreground mb-1">Slug (Opsional)</label>
                <input type="text" name="slug" id="keywordSlugInput" placeholder="ihsg-hari-ini" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring font-mono text-xs">
                <p class="text-[11px] text-muted-foreground mt-1">Otomatis digenerate dari nama jika dikosongkan.</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-border">
                <button type="button" onclick="closeKeywordModal()" class="px-4 py-2 rounded-md border border-input bg-background hover:bg-muted text-xs font-medium text-foreground transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-md bg-primary hover:bg-primary/90 text-xs font-semibold text-primary-foreground shadow-sm transition-colors">
                    Simpan Keyword
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddKeywordModal() {
        document.getElementById('modalTitle').innerText = 'Tambah Keyword Baru';
        document.getElementById('keywordIdInput').value = '';
        document.getElementById('keywordTextInput').value = '';
        document.getElementById('keywordSlugInput').value = '';
        document.getElementById('keywordModal').classList.remove('hidden');
        document.getElementById('keywordTextInput').focus();
    }

    function openEditKeywordModal(data) {
        document.getElementById('modalTitle').innerText = 'Edit Keyword';
        document.getElementById('keywordIdInput').value = data.id;
        document.getElementById('keywordTextInput').value = data.keyword;
        document.getElementById('keywordSlugInput').value = data.slug;
        document.getElementById('keywordModal').classList.remove('hidden');
        document.getElementById('keywordTextInput').focus();
    }

    function closeKeywordModal() {
        document.getElementById('keywordModal').classList.add('hidden');
    }

    function autoSlug(text) {
        const slugInput = document.getElementById('keywordSlugInput');
        // Hanya auto isi jika form tambah (id kosong)
        if (!document.getElementById('keywordIdInput').value) {
            slugInput.value = text.toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    }
</script>

<?= $this->endSection() ?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- PageHeader Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Mobile App Feature Content</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Curate featured banners, editorial specials, and investor academy highlights on the mobile app.</p>
        </div>
        <button class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow hover:bg-primary/90 transition-colors self-start sm:self-auto" onclick="document.getElementById('newFeatureModal').style.display='flex'">
            <i data-lucide="plus" class="size-4"></i>
            <span>Add Feature Card</span>
        </button>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <?php foreach ($features as $f): ?>
    <div class="surface-card overflow-hidden flex flex-col justify-between">
        <div>
            <div class="relative h-44 w-full">
                <img src="<?= esc($f['image']) ?>" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover">
                <span class="absolute top-3 left-3 inline-flex items-center rounded-sm bg-sidebar text-sidebar-foreground px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">
                    <?= esc($f['badge']) ?>
                </span>
                <span class="absolute top-3 right-3 inline-flex items-center rounded-sm bg-primary text-primary-foreground px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">
                    Position #<?= esc($f['order']) ?>
                </span>
            </div>
            <div class="p-5 space-y-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-accent"><?= esc($f['type']) ?></span>
                <h3 class="font-bold text-foreground text-sm leading-snug"><?= esc($f['title']) ?></h3>
            </div>
        </div>
        <div class="flex items-center justify-between border-t border-border p-4">
            <span class="text-xs text-muted-foreground">Posisi: <strong class="text-foreground capitalize"><?= esc($f['position'] ?? 'top') ?></strong></span>
            <div class="flex gap-2">
                <button type="button" onclick="editFeatureCard(<?= htmlspecialchars(json_encode($f), ENT_QUOTES, 'UTF-8') ?>)" class="inline-flex size-8 items-center justify-center rounded-md border border-input bg-background text-muted-foreground hover:text-foreground hover:bg-muted" title="Edit Feature"><i data-lucide="edit-3" class="size-3.5"></i></button>
                <a href="<?= base_url('apps-feature-content/delete/' . $f['id']) ?>" onclick="return confirm('Hapus feature card ini?')" class="inline-flex size-8 items-center justify-center rounded-md border border-input bg-background text-destructive hover:bg-destructive/10" title="Hapus Feature"><i data-lucide="trash-2" class="size-3.5"></i></a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal -->
<div id="newFeatureModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.6); z-index: 99; align-items: center; justify-content: center; padding: 1rem;">
    <div class="surface-card w-full max-w-md p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <h3 class="text-sm font-bold text-foreground" id="featureModalTitle">New App Feature Banner</h3>
            <button type="button" onclick="document.getElementById('newFeatureModal').style.display='none'" class="text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>
        <form method="post" action="<?= base_url('apps-feature-content/save') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="featureId" value="">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Tipe Konten</label>
                <select name="type" id="featureType" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="banner">Banner (Link ke Artikel / URL)</option>
                    <option value="tag">Tag (Kategori Khusus / Tag)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Judul / Headline</label>
                <input type="text" name="title" id="featureTitle" required placeholder="e.g. Masterclass Investasi Saham Pemula" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Badge Tag (Opsional jika tipe tag)</label>
                <input type="text" name="badge" id="featureBadge" placeholder="e.g. Special Report, Dividen" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Target Link URL (Untuk Banner)</label>
                <input type="text" name="link_url" id="featureLinkUrl" placeholder="/artikel/... atau https://..." class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Image URL</label>
                <input type="text" name="image" id="featureImage" placeholder="/img/... atau https://..." class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-border">
                <button type="button" onclick="document.getElementById('newFeatureModal').style.display='none'" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted">Batal</button>
                <button type="submit" class="rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase text-primary-foreground hover:bg-primary/90">Simpan Feature</button>
            </div>
        </form>
    </div>
</div>

<script>
function editFeatureCard(data) {
    document.getElementById('featureId').value = data.id || '';
    document.getElementById('featureTitle').value = data.title || '';
    document.getElementById('featureType').value = data.type || 'banner';
    document.getElementById('featureBadge').value = data.badge || '';
    document.getElementById('featureLinkUrl').value = data.target_url || '';
    document.getElementById('featureImage').value = data.image || '';
    document.getElementById('featureModalTitle').textContent = 'Edit App Feature Card';
    document.getElementById('newFeatureModal').style.display = 'flex';
}
</script>

<?= $this->endSection() ?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/css/pages/media.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground"><?= $isEdit ? 'Edit Media Information' : 'Upload Media Baru' ?></h1>
            <p class="mt-1 text-xs text-muted-foreground">Isi informasi data media untuk bank aset editorial Investor.id.</p>
        </div>
        <a href="<?= base_url('media') ?>" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted transition-colors">
            &larr; Kembali ke Media
        </a>
    </div>
</div>

<form method="post" action="<?= $isEdit ? base_url('media/update/' . $media['id']) : base_url('media/save') ?>" class="surface-card p-6 max-w-3xl space-y-5">
    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1.5">Judul Media / Tokoh / Topik</label>
        <input type="text" name="title" required value="<?= esc($media['title'] ?? '') ?>" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="e.g. ESSA Industries Indonesia (ESSA)">
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1.5">Media Information / Deskripsi / Caption</label>
        <textarea name="caption" rows="4" required class="flex w-full rounded-md border border-input bg-background p-3 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Tuliskan keterangan foto, subjek, lokasi, dan konteks..."><?= esc($media['caption'] ?? '') ?></textarea>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1.5">Sumber Foto / Source</label>
            <input type="text" name="source" value="<?= esc($media['source'] ?? '/') ?>" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="e.g. Istimewa / Antara / Reuters">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1.5">Image URL / File Path</label>
            <input type="url" name="image" id="mediaImageUrl" value="<?= esc($media['image'] ?? 'https://images.unsplash.com/photo-1541888946425-d0fbb180c5f5?w=600&auto=format&fit=crop&q=80') ?>" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="https://...">
        </div>
    </div>

    <!-- Preview Box -->
    <div class="space-y-1.5 pt-2">
        <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground">Preview Gambar</label>
        <div class="h-48 w-full sm:w-80 rounded-md border border-border overflow-hidden bg-muted">
            <img id="mediaImagePreview" src="<?= esc($media['image'] ?? 'https://images.unsplash.com/photo-1541888946425-d0fbb180c5f5?w=600&auto=format&fit=crop&q=80') ?>" alt="" class="h-full w-full object-cover">
        </div>
    </div>

    <div class="border-t border-border pt-4 flex justify-end gap-3">
        <a href="<?= base_url('media') ?>" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted">Batal</a>
        <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-[#2B6CB0] px-5 py-2 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90">
            <i data-lucide="save" class="size-4"></i>
            <span>Simpan Media</span>
        </button>
    </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.getElementById('mediaImageUrl')?.addEventListener('input', (e) => {
    const preview = document.getElementById('mediaImagePreview');
    if (preview && e.target.value) {
        preview.src = e.target.value;
    }
});
</script>
<?= $this->endSection() ?>

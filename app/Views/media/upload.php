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

<form method="post" action="<?= $isEdit ? base_url('media/update/' . $media['id']) : base_url('media/save') ?>" enctype="multipart/form-data" class="surface-card p-6 max-w-3xl space-y-5">
    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1.5">Judul Media / Tokoh / Topik</label>
        <input type="text" name="title" required value="<?= esc($media['title'] ?? '') ?>" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="e.g. ESSA Industries Indonesia (ESSA)">
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1.5">Media Information / Deskripsi / Caption</label>
        <textarea name="caption" rows="3" required class="flex w-full rounded-md border border-input bg-background p-3 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Tuliskan keterangan foto, subjek, lokasi, dan konteks..."><?= esc($media['caption'] ?? '') ?></textarea>
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1.5">Sumber Foto / Source</label>
        <input type="text" name="source" value="<?= esc($media['source'] ?? 'Investor.id') ?>" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="e.g. Istimewa / Antara / Reuters">
    </div>

    <!-- Image Selection: Local Upload Only -->
    <div class="space-y-3 pt-1 border-t border-border">
        <label class="block text-xs font-bold uppercase tracking-wider text-foreground">File Gambar Media (Upload Lokal)</label>
        
        <!-- Styled Drag & Drop Local Upload Zone -->
        <div id="dropzoneContainer" class="relative group cursor-pointer border-2 border-dashed border-primary/40 hover:border-primary bg-primary/5 hover:bg-primary/10 transition-all rounded-xl p-6 text-center space-y-3">
            <input type="file" name="media_file" id="mediaFileInput" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
            
            <div class="mx-auto size-12 rounded-full bg-primary/15 flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                <i data-lucide="upload-cloud" class="size-6"></i>
            </div>
            <div>
                <div class="text-sm font-bold text-foreground">
                    <span class="text-primary hover:underline">Klik untuk pilih gambar</span> atau seret file ke sini
                </div>
                <p class="text-xs text-muted-foreground mt-1">Mendukung format JPG, PNG, atau WebP (Rekomendasi min. 1200×675 px, max 5MB)</p>
            </div>
            <div id="fileSelectedInfo" class="hidden text-xs font-mono font-semibold text-primary bg-primary/10 py-1.5 px-3 rounded-md inline-block"></div>
        </div>
    </div>

    <!-- Preview Box: Real Size / Natural Aspect Ratio -->
    <div class="space-y-2 pt-2 border-t border-border">
        <div class="flex items-center justify-between">
            <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground">Preview Gambar (Ukuran &amp; Rasio Real)</label>
            <span id="previewResolutionBadge" class="text-[11px] font-mono text-muted-foreground">Original Dimensions</span>
        </div>

        <div class="relative w-full rounded-xl border border-border bg-slate-950/60 p-2 flex items-center justify-center min-h-[160px] overflow-hidden">
            <img id="mediaImagePreview" src="<?= esc($media['image'] ?? '/img/article1.jpg') ?>" alt="Preview" class="max-h-[480px] w-auto max-w-full rounded-lg object-contain shadow-md" onerror="this.onerror=null;this.src='/img/article1.jpg';">
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
// Local File Upload & Real-Size Preview Handler
const fileInput = document.getElementById('mediaFileInput');
const fileInfo = document.getElementById('fileSelectedInfo');
const preview = document.getElementById('mediaImagePreview');
const resBadge = document.getElementById('previewResolutionBadge');

function updateResolution() {
    if (preview && preview.naturalWidth && resBadge) {
        resBadge.textContent = `${preview.naturalWidth} × ${preview.naturalHeight} px (Rasio Real)`;
    }
}

if (preview.complete) {
    updateResolution();
} else {
    preview.onload = updateResolution;
}

if (fileInput) {
    fileInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            if (fileInfo) {
                fileInfo.textContent = `✓ Terpilih: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                fileInfo.classList.remove('hidden');
            }
            const reader = new FileReader();
            reader.onload = function(evt) {
                if (preview) {
                    preview.src = evt.target.result;
                    preview.onload = () => {
                        updateResolution();
                    };
                }
            };
            reader.readAsDataURL(file);
        }
    });
}
</script>
<?= $this->endSection() ?>

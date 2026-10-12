<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/css/pages/files.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>


<!-- Toolbar & Search Bar Matching Gambar 4 -->
<div class="surface-card p-4 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <button type="button" onclick="document.getElementById('uploadFileModal').style.display='flex'" class="inline-flex items-center gap-2 rounded-md bg-[#ED8936] px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow-sm hover:bg-[#ED8936]/90 transition-colors">
                <i data-lucide="upload" class="size-4"></i>
                <span>Upload New</span>
            </button>
        </div>

        <form method="get" action="<?= base_url('files') ?>" class="flex items-center gap-2 flex-1 max-w-md justify-end">
            <span class="text-xs font-bold text-foreground">Pencarian:</span>
            <input type="text" name="q" value="<?= esc($keyword) ?>" placeholder="Search file..." class="flex h-9 w-64 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-[#2B6CB0] px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow-sm hover:bg-[#2B6CB0]/90">
                Search
            </button>
        </form>
    </div>
</div>

<!-- Table Layout Matching Gambar 4 (Nama File, Date, Upload By, URL) -->
<div class="surface-card overflow-hidden">
    <div class="relative w-full overflow-auto">
        <table class="w-full caption-bottom text-sm">
            <thead class="[&_tr]:border-b border-border bg-muted/40">
                <tr class="text-left font-bold text-foreground text-xs">
                    <th class="h-10 px-4">Nama File</th>
                    <th class="h-10 px-4 w-40">Date</th>
                    <th class="h-10 px-4 w-36">Upload By</th>
                    <th class="h-10 px-4">URL</th>
                    <th class="h-10 px-4 text-center w-20">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php if (empty($files)): ?>
                    <tr>
                        <td colspan="5" class="p-8 text-center text-muted-foreground text-xs">No files available.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($files as $file): ?>
                    <tr class="file-table-row transition-colors">
                        <td class="p-4 font-medium text-foreground text-xs">
                            <div class="flex items-center gap-2">
                                <i data-lucide="file" class="size-4 text-muted-foreground"></i>
                                <span><?= esc($file['name']) ?></span>
                            </div>
                        </td>
                        <td class="p-4 text-xs text-muted-foreground whitespace-nowrap">
                            <?= esc($file['date']) ?>
                        </td>
                        <td class="p-4 text-xs font-mono text-muted-foreground">
                            <?= esc($file['upload_by']) ?>
                        </td>
                        <td class="p-4 text-xs">
                            <a href="<?= esc($file['url']) ?>" target="_blank" class="text-[#2B6CB0] hover:underline font-mono truncate block max-w-sm">
                                <?= esc($file['url']) ?>
                            </a>
                        </td>
                        <td class="p-4 text-center">
                            <a href="<?= base_url('files/delete/' . $file['id']) ?>" class="inline-flex size-7 items-center justify-center rounded-md bg-destructive text-destructive-foreground hover:bg-destructive/90 shadow-xs" title="Delete File" onclick="return confirm('Delete this file entry?')">
                                <i data-lucide="trash-2" class="size-3.5"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <?php if (!empty($pagination)): ?>
        <?= view('layouts/partials/pagination', ['pagination' => $pagination, 'baseUrl' => base_url('files')]) ?>
    <?php endif; ?>
</div>

<!-- Upload File Modal -->
<div id="uploadFileModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1.25rem;">
    <div class="surface-card w-full max-w-lg p-5 shadow-2xl space-y-3.5 rounded-xl border border-border animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <h3 class="text-sm font-bold text-foreground">Upload & Register Document</h3>
            <button type="button" onclick="closeUploadFileModal()" class="text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>
        <form method="post" action="<?= base_url('files/upload') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Nama File Document</label>
                <input type="text" name="name" required placeholder="e.g. Prospektus Obligasi 2026.pdf" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Direct URL Link (Opsional)</label>
                <input type="url" name="url" placeholder="https://img.beritasatu.com/..." class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-border">
                <button type="button" onclick="closeUploadFileModal()" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted">Batal</button>
                <button type="submit" class="rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase text-primary-foreground hover:bg-primary/90">Simpan File</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/pages/files.js"></script>
<script>
function closeUploadFileModal() {
    const m = document.getElementById('uploadFileModal');
    if (m) m.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.ModalUtils) {
        window.ModalUtils.register('uploadFileModal', { onClose: closeUploadFileModal });
    }
});
</script>
<?= $this->endSection() ?>

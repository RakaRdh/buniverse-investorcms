<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Mobile App Feature Content</h1>
            <p class="mt-1 text-xs text-muted-foreground">Curate featured banners, editorial specials, and investor academy highlights on the mobile app.</p>
        </div>
        <button class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow hover:bg-primary/90 transition-colors" onclick="document.getElementById('newFeatureModal').style.display='flex'">
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
                <img src="<?= esc($f['image']) ?>" alt="" class="h-full w-full object-cover">
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
            <span class="text-xs text-muted-foreground">Status: <strong class="text-foreground"><?= esc($f['status']) ?></strong></span>
            <div class="flex gap-2">
                <button type="button" class="inline-flex items-center gap-1 rounded-md border border-input bg-background px-2.5 py-1 text-xs font-medium text-foreground hover:bg-muted"><i data-lucide="arrow-up" class="size-3.5"></i> Up</button>
                <button type="button" class="inline-flex size-8 items-center justify-center rounded-md border border-input bg-background text-muted-foreground hover:text-foreground hover:bg-muted"><i data-lucide="edit-3" class="size-3.5"></i></button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal -->
<div id="newFeatureModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.6); z-index: 99; align-items: center; justify-content: center; padding: 1rem;">
    <div class="surface-card w-full max-w-md p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <h3 class="text-sm font-bold text-foreground">New App Feature Banner</h3>
            <button type="button" onclick="document.getElementById('newFeatureModal').style.display='none'" class="text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>
        <form method="post" action="<?= base_url('apps-feature-content/save') ?>" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Banner Title</label>
                <input type="text" name="title" required placeholder="e.g. Masterclass Investasi Saham Pemula" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Badge Label</label>
                <input type="text" name="badge" required placeholder="e.g. Special Report, Academy" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Image URL</label>
                <input type="url" name="image" placeholder="https://images.unsplash.com/..." class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-border">
                <button type="button" onclick="document.getElementById('newFeatureModal').style.display='none'" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted">Batal</button>
                <button type="submit" class="rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase text-primary-foreground hover:bg-primary/90">Save Feature</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground">News Categories</h1>
            <p class="mt-1 text-xs text-muted-foreground">Organize and structure content taxonomy, slug routing, and article categorization.</p>
        </div>
        <button class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow hover:bg-primary/90 transition-colors" onclick="document.getElementById('newCatModal').style.display='flex'">
            <i data-lucide="plus" class="size-4"></i>
            <span>Add Category</span>
        </button>
    </div>
</div>

<div class="surface-card overflow-hidden">
    <div class="relative w-full overflow-auto">
        <table class="w-full caption-bottom text-sm">
            <thead class="[&_tr]:border-b border-border bg-muted/40">
                <tr class="text-left font-bold text-foreground text-xs">
                    <th class="h-10 px-4">Category Name</th>
                    <th class="h-10 px-4">URL Slug</th>
                    <th class="h-10 px-4">Total Articles</th>
                    <th class="h-10 px-4">Status</th>
                    <th class="h-10 px-4">Last Updated</th>
                    <th class="h-10 px-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php foreach ($categories as $cat): ?>
                <tr class="hover:bg-muted/40 transition-colors">
                    <td class="p-4 font-bold text-foreground text-xs"><?= esc($cat['name']) ?></td>
                    <td class="p-4 text-xs font-mono text-muted-foreground">/category/<?= esc($cat['slug']) ?></td>
                    <td class="p-4 text-xs font-semibold text-foreground"><?= number_format($cat['count']) ?></td>
                    <td class="p-4">
                        <span class="inline-flex items-center gap-1.5 rounded-md border border-success/25 bg-success/12 px-2 py-0.5 text-xs font-medium text-success capitalize">
                            <span class="size-1.5 rounded-full bg-current"></span>
                            <?= esc($cat['status']) ?>
                        </span>
                    </td>
                    <td class="p-4 text-xs text-muted-foreground"><?= esc($cat['updated']) ?></td>
                    <td class="p-4 text-right">
                        <div class="inline-flex items-center gap-1.5">
                            <a href="<?= base_url('post?category=' . urlencode($cat['name'])) ?>" class="inline-flex size-8 items-center justify-center rounded-md border border-input bg-background text-muted-foreground hover:text-foreground hover:bg-muted" title="View Articles in Category">
                                <i data-lucide="external-link" class="size-3.5"></i>
                            </a>
                            <a href="<?= base_url('categories/delete/' . $cat['id']) ?>" class="inline-flex size-8 items-center justify-center rounded-md border border-input bg-background text-destructive hover:bg-destructive/10" title="Delete Category" onclick="return confirm('Delete this category?')">
                                <i data-lucide="trash-2" class="size-3.5"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="newCatModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.6); z-index: 99; align-items: center; justify-content: center; padding: 1rem;">
    <div class="surface-card w-full max-w-md p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <h3 class="text-sm font-bold text-foreground">Add New Category</h3>
            <button type="button" onclick="document.getElementById('newCatModal').style.display='none'" class="text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>
        <form method="post" action="<?= base_url('categories/save') ?>" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Category Name</label>
                <input type="text" name="name" required placeholder="e.g. Fintech & Web3" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-border">
                <button type="button" onclick="document.getElementById('newCatModal').style.display='none'" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted">Batal</button>
                <button type="submit" class="rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase text-primary-foreground hover:bg-primary/90">Create Category</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- PageHeader Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Mobile App Versions &amp; Releases</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Manage iOS &amp; Android application releases, build numbers, mandatory update flags, and changelogs.</p>
        </div>
        <button class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow hover:bg-primary/90 transition-colors self-start sm:self-auto" onclick="document.getElementById('newVersionModal').style.display='flex'">
            <i data-lucide="plus" class="size-4"></i>
            <span>Register New Version</span>
        </button>
    </div>
</div>

<div class="surface-card overflow-hidden">
    <div class="relative w-full overflow-auto">
        <table class="w-full caption-bottom text-sm">
            <thead class="[&_tr]:border-b border-border bg-muted/40">
                <tr class="text-left font-bold text-foreground text-xs">
                    <th class="h-10 px-4">App Name</th>
                    <th class="h-10 px-4">Platform</th>
                    <th class="h-10 px-4">Version</th>
                    <th class="h-10 px-4">Build</th>
                    <th class="h-10 px-4">Release Date</th>
                    <th class="h-10 px-4">Status</th>
                    <th class="h-10 px-4">Changelog</th>
                    <th class="h-10 px-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php foreach ($versions as $ver): ?>
                <tr class="hover:bg-muted/40 transition-colors">
                    <td class="p-4 font-bold text-foreground text-xs"><?= esc($ver['app']) ?></td>
                    <td class="p-4 text-xs">
                        <div class="flex items-center gap-2 text-muted-foreground">
                            <i data-lucide="<?= str_contains($ver['platform'], 'iOS') ? 'apple' : 'smartphone' ?>" class="size-4"></i>
                            <span><?= esc($ver['platform']) ?></span>
                        </div>
                    </td>
                    <td class="p-4"><span class="inline-flex rounded-md border border-primary/20 bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary"><?= esc($ver['version']) ?></span></td>
                    <td class="p-4 text-xs font-mono text-muted-foreground">#<?= esc($ver['build']) ?></td>
                    <td class="p-4 text-xs text-muted-foreground whitespace-nowrap"><?= esc($ver['release_date']) ?></td>
                    <td class="p-4"><span class="inline-flex items-center gap-1.5 rounded-md border border-success/25 bg-success/12 px-2 py-0.5 text-xs font-medium text-success capitalize"><span class="size-1.5 rounded-full bg-current"></span><?= esc($ver['status']) ?></span></td>
                    <td class="p-4 text-xs text-muted-foreground max-w-xs truncate"><?= esc($ver['changelog']) ?></td>
                    <td class="p-4 text-right">
                        <button class="inline-flex size-8 items-center justify-center rounded-md border border-input bg-background text-muted-foreground hover:text-foreground hover:bg-muted"><i data-lucide="edit-3" class="size-3.5"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="newVersionModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.6); z-index: 99; align-items: center; justify-content: center; padding: 1rem;">
    <div class="surface-card w-full max-w-md p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <h3 class="text-sm font-bold text-foreground">Register App Version</h3>
            <button type="button" onclick="document.getElementById('newVersionModal').style.display='none'" class="text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>
        <form method="post" action="<?= base_url('apps-version/save') ?>" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Platform</label>
                <select name="platform" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="iOS (App Store)">iOS (App Store)</option>
                    <option value="Android (Play Store)">Android (Play Store)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Version Number</label>
                <input type="text" name="version" required placeholder="e.g. v4.3.1" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Changelog & Notes</label>
                <textarea name="changelog" rows="3" placeholder="Key updates in this version..." class="flex w-full rounded-md border border-input bg-background p-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-border">
                <button type="button" onclick="document.getElementById('newVersionModal').style.display='none'" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted">Batal</button>
                <button type="submit" class="rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase text-primary-foreground hover:bg-primary/90">Save Version</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

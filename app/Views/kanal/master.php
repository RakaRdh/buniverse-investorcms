<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- PageHeader Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Master Kanal</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Kelola taksonomi kanal induk redaksi Investor.id, URL slug, dan alokasi rubrik.</p>
        </div>
        <button class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow hover:bg-primary/90 transition-colors self-start sm:self-auto" onclick="document.getElementById('newKanalModal').style.display='flex'">
            <i data-lucide="plus" class="size-4"></i>
            <span>Tambah Master Kanal</span>
        </button>
    </div>
</div>

<div class="surface-card overflow-hidden">
    <div class="relative w-full overflow-auto">
        <table class="w-full caption-bottom text-xs">
            <thead class="border-b border-border bg-muted/40 font-bold text-muted-foreground uppercase">
                <tr>
                    <th class="h-10 px-4 text-left">Nama Master Kanal</th>
                    <th class="h-10 px-4 text-left">URL Slug</th>
                    <th class="h-10 px-4 text-center">Jumlah Sub-Kanal</th>
                    <th class="h-10 px-4 text-center">Total Artikel Terbit</th>
                    <th class="h-10 px-4 text-right">Total Akumulasi Views</th>
                    <th class="h-10 px-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php foreach ($kanals as $k): ?>
                <tr class="hover:bg-muted/40 transition-colors">
                    <td class="p-4 font-bold text-foreground text-xs">
                        <div class="flex items-center gap-2">
                            <span class="grid size-7 shrink-0 place-items-center rounded bg-primary/10 text-primary font-bold">
                                <?= esc(substr($k['name'], 0, 1)) ?>
                            </span>
                            <span><?= esc($k['name']) ?></span>
                        </div>
                    </td>
                    <td class="p-4 font-mono text-muted-foreground text-xs">/kanal/<?= esc($k['slug']) ?></td>
                    <td class="p-4 text-center">
                        <span class="inline-flex items-center rounded-md border border-border px-2.5 py-0.5 text-[11px] font-semibold bg-muted">
                            <?= esc($k['sub_count']) ?> Sub-Kanal
                        </span>
                    </td>
                    <td class="p-4 text-center font-bold text-primary">
                        <?= esc($k['article_count']) ?> artikel
                    </td>
                    <td class="p-4 text-right font-mono font-bold text-foreground">
                        <?= number_format($k['total_views']) ?>
                    </td>
                    <td class="p-4 text-right">
                        <a href="<?= base_url('post?kanal=' . urlencode($k['name'])) ?>" class="inline-flex size-8 items-center justify-center rounded-md border border-input bg-background text-muted-foreground hover:text-foreground hover:bg-muted" title="Lihat Artikel di Kanal Ini">
                            <i data-lucide="external-link" class="size-3.5"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Master Kanal -->
<div id="newKanalModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.6); z-index: 99; align-items: center; justify-content: center; padding: 1rem;">
    <div class="surface-card w-full max-w-md p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <h3 class="text-sm font-bold text-foreground">Tambah Master Kanal Baru</h3>
            <button type="button" onclick="document.getElementById('newKanalModal').style.display='none'" class="text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>
        <form method="post" action="<?= base_url('kanal/master/save') ?>" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Nama Kanal</label>
                <input type="text" name="name" required placeholder="Contoh: Lifestyle, Syariah, ESG" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-border">
                <button type="button" onclick="document.getElementById('newKanalModal').style.display='none'" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted">Batal</button>
                <button type="submit" class="rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase text-primary-foreground hover:bg-primary/90">Simpan Kanal</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

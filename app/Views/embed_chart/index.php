<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- PageHeader Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Financial Embed Charts</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Configure, preview, and generate embed codes for live stock, commodity, and currency charts.</p>
        </div>
        <button class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow hover:bg-primary/90 transition-colors self-start sm:self-auto" onclick="document.getElementById('newChartModal').style.display='flex'">
            <i data-lucide="plus" class="size-4"></i>
            <span>Create Chart Embed</span>
        </button>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php foreach ($charts as $chart): ?>
    <div class="surface-card p-5 flex flex-col justify-between space-y-4">
        <div>
            <div class="flex items-start justify-between gap-3 border-b border-border pb-3">
                <div>
                    <span class="inline-flex items-center rounded-sm bg-primary/10 px-2 py-0.5 text-[10px] font-bold text-primary mb-1">
                        <?= esc($chart['symbol']) ?>
                    </span>
                    <h3 class="font-bold text-foreground text-sm leading-snug"><?= esc($chart['title']) ?></h3>
                    <p class="text-[11px] text-muted-foreground"><?= esc($chart['type']) ?> &bull; <?= esc($chart['source']) ?></p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-md border border-success/25 bg-success/12 px-2 py-0.5 text-xs font-medium text-success capitalize">
                    <span class="size-1.5 rounded-full bg-current"></span>
                    <?= esc($chart['status']) ?>
                </span>
            </div>

            <!-- Mini Live Stream Visual -->
            <div class="my-4 rounded-lg border border-border bg-muted/30 p-3 space-y-2">
                <div class="flex justify-between items-center text-xs font-bold">
                    <span class="text-muted-foreground uppercase text-[10px]">Data Stream</span>
                    <span class="text-success">+1.42%</span>
                </div>
                <div class="flex items-end gap-1.5 h-16 pt-2">
                    <div class="flex-1 bg-slate-300 h-1/3 rounded-xs"></div>
                    <div class="flex-1 bg-slate-400 h-2/3 rounded-xs"></div>
                    <div class="flex-1 bg-slate-300 h-1/2 rounded-xs"></div>
                    <div class="flex-1 bg-blue-400 h-4/5 rounded-xs"></div>
                    <div class="flex-1 bg-primary h-full rounded-xs"></div>
                </div>
            </div>

            <div class="rounded-md bg-muted/60 p-2 text-[11px] font-mono text-muted-foreground overflow-x-auto">
                <?= esc($chart['embed_code']) ?>
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-border pt-3">
            <span class="text-[11px] text-muted-foreground">Updated: <?= esc($chart['updated']) ?></span>
            <div class="flex gap-2">
                <button type="button" class="inline-flex items-center gap-1.5 rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium text-foreground hover:bg-muted" onclick="navigator.clipboard.writeText('<?= esc($chart['embed_code']) ?>'); showToast('Embed iframe code copied!', 'success');">
                    <i data-lucide="code" class="size-3.5"></i>
                    <span>Copy Code</span>
                </button>
                <a href="<?= base_url('embed-chart/delete/' . $chart['id']) ?>" class="inline-flex size-8 items-center justify-center rounded-md border border-input bg-background text-destructive hover:bg-destructive/10" title="Delete" onclick="return confirm('Delete chart embed?')">
                    <i data-lucide="trash-2" class="size-3.5"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal -->
<div id="newChartModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1.25rem;">
    <div class="surface-card w-full max-w-lg p-5 shadow-2xl space-y-3.5 rounded-xl border border-border animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <h3 class="text-sm font-bold text-foreground">New Embed Chart</h3>
            <button type="button" onclick="document.getElementById('newChartModal').style.display='none'" class="text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>
        <form method="post" action="<?= base_url('embed-chart/save') ?>" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Chart Title</label>
                <input type="text" name="title" required placeholder="e.g. Saham Bank BCA (BBCA) 30-Day Candle" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Ticker / Symbol</label>
                <input type="text" name="symbol" required placeholder="e.g. IDX:BBCA" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Chart Type</label>
                <select name="type" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="Candlestick / Area">Candlestick & Volume</option>
                    <option value="Line Chart">Smooth Line</option>
                    <option value="Comparison Heatmap">Comparison Heatmap</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-border">
                <button type="button" onclick="document.getElementById('newChartModal').style.display='none'" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted">Batal</button>
                <button type="submit" class="rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase text-primary-foreground hover:bg-primary/90">Generate Embed</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

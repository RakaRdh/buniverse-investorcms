<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Page Header Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <div class="flex items-center gap-2 mb-1">
                <a href="<?= base_url('post/history') ?>" class="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-primary transition-colors">
                    <i data-lucide="arrow-left" class="size-3.5"></i>
                    <span>Kembali ke Log Revisi</span>
                </a>
                <span class="text-muted-foreground/60">&bull;</span>
                <span class="text-xs font-mono text-muted-foreground">Log #<?= (int)$detail['id'] ?></span>
            </div>
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight truncate">
                <?= esc($detail['title_after'] ?: ($detail['current_title'] ?: 'Revisi Artikel #' . $detail['articleID'])) ?>
            </h1>
            <div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-muted-foreground">
                <span class="flex items-center gap-1">
                    <i data-lucide="calendar" class="size-3.5 text-muted-foreground"></i>
                    <?= date('d F Y, H:i:s', strtotime($detail['timestamp'])) ?> WIB
                </span>
                <span>&bull;</span>
                <span class="flex items-center gap-1 font-medium text-foreground">
                    <i data-lucide="user-check" class="size-3.5 text-primary"></i>
                    Editor: <?= esc($detail['editor_name'] ?: ($detail['editor_username'] ?: 'User #' . $detail['editorID'])) ?>
                </span>
                <?php if (!empty($detail['articleID'])): ?>
                    <span>&bull;</span>
                    <a href="<?= base_url('post/edit/' . $detail['articleID']) ?>" target="_blank" class="text-primary hover:underline font-semibold flex items-center gap-1">
                        <span>Buka di Editor Artikel</span>
                        <i data-lucide="external-link" class="size-3"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Comparison Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <!-- Judul Comparison Card -->
    <div class="surface-card p-4 rounded-xl border border-border space-y-3">
        <div class="flex items-center justify-between border-b border-border pb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Perubahan Headline / Judul</span>
            <i data-lucide="type" class="size-4 text-primary"></i>
        </div>
        <div class="space-y-2 text-xs">
            <div>
                <span class="text-[10px] uppercase tracking-wider font-bold text-rose-600 block mb-0.5">Sebelum:</span>
                <div class="p-2.5 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-900 dark:text-rose-200 font-medium">
                    <?= esc($detail['title_prev'] ?: '(Kosong)') ?>
                </div>
            </div>
            <div>
                <span class="text-[10px] uppercase tracking-wider font-bold text-emerald-600 block mb-0.5">Sesudah:</span>
                <div class="p-2.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-900 dark:text-emerald-200 font-medium">
                    <?= esc($detail['title_after'] ?: '(Kosong)') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Status & Meta Comparison Card -->
    <div class="surface-card p-4 rounded-xl border border-border space-y-3">
        <div class="flex items-center justify-between border-b border-border pb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Perubahan Status Naskah</span>
            <i data-lucide="workflow" class="size-4 text-primary"></i>
        </div>
        <div class="space-y-3 text-xs">
            <div class="flex items-center justify-between p-3 rounded-lg bg-muted/40 border border-border">
                <div>
                    <span class="text-[10px] uppercase tracking-wider font-bold text-muted-foreground block mb-0.5">Status Sebelum:</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-muted border border-border">
                        <?= esc($detail['status_prev'] ?: 'Draft') ?>
                    </span>
                </div>
                <i data-lucide="arrow-right" class="size-5 text-muted-foreground"></i>
                <div class="text-right">
                    <span class="text-[10px] uppercase tracking-wider font-bold text-muted-foreground block mb-0.5">Status Sesudah:</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-primary/15 text-primary border border-primary/30">
                        <?= esc($detail['status_after'] ?: 'Draft') ?>
                    </span>
                </div>
            </div>
            <div class="text-[11px] text-muted-foreground leading-relaxed">
                Revisi tercatat pada sistem basis data audit trail tabel <code class="font-mono text-foreground font-semibold">articlehistory</code>.
            </div>
        </div>
    </div>
</div>

<!-- Side-by-Side Content Diff Canvas -->
<div class="surface-card rounded-xl border border-border shadow-sm overflow-hidden mb-8">
    <div class="flex items-center justify-between p-4 border-b border-border bg-muted/30">
        <div class="flex items-center gap-2">
            <i data-lucide="git-diff" class="size-4 text-primary"></i>
            <h2 class="text-xs font-bold uppercase tracking-wider text-foreground">Perbandingan Konten Isi Berita (Side-by-Side Diff)</h2>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="toggleDiffView('formatted')" id="btnFormatted" class="px-2.5 py-1 text-xs font-semibold rounded bg-primary text-primary-foreground">
                Tampilan Rapi (HTML)
            </button>
            <button type="button" onclick="toggleDiffView('source')" id="btnSource" class="px-2.5 py-1 text-xs font-semibold rounded bg-muted text-muted-foreground hover:text-foreground">
                Kode Sumber (Raw)
            </button>
        </div>
    </div>

    <!-- 2-Column Side-by-Side Body -->
    <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-border">
        <!-- Kolom Kiri: Sebelum -->
        <div class="p-5 space-y-3 bg-rose-500/[0.02]">
            <div class="flex items-center justify-between border-b border-border/80 pb-2">
                <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-rose-600">
                    <span class="size-2 rounded-full bg-rose-500"></span>
                    <span>Naskah Sebelumnya</span>
                </span>
                <span class="text-[10px] font-mono text-muted-foreground">
                    <?= strlen(strip_tags($detail['content_prev'] ?? '')) ?> karakter
                </span>
            </div>

            <!-- Formatted HTML Preview -->
            <div id="prevFormatted" class="prose prose-sm max-w-none text-xs text-foreground/90 leading-relaxed overflow-y-auto max-h-[600px] pr-2">
                <?= $detail['content_prev'] ?: '<p class="text-muted-foreground italic">(Konten sebelumnya kosong)</p>' ?>
            </div>

            <!-- Raw Source Code -->
            <pre id="prevSource" class="hidden font-mono text-[11px] p-3 rounded-lg bg-muted text-foreground overflow-x-auto whitespace-pre-wrap max-h-[600px]"><?= esc($detail['content_prev'] ?? '') ?></pre>
        </div>

        <!-- Kolom Kanan: Sesudah -->
        <div class="p-5 space-y-3 bg-emerald-500/[0.02]">
            <div class="flex items-center justify-between border-b border-border/80 pb-2">
                <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-emerald-600">
                    <span class="size-2 rounded-full bg-emerald-500"></span>
                    <span>Naskah Sesudah Revisi</span>
                </span>
                <span class="text-[10px] font-mono text-muted-foreground">
                    <?= strlen(strip_tags($detail['content_after'] ?? '')) ?> karakter
                </span>
            </div>

            <!-- Formatted HTML Preview -->
            <div id="afterFormatted" class="prose prose-sm max-w-none text-xs text-foreground/90 leading-relaxed overflow-y-auto max-h-[600px] pr-2">
                <?= $detail['content_after'] ?: '<p class="text-muted-foreground italic">(Konten sesudah revisi kosong)</p>' ?>
            </div>

            <!-- Raw Source Code -->
            <pre id="afterSource" class="hidden font-mono text-[11px] p-3 rounded-lg bg-muted text-foreground overflow-x-auto whitespace-pre-wrap max-h-[600px]"><?= esc($detail['content_after'] ?? '') ?></pre>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function toggleDiffView(mode) {
    const prevFormatted = document.getElementById('prevFormatted');
    const prevSource = document.getElementById('prevSource');
    const afterFormatted = document.getElementById('afterFormatted');
    const afterSource = document.getElementById('afterSource');
    const btnFormatted = document.getElementById('btnFormatted');
    const btnSource = document.getElementById('btnSource');

    if (mode === 'source') {
        prevFormatted.classList.add('hidden');
        prevSource.classList.remove('hidden');
        afterFormatted.classList.add('hidden');
        afterSource.classList.remove('hidden');

        btnSource.className = 'px-2.5 py-1 text-xs font-semibold rounded bg-primary text-primary-foreground';
        btnFormatted.className = 'px-2.5 py-1 text-xs font-semibold rounded bg-muted text-muted-foreground hover:text-foreground';
    } else {
        prevFormatted.classList.remove('hidden');
        prevSource.classList.add('hidden');
        afterFormatted.classList.remove('hidden');
        afterSource.classList.add('hidden');

        btnFormatted.className = 'px-2.5 py-1 text-xs font-semibold rounded bg-primary text-primary-foreground';
        btnSource.className = 'px-2.5 py-1 text-xs font-semibold rounded bg-muted text-muted-foreground hover:text-foreground';
    }
}
</script>
<?= $this->endSection() ?>

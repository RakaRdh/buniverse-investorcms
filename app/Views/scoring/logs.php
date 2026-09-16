<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/css/pages/scoring.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight flex items-center gap-2">
                <i data-lucide="history" class="size-6 text-primary"></i>
                <span>Riwayat Log SEO Scoring</span>
            </h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">
                Daftar audit log naskah yang telah diskoring beserta data pengguna (used by), model AI, dan skor hybrid.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= base_url('scoring') ?>" class="inline-flex items-center gap-1.5 rounded-md bg-[#2B6CB0] px-3.5 py-2 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90 transition-colors">
                <i data-lucide="plus" class="size-4"></i>
                <span>Hitung Skor Naskah Baru</span>
            </a>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="surface-card p-4 mb-6 rounded-xl border border-border shadow-sm">
    <form method="get" action="<?= base_url('scoring/logs') ?>" id="scoringLogFilterForm">
        <div class="flex flex-wrap gap-3 items-center justify-between">
            <!-- Keyword Search -->
            <div class="relative flex-1 min-w-[240px]">
                <input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary" placeholder="Cari judul artikel, ID, atau nama pengguna...">
            </div>

            <!-- Category Filter -->
            <div class="w-48">
                <select name="category" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary" onchange="this.form.submit()">
                    <option value="all">Kategori: Semua</option>
                    <option value="Excellent" <?= ($filters['category'] ?? '') === 'Excellent' ? 'selected' : '' ?>>Excellent (&ge;85)</option>
                    <option value="Good" <?= ($filters['category'] ?? '') === 'Good' ? 'selected' : '' ?>>Good (70–84)</option>
                    <option value="Needs Improvement" <?= ($filters['category'] ?? '') === 'Needs Improvement' ? 'selected' : '' ?>>Needs Improvement (50–69)</option>
                    <option value="Poor" <?= ($filters['category'] ?? '') === 'Poor' ? 'selected' : '' ?>>Poor (&lt;50)</option>
                </select>
            </div>

            <!-- Submit & Reset -->
            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3.5 py-2 text-xs font-semibold text-white hover:bg-primary/90 transition-colors shadow-2xs">
                    <span>Filter</span>
                </button>
                <?php if (!empty($filters['q']) || ($filters['category'] ?? 'all') !== 'all'): ?>
                    <a href="<?= base_url('scoring/logs') ?>" class="inline-flex items-center gap-1 rounded-md border border-input bg-background px-3 py-2 text-xs font-medium text-muted-foreground hover:bg-muted transition-colors">
                        <span>Reset</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Header Top Counter -->
<div class="flex flex-wrap items-center justify-between gap-1 mb-3 px-1 text-xs text-muted-foreground">
    <div>
        Menampilkan <strong class="text-foreground"><?= $pagination['from'] ?>–<?= $pagination['to'] ?></strong> dari <strong class="text-foreground"><?= $pagination['totalItems'] ?></strong> log audit
    </div>
    <div class="text-[11px] text-muted-foreground/80">
        Halaman <?= $pagination['page'] ?> dari <?= $pagination['totalPages'] ?>
    </div>
</div>

<!-- Logs Table Container -->
<div class="surface-card overflow-hidden rounded-xl border border-border shadow-sm mb-6">
    <div class="w-full">
        <table class="w-full text-left text-xs table-fixed">
            <thead class="border-b border-border bg-muted/40 text-muted-foreground uppercase tracking-wider font-bold">
                <tr>
                    <th class="py-3 px-3 w-[6%] text-center">ID</th>
                    <th class="py-3 px-3 w-[40%]">Artikel / Headline</th>
                    <th class="py-3 px-2 w-[13%] text-center">Skor Akhir</th>
                    <th class="py-3 px-2 w-[13%] text-center">Komposisi (Rule/AI)</th>
                    <th class="py-3 px-2.5 w-[12%]">Used By (User)</th>
                    <th class="py-3 px-2 w-[10%]">Waktu Audit</th>
                    <th class="py-3 px-3 w-[6%] text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <i data-lucide="file-question" class="size-8 text-muted-foreground"></i>
                                <p class="text-sm font-medium text-foreground">Belum ada riwayat log scoring</p>
                                <p class="text-xs text-muted-foreground">Jalankan scoring pada naskah artikel untuk mencatat log audit.</p>
                                <a href="<?= base_url('scoring') ?>" class="mt-2 inline-flex rounded-md bg-primary text-white px-3.5 py-1.5 text-xs font-bold hover:bg-primary/90">Mulai Scoring</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <?php 
                        $finalScore = round($log['final_score']);
                        $badgeColor = match($log['score_category']) {
                            'Excellent' => 'badge-score-excellent',
                            'Good' => 'badge-score-good',
                            'Needs Improvement' => 'badge-score-warning',
                            default => 'badge-score-poor'
                        };
                        $userName = !empty($log['used_by_name']) 
                            ? $log['used_by_name'] 
                            : (!empty($log['scored_by_name']) ? $log['scored_by_name'] : 'System');
                    ?>
                    <tr class="hover:bg-muted/30 transition-colors">
                        <td class="py-3 px-3 text-center font-mono text-[11px] text-muted-foreground">
                            #<?= $log['id'] ?>
                        </td>
                        <td class="py-3 px-3">
                            <div class="font-bold text-foreground line-clamp-1">
                                <?= esc($log['article_title'] ?: 'Artikel ID #' . $log['article_id']) ?>
                            </div>
                            <div class="text-[10px] text-muted-foreground flex items-center gap-2 mt-0.5 font-mono">
                                <span>ID: <?= $log['article_id'] ?></span>
                                <span>&bull;</span>
                                <span class="uppercase font-semibold"><?= esc($log['article_status'] ?: 'Draft') ?></span>
                                <?php if (!empty($log['model_used'])): ?>
                                    <span>&bull;</span>
                                    <span class="text-accent font-medium"><?= esc($log['model_used']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="py-3 px-2 text-center">
                            <div class="flex flex-col items-center justify-center gap-1">
                                <span class="text-base font-extrabold font-mono text-foreground"><?= $finalScore ?>%</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold uppercase <?= $badgeColor ?>">
                                    <?= esc($log['score_category']) ?>
                                </span>
                            </div>
                        </td>
                        <td class="py-3 px-2 text-center font-mono text-[11px]">
                            <div class="text-foreground font-semibold">
                                Rule: <span class="text-primary font-bold"><?= round($log['rule_score']) ?>%</span>
                            </div>
                            <div class="text-muted-foreground text-[10px]">
                                AI: <?= $log['ai_score'] !== null ? round($log['ai_score']) . '%' : 'N/A' ?>
                            </div>
                        </td>
                        <td class="py-3 px-2.5">
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="user" class="size-3.5 text-muted-foreground shrink-0"></i>
                                <span class="font-medium text-foreground truncate"><?= esc($userName) ?></span>
                            </div>
                        </td>
                        <td class="py-3 px-2 text-muted-foreground font-mono text-[11px]">
                            <?= !empty($log['created_at']) ? date('d M Y H:i', strtotime($log['created_at'])) : '-' ?>
                        </td>
                        <td class="py-3 px-3 text-center">
                            <a href="<?= base_url('scoring/detail/' . $log['id']) ?>" class="inline-flex items-center justify-center gap-1 rounded border border-input bg-background px-2.5 py-1 text-[11px] font-bold text-primary hover:bg-primary hover:text-white transition-colors shadow-2xs" title="Lihat Detail Hasil Scoring di Halaman Khusus">
                                <i data-lucide="eye" class="size-3"></i>
                                <span>Detail</span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <?php if ($pagination['totalPages'] > 1): ?>
    <div class="p-3 border-t border-border bg-muted/20 flex items-center justify-between text-xs">
        <div>
            Halaman <strong><?= $pagination['page'] ?></strong> dari <strong><?= $pagination['totalPages'] ?></strong>
        </div>
        <div class="flex items-center gap-1">
            <?php if ($pagination['page'] > 1): ?>
                <a href="<?= base_url('scoring/logs?page=' . ($pagination['page'] - 1) . '&q=' . urlencode($filters['q']) . '&category=' . urlencode($filters['category'])) ?>" class="px-2.5 py-1 rounded border border-input bg-background text-foreground hover:bg-muted">Prev</a>
            <?php endif; ?>
            <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                <a href="<?= base_url('scoring/logs?page=' . ($pagination['page'] + 1) . '&q=' . urlencode($filters['q']) . '&category=' . urlencode($filters['category'])) ?>" class="px-2.5 py-1 rounded border border-input bg-background text-foreground hover:bg-muted">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Detail Modal for SEO Score Log -->
<div id="scoreDetailModal" class="fixed inset-0 z-[1000] bg-black/60 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="surface-card w-full max-w-2xl max-h-[88vh] flex flex-col p-5 sm:p-6 rounded-xl border border-border shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <div>
                <h3 class="text-base font-bold text-foreground flex items-center gap-2">
                    <i data-lucide="file-check-2" class="size-5 text-primary"></i>
                    <span>Rincian Hasil Audit SEO</span>
                </h3>
                <p class="text-xs text-muted-foreground mt-0.5" id="modalArticleTitle">-</p>
            </div>
            <button type="button" onclick="closeScoreDetailModal()" class="rounded p-1 text-muted-foreground hover:text-foreground hover:bg-muted transition-colors">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>

        <div id="modalScoreContent" class="overflow-y-auto space-y-4 pr-1 text-xs">
            <div class="flex items-center justify-center py-8">
                <svg class="animate-spin size-6 text-primary" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-border text-xs">
            <div class="text-muted-foreground font-mono text-[11px]" id="modalScoreAuditMeta">
                Audit Log
            </div>
            <div class="flex items-center gap-2">
                <a id="modalBtnEditArticle" href="#" target="_blank" class="inline-flex items-center gap-1.5 rounded-md border border-input bg-background px-3 py-1.5 font-semibold text-foreground hover:bg-muted transition-colors">
                    <i data-lucide="edit" class="size-3.5 text-primary"></i>
                    <span>Buka di Editor</span>
                </a>
                <button type="button" onclick="closeScoreDetailModal()" class="rounded-md bg-primary text-white px-4 py-1.5 font-bold hover:bg-primary/90 transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
async function openScoreDetailModal(logId) {
    const modal = document.getElementById('scoreDetailModal');
    const content = document.getElementById('modalScoreContent');
    const titleEl = document.getElementById('modalArticleTitle');
    const metaEl = document.getElementById('modalScoreAuditMeta');
    const editBtn = document.getElementById('modalBtnEditArticle');

    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    content.innerHTML = `
        <div class="flex items-center justify-center py-8">
            <svg class="animate-spin size-6 text-primary" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    `;

    try {
        const res = await fetch(`/api/scoring/log/${logId}`);
        const json = await res.json();

        if (json.status === 'success') {
            const d = json.data;
            const s = d.score;

            titleEl.textContent = d.article ? d.article.headline : `Artikel ID #${s.article_id}`;
            metaEl.textContent = `Used by: ${d.used_by_name} • Audit: ${s.created_at || '-'}`;
            editBtn.href = `/post/edit/${s.article_id}`;

            let paramsHtml = '';
            if (Array.isArray(s.details) && s.details.length > 0) {
                paramsHtml = s.details.map(p => `
                    <div class="p-2.5 rounded border border-border bg-card flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-foreground">${p.title}</div>
                            <div class="text-[11px] text-muted-foreground mt-0.5">${p.message}</div>
                        </div>
                        <span class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded ${p.status === 'pass' ? 'bg-emerald-500/10 text-emerald-600' : (p.status === 'warning' ? 'bg-amber-500/10 text-amber-600' : 'bg-red-500/10 text-red-600')} shrink-0">
                            ${p.status}
                        </span>
                    </div>
                `).join('');
            } else {
                paramsHtml = '<div class="text-muted-foreground italic">Detail parameter tidak tercatat.</div>';
            }

            let aiHtml = '';
            if (s.ai_feedback) {
                aiHtml = `
                    <div class="p-3 rounded-lg border border-border bg-muted/20 space-y-2">
                        <div class="font-bold text-foreground flex items-center gap-1.5 text-xs">
                            <i data-lucide="sparkles" class="size-3.5 text-accent"></i>
                            <span>Feedback AI (Google Gemini)</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                            <div>Intent: <strong>${s.ai_feedback.search_intent?.score || 0}%</strong></div>
                            <div>Depth: <strong>${s.ai_feedback.content_depth?.score || 0}%</strong></div>
                            <div>Click: <strong>${s.ai_feedback.click_readiness?.score || 0}%</strong></div>
                            <div>Naturalness: <strong>${s.ai_feedback.keyword_naturalness?.score || 0}%</strong></div>
                        </div>
                    </div>
                `;
            }

            content.innerHTML = `
                <!-- Summary Cards -->
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="p-3 rounded-lg border border-border bg-muted/20">
                        <div class="text-[10px] uppercase font-bold text-muted-foreground">Skor Akhir</div>
                        <div class="text-2xl font-extrabold font-mono text-foreground">${Math.round(s.final_score)}%</div>
                        <div class="text-[10px] font-bold text-primary uppercase mt-0.5">${s.score_category}</div>
                    </div>
                    <div class="p-3 rounded-lg border border-border bg-muted/20">
                        <div class="text-[10px] uppercase font-bold text-muted-foreground">Rule Teknis (60%)</div>
                        <div class="text-2xl font-extrabold font-mono text-foreground">${Math.round(s.rule_score)}%</div>
                    </div>
                    <div class="p-3 rounded-lg border border-border bg-muted/20">
                        <div class="text-[10px] uppercase font-bold text-muted-foreground">AI Quality (40%)</div>
                        <div class="text-2xl font-extrabold font-mono text-foreground">${s.ai_score !== null ? Math.round(s.ai_score) + '%' : 'N/A'}</div>
                    </div>
                </div>

                ${aiHtml}

                <div class="space-y-2">
                    <div class="font-bold text-foreground text-xs uppercase tracking-wider">12 Parameter Teknis:</div>
                    <div class="space-y-1.5 max-h-64 overflow-y-auto pr-1">
                        ${paramsHtml}
                    </div>
                </div>
            `;

            if (typeof lucide !== 'undefined') lucide.createIcons();
        } else {
            content.innerHTML = `<div class="p-4 text-destructive text-center">${json.message || 'Gagal memuat data.'}</div>`;
        }
    } catch (err) {
        content.innerHTML = `<div class="p-4 text-destructive text-center">Terjadi kesalahan saat memuat log.</div>`;
    }
}

function closeScoreDetailModal() {
    const modal = document.getElementById('scoreDetailModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}
</script>
<?= $this->endSection() ?>

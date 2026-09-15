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
                <i data-lucide="file-check-2" class="size-6 text-primary"></i>
                <span>Detail Audit SEO #<?= esc($score['id']) ?></span>
            </h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">
                Snapshot log penilaian On-Page SEO untuk artikel berita ini.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= base_url('scoring/logs') ?>" class="inline-flex items-center gap-1.5 rounded-md border border-input bg-background px-3 py-2 text-xs font-semibold text-foreground hover:bg-muted transition-colors shadow-2xs">
                <i data-lucide="arrow-left" class="size-4"></i>
                <span>Kembali ke Riwayat Log</span>
            </a>
            <?php if (!empty($article)): ?>
            <a href="<?= base_url('post/edit/' . (int)$article['articleID']) ?>" target="_blank" class="inline-flex items-center gap-1.5 rounded-md bg-[#2B6CB0] px-3.5 py-2 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90 transition-colors">
                <i data-lucide="edit-3" class="size-4"></i>
                <span>Buka di Editor</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Main Detail Page Container -->
<div class="flex flex-col min-h-[calc(100vh-220px)] space-y-6">

    <!-- 1. Article Snapshot Information Card -->
    <div class="surface-card p-5 sm:p-6 rounded-xl border border-border shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-border pb-3">
            <div class="flex items-center gap-2">
                <i data-lucide="newspaper" class="size-4 text-primary"></i>
                <h2 class="text-sm font-bold uppercase tracking-wider text-foreground">Artikel Terkait</h2>
            </div>
            <div class="flex items-center gap-3 text-xs text-muted-foreground font-mono">
                <span>Diaudit oleh: <strong class="text-foreground font-sans"><?= esc($usedByName) ?></strong></span>
                <span>&bull;</span>
                <span>Waktu Audit: <strong class="text-foreground"><?= !empty($score['created_at']) ? date('d M Y H:i:s', strtotime($score['created_at'])) : '-' ?></strong></span>
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex items-center gap-2 text-xs text-muted-foreground font-mono">
                <span class="inline-flex items-center px-2 py-0.5 rounded bg-primary/10 text-primary font-bold">
                    ID: <?= esc($score['article_id']) ?>
                </span>
                <span>&bull;</span>
                <span class="uppercase font-semibold text-foreground">
                    <?= !empty($article['status']) ? esc($article['status']) : 'Draft' ?>
                </span>
                <?php if (!empty($score['model_used'])): ?>
                    <span>&bull;</span>
                    <span class="text-accent font-medium">Model: <?= esc($score['model_used']) ?></span>
                <?php endif; ?>
            </div>

            <h3 class="text-base sm:text-lg font-bold text-foreground leading-snug">
                <?= !empty($article['headline']) ? esc($article['headline']) : '(Judul artikel tidak ditemukan atau telah dihapus)' ?>
            </h3>

            <?php if (!empty($article['summary'])): ?>
                <p class="text-xs text-muted-foreground line-clamp-2">
                    <?= esc($article['summary']) ?>
                </p>
            <?php endif; ?>

            <!-- Keywords / Tags -->
            <div class="pt-2 border-t border-border/60">
                <div class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground mb-1.5 flex items-center gap-1.5">
                    <i data-lucide="tag" class="size-3 text-primary"></i>
                    <span>Kata Kunci / Tag Terdaftar:</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <?php if (!empty($keywords)): ?>
                        <?php foreach ($keywords as $kw): ?>
                            <span class="inline-flex items-center rounded bg-primary/10 border border-primary/20 px-2 py-0.5 text-[11px] font-medium text-primary">
                                #<?= esc($kw) ?>
                            </span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-xs text-muted-foreground italic">Belum ada kata kunci/tag terdaftar.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Scoring Results Section (Pre-rendered for this log snapshot) -->
    <div id="scoringResultsSection" class="space-y-6">

        <!-- Top Overview Card: Full Circle Score Gauge (Overall) + 2 Semicircle Gauges (Rule & AI) -->
        <div class="surface-card p-6 rounded-xl border border-border shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
                
                <!-- Full Circle Gauge (Overall Score: 1 Lingkaran Penuh) -->
                <div class="flex flex-col items-center justify-center text-center p-4 border-b lg:border-b-0 lg:border-r border-border">
                    <div class="relative w-44 h-44 flex items-center justify-center">
                        <svg class="w-44 h-44 overflow-visible" viewBox="0 0 100 100">
                            <!-- Background Full Circle Track -->
                            <circle class="full-circle-track" cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="8"/>
                            <!-- Active Animated Full Circle Bar -->
                            <circle id="scoreCircleBar" class="full-circle-bar" cx="50" cy="50" r="40" fill="none" stroke="#10b981" stroke-width="8" stroke-linecap="round"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span id="gaugeScoreText" class="text-3xl sm:text-4xl font-extrabold font-mono tracking-tight text-foreground leading-none">
                                <?= round($score['final_score']) ?>%
                            </span>
                            <span class="text-[10px] uppercase font-bold text-muted-foreground tracking-wider mt-1.5">Skor Akhir</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span id="gaugeCategoryLabel" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold">
                            <?= esc($score['score_category']) ?>
                        </span>
                    </div>
                    <p class="text-[11px] text-muted-foreground mt-1">Status Kesiapan SEO On-Page</p>
                </div>

                <!-- Sub-score Breakdown: Rule (60%) vs AI (40%) with clean semicircle gauges -->
                <div class="lg:col-span-2 space-y-4">
                    <div>
                        <h3 class="text-sm font-bold text-foreground">Komposisi Skor Hybrid</h3>
                        <p class="text-xs text-muted-foreground">Kombinasi audit teknis deterministik dan penilaian kedalaman konten editorial.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Rule Score Box with Semicircle Gauge -->
                        <div class="p-4 rounded-xl border border-border bg-muted/20 flex flex-col justify-between space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-foreground flex items-center gap-1.5">
                                    <i data-lucide="check-square" class="size-3.5 text-primary"></i>
                                    <span>Rule-Based Teknis</span>
                                </span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-primary/10 text-primary uppercase">Bobot 60%</span>
                            </div>

                            <!-- Semicircle Gauge for Rule Score -->
                            <div class="flex items-center gap-3.5 py-1">
                                 <div class="relative w-32 h-20 shrink-0 flex flex-col items-center justify-end">
                                     <svg class="w-32 h-20 overflow-visible" viewBox="0 0 100 56">
                                         <path class="semicircle-track" d="M 12,50 A 38,38 0 0,1 88,50" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round"/>
                                         <path id="ruleSemicircleBar" class="semicircle-bar" d="M 12,50 A 38,38 0 0,1 88,50" fill="none" stroke="#10b981" stroke-width="10" stroke-linecap="round"/>
                                     </svg>
                                     <div class="absolute bottom-1 flex flex-col items-center justify-center pointer-events-none">
                                         <span class="text-xl font-extrabold font-mono text-foreground leading-none" id="ruleScoreVal">
                                             <?= round($score['rule_score']) ?>%
                                         </span>
                                     </div>
                                 </div>
                                 <div class="text-[11px] text-muted-foreground leading-snug">
                                     12 parameter teknis: struktur heading, panjang naskah, link, slug, &amp; kerapatan tag.
                                 </div>
                            </div>
                        </div>

                        <!-- AI Score Box with Semicircle Gauge -->
                        <div class="p-4 rounded-xl border border-border bg-muted/20 flex flex-col justify-between space-y-3" id="aiScoreBadge">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-foreground flex items-center gap-1.5">
                                    <i data-lucide="sparkles" class="size-3.5 text-accent"></i>
                                    <span>AI Quality Analysis</span>
                                </span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-accent/15 text-accent uppercase">Bobot 40%</span>
                            </div>

                            <!-- Semicircle Gauge for AI Score -->
                            <div class="flex items-center gap-3.5 py-1">
                                <div class="relative w-32 h-20 shrink-0 flex flex-col items-center justify-end">
                                    <svg class="w-32 h-20 overflow-visible" viewBox="0 0 100 56">
                                        <path class="semicircle-track" d="M 12,50 A 38,38 0 0,1 88,50" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round"/>
                                        <path id="aiSemicircleBar" class="semicircle-bar" d="M 12,50 A 38,38 0 0,1 88,50" fill="none" stroke="#6366f1" stroke-width="10" stroke-linecap="round"/>
                                    </svg>
                                    <div class="absolute bottom-1 flex flex-col items-center justify-center pointer-events-none">
                                        <span class="text-xl font-extrabold font-mono text-foreground leading-none" id="aiScoreVal">
                                            <?= $score['ai_score'] !== null ? round($score['ai_score']) . '%' : 'N/A' ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-[11px] text-muted-foreground leading-snug">
                                    Analisis Gemini AI: search intent, kedalaman konten, click-readiness, dan naturalness.
                                    <span id="aiScoreMissingNote" class="<?= $score['ai_score'] !== null ? 'hidden' : '' ?> text-[10px] text-amber-600 font-semibold mt-0.5 block">⚠️ AI tidak tersedia (fallback rule 100%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Two Columns: Technical 12 Rules (Left) vs AI Feedback & Recommendations (Right) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Left: 12 Rule-Based Parameters Detailed List -->
            <div class="surface-card p-5 sm:p-6 rounded-xl border border-border shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="list-checks" class="size-4 text-primary"></i>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-foreground">12 Parameter Teknis SEO</h3>
                    </div>
                    <span class="text-[11px] font-mono text-muted-foreground">Rule Engine</span>
                </div>

                <div id="ruleParametersList" class="space-y-2.5 max-h-[700px] overflow-y-auto pr-1">
                    <!-- Dynamic rendering via JS -->
                </div>
            </div>

            <!-- Right: AI Evaluation & Recommendations -->
            <div class="space-y-6">

                <!-- AI Evaluation Breakdown -->
                <div class="surface-card p-5 sm:p-6 rounded-xl border border-border shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-border pb-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="sparkles" class="size-4 text-accent"></i>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-foreground">Analisis Kualitas Gemini AI</h3>
                        </div>
                        <span class="text-[11px] font-mono text-muted-foreground">Editorial Insight</span>
                    </div>

                    <div id="aiFeedbackSection" class="space-y-3">
                        <!-- Dynamic rendering via JS -->
                    </div>
                </div>

                <!-- Consolidated Actionable Recommendations -->
                <div class="surface-card p-5 sm:p-6 rounded-xl border border-border shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-border pb-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="check-circle" class="size-4 text-emerald-600"></i>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-foreground">Rekomendasi Perbaikan</h3>
                        </div>
                        <span class="text-[11px] font-mono text-muted-foreground">Action Items</span>
                    </div>

                    <ul id="recommendationsList" class="space-y-2">
                        <!-- Dynamic rendering via JS -->
                    </ul>
                </div>

                <!-- History Log Card (if any) -->
                <?php if (!empty($scoreHistory)): ?>
                <div class="surface-card p-4 sm:p-5 rounded-xl border border-border shadow-sm space-y-3">
                    <div class="flex items-center justify-between border-b border-border pb-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-foreground flex items-center gap-1.5">
                            <i data-lucide="history" class="size-3.5 text-primary"></i>
                            <span>Riwayat Skor Artikel Ini</span>
                        </h4>
                        <span class="text-[10px] font-mono text-muted-foreground"><?= count($scoreHistory) ?> entri</span>
                    </div>
                    <div id="scoreHistoryList" class="space-y-1 divide-y divide-border/40">
                        <?php foreach ($scoreHistory as $h): ?>
                            <div class="flex items-center justify-between py-1.5 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold font-mono text-foreground"><?= round($h['final_score']) ?>/100</span>
                                    <span class="text-muted-foreground font-mono text-[10px]"><?= date('d M Y H:i', strtotime($h['created_at'])) ?></span>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-muted uppercase"><?= esc($h['score_category']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- 3. Methodology Footnote Card -->
    <div class="scoring-footnote-card mt-auto p-5 sm:p-6 rounded-xl border-2 border-dashed border-[#2B6CB0]/60 bg-[#2B6CB0]/10 dark:bg-[#2B6CB0]/15 shadow-sm space-y-3.5 text-xs text-foreground/85 leading-relaxed">
        <div class="flex items-center gap-2 text-[#2B6CB0] dark:text-[#63B3ED] font-bold border-b border-[#2B6CB0]/30 pb-2.5 text-xs uppercase tracking-wider">
            <i data-lucide="book-open" class="size-4"></i>
            <span>Catatan Metodologi &amp; Parameter Penilaian SEO On-Page</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-1">
            <div class="space-y-2">
                <h4 class="font-bold text-foreground text-xs">1. Formula Skor Hybrid (60% Rule + 40% AI)</h4>
                <p>
                    Skor akhir dihitung secara proporsional. <strong>60% skor</strong> berasal dari engine evaluasi teknis deterministik (12 parameter on-page) yang menguji kepatuhan struktur HTML terhadap algoritma perayapan mesin pencari (Googlebot). <strong>40% skor</strong> dinilai melalui analisis konten kontekstual oleh model Generative AI (Google Gemini) yang mengevaluasi kepuasan pembaca dan relevansi semantik.
                </p>
                <p class="text-muted-foreground italic">
                    Catatan: Jika artikel memiliki panjang di bawah 150 kata atau API AI tidak dapat diakses, sistem secara otomatis mengadopsi 100% skor teknis agar operasional redaksi tidak terhambat.
                </p>
            </div>

            <div class="space-y-2">
                <h4 class="font-bold text-foreground text-xs">2. Dua Belas (12) Parameter Teknis yang Digunakan</h4>
                <ul class="list-disc pl-4 space-y-1.5">
                    <li><strong>Panjang Judul (Title Length):</strong> Optimal 50–70 karakter agar tampil utuh di SERP Google.</li>
                    <li><strong>Kata Kunci di Judul:</strong> Verifikasi kemunculan tag kata kunci utama di judul berita.</li>
                    <li><strong>Panjang Ringkasan (Meta Description):</strong> Optimal 120–160 karakter untuk snippet search engine.</li>
                    <li><strong>Kata Kunci di Ringkasan:</strong> Keberadaan kata kunci terdaftar dalam ringkasan artikel.</li>
                    <li><strong>Panjang Konten:</strong> Standar minimal 300 kata (hard news) atau 600+ kata (in-depth/feature).</li>
                    <li><strong>Kata Kunci di Lead Paragraf:</strong> Kemunculan kata kunci di 100 kata pertama body artikel.</li>
                    <li><strong>Format URL Slug:</strong> Penggunaan huruf kecil, pemisah tanda hubung (-), dan bebas karakter khusus.</li>
                    <li><strong>Struktur Heading (H2/H3):</strong> Keberadaan subjudul terstruktur untuk memindai poin berita.</li>
                    <li><strong>Keterbacaan Paragraf:</strong> Paragraf ringkas (rata-rata &lt; 60–80 kata) agar nyaman di smartphone.</li>
                    <li><strong>Foto Utama &amp; Alt Text:</strong> Keberadaan foto sampul dan teks alternatif gambar pendukung.</li>
                    <li><strong>Tautan Internal/Eksternal:</strong> Minimal 1–2 tautan rujukan atau berita terkait (internal links).</li>
                    <li><strong>Kerapatan Tag &amp; Keyword:</strong> 3–8 tag terdaftar dengan frekuensi alami tanpa keyword stuffing.</li>
                </ul>
            </div>
        </div>

        <div class="pt-3 border-t border-[#2B6CB0]/30 text-[11px] text-muted-foreground leading-normal">
            <strong>Apa yang TIDAK Direpresentasikan oleh Skor Ini:</strong> Skor ini adalah panduan kesiapan teknis editorial on-page (on-page readiness), bukan jaminan artikel pasti menduduki peringkat #1 Google.
        </div>
    </div>

</div>

<!-- Pass server data to JS for detail page -->
<script>
    window.IS_DETAIL_PAGE = true;
    window.INITIAL_ARTICLE_LOADED = true;
    window.INITIAL_SCORE_DATA = <?= json_encode($score) ?>;
</script>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/pages/scoring.js"></script>
<?= $this->endSection() ?>

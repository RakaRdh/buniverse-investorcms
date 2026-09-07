<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> — Investor.id Live Preview</title>
    
    <!-- Fonts & CDN Tailwind -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Merriweather:ital,wght@0,300;0,400;0,700;1,400&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&display=swap" rel="stylesheet">
    
    <!-- Static Compiled Tailwind CSS & Design System -->
    <link rel="stylesheet" href="/css/tailwind.min.css">
    <link rel="stylesheet" href="/css/newsroom.css">
    <link rel="stylesheet" href="/css/pages/editor.css">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        .preview-article-body {
            font-family: 'Merriweather', Georgia, serif;
            font-size: 1.0625rem;
            line-height: 1.8;
            color: #1e293b;
        }
        .preview-article-body p {
            margin-bottom: 1.35rem;
        }
        .preview-article-body h2 {
            font-family: 'Inter', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #0f172a;
        }
        .preview-article-body h3 {
            font-family: 'Inter', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: #0f172a;
        }
        .preview-article-body blockquote {
            border-left: 4px solid #2B6CB0;
            padding-left: 1rem;
            font-style: italic;
            color: #475569;
            margin: 1.5rem 0;
        }
        .preview-article-body img {
            border-radius: 0.5rem;
            margin: 1.5rem auto;
            max-width: 100%;
        }
        .preview-article-body .article-embed-tradingview {
            background: #0b1329 !important;
            border: 1px solid #1e293b !important;
            border-radius: 12px;
            padding: 16px;
            margin: 20px 0;
            color: #f8fafc;
            font-family: 'Inter', sans-serif;
        }
        .preview-article-body .article-embed-stock-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-left: 4px solid #4f46e5;
            border-radius: 8px;
            padding: 14px 18px;
            margin: 16px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            font-family: 'Inter', sans-serif;
        }
        .device-frame-desktop { width: 100%; max-width: 860px; }
        .device-frame-tablet { width: 100%; max-width: 640px; }
        .device-frame-mobile { width: 100%; max-width: 390px; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col antialiased">

    <!-- Top Simulation Header -->
    <header class="bg-slate-900 text-white px-6 py-3 flex items-center justify-between border-b border-slate-800 shrink-0 sticky top-0 z-50 shadow-md">
        <div class="flex items-center gap-3">
            <span class="grid size-7 place-items-center rounded bg-primary text-xs font-black text-white">ID</span>
            <div>
                <span class="font-bold text-xs tracking-wider uppercase text-white block">LIVE SIMULATION PREVIEW</span>
                <span class="text-[10px] text-slate-400">Portal Berita Investor.id &bull; Pratinjau Tampilan Pembaca</span>
            </div>
        </div>

        <!-- Viewport Size Switcher Toolbar -->
        <div class="flex items-center gap-1 bg-slate-800 p-1 rounded-lg border border-slate-700 text-xs">
            <button type="button" onclick="setDeviceView('desktop')" id="btnDesktop" class="device-btn flex items-center gap-1.5 px-3 py-1 rounded bg-primary text-white font-bold transition-all">
                <i data-lucide="monitor" class="size-3.5"></i>
                <span class="hidden sm:inline">Desktop</span>
            </button>
            <button type="button" onclick="setDeviceView('tablet')" id="btnTablet" class="device-btn flex items-center gap-1.5 px-3 py-1 rounded text-slate-400 hover:text-white transition-all">
                <i data-lucide="tablet" class="size-3.5"></i>
                <span class="hidden sm:inline">Tablet (640px)</span>
            </button>
            <button type="button" onclick="setDeviceView('mobile')" id="btnMobile" class="device-btn flex items-center gap-1.5 px-3 py-1 rounded text-slate-400 hover:text-white transition-all">
                <i data-lucide="smartphone" class="size-3.5"></i>
                <span class="hidden sm:inline">Mobile (390px)</span>
            </button>
        </div>

        <button type="button" onclick="window.close()" class="inline-flex items-center gap-1.5 text-xs text-slate-300 hover:text-white rounded px-2.5 py-1.5 hover:bg-slate-800 transition-colors">
            <i data-lucide="x" class="size-4"></i>
            <span class="hidden sm:inline">Tutup Tab</span>
        </button>
    </header>

    <!-- Main 2-Column Split Workspace (Reference Layout) -->
    <div class="flex-1 max-w-7xl mx-auto w-full p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Sidebar: Article Metadata & Actions (4 cols) -->
        <div class="lg:col-span-4 space-y-4">
            <div class="surface-card p-5 space-y-4 shadow-sm">
                
                <!-- Brand Badge -->
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-red-600 text-white font-black text-[10px] uppercase tracking-widest">
                    INVESTOR.ID
                </div>

                <div>
                    <h2 class="text-base font-bold text-foreground leading-tight"><?= esc($article['title']) ?></h2>
                    <p class="text-xs text-muted-foreground mt-1 flex items-center gap-1.5">
                        <i data-lucide="calendar" class="size-3.5 text-primary"></i>
                        <span><?= esc($article['publish_date']) ?> WIB</span>
                    </p>
                </div>

                <hr class="border-border">

                <!-- Metadata List -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Metadata Artikel</h3>
                    
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-border/60">
                            <span class="text-muted-foreground">Author:</span>
                            <span class="font-bold text-foreground"><?= esc($article['author']) ?></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-border/60">
                            <span class="text-muted-foreground">Kanal:</span>
                            <span class="font-bold text-primary"><?= esc($article['kanal'] ?? $article['category'] ?? 'Market') ?></span>
                        </div>
                        <?php if (!empty($article['sub_kanal'])): ?>
                        <div class="flex justify-between py-1 border-b border-border/60">
                            <span class="text-muted-foreground">Sub-Kanal:</span>
                            <span class="font-bold text-foreground"><?= esc($article['sub_kanal']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between py-1 border-b border-border/60">
                            <span class="text-muted-foreground">Tier Akses:</span>
                            <span class="font-bold <?= !empty($article['is_premium']) ? 'text-amber-600' : 'text-foreground' ?>">
                                <?= !empty($article['is_premium']) ? '★ Member Premium' : 'Gratis / Publik' ?>
                            </span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-border/60">
                            <span class="text-muted-foreground">Sumber Berita:</span>
                            <span class="font-medium text-foreground"><?= esc($article['sumber_berita'] ?? $article['source'] ?? 'Investor.id') ?></span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-muted-foreground">Status:</span>
                            <span class="inline-flex rounded bg-success/15 px-2 py-0.5 font-bold text-success text-[10px] uppercase">
                                <?= esc($article['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($article['tags'])): ?>
                <div class="pt-2 border-t border-border">
                    <span class="text-[11px] font-bold text-muted-foreground block mb-1.5">Kata Kunci (Tags):</span>
                    <div class="flex flex-wrap gap-1">
                        <?php foreach (explode(',', $article['tags']) as $t): ?>
                            <?php if (trim($t)): ?>
                                <span class="rounded bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground">#<?= esc(trim($t)) ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="pt-3 border-t border-border space-y-2">
                    <button type="button" onclick="window.close()" class="flex w-full items-center justify-center gap-2 rounded-md border border-input bg-background py-2.5 text-xs font-bold text-foreground hover:bg-muted transition-colors shadow-xs">
                        <i data-lucide="arrow-left" class="size-4"></i>
                        <span>Kembali ke Editor</span>
                    </button>
                </div>
            </div>

            <p class="text-[11px] text-slate-500 italic px-2">
                * Tampilan di sebelah kanan adalah simulasi portal pembaca. Hasil akhir dapat sedikit berbeda sesuai resolusi browser pembaca.
            </p>
        </div>

        <!-- Right Main Viewport: Reader Portal Simulation (8 cols) -->
        <div class="lg:col-span-8 flex justify-center">
            <div id="deviceFrame" class="device-frame-desktop bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden transition-all duration-300">
                
                <!-- Mock Portal Header Bar -->
                <div class="bg-[#1F4E79] text-white px-6 py-4 flex items-center justify-between border-b border-[#183F63]">
                    <div class="flex items-center gap-3">
                        <span class="text-lg font-black tracking-tight">INVESTOR<span class="text-red-400">.ID</span></span>
                        <span class="text-xs text-white/70 border-l border-white/20 pl-3 uppercase tracking-wider font-semibold"><?= esc($article['kanal'] ?? 'MARKET') ?></span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-white/80">
                        <span><?= date('l, d F Y') ?></span>
                    </div>
                </div>

                <!-- Article Content Canvas -->
                <article class="p-6 sm:p-10 space-y-6">
                    
                    <!-- Upper Deck & Badges -->
                    <div class="flex flex-wrap items-center gap-2">
                        <?php if (!empty($article['upper_deck'])): ?>
                            <span class="inline-flex rounded bg-amber-500/15 border border-amber-500/30 px-2.5 py-0.5 text-[11px] font-bold text-amber-800 uppercase tracking-wider">
                                <?= esc($article['upper_deck']) ?>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($article['is_premium'])): ?>
                            <span class="inline-flex items-center gap-1 rounded bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white uppercase tracking-wider shadow-2xs">
                                <i data-lucide="star" class="size-3"></i> PREMIUM
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Headline Title -->
                    <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-slate-950 font-serif leading-tight">
                        <?= esc($article['title']) ?>
                    </h1>

                    <!-- Summary / Lead Box -->
                    <?php if (!empty($article['summary'])): ?>
                    <div class="p-4 rounded-xl bg-slate-50 border-l-4 border-[#1F4E79] text-slate-700 text-base leading-relaxed font-sans italic">
                        <?= esc($article['summary']) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Author Byline & Date -->
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3 border-y border-slate-200 text-xs text-slate-500 font-sans">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-900"><?= esc($article['author']) ?></span>
                            <span>&bull;</span>
                            <span><?= esc($article['publish_date']) ?> WIB</span>
                        </div>
                        <div class="flex items-center gap-4 text-slate-400">
                            <span class="flex items-center gap-1"><i data-lucide="share-2" class="size-3.5"></i> Bagikan</span>
                            <span class="flex items-center gap-1"><i data-lucide="bookmark" class="size-3.5"></i> Simpan</span>
                        </div>
                    </div>

                    <!-- Featured Hero Image -->
                    <figure class="space-y-2">
                        <div class="relative rounded-xl overflow-hidden bg-slate-900 shadow-md aspect-video max-h-[440px]">
                            <img src="<?= esc($article['thumbnail'] ?: '/img/article1.jpg') ?>" alt="<?= esc($article['title']) ?>" class="w-full h-full object-cover">
                        </div>
                        <figcaption class="text-[11px] text-slate-500 italic">
                            Foto: <?= esc($article['sumber_berita'] ?? 'Investor.id') ?>
                        </figcaption>
                    </figure>

                    <!-- 3 Poin Utama Highlight (If Any) -->
                    <?php if (!empty($article['points']) && is_array($article['points'])): ?>
                    <div class="p-5 rounded-xl border border-primary/20 bg-primary/5 space-y-3 font-sans">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-[#1F4E79] flex items-center gap-1.5">
                            <i data-lucide="sparkles" class="size-4 text-[#1F4E79]"></i>
                            <span>POIN PENTING BERITA</span>
                        </h3>
                        <ul class="space-y-2 text-xs text-slate-700">
                            <?php foreach ($article['points'] as $pt): ?>
                                <?php if (trim($pt)): ?>
                                <li class="flex items-start gap-2">
                                    <span class="size-1.5 rounded-full bg-[#1F4E79] mt-1.5 shrink-0"></span>
                                    <span><?= esc($pt) ?></span>
                                </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Body HTML Content -->
                    <div class="preview-article-body pt-2">
                        <?= $article['content'] ?>
                    </div>

                    <!-- Article Footer Source & Tags -->
                    <div class="pt-6 border-t border-slate-200 space-y-3 font-sans text-xs">
                        <p class="text-slate-500">
                            Sumber Berita: <strong class="text-slate-800"><?= esc($article['sumber_berita'] ?? 'Investor.id / Riset') ?></strong>
                        </p>
                    </div>

                </article>

            </div>
        </div>

    </div>

    <script>
        lucide.createIcons();

        function setDeviceView(mode) {
            const frame = document.getElementById('deviceFrame');
            const btns = document.querySelectorAll('.device-btn');
            
            btns.forEach(b => {
                b.classList.remove('bg-primary', 'text-white', 'font-bold');
                b.classList.add('text-slate-400');
            });

            if (mode === 'desktop') {
                frame.className = 'device-frame-desktop bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden transition-all duration-300';
                document.getElementById('btnDesktop').classList.add('bg-primary', 'text-white', 'font-bold');
                document.getElementById('btnDesktop').classList.remove('text-slate-400');
            } else if (mode === 'tablet') {
                frame.className = 'device-frame-tablet bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden transition-all duration-300';
                document.getElementById('btnTablet').classList.add('bg-primary', 'text-white', 'font-bold');
                document.getElementById('btnTablet').classList.remove('text-slate-400');
            } else if (mode === 'mobile') {
                frame.className = 'device-frame-mobile bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden transition-all duration-300';
                document.getElementById('btnMobile').classList.add('bg-primary', 'text-white', 'font-bold');
                document.getElementById('btnMobile').classList.remove('text-slate-400');
            }
        }

        // Auto-render live TradingView widget for any embedded stock boxes
        document.querySelectorAll('.article-embed-tradingview').forEach(box => {
            const symbol = box.getAttribute('data-stock-symbol') || 'BBCA';
            const inner = box.querySelector('div[style*="height:"]') || box.querySelector('.tradingview-placeholder');
            if (inner && !inner.querySelector('iframe')) {
                inner.style.height = '320px';
                inner.style.border = 'none';
                inner.style.background = 'transparent';
                inner.innerHTML = `<iframe src="https://s.tradingview.com/widgetembed/?symbol=IDX%3A${encodeURIComponent(symbol)}&interval=D&theme=dark&style=1&timezone=Asia%2FJakarta&locale=id" style="width:100%; height:100%; border:none; border-radius:8px;" frameborder="0" allowtransparency="true" scrolling="no"></iframe>`;
            }
        });
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> — Investor.id Live Preview</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="/img/investor-logo.jpg">
    <link rel="shortcut icon" href="/img/investor-logo.jpg">
    
    <!-- Fonts: Plus Jakarta Sans (Headings) & Inter (UI/Body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Static Tailwind CSS & Lucide Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        jakarta: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        accent: {
                            50: '#EBF8FF',
                            100: '#BEE3F8',
                            500: '#2B6CB0',   // CMS Primary Corporate Blue
                            600: '#1F4E79',   // Dark Brand Navy / Blue
                            700: '#1A365D',
                        },
                        ink: {
                            50: '#F8FAFC',
                            150: '#E2E8F0',
                            300: '#CBD5E1',
                            500: '#64748B',
                            700: '#334155',
                            900: '#0F172A',
                        },
                        market: {
                            up: '#16A34A',
                            down: '#DC2626',
                            flat: '#64748B',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="/css/newsroom.css">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        .article-body {
            font-family: 'Inter', sans-serif;
            font-size: 1.0625rem;
            line-height: 1.85;
            color: #0F172A;
        }
        .article-body p {
            margin-bottom: 1.35rem;
        }
        .article-body h2 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #0F172A;
            line-height: 1.3;
        }
        .article-body h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: #0F172A;
            line-height: 1.35;
        }
        .article-body blockquote {
            border-left: 4px solid #2B6CB0;
            padding-left: 1rem;
            font-style: italic;
            color: #475569;
            margin: 1.5rem 0;
        }
        .article-body img {
            border-radius: 0.75rem;
            margin: 1.5rem auto;
            max-width: 100%;
        }
        .device-frame-desktop { width: 100%; max-width: 900px; }
        .device-frame-tablet { width: 100%; max-width: 640px; }
        .device-frame-mobile { width: 100%; max-width: 390px; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col antialiased font-sans">

    <!-- Top Simulation Header -->
    <header class="bg-slate-900 text-white px-6 py-3 flex items-center justify-between border-b border-slate-800 shrink-0 sticky top-0 z-50 shadow-md">
        <div class="flex items-center gap-3">
            <span class="grid size-7 place-items-center rounded bg-[#2B6CB0] text-xs font-black text-white">ID</span>
            <div>
                <span class="font-bold text-xs tracking-wider uppercase text-white block">LIVE SIMULATION PREVIEW</span>
                <span class="text-[10px] text-slate-400">Portal Berita Investor.id &bull; Pratinjau Tampilan Pembaca (Frontend Sync)</span>
            </div>
        </div>

        <!-- Viewport Size Switcher Toolbar -->
        <div class="flex items-center gap-1 bg-slate-800 p-1 rounded-lg border border-slate-700 text-xs">
            <button type="button" onclick="setDeviceView('desktop')" id="btnDesktop" class="device-btn flex items-center gap-1.5 px-3 py-1 rounded bg-[#2B6CB0] text-white font-bold transition-all">
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

    <!-- Main Split Workspace: Fixed Left Sidebar + Scrollable Preview Content -->
    <div class="flex-1 w-full flex flex-col lg:flex-row min-h-[calc(100vh-53px)]">
        
        <!-- Left Sidebar: Article Metadata & Actions (Fixed on Screen) -->
        <aside class="w-full lg:w-80 xl:w-96 shrink-0 bg-white border-r border-slate-200 p-5 space-y-4 lg:min-h-[calc(100vh-53px)] lg:sticky lg:top-[53px] lg:self-start lg:max-h-[calc(100vh-53px)] lg:overflow-y-auto custom-sidebar-scroll shadow-sm z-30">
            
            <!-- Brand Badge -->
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-[#1F4E79] text-white font-black text-[10px] uppercase tracking-widest">
                INVESTOR.ID CMS
            </div>

            <div>
                <h2 class="text-base font-bold text-slate-900 leading-tight"><?= esc($article['title']) ?></h2>
                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                    <i data-lucide="calendar" class="size-3.5 text-[#2B6CB0]"></i>
                    <span><?= esc($article['publish_date']) ?> WIB</span>
                </p>
            </div>

            <hr class="border-slate-200">

            <!-- Metadata List -->
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Metadata Redaksi</h3>
                
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">Author:</span>
                        <span class="font-bold text-slate-900"><?= esc($article['author']) ?></span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">Kanal:</span>
                        <span class="font-bold text-[#2B6CB0]"><?= esc($article['kanal'] ?? $article['category'] ?? 'Market') ?></span>
                    </div>
                    <?php if (!empty($article['sub_kanal'])): ?>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">Sub-Kanal:</span>
                        <span class="font-bold text-slate-900"><?= esc($article['sub_kanal']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">Tier Akses:</span>
                        <span class="font-bold <?= !empty($article['is_premium']) ? 'text-amber-600' : 'text-slate-700' ?>">
                            <?= !empty($article['is_premium']) ? '★ Member Premium' : 'Gratis / Publik' ?>
                        </span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">Sumber Berita:</span>
                        <span class="font-medium text-slate-900"><?= esc($article['sumber_berita'] ?? $article['source'] ?? 'Investor.id') ?></span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-slate-500">Status:</span>
                        <span class="inline-flex rounded bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 font-bold text-[10px] uppercase">
                            <?= esc($article['status']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if (!empty($article['tags'])): ?>
            <div class="pt-2 border-t border-slate-200">
                <span class="text-[11px] font-bold text-slate-400 block mb-1.5">Kata Kunci (Tags):</span>
                <div class="flex flex-wrap gap-1">
                    <?php foreach (explode(',', $article['tags']) as $t): ?>
                        <?php if (trim($t)): ?>
                            <span class="rounded bg-slate-100 text-slate-700 px-2 py-0.5 text-[10px] font-medium">#<?= esc(trim($t)) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="pt-3 border-t border-slate-200 space-y-2">
                <button type="button" onclick="window.close()" class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-slate-50 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors shadow-2xs">
                    <i data-lucide="arrow-left" class="size-4"></i>
                    <span>Kembali ke Editor</span>
                </button>
            </div>

            <p class="text-[11px] text-slate-400 italic pt-2">
                * Pratinjau ini menggunakan parser shortcode dan design tokens yang identik 100% dengan portal pembaca frontend Investor.id.
            </p>
        </aside>

        <!-- Right Main Viewport: Reader Portal Simulation -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 flex justify-center items-start overflow-y-auto">
            <div id="deviceFrame" class="device-frame-desktop bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden transition-all duration-300">
                
                <!-- Mock Portal Header Bar (Identical to Frontend/app/Views/portal/layouts/header.php) -->
                <div class="bg-[#0B1F33] text-white px-6 py-3.5 flex items-center justify-between border-b border-[#1A365D]">
                    <div class="flex items-center gap-3">
                        <span class="font-jakarta font-extrabold text-lg tracking-tight text-white">
                            INVESTOR<span class="text-[#2B6CB0]">.ID</span>
                        </span>
                        <span class="text-xs text-slate-400 border-l border-slate-700 pl-3 uppercase tracking-wider font-semibold">
                            <?= esc($article['kanal'] ?? 'MARKET') ?>
                        </span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-400 font-mono">
                        <span><?= date('l, d F Y') ?></span>
                    </div>
                </div>

                <!-- Sub-nav Strip -->
                <div class="bg-[#1A365D]/40 px-6 py-2 border-b border-slate-200/80 flex items-center gap-4 text-xs font-semibold overflow-x-auto text-slate-600">
                    <span class="text-[#2B6CB0] font-bold">Portal Berita</span>
                    <span class="hover:text-slate-900">Market</span>
                    <span class="hover:text-slate-900">Finance</span>
                    <span class="hover:text-slate-900">Macroeconomy</span>
                    <span class="hover:text-slate-900">Insight</span>
                </div>

                <!-- Article Content Canvas (Identical to Frontend/app/Views/portal/article_detail.php) -->
                <article class="p-6 sm:p-10 space-y-6">
                    
                    <!-- Breadcrumb -->
                    <nav class="flex items-center text-[10px] text-ink-500 font-semibold uppercase tracking-wider overflow-x-auto whitespace-nowrap">
                        <span>Home</span>
                        <span class="mx-2 text-ink-300">/</span>
                        <span class="text-accent-600 font-bold"><?= esc($article['kanal'] ?? 'Market') ?></span>
                        <span class="mx-2 text-ink-300">/</span>
                        <span class="text-ink-700 line-clamp-1 max-w-sm"><?= esc($article['title']) ?></span>
                    </nav>

                    <!-- Upper Deck & Stock Badges -->
                    <div class="flex flex-wrap items-center gap-2">
                        <?php if (!empty($article['upper_deck'])): ?>
                            <span class="inline-block px-2.5 py-0.5 bg-amber-50 border border-amber-200 text-amber-800 text-[10px] font-bold rounded uppercase tracking-wider">
                                <?= esc($article['upper_deck']) ?>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($article['is_premium'])): ?>
                            <span class="inline-block px-2 py-0.5 bg-amber-500 text-white text-[10px] font-bold rounded uppercase tracking-wider">
                                ★ Member Premium
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($article['stock_symbols'])): ?>
                            <?php foreach ($article['stock_symbols'] as $sym): ?>
                                <span class="bg-ink-50 border border-ink-150 rounded px-2 py-0.5 text-[10px] font-bold font-mono text-ink-900 flex items-center gap-1">
                                    <span class="text-accent-500 font-extrabold">$</span><?= esc($sym) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Article Headline Title -->
                    <h1 class="text-xl sm:text-3xl font-jakarta font-extrabold text-ink-900 leading-tight">
                        <?= esc($article['title']) ?>
                    </h1>

                    <!-- Author Byline & Date Toolbar -->
                    <div class="flex items-center justify-between border-y border-ink-150 py-3.5 text-xs text-ink-500 font-sans">
                        <div class="flex items-center gap-3">
                            <div class="size-9 rounded-full bg-accent-50 overflow-hidden flex items-center justify-center font-bold text-accent-600 border border-accent-100 text-xs shrink-0">
                                ID
                            </div>
                            <div>
                                <p class="font-bold text-ink-900 leading-tight"><?= esc($article['author'] ?? 'Redaksi Investor.id') ?></p>
                                <p class="text-[10px] text-ink-500 mt-0.5">Diterbitkan <?= date('d M Y, H:i', strtotime($article['publish_date'] ?? 'now')) ?> WIB</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5 text-ink-500">
                            <span class="text-[10px] bg-ink-50 px-2 py-1 rounded border border-ink-150"><?= number_format($article['views'] ?? 1) ?> dibaca</span>
                            
                            <!-- Bookmark / Simpan Button -->
                            <button type="button" class="flex items-center gap-1.5 px-2.5 py-1 rounded border border-ink-150 bg-white text-ink-700 text-[11px] font-semibold hover:border-accent-500 hover:text-accent-600 transition">
                                <i data-lucide="bookmark" class="size-3.5"></i>
                                <span>Simpan</span>
                            </button>

                            <!-- Copy Link -->
                            <button type="button" class="p-1.5 rounded hover:bg-ink-50 text-ink-700 hover:text-accent-600 transition" title="Bagikan">
                                <i data-lucide="share-2" class="size-3.5"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Featured Hero Image 16:9 -->
                    <figure class="space-y-2">
                        <div class="w-full aspect-[16/9] overflow-hidden rounded-xl border border-ink-150 shadow-xs bg-ink-50">
                            <img src="<?= esc($article['thumbnail'] ?: '/img/investor_placeholder.jpg') ?>" alt="<?= esc($article['title']) ?>" class="w-full h-full object-cover">
                        </div>
                        <figcaption class="text-[11px] text-ink-500 italic">
                            Foto: <?= esc($article['sumber_berita'] ?? 'Investor.id') ?>
                        </figcaption>
                    </figure>

                    <!-- 🔶 Box Highlight AI: "Singkatnya" (Poin Penting) -->
                    <?php if (!empty($article['points']) && is_array($article['points'])): ?>
                        <div class="bg-accent-50 border-l-4 border-accent-500 p-4 sm:p-5 rounded-r-lg">
                            <div class="flex items-center gap-2 mb-2 text-accent-600 font-jakarta font-bold text-xs uppercase tracking-wider">
                                <svg class="size-4 text-accent-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span>Singkatnya (AI Summary)</span>
                            </div>
                            <ul class="space-y-2 text-xs text-ink-900 leading-relaxed font-sans">
                                <?php foreach ($article['points'] as $pt): ?>
                                    <?php if (trim($pt)): ?>
                                        <li class="flex items-start gap-2">
                                            <span class="size-1.5 rounded-full bg-accent-500 mt-1.5 shrink-0"></span>
                                            <span class="font-medium"><?= esc($pt) ?></span>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php elseif (!empty($article['summary'])): ?>
                        <div class="bg-accent-50 border-l-4 border-accent-500 p-4 rounded-r-lg text-xs text-ink-700 leading-relaxed font-semibold">
                            <?= esc($article['summary']) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Body HTML Content (Parsed Embeds) -->
                    <div class="article-body prose max-w-none text-ink-900 pt-2">
                        <?= $article['content'] ?>
                    </div>

                    <!-- Multi-page Article Pagination (Page Break Preview) -->
                    <?php if (!empty($pagination) && !empty($pagination['is_multipage'])): ?>
                        <div class="my-6 p-4 bg-ink-50 border border-ink-150 rounded-xl flex flex-col sm:flex-row items-center justify-between gap-4 font-sans">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-ink-500">Halaman:</span>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                        <a href="?halaman=<?= $i ?>" class="size-8 rounded-lg flex items-center justify-center text-xs font-bold transition <?= ((string)$pagination['current_page'] === (string)$i) ? 'bg-accent-600 text-white shadow-xs' : 'bg-white border border-ink-150 text-ink-700 hover:border-accent-500 hover:text-accent-600' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>
                                    <a href="?halaman=all" class="px-3 h-8 rounded-lg flex items-center justify-center text-xs font-bold transition <?= ($pagination['current_page'] === 'all') ? 'bg-accent-600 text-white shadow-xs' : 'bg-white border border-ink-150 text-ink-700 hover:border-accent-500 hover:text-accent-600' ?>">
                                        Semua Halaman
                                    </a>
                                </div>
                            </div>

                            <?php if (!empty($pagination['has_next'])): ?>
                                <a href="?halaman=<?= $pagination['next_page'] ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-accent-600 text-white font-bold text-xs shadow-xs hover:bg-accent-700 transition self-stretch sm:self-auto justify-center">
                                    <span>Halaman Selanjutnya (<?= $pagination['next_page'] ?>)</span>
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Source / Sumber Berita -->
                    <div class="pt-4 border-t border-ink-150 text-xs text-ink-500 font-sans">
                        Sumber Berita: <strong class="text-ink-900"><?= esc($article['sumber_berita'] ?? 'Investor.id / Riset') ?></strong>
                    </div>

                    <!-- Tags List -->
                    <?php if (!empty($article['tags'])): ?>
                        <div class="pt-3 border-t border-ink-150 flex flex-wrap gap-2">
                            <?php foreach (explode(',', $article['tags']) as $t): ?>
                                <?php $trimmed = trim($t); if ($trimmed): ?>
                                    <span class="text-[10px] font-bold bg-ink-50 text-ink-700 border border-ink-150 px-2.5 py-1 rounded">
                                        #<?= esc($trimmed) ?>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- 💬 Simulated Reader Comments Section (Preview) -->
                    <section class="mt-10 pt-8 border-t border-ink-150 font-sans" id="commentsSimulation">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="font-jakarta font-extrabold text-base text-ink-900 flex items-center gap-2">
                                    <i data-lucide="message-square" class="size-4 text-accent-600"></i>
                                    <span>Diskusi & Komentar Investor</span>
                                </h3>
                                <p class="text-[11px] text-ink-500 mt-0.5">Tampilan interaktif komentar pembaca yang akan tampil di frontend.</p>
                            </div>
                            <span class="px-2.5 py-1 bg-accent-50 text-accent-700 text-xs font-bold rounded-full border border-accent-200">
                                2 Komentar
                            </span>
                        </div>

                        <!-- Mock Comment Box -->
                        <div class="p-4 rounded-xl bg-ink-50/70 border border-ink-150 shadow-2xs mb-4">
                            <div class="flex items-start gap-3">
                                <div class="size-8 rounded-full bg-[#1F4E79] text-white font-bold flex items-center justify-center text-xs shrink-0">
                                    BS
                                </div>
                                <div class="flex-grow space-y-2">
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-ink-900">Budi Santoso, CFA</span>
                                            <span class="px-1.5 py-0.2 bg-accent-100 text-accent-800 rounded text-[9px] font-bold">MEMBER VIP</span>
                                        </div>
                                        <span class="text-[10px] text-ink-400">10 menit yang lalu</span>
                                    </div>
                                    <p class="text-xs text-ink-800 leading-relaxed">
                                        Analisis yang sangat komprehensif. Sentimen suku bunga BI dan kinerja kuartalan ini sangat relevan untuk strategi swing trading emiten perbankan besar.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                </article>

            </div>
        </main>
    </div>

    <!-- Mobile Floating Action Bar (Sticky at bottom on small screens) -->
    <div class="block lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-xs border-t border-slate-200 p-3 shadow-lg">
        <div class="flex items-center justify-between gap-3 max-w-md mx-auto">
            <div class="min-w-0">
                <span class="block truncate text-xs font-bold text-slate-900"><?= esc($article['title']) ?></span>
                <span class="text-[10px] text-slate-500 font-medium"><?= esc($article['kanal'] ?? 'Market') ?> &bull; <?= esc($article['status']) ?></span>
            </div>
            <button type="button" onclick="window.close()" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-[#2B6CB0] px-3.5 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-[#2B6CB0]/90 transition-colors">
                <i data-lucide="arrow-left" class="size-3.5"></i>
                <span>Kembali</span>
            </button>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function setDeviceView(mode) {
            const frame = document.getElementById('deviceFrame');
            const btns = document.querySelectorAll('.device-btn');
            
            btns.forEach(b => {
                b.classList.remove('bg-[#2B6CB0]', 'text-white', 'font-bold');
                b.classList.add('text-slate-400');
            });

            if (mode === 'desktop') {
                frame.className = 'device-frame-desktop bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden transition-all duration-300';
                document.getElementById('btnDesktop').classList.add('bg-[#2B6CB0]', 'text-white', 'font-bold');
                document.getElementById('btnDesktop').classList.remove('text-slate-400');
            } else if (mode === 'tablet') {
                frame.className = 'device-frame-tablet bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden transition-all duration-300';
                document.getElementById('btnTablet').classList.add('bg-[#2B6CB0]', 'text-white', 'font-bold');
                document.getElementById('btnTablet').classList.remove('text-slate-400');
            } else if (mode === 'mobile') {
                frame.className = 'device-frame-mobile bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden transition-all duration-300';
                document.getElementById('btnMobile').classList.add('bg-[#2B6CB0]', 'text-white', 'font-bold');
                document.getElementById('btnMobile').classList.remove('text-slate-400');
            }
        }
    </script>
</body>
</html>

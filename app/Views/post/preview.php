<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($article['title'] ?? 'Preview Artikel') ?> — Investor.id Live Preview</title>

    <?php
        $kwList = !empty($article['tags']) ? (is_array($article['tags']) ? implode(', ', $article['tags']) : $article['tags']) : '';
        $metaKeywords = 'Berita terkini, berita hari ini' . ($kwList ? ', ' . $kwList : '');
    ?>
    <meta name="keywords" content="<?= esc($metaKeywords) ?>">
    <meta name="description" content="<?= esc($article['summary'] ?? '') ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="/img/investor-logo.jpg">
    <link rel="shortcut icon" href="/img/investor-logo.jpg">
    
    <!-- Google Fonts: Open Sans (Identik 100% dengan Investor.id) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.2.3 CSS (Fondasi Utama Stylesheet Investor.id style_260410v2.css) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            --id-theme: #1E6EA3;
            --id-theme-hover: #175E8E;
            --id-theme-active: #125684;
            --id-accent-border: #4D9EC7;
            --id-text-body: #333333;
            --id-text-heading: #000000;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #F1F5F9;
            color: #232323;
            -webkit-font-smoothing: antialiased;
            margin: 0;
            padding: 0;
        }

        /* Top Simulation Header (CMS Admin Context) */
        .cms-simulation-bar {
            position: sticky;
            top: 0;
            z-index: 1050;
            background: #0f172a;
            color: #f8fafc;
            padding: 0.6rem 1.25rem;
            border-bottom: 2px solid var(--id-theme);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .device-btn {
            padding: 0.3rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid transparent;
            color: #94a3b8;
            background: transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .device-btn.active {
            background: var(--id-theme);
            color: #ffffff;
            border-color: var(--id-theme);
        }

        .device-btn:hover:not(.active) {
            color: #ffffff;
            background: rgba(255,255,255,0.08);
        }

        /* Device Wrapper Simulator */
        .preview-viewport-wrapper {
            transition: max-width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-left: auto;
            margin-right: auto;
            width: 100%;
        }

        .preview-viewport-wrapper.mode-desktop {
            max-width: 100%;
        }

        .preview-viewport-wrapper.mode-tablet {
            max-width: 768px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            border-left: 1px solid #cbd5e1;
            border-right: 1px solid #cbd5e1;
            background: #ffffff;
        }

        .preview-viewport-wrapper.mode-mobile {
            max-width: 414px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
            border-left: 1px solid #cbd5e1;
            border-right: 1px solid #cbd5e1;
            background: #ffffff;
        }

        /* Mock Public Portal Navigation Header */
        .portal-header-mock {
            background: #0B1F33;
            color: #ffffff;
            border-bottom: 2px solid var(--id-theme);
        }

        .portal-header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1rem;
        }

        .portal-brand {
            font-size: 1.25rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
            text-decoration: none;
        }

        .portal-brand span {
            color: var(--id-accent-border);
        }

        /* Grid Layout Investor.id (.id-grid) */
        .id-grid {
            width: 100%;
            padding-right: 0.75rem;
            padding-left: 0.75rem;
            margin-right: auto;
            margin-left: auto;
        }

        @media (min-width: 1200px) {
            .mode-desktop .id-grid {
                width: 1008px;
            }
        }

        @media (min-width: 1400px) {
            .mode-desktop .id-grid {
                width: 1080px;
            }
        }

        /* Kolom Artikel Utama (Max-width 740px) */
        .col-content-investor {
            max-width: 740px;
            flex: 1 0 0%;
            background: #ffffff;
            padding: 1.75rem 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
        }

        /* Kolom Sidebar (320px) */
        .id-width-right {
            width: 320px;
            flex: 0 0 auto;
        }

        @media (max-width: 991.98px) {
            .col-content-investor {
                max-width: 100%;
                padding: 1.25rem;
            }
            .id-width-right {
                width: 100%;
                margin-top: 1.5rem;
            }
        }

        /* In Tablet / Mobile Simulation, force 1-column stack */
        .mode-tablet .col-content-investor,
        .mode-mobile .col-content-investor {
            max-width: 100% !important;
            padding: 1.25rem 1rem !important;
            border: none;
            box-shadow: none;
        }

        .mode-tablet .id-width-right,
        .mode-mobile .id-width-right {
            width: 100% !important;
            padding: 0 1rem 1.5rem !rem;
        }

        /* Brand Colors & Utilities */
        .bg-theme { background-color: var(--id-theme) !important; color: #ffffff !important; }
        .text-theme { color: var(--id-theme) !important; }
        .border-theme { border-color: var(--id-theme) !important; }

        /* Judul Artikel (2.1rem Bold) */
        h1.article-title {
            font-size: 2.1rem !important;
            font-weight: 700;
            line-height: 1.25;
            color: var(--id-text-heading);
            margin-bottom: 0.75rem;
        }

        .mode-mobile h1.article-title {
            font-size: 1.6rem !important;
            line-height: 1.3;
        }

        /* Breadcrumb */
        .breadcrumb-item a {
            color: #64748b;
            text-decoration: none;
        }
        .breadcrumb-item a:hover {
            color: var(--id-theme);
        }

        /* Widget Bagikan (Capsule Pill Bar) */
        .share-pill-bar {
            border: 1px solid #cbd5e1;
            border-radius: 50rem;
            padding: 0.25rem 0.85rem;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
        }

        .share-pill-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #334155;
            letter-spacing: 0.5px;
        }

        .share-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.15s ease;
            text-decoration: none;
        }
        .share-icon-btn:hover {
            transform: scale(1.15);
        }

        /* Hero Image Container 16:10 */
        .hero-img-box {
            aspect-ratio: 16/10;
            overflow: hidden;
            border-radius: 0.5rem;
            background: #e2e8f0;
            margin-bottom: 0.5rem;
        }

        .hero-img-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Body Formatting (.fsbody2 p, 1.1rem, margin-bottom 1.8rem) */
        .fsbody2 {
            font-family: 'Open Sans', sans-serif;
            color: var(--id-text-body);
        }

        .fsbody2 p {
            font-size: 1.1rem;
            font-weight: 500;
            line-height: 1.65rem;
            margin-bottom: 1.8rem;
            color: var(--id-text-body);
        }

        .mode-mobile .fsbody2 p {
            font-size: 1.025rem;
            line-height: 1.55rem;
            margin-bottom: 1.4rem;
        }

        .body-content p a, .body-content ul li a {
            color: var(--id-theme) !important;
            word-wrap: break-word;
            text-decoration: none;
        }

        .body-content p a:hover {
            text-decoration: underline;
        }

        /* Widget Inline "Baca Juga" (.id-group) */
        .id-group {
            border-left: 3px solid var(--id-accent-border);
            padding: 0.35rem 0 0.35rem 0.75rem;
            line-height: 1.4;
            font-size: 1.05rem;
            margin-top: 1.5rem;
            margin-bottom: 1.8rem;
            background: #f8fafc;
            border-radius: 0 0.375rem 0.375rem 0;
        }

        .id-group h2, .id-group .h6 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 0.25rem;
            font-weight: 700;
        }

        .id-group a {
            color: var(--id-theme) !important;
            text-decoration: none;
            font-weight: 700;
        }
        .id-group a:hover {
            text-decoration: underline;
        }

        /* Paginasi Multi Halaman (.paging) */
        .paging {
            border: 1px solid var(--id-theme);
            color: var(--id-theme) !important;
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.15s ease;
            background: #ffffff;
        }

        .paging.active {
            background-color: var(--id-theme);
            color: #ffffff !important;
        }

        .paging:hover:not(.active) {
            background-color: #f0f7fb;
        }

        /* Highlight AI / Singkatnya Box */
        .ai-summary-box {
            background: #f0f7fb;
            border-left: 4px solid var(--id-theme);
            border-radius: 0 0.5rem 0.5rem 0;
            padding: 1.1rem 1.25rem;
            margin-bottom: 1.8rem;
        }

        /* Tag Cloud Badge Pill */
        .tag-pill {
            background-color: var(--id-theme);
            color: #ffffff;
            border-radius: 50rem;
            padding: 0.35rem 0.85rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-right: 0.35rem;
            margin-bottom: 0.5rem;
            transition: background-color 0.15s ease;
        }
        .tag-pill:hover {
            background-color: var(--id-theme-hover);
            color: #ffffff;
        }

        /* Embedded blockquotes from CKEditor */
        blockquote[contenteditable="false"] {
            border-left: 3px solid var(--id-accent-border);
            padding-left: 1rem;
            margin: 1.5rem 0;
            font-style: italic;
            color: #475569;
        }
    </style>
</head>
<body>

    <!-- Top CMS Preview Floating Simulation Toolbar -->
    <header class="cms-simulation-bar d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2.5">
            <span class="badge bg-primary px-2 py-1 text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">PREVIEW CMS</span>
            <div>
                <strong class="text-white small d-block leading-tight">Pratinjau Halaman Investor.id</strong>
                <span class="text-muted text-[11px]" style="font-size: 11px;">Paritas Visual 1:1 Frontend Portal Berita Publik</span>
            </div>
        </div>

        <!-- Viewport Size Switcher -->
        <div class="d-flex align-items-center bg-slate-800 p-1 rounded border border-slate-700">
            <button type="button" onclick="setDeviceMode('desktop')" id="btnDesktop" class="device-btn active" title="Tampilan Komputer Desktop (1080px)">
                <i data-lucide="monitor" class="size-3.5"></i>
                <span class="d-none d-sm-inline">Desktop (1080px)</span>
            </button>
            <button type="button" onclick="setDeviceMode('tablet')" id="btnTablet" class="device-btn" title="Tampilan Tablet iPad (768px)">
                <i data-lucide="tablet" class="size-3.5"></i>
                <span class="d-none d-sm-inline">Tablet (768px)</span>
            </button>
            <button type="button" onclick="setDeviceMode('mobile')" id="btnMobile" class="device-btn" title="Tampilan Smartphone (414px)">
                <i data-lucide="smartphone" class="size-3.5"></i>
                <span class="d-none d-sm-inline">Mobile (414px)</span>
            </button>
        </div>

        <!-- Actions -->
        <div class="d-flex align-items-center gap-2">
            <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-light d-inline-flex align-items-center gap-1.5" style="font-size: 11px;">
                <i data-lucide="printer" class="size-3.5"></i>
                <span class="d-none d-md-inline">Cetak</span>
            </button>
            <button type="button" onclick="window.close()" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1.5 fw-bold" style="font-size: 11px;">
                <i data-lucide="x" class="size-3.5"></i>
                <span>Tutup Preview</span>
            </button>
        </div>
    </header>

    <!-- Main Viewport Simulator Container -->
    <div id="viewportContainer" class="preview-viewport-wrapper mode-desktop py-4">
        
        <!-- Mock Portal Header -->
        <div class="portal-header-mock mb-3 shadow-xs">
            <div class="id-grid">
                <div class="portal-header-inner">
                    <div class="d-flex align-items-center gap-3">
                        <a href="javascript:void(0)" class="portal-brand">
                            INVESTOR<span>.ID</span>
                        </a>
                        <span class="text-white-50 small border-start border-secondary ps-3 text-uppercase fw-semibold d-none d-sm-inline" style="font-size: 11px;">
                            <?= esc($article['kanal'] ?? 'MARKET') ?>
                        </span>
                    </div>
                    <div class="text-white-50 small font-monospace d-none d-md-block" style="font-size: 11px;">
                        <?= date('l, d F Y') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Article Content Canvas (.id-grid) -->
        <main class="id-grid">
            <div class="row g-4 justify-content-center">

                <!-- Left Column: Kolom Konten Artikel (740px) -->
                <article class="col-content-investor">
                    
                    <!-- Breadcrumbs -->
                    <nav aria-label="breadcrumb" class="mb-2.5">
                        <ol class="breadcrumb mb-0" style="font-size: 0.82rem;">
                            <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0)" class="text-theme fw-bold"><?= esc($article['kanal'] ?? 'Market') ?></a></li>
                            <?php if (!empty($article['sub_kanal'])): ?>
                                <li class="breadcrumb-item text-muted"><?= esc($article['sub_kanal']) ?></li>
                            <?php endif; ?>
                        </ol>
                    </nav>

                    <!-- Upper Deck & Premium Flag Badges -->
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <?php if (!empty($article['upper_deck'])): ?>
                            <span class="badge bg-warning text-dark text-uppercase px-2.5 py-1 fw-bold" style="font-size: 10px;">
                                <?= esc($article['upper_deck']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($article['is_premium'])): ?>
                            <span class="badge bg-dark text-warning text-uppercase px-2.5 py-1 fw-bold" style="font-size: 10px;">
                                ★ MEMBER ONLY
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Judul Artikel (H1: 2.1rem Bold, line-height 1.25) -->
                    <h1 class="article-title">
                        <?= esc($article['title'] ?? 'Judul Artikel Masih Kosong') ?>
                    </h1>

                    <!-- Meta Bar: Penulis & Tanggal (Kiri) + Widget Bagikan Kapsul (Kanan) -->
                    <div class="row align-items-center mb-3 pb-3 border-bottom pt-1">
                        <!-- Sisi Kiri: Penulis & Tanggal -->
                        <div class="col-12 col-md mb-2 mb-md-0">
                            <div class="text-secondary" style="font-size: 0.9rem; line-height: 1.45;">
                                Penulis : <b class="text-dark"><?= esc($article['author'] ?? 'Redaksi Investor.id') ?></b><br>
                                <span class="text-muted d-inline-flex align-items-center gap-1 mt-1" style="font-size: 0.8rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
                                    </svg>
                                    <?= date('d M Y | H:i', strtotime($article['publish_date'] ?? 'now')) ?> WIB
                                </span>
                            </div>
                        </div>

                        <!-- Sisi Kanan: Widget Bagikan (Pill Bar) -->
                        <div class="col-12 col-md-auto">
                            <div class="share-pill-bar">
                                <span class="share-pill-label">BAGIKAN</span>
                                <div class="d-flex align-items-center gap-2">
                                    <!-- Facebook -->
                                    <a href="javascript:void(0)" class="share-icon-btn" title="Facebook">
                                        <svg width="17" height="17" fill="#1877F2" viewBox="0 0 16 16"><path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951"/></svg>
                                    </a>
                                    <!-- X / Twitter -->
                                    <a href="javascript:void(0)" class="share-icon-btn" title="X (Twitter)">
                                        <svg width="15" height="15" fill="#000000" viewBox="0 0 16 16"><path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z"/></svg>
                                    </a>
                                    <!-- WhatsApp -->
                                    <a href="javascript:void(0)" class="share-icon-btn" title="WhatsApp">
                                        <svg width="17" height="17" fill="#47C664" viewBox="0 0 16 16"><path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326z"/></svg>
                                    </a>
                                    <!-- Telegram -->
                                    <a href="javascript:void(0)" class="share-icon-btn" title="Telegram">
                                        <svg width="17" height="17" fill="#2AABEE" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.287 5.906q-1.168.486-4.666 2.01-.567.225-.595.442c-.03.243.275.339.69.47l.175.055c.408.133.958.288 1.243.294q.39.01.868-.32 3.269-2.206 3.374-2.23c.05-.012.12-.026.166.016s.042.12.037.141c-.03.129-1.227 1.241-1.846 1.817-.193.18-.33.307-.358.336a8 8 0 0 1-.188.186c-.38.366-.664.64.015 1.088.327.216.589.393.85.571.284.194.568.387.936.629q.14.092.27.187c.331.236.63.448.997.414.214-.02.435-.22.547-.82.265-1.417.786-4.486.906-5.751a1.4 1.4 0 0 0-.012-.315.34.34 0 0 0-.114-.217.53.53 0 0 0-.31-.093c-.3 0-.74.15-2.215.766"/></svg>
                                    </a>
                                    <!-- Salin URL -->
                                    <a href="javascript:void(0)" onclick="navigator.clipboard.writeText(window.location.href); alert('Tautan artikel berhasil disalin!');" class="share-icon-btn" title="Salin Tautan">
                                        <svg width="15" height="15" fill="#807F7F" viewBox="0 0 16 16"><path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1 1 0 0 0-.154.199 2 2 0 0 1 .861 3.337L7.343 10.99a2 2 0 1 1-2.828-2.828l1.371-1.371a1 1 0 1 0-1.171-1.623z"/><path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a2 2 0 0 1-.896-3.346L9.12 3.55a2 2 0 1 1 2.83 2.83l-.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 0 0-4.243-4.243z"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Featured Hero Image (Paritas 1:1 Frontend Prod .rounded-3.overflow-hidden.mb-2) -->
                    <?php if (!empty($article['thumbnail']) && $article['thumbnail'] !== '/img/investor_placeholder.jpg'): ?>
                        <?php 
                            $rawCap = trim(($article['thumbnail_caption'] ?? '') ?: ($article['thumbnail_description'] ?? ''));
                            $rawSrc = trim($article['sumber_berita'] ?? ($article['source'] ?? ''));
                            $displayCaption = $rawCap;
                            if ($rawSrc && stripos($rawCap, 'sumber') === false) {
                                $displayCaption .= ($displayCaption ? "\n" : "") . "Sumber: " . $rawSrc;
                            }
                        ?>
                        <div class="mb-3">
                            <div class="rounded-3 overflow-hidden mb-2">
                                <img src="<?= esc($article['thumbnail']) ?>" 
                                     alt="<?= esc($displayCaption ?: $article['title']) ?>" 
                                     class="lazy w-100" 
                                     style="aspect-ratio: 920/620; object-fit: cover;" 
                                     width="920" 
                                     height="620">
                            </div>
                            <?php if ($displayCaption): ?>
                                <span class="text-muted small d-block mb-2" style="white-space: pre-line; line-height: 1.45;">
                                    <?= esc($displayCaption) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Highlight AI / "Singkatnya" (Poin Utama Naskah) -->
                    <?php if (!empty($article['points']) && is_array($article['points'])): ?>
                        <div class="ai-summary-box">
                            <div class="d-flex align-items-center gap-2 mb-2 text-theme fw-bold text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                                <i data-lucide="sparkles" class="size-4 text-theme"></i>
                                <span>Singkatnya (Poin Utama Berita)</span>
                            </div>
                            <ul class="mb-0 ps-3 small text-dark" style="line-height: 1.6;">
                                <?php foreach ($article['points'] as $pt): ?>
                                    <?php if (trim($pt)): ?>
                                        <li class="mb-1 fw-semibold"><?= esc($pt) ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php elseif (!empty($article['summary'])): ?>
                        <div class="ai-summary-box">
                            <p class="mb-0 small fw-semibold text-dark leading-relaxed">
                                <?= esc($article['summary']) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Body Content (.body-content.fsbody2) -->
                    <div class="body-content fsbody2">
                        <?= $article['content'] ?>
                    </div>

                    <!-- Multi-page Article Pagination (Paritas 1:1 Frontend Prod: .mt-4.mb-4 > Halaman: > a > span.paging) -->
                    <?php if (!empty($pagination) && !empty($pagination['is_multipage'])): ?>
                        <div class="mt-4 mb-4">
                            Halaman:&nbsp;
                            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                <a href="?halaman=<?= $i ?>" class="text-decoration-none">
                                    <span class="paging <?= ((string)$pagination['current_page'] === (string)$i) ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </span>&nbsp;
                                </a>
                            <?php endfor; ?>

                            <?php if (!empty($pagination['has_next'])): ?>
                                <a href="?halaman=<?= $pagination['next_page'] ?>" class="text-decoration-none">
                                    <span class="paging">selanjutnya</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Editor Line (Identik Frontend Prod: <p>Editor: <b>...</b></p>) -->
                    <p class="mt-4 mb-2 text-secondary">
                        Editor: <b><?= esc($article['editor'] ?? session('user_name') ?? 'Redaksi Investor.id') ?></b>
                    </p>

                    <!-- Cloud Tags List (Paritas 1:1 Frontend Prod: .mb-3 style="line-height:2.5" > a.badge.bg-theme.rounded-pill.px-3.py-2.text-white.me-2) -->
                    <?php if (!empty($article['tags'])): ?>
                        <div class="mb-3" style="line-height: 2.5;">
                            <?php 
                                $tagItems = is_array($article['tags']) ? $article['tags'] : explode(',', $article['tags']);
                                foreach ($tagItems as $t): 
                                    $trimmed = trim(ltrim(trim($t), '#')); 
                                    if ($trimmed): 
                            ?>
                                <a class="badge bg-theme rounded-pill px-3 py-2 text-white me-2 text-decoration-none" href="javascript:void(0)">
                                    #<?= esc($trimmed) ?>
                                </a>
                            <?php 
                                    endif;
                                endforeach; 
                            ?>
                        </div>
                    <?php endif; ?>

                </article>

                <!-- Right Column: Sidebar Investor.id (320px) -->
                <aside class="id-width-right">
                    
                    <!-- Widget Berita Terpopuler di Investor.id -->
                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4 bg-white">
                        <div class="bg-theme text-white py-2.5 px-3.5 fw-bold" style="font-size: 0.88rem; letter-spacing: 0.5px;">
                            TERPOPULER DI INVESTOR.ID
                        </div>
                        <div class="card-body p-3">
                            <?php 
                                $popularDummies = [
                                    'IHSG Menguat Tipis di Awal Sesi, Saham-Saham Big Caps Jadi Penopang',
                                    'OJK Siapkan Aturan Baru Transaksi Derivatif untuk Tingkatkan Likuiditas Bursa',
                                    'Penerbitan Obligasi Korporasi Tembus Rp 120 Triliun, Sektor Finansial Mendominasi',
                                    'Kinerja Keuangan Bank Jumbo Kuartal III Solid, Dividen Interim Jadi Daya Tarik',
                                    'Emas Antam Naik ke Level Tertinggi, Investor Pantau Sinyal Kebijakan Suku Bunga The Fed'
                                ];
                            ?>
                            <?php foreach ($popularDummies as $idx => $popTitle): ?>
                                <div class="d-flex gap-2.5 mb-3 pb-2.5 <?= ($idx < count($popularDummies) - 1) ? 'border-bottom' : '' ?> align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold bg-light text-theme border" style="width: 28px; height: 28px; flex-shrink: 0; font-size: 0.85rem;">
                                        <?= $idx + 1 ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <a href="javascript:void(0)" class="text-dark fw-bold text-decoration-none small d-block leading-snug hover-theme" style="font-size: 0.8rem;">
                                            <?= esc($popTitle) ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Metadata Redaksi Info Box (CMS Preview Info) -->
                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white mb-4">
                        <div class="card-header bg-slate-100 py-2 px-3 fw-bold text-slate-700" style="font-size: 0.8rem;">
                            STATUS NASKAH REDAKSI
                        </div>
                        <div class="card-body p-3 small text-secondary">
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>Status Tayang:</span>
                                <span class="badge bg-success text-white text-uppercase" style="font-size: 9px;"><?= esc($article['status'] ?? 'Draft') ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>Kategori / Kanal:</span>
                                <span class="fw-bold text-dark"><?= esc($article['kanal'] ?? 'Market') ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>Jumlah Kata:</span>
                                <span class="fw-bold text-dark"><?= str_word_count(strip_tags($article['content'] ?? '')) ?> kata</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span>Jadwal Terbit:</span>
                                <span class="font-monospace text-dark"><?= date('d/m/Y H:i', strtotime($article['publish_date'] ?? 'now')) ?></span>
                            </div>
                        </div>
                    </div>

                </aside>

            </div>
        </main>
    </div>

    <!-- Chart.js Engine for Embedded Editorial Data Charts -->
    <script src="/js/chart.umd.min.js"></script>

    <script>
        // Initialize Lucide icons
        if (window.lucide) {
            lucide.createIcons();
        }

        // Viewport Switcher Logic
        function setDeviceMode(mode) {
            const container = document.getElementById('viewportContainer');
            const btnDesktop = document.getElementById('btnDesktop');
            const btnTablet = document.getElementById('btnTablet');
            const btnMobile = document.getElementById('btnMobile');

            btnDesktop.classList.remove('active');
            btnTablet.classList.remove('active');
            btnMobile.classList.remove('active');

            container.classList.remove('mode-desktop', 'mode-tablet', 'mode-mobile');

            if (mode === 'tablet') {
                container.classList.add('mode-tablet');
                btnTablet.classList.add('active');
            } else if (mode === 'mobile') {
                container.classList.add('mode-mobile');
                btnMobile.classList.add('active');
            } else {
                container.classList.add('mode-desktop');
                btnDesktop.classList.add('active');
            }
        }

        // Chart.js Embed Rendering
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof Chart === 'undefined') return;

            document.querySelectorAll('canvas[id^="custom_chart_"]').forEach(canvas => {
                try {
                    const ctx = canvas.getContext('2d');
                    const style = canvas.dataset.type || 'line';
                    const yTitle = canvas.dataset.ytitle || 'Nilai';
                    let labels = [];
                    let values = [];

                    try { labels = JSON.parse(canvas.dataset.labels || '[]'); } catch (e) {}
                    try { values = JSON.parse(canvas.dataset.values || '[]'); } catch (e) {}

                    if (!labels.length) labels = ['2021', '2022', '2023', '2024', '2025', '2026'];
                    if (!values.length) values = [45, 60, 75, 90, 110, 135];

                    let config = {};

                    if (style === 'line') {
                        config = {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: yTitle,
                                    data: values,
                                    borderColor: '#1E6EA3',
                                    backgroundColor: 'rgba(30, 110, 163, 0.12)',
                                    borderWidth: 2.5,
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 4,
                                    pointBackgroundColor: '#1E6EA3'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } }
                            }
                        };
                    } else if (style === 'bar-horizontal') {
                        config = {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: yTitle,
                                    data: values,
                                    backgroundColor: 'rgba(30, 110, 163, 0.85)',
                                    borderRadius: 4
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } }
                            }
                        };
                    } else if (style === 'pie') {
                        config = {
                            type: 'pie',
                            data: {
                                labels: labels,
                                datasets: [{
                                    data: values,
                                    backgroundColor: ['#1E6EA3', '#4D9EC7', '#059669', '#D97706', '#8B5CF6', '#EC4899']
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { position: 'bottom' } }
                            }
                        };
                    } else { // bar-vertical
                        config = {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: yTitle,
                                    data: values,
                                    backgroundColor: 'rgba(30, 110, 163, 0.85)',
                                    borderRadius: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } }
                            }
                        };
                    }

                    new Chart(ctx, config);
                } catch (e) {
                    console.error('Error initializing chart:', e);
                }
            });
        });
    </script>
</body>
</html>

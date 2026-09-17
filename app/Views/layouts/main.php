<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Investor.ID CMS') ?> — Newsroom Workspace</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/img/investor-logo.ico">
    <link rel="shortcut icon" href="/img/investor-logo.ico">

    <!-- Modern Typography: Inter (Streamlined Weights) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Static Compiled Tailwind CSS (Optimized & Prebuilt) -->
    <link rel="stylesheet" href="/css/tailwind.min.css">

    <!-- CSRF Tokens for Asynchronous Actions -->
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <meta name="csrf-header" content="<?= csrf_header() ?>">

    <!-- Icons: Lucide (Pinned & Minified) -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.min.js"></script>

    <!-- Design System CSS Tokens -->
    <link rel="stylesheet" href="/css/newsroom.css">

    <!-- Page Specific CSS -->
    <?= $this->renderSection('styles') ?>

    <!-- Anti-Flicker Zero-FOUC Dark Mode & Sidebar Pre-renderer -->
    <script>
        (function() {
            try {
                const storedTheme = localStorage.getItem('investor_theme');
                if (storedTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    if (!storedTheme) {
                        localStorage.setItem('investor_theme', 'light');
                    }
                }

                // Pre-render collapsed sidebar state immediately to prevent layout blink
                if (localStorage.getItem('investor_sidebar_collapsed') === '1' && window.innerWidth >= 1024) {
                    document.documentElement.classList.add('sidebar-pre-collapsed');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body class="bg-background text-foreground font-sans antialiased preload">

<div class="flex min-h-screen w-full bg-background">
    <!-- Sidebar Navigation (Desktop Fixed & Collapsible) -->
    <?= $this->include('layouts/sidebar') ?>

    <!-- Mobile Drawer Overlay & Sheet Navigation -->
    <div id="mobileDrawerOverlay" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden lg:hidden transition-opacity duration-200" onclick="toggleMobileNav(false)"></div>
    <div id="mobileDrawerSheet" class="fixed inset-y-0 left-0 z-50 w-[310px] sm:w-[320px] bg-sidebar border-r border-sidebar-border -translate-x-full transition-transform duration-200 ease-out lg:hidden">
        <?= $this->include('layouts/sidebar_content', ['isMobile' => true]) ?>
    </div>

    <!-- Main Workspace Area -->
    <div id="mainWorkspaceWrapper" class="flex min-w-0 flex-1 flex-col transition-[padding] duration-200 ease-out lg:pl-[264px]">
        <!-- Topbar -->
        <?= $this->include('layouts/topbar') ?>

        <!-- Content Body -->
        <main class="mx-auto w-full max-w-[1440px] flex-1 px-4 py-6 sm:px-6 sm:py-8">
            <?php if (session()->getFlashdata('success')): ?>
                <div class="mb-6 flex items-center justify-between rounded-lg border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">
                    <div class="flex items-center gap-2.5 font-medium">
                        <i data-lucide="check-circle" class="size-4"></i>
                        <span><?= esc(session()->getFlashdata('success')) ?></span>
                    </div>
                    <button class="text-success/70 hover:text-success" onclick="this.parentElement.remove()">
                        <i data-lucide="x" class="size-4"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="mb-6 flex items-center justify-between rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                    <div class="flex items-center gap-2.5 font-medium">
                        <i data-lucide="alert-circle" class="size-4"></i>
                        <span><?= esc(session()->getFlashdata('error')) ?></span>
                    </div>
                    <button class="text-destructive/70 hover:text-destructive" onclick="this.parentElement.remove()">
                        <i data-lucide="x" class="size-4"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('warning')): ?>
                <div class="mb-6 flex items-center justify-between rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-600/30 dark:bg-amber-950/30 dark:text-amber-300">
                    <div class="flex items-center gap-2.5 font-medium">
                        <i data-lucide="alert-triangle" class="size-4 shrink-0 text-amber-600 dark:text-amber-400"></i>
                        <span><?= esc(session()->getFlashdata('warning')) ?></span>
                    </div>
                    <button class="text-amber-700/70 hover:text-amber-900 dark:text-amber-400/70 dark:hover:text-amber-200" onclick="this.parentElement.remove()">
                        <i data-lucide="x" class="size-4"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('info')): ?>
                <div class="mb-6 flex items-center justify-between rounded-lg border border-primary/30 bg-primary/10 px-4 py-3 text-sm text-primary">
                    <div class="flex items-center gap-2.5 font-medium">
                        <i data-lucide="info" class="size-4"></i>
                        <span><?= esc(session()->getFlashdata('info')) ?></span>
                    </div>
                    <button class="text-primary/70 hover:text-primary" onclick="this.parentElement.remove()">
                        <i data-lucide="x" class="size-4"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Global Informasi Penting Notice Banner -->
            <div class="mb-6 rounded-md border border-[#FEDF89] bg-[#FFF9EB] px-4 py-3 text-xs text-[#B54708]">
                <p class="font-bold">Informasi Penting!</p>
                <p class="mt-0.5">Pastikan untuk selalu check email resmi kantor.</p>
            </div>

            <?= $this->renderSection('content') ?>
        </main>
    </div>
</div>

<!-- Logout Alert Dialog Modal (Radix AlertDialog replica) -->
<div id="logoutAlertDialog" class="fixed inset-0 z-[9999] bg-black/70 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="surface-card w-full max-w-md p-6 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
        <h3 class="text-lg font-semibold text-foreground">Log out of Investor.ID CMS?</h3>
        <p class="mt-2 text-sm text-muted-foreground">Unsaved drafts stay saved on the server. You will need to sign in again to continue editing.</p>
        <div class="mt-6 flex justify-end gap-2.5">
            <button type="button" onclick="closeLogoutModal()" class="rounded-md border border-input bg-background px-4 py-2 text-sm font-medium text-foreground hover:bg-muted transition-colors">Cancel</button>
            <a href="<?= base_url('logout') ?>" class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors">Logout</a>
        </div>
    </div>
</div>

<!-- AI Loading Custom Modal (Matching PortalBerita/BackendCMS) -->
<div id="aiLoadingModal" class="fixed inset-0 z-[10000] bg-black/50 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="bg-card text-card-foreground rounded-xl p-6 max-w-xs w-full border border-border shadow-2xl flex flex-col items-center justify-center space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <div class="custom-spinner"></div>
        <p id="aiLoadingMessage" class="text-xs font-semibold text-foreground text-center leading-relaxed">Sedang memproses...</p>
    </div>
</div>

<!-- AI Service Failure / Limit Alert Custom Modal (Matching PortalBerita/BackendCMS) -->
<div id="aiErrorModal" class="fixed inset-0 z-[100] bg-black/40 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="bg-card text-card-foreground rounded-xl p-6 max-w-md w-full border border-border shadow-xl space-y-4">
        <div class="flex items-center gap-2.5 border-b border-border pb-3 text-destructive">
            <i data-lucide="alert-triangle" class="size-5 shrink-0"></i>
            <h4 class="font-bold text-sm uppercase tracking-wider text-foreground" id="aiErrorModalTitle">Pemberitahuan AI</h4>
        </div>
        <p id="aiErrorModalMessage" class="text-xs text-muted-foreground leading-relaxed"></p>
        <div class="flex justify-end pt-2">
            <button type="button" onclick="closeAiErrorModal()" class="px-4 py-1.5 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary/90 transition-colors">Tutup</button>
        </div>
    </div>
</div>

<div id="toast-container"></div>

<!-- Global Back to Top Floating Button -->
<button type="button" 
        id="backToTopBtn" 
        title="Kembali ke atas"
        class="fixed bottom-6 right-6 z-40 grid size-10 place-items-center rounded-full bg-primary text-primary-foreground shadow-lg hover:bg-primary/90 focus-visible:outline-none transition-all duration-200 opacity-0 pointer-events-none translate-y-4">
    <i data-lucide="arrow-up" class="size-4"></i>
</button>

<!-- Modal Utilities -->
<script src="/js/utils/modal-utils.js"></script>
<!-- Custom Modern Newsroom JS -->
<script src="/js/newsroom.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>

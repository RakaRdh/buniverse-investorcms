<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Investor.ID CMS') ?> — Newsroom Workspace</title>
    
    <!-- Modern Typography: Inter (Streamlined Weights) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Static Compiled Tailwind CSS (Optimized & Prebuilt) -->
    <link rel="stylesheet" href="/css/tailwind.min.css">

    <!-- Icons: Lucide -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Design System CSS Tokens -->
    <link rel="stylesheet" href="/css/newsroom.css">

    <!-- Page Specific CSS -->
    <?= $this->renderSection('styles') ?>
</head>
<body class="bg-background text-foreground font-sans antialiased">

<div class="flex min-h-screen w-full bg-background">
    <!-- Sidebar Navigation (Desktop Fixed & Collapsible) -->
    <?= $this->include('layouts/sidebar') ?>

    <!-- Mobile Drawer Overlay & Sheet Navigation -->
    <div id="mobileDrawerOverlay" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden lg:hidden transition-opacity duration-200" onclick="toggleMobileNav(false)"></div>
    <div id="mobileDrawerSheet" class="fixed inset-y-0 left-0 z-50 w-[280px] bg-sidebar border-r border-sidebar-border -translate-x-full transition-transform duration-200 ease-out lg:hidden">
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

            <?= $this->renderSection('content') ?>
        </main>
    </div>
</div>

<!-- Logout Alert Dialog Modal (Radix AlertDialog replica) -->
<div id="logoutAlertDialog" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="surface-card w-full max-w-md p-6 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
        <h3 class="text-lg font-semibold text-foreground">Log out of Investor.ID CMS?</h3>
        <p class="mt-2 text-sm text-muted-foreground">Unsaved drafts stay saved on the server. You will need to sign in again to continue editing.</p>
        <div class="mt-6 flex justify-end gap-2.5">
            <button type="button" onclick="closeLogoutModal()" class="rounded-md border border-input bg-background px-4 py-2 text-sm font-medium text-foreground hover:bg-muted transition-colors">Cancel</button>
            <a href="<?= base_url('logout') ?>" class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors">Logout</a>
        </div>
    </div>
</div>

<div id="toast-container"></div>

<!-- Custom Modern Newsroom JS -->
<script src="/js/newsroom.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>

<?php
$currentUri = uri_string();
if (empty($currentUri)) $currentUri = 'dashboard';

$routeTitles = [
    'dashboard' => ['section' => 'Dashboard', 'page' => 'Overview'],
    'post' => ['section' => 'Post', 'page' => 'All Posts'],
    'post/new' => ['section' => 'Post', 'page' => 'New Post'],
    'headline' => ['section' => 'Headline', 'page' => 'Placements'],
    'media' => ['section' => 'Media', 'page' => 'Library'],
    'files' => ['section' => 'Files', 'page' => 'All Files'],
    'embed-chart' => ['section' => 'Embed Chart', 'page' => 'Template Charts'],
    'embed-chart/template' => ['section' => 'Embed Chart', 'page' => 'Template Charts'],
    'embed-chart/custom' => ['section' => 'Embed Chart', 'page' => 'Custom Data'],
    'embed-chart/custom/create' => ['section' => 'Embed Chart', 'page' => 'Create Custom'],
    'statistic' => ['section' => 'Insights', 'page' => 'Trafik & Audiens'],
    'statistic/report' => ['section' => 'Insights', 'page' => 'Performa Tim Editorial'],
    'kanal/master' => ['section' => 'Kanal', 'page' => 'Master Kanal'],
    'kanal/sub' => ['section' => 'Kanal', 'page' => 'Sub Kanal'],
    'kanal/stats' => ['section' => 'Kanal', 'page' => 'Performa Kanal'],
    'apps-version' => ['section' => 'Apps', 'page' => 'Version'],
    'apps-push-notif' => ['section' => 'Apps', 'page' => 'Push Notif'],
    'apps-feature-content' => ['section' => 'Apps', 'page' => 'Feature Content'],
    'user' => ['section' => 'Management', 'page' => 'All Users'],
    'user/profile' => ['section' => 'Account', 'page' => 'My Profile'],
];

$crumb = $routeTitles[$currentUri] ?? null;
if (!$crumb) {
    if (str_starts_with($currentUri, 'embed-chart/custom/edit/')) {
        $crumb = ['section' => 'Embed Chart', 'page' => 'Edit Custom'];
    } else {
        $crumb = ['section' => 'Dashboard', 'page' => 'Overview'];
    }
}
?>

<header class="sticky top-0 z-30 border-b border-border bg-card/85 backdrop-blur-md">
    <div class="flex h-16 items-center gap-3 px-4 sm:px-6">
        <!-- Mobile Navigation Menu Toggle Button -->
        <button type="button" onclick="toggleMobileNav(true)" class="inline-flex size-10 items-center justify-center rounded-md text-foreground hover:bg-muted lg:hidden" aria-label="Open navigation">
            <i data-lucide="menu" class="size-5"></i>
        </button>

        <!-- Breadcrumb Navigation -->
        <nav aria-label="Breadcrumb" class="min-w-0 flex-1">
            <ol class="flex min-w-0 items-center gap-2 text-sm">
                <li class="hidden text-muted-foreground sm:block"><?= esc($crumb['section']) ?></li>
                <li class="hidden text-muted-foreground/50 sm:block" aria-hidden="true">/</li>
                <li class="truncate font-medium text-foreground"><?= esc($crumb['page']) ?></li>
            </ol>
        </nav>



        <!-- Theme Toggle Switcher Button (Lucide Icons: Sun / Moon) -->
        <button type="button" onclick="toggleTheme()" id="globalThemeToggleBtn" class="inline-flex size-9 sm:size-10 items-center justify-center rounded-md text-foreground hover:bg-muted transition-colors" title="Ganti Tema (Dark / Light)" aria-label="Toggle Theme">
            <i data-lucide="moon" class="size-4 sm:size-5 theme-icon-moon"></i>
            <i data-lucide="sun" class="size-4 sm:size-5 theme-icon-sun"></i>
        </button>

        <!-- Help Button -->
        <a href="https://investor.id" target="_blank" class="inline-flex size-9 sm:size-10 items-center justify-center rounded-md text-foreground hover:bg-muted transition-colors" aria-label="Help and documentation">
            <i data-lucide="help-circle" class="size-4 sm:size-5"></i>
        </a>

        <!-- User Profile Dropdown Button -->
        <div class="relative">
            <button type="button" onclick="toggleUserDropdown()" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm transition-colors hover:bg-muted">
                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">
                    <?= esc(session('user_initials') ?? 'ID') ?>
                </span>
                <span class="hidden text-left leading-tight sm:block">
                    <span class="block text-sm font-medium text-foreground"><?= esc(session('user_name') ?? 'Editorial Staff') ?></span>
                    <span class="block text-xs text-muted-foreground capitalize"><?= esc(session('user_role') ?? 'Staff') ?></span>
                </span>
                <i data-lucide="chevron-down" class="size-4 text-muted-foreground" aria-hidden="true"></i>
            </button>

            <!-- Dropdown Menu Overlay -->
            <div id="userDropdownMenu" class="absolute right-0 top-full mt-2 hidden w-56 rounded-md border border-border bg-card p-1 text-foreground shadow-lg animate-in fade-in zoom-in-95 duration-100 z-50">
                <div class="px-2 py-1.5 text-xs font-semibold text-muted-foreground truncate block max-w-full" title="<?= esc(session('user_email') ?? 'user@investor.id') ?>"><?= esc(session('user_email') ?? 'user@investor.id') ?></div>
                <div class="my-1 h-px bg-border"></div>
                <a href="<?= base_url('user/profile') ?>" class="flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-muted">
                    <i data-lucide="user" class="size-4 mr-2 text-muted-foreground"></i> My Profile
                </a>
                <a href="<?= base_url('user') ?>" class="flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-muted">
                    <i data-lucide="users" class="size-4 mr-2 text-muted-foreground"></i> Team Staff
                </a>
                <div class="my-1 h-px bg-border"></div>
                <button type="button" onclick="openLogoutModal(); toggleUserDropdown();" class="flex w-full cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm text-destructive hover:bg-destructive/10">
                    <i data-lucide="log-out" class="size-4 mr-2"></i> Logout
                </button>
            </div>
        </div>
    </div>
</header>

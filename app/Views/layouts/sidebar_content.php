<?php
$currentUri = uri_string();
if (empty($currentUri)) $currentUri = 'dashboard';

$navGroups = [
    [
        'label' => 'Content',
        'items' => [
            ['label' => 'Dashboard', 'to' => 'dashboard', 'icon' => 'layout-dashboard'],
            ['label' => 'Post', 'to' => 'post', 'icon' => 'file-text'],
            ['label' => 'Headline', 'to' => 'headline', 'icon' => 'newspaper'],
            ['label' => 'Media', 'to' => 'media', 'icon' => 'image'],
            ['label' => 'Files', 'to' => 'files', 'icon' => 'folder-open'],
        ]
    ],
    [
        'label' => 'Insights',
        'items' => [
            ['label' => 'Embed Chart', 'to' => 'embed-chart', 'icon' => 'line-chart'],
            ['label' => 'Statistic', 'to' => 'statistic', 'icon' => 'bar-chart-3'],
        ]
    ],
    [
        'label' => 'Apps',
        'items' => [
            ['label' => 'Apps - Version', 'to' => 'apps-version', 'icon' => 'smartphone'],
            ['label' => 'Apps - Push Notif', 'to' => 'apps-push-notif', 'icon' => 'bell-ring'],
            ['label' => 'Apps - Feature Content', 'to' => 'apps-feature-content', 'icon' => 'star'],
        ]
    ],
    [
        'label' => 'Management',
        'items' => [
            ['label' => 'Categories', 'to' => 'categories', 'icon' => 'tags'],
            ['label' => 'User', 'to' => 'user', 'icon' => 'users'],
        ]
    ]
];
?>

<div class="flex h-screen flex-col justify-between bg-sidebar text-sidebar-foreground select-none overflow-hidden">
    <!-- Wordmark / Brand Header -->
    <div class="flex h-16 shrink-0 items-center gap-3 px-4 border-b border-sidebar-border/40">
        <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-sidebar-primary text-sm font-bold text-sidebar-primary-foreground shadow-sm">
            ID
        </span>
        <div class="min-w-0 brand-desc">
            <span class="block truncate text-sm font-bold tracking-tight text-sidebar-accent-foreground">
                INVESTOR<span class="text-sidebar-primary">.ID</span>
            </span>
            <span class="block text-[10px] tracking-[0.16em] text-sidebar-foreground/60 uppercase">Newsroom CMS</span>
        </div>
    </div>

    <!-- Navigation List with Visual Groups (Internal Scroll only if screen height is small) -->
    <nav aria-label="Main navigation" class="flex-1 overflow-y-auto overflow-x-hidden px-4 py-3 space-y-4 overscroll-contain">
        <?php foreach ($navGroups as $group): ?>
            <div>
                <p class="group-label mb-1.5 px-3 text-[10px] font-semibold tracking-[0.14em] text-sidebar-foreground/45 uppercase">
                    <?= esc($group['label']) ?>
                </p>
                <div class="hidden group-divider mx-auto mb-2 h-px w-6 bg-sidebar-border" aria-hidden="true"></div>

                <ul class="space-y-0.5">
                    <?php foreach ($group['items'] as $item): ?>
                        <?php 
                            $isActive = ($item['to'] === 'dashboard' && ($currentUri === 'dashboard' || $currentUri === '')) 
                                || ($item['to'] !== 'dashboard' && str_starts_with($currentUri, $item['to']));
                        ?>
                        <li>
                            <a href="<?= base_url($item['to']) ?>"
                               title="<?= esc($item['label']) ?>"
                               class="nav-item-link group relative flex items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-xs transition-colors duration-150 <?= $isActive ? 'bg-sidebar-accent font-semibold text-sidebar-accent-foreground shadow-xs' : 'text-sidebar-foreground/75 hover:bg-sidebar-accent/50 hover:text-sidebar-accent-foreground' ?>">
                                
                                <?php if ($isActive): ?>
                                    <span class="absolute top-1 bottom-1 -left-2 w-0.5 rounded-full bg-sidebar-primary" aria-hidden="true"></span>
                                <?php endif; ?>

                                <i data-lucide="<?= esc($item['icon']) ?>" class="size-4 shrink-0 <?= $isActive ? 'text-sidebar-primary' : '' ?>" aria-hidden="true"></i>
                                <span class="nav-label truncate"><?= esc($item['label']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </nav>

    <!-- Sidebar Footer: Stick to bottom with user info and Logout Button -->
    <div class="shrink-0 border-t border-sidebar-border bg-sidebar/95 p-3.5 space-y-2">
        <?php if (session()->has('user_name')): ?>
            <div class="flex items-center gap-2.5 px-2 py-1">
                <span class="grid size-7 shrink-0 place-items-center rounded-full bg-primary/20 text-primary text-[11px] font-bold">
                    <?= esc(session('user_initials') ?? 'ID') ?>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold text-sidebar-accent-foreground leading-tight"><?= esc(session('user_name')) ?></p>
                    <p class="truncate text-[10px] text-sidebar-foreground/60 capitalize"><?= esc(session('user_role')) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <button type="button" onclick="openLogoutModal()" class="nav-item-link flex w-full items-center gap-3 rounded-lg px-3 py-2 text-xs font-medium text-sidebar-foreground/75 transition-colors duration-150 hover:bg-destructive/15 hover:text-destructive">
            <i data-lucide="log-out" class="size-4 shrink-0 text-destructive" aria-hidden="true"></i>
            <span class="nav-label">Keluar Akun</span>
        </button>
    </div>
</div>

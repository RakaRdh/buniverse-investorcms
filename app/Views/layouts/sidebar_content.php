<?php
$currentUri = uri_string();
if (empty($currentUri)) $currentUri = 'dashboard';

$userRole = strtolower(session('user_role') ?? 'admin');

// Definisi Navigation Groups Master Terpadu
// Akses: roles => ['all'] atau roles => ['administrator', 'master', 'admin']
$navGroups = [
    [
        'label' => 'Content',
        'items' => [
            ['label' => 'Dashboard', 'to' => 'dashboard', 'icon' => 'layout-dashboard', 'roles' => ['all']],
            [
                'label' => 'Post',
                'type' => 'dropdown',
                'icon' => 'file-text',
                'roles' => ['all'],
                'children' => [
                    ['label' => 'Daftar Artikel', 'to' => 'post', 'roles' => ['all']],
                    ['label' => 'Tambah Naskah', 'to' => 'post/new', 'roles' => ['all']],
                    ['label' => 'Riwayat Revisi', 'to' => 'post/history', 'roles' => ['all']],
                    ['label' => 'Master Keyword', 'to' => 'keyword', 'roles' => ['administrator', 'master', 'admin']],
                ]
            ],
            [
                'label' => 'Headline',
                'type' => 'dropdown',
                'icon' => 'newspaper',
                'roles' => ['administrator', 'master', 'admin', 'editor'],
                'children' => [
                    ['label' => 'Atur Headline', 'to' => 'headline', 'roles' => ['administrator', 'master', 'admin', 'editor']],
                    ['label' => 'Riwayat Log', 'to' => 'headline/history', 'roles' => ['administrator', 'master', 'admin', 'editor']],
                ]
            ],
            ['label' => 'Media', 'to' => 'media', 'icon' => 'image', 'roles' => ['all']],
            ['label' => 'Files', 'to' => 'files', 'icon' => 'folder-open', 'roles' => ['all']],
            [
                'label' => 'Scoring',
                'type' => 'dropdown',
                'icon' => 'check-circle-2',
                'roles' => ['all'],
                'children' => [
                    ['label' => 'Nilai SEO', 'to' => 'scoring', 'roles' => ['all']],
                    ['label' => 'Riwayat Log', 'to' => 'scoring/logs', 'roles' => ['all']],
                ]
            ],
        ]
    ],
    [
        'label' => 'Insights',
        'items' => [
            [
                'label' => 'Embed Chart',
                'type' => 'dropdown',
                'icon' => 'line-chart',
                'roles' => ['administrator', 'master', 'admin', 'editor'],
                'children' => [
                    ['label' => 'Template', 'to' => 'embed-chart/template', 'roles' => ['administrator', 'master', 'admin', 'editor']],
                    ['label' => 'Custom Data', 'to' => 'embed-chart/custom', 'roles' => ['administrator', 'master', 'admin', 'editor']],
                ]
            ],
            [
                'label' => 'Statistic',
                'type' => 'dropdown',
                'icon' => 'bar-chart-3',
                'roles' => ['all'],
                'children' => [
                    ['label' => 'Trafik & Audiens', 'to' => 'statistic', 'roles' => ['all']],
                    ['label' => 'Performa Tim Editorial', 'to' => 'statistic/report', 'roles' => ['all']],
                    ['label' => 'Publish Log', 'to' => 'statistic/publish-log', 'roles' => ['all']],
                    ['label' => 'Artikel Populer', 'to' => 'statistic/popular', 'roles' => ['all']],
                    ['label' => 'Heatmap 24 Jam', 'to' => 'statistic/heatmap', 'roles' => ['all']],
                ]
            ],
        ]
    ],
    [
        'label' => 'Apps',
        'items' => [
            ['label' => 'Apps - Version', 'to' => 'apps-version', 'icon' => 'smartphone', 'roles' => ['administrator', 'master', 'admin']],
            ['label' => 'Apps - Push Notif', 'to' => 'apps-push-notif', 'icon' => 'bell-ring', 'roles' => ['administrator', 'master', 'admin']],
            ['label' => 'Apps - Feature Content', 'to' => 'apps-feature-content', 'icon' => 'star', 'roles' => ['administrator', 'master', 'admin']],
        ]
    ],
    [
        'label' => 'Management',
        'items' => [
            [
                'label' => 'Kanal',
                'type' => 'dropdown',
                'icon' => 'layers',
                'roles' => ['administrator', 'master', 'admin'],
                'children' => [
                    ['label' => 'Master Kanal', 'to' => 'kanal/master', 'roles' => ['administrator', 'master', 'admin']],
                    ['label' => 'Sub Kanal', 'to' => 'kanal/sub', 'roles' => ['administrator', 'master', 'admin']],
                    ['label' => 'Performa Kanal', 'to' => 'kanal/stats', 'roles' => ['administrator', 'master', 'admin']],
                ]
            ],
            ['label' => 'Member', 'to' => 'member', 'icon' => 'contact-round', 'roles' => ['administrator', 'master', 'admin']],
            ['label' => 'User', 'to' => 'user', 'icon' => 'users', 'roles' => ['administrator', 'master', 'admin']],
        ]
    ]
];

// Helper check permission
$checkAccess = function(array $roles) use ($userRole): bool {
    if (in_array('all', $roles, true)) return true;
    if (in_array($userRole, ['master', 'admin', 'administrator'], true)) return true;
    return in_array($userRole, $roles, true);
};
?>

<div class="flex h-screen flex-col justify-between bg-sidebar text-sidebar-foreground select-none overflow-hidden">
    <!-- Wordmark / Brand Header -->
    <div class="flex h-16 shrink-0 items-center gap-3 px-4 border-b border-sidebar-border/40">
        <img src="/img/investor-logo.jpg" alt="Investor.ID Logo" class="size-9 shrink-0 rounded-lg object-cover shadow-sm">
        <div class="min-w-0 brand-desc">
            <span class="block truncate text-sm font-bold tracking-tight text-sidebar-accent-foreground">
                INVESTOR<span class="text-sidebar-primary">.ID</span>
            </span>
            <span class="block text-[10px] tracking-[0.16em] text-sidebar-foreground/60 uppercase">Newsroom CMS</span>
        </div>
    </div>

    <!-- Navigation List with Visual Groups (Internal Scroll styled with custom scrollbar) -->
    <nav id="sidebarMainNavigation" aria-label="Main navigation" class="flex-1 overflow-y-auto overflow-x-hidden px-4 py-3 space-y-4 overscroll-contain custom-sidebar-scroll">
        <?php foreach ($navGroups as $group): ?>
            <div>
                <p class="group-label mb-1.5 px-3 text-[10px] font-semibold tracking-[0.14em] text-sidebar-foreground/45 uppercase">
                    <?= esc($group['label']) ?>
                </p>
                <div class="hidden group-divider mx-auto mb-2 h-px w-6 bg-sidebar-border" aria-hidden="true"></div>

                <ul class="space-y-0.5">
                    <?php foreach ($group['items'] as $item): ?>
                        <?php 
                            $canAccessItem = $checkAccess($item['roles'] ?? ['all']);
                        ?>

                        <?php if (($item['type'] ?? 'link') === 'dropdown'): ?>
                            <?php
                                $isChildActive = false;
                                foreach ($item['children'] as $child) {
                                    if (($child['to'] === 'post' && ($currentUri === 'post' || str_starts_with($currentUri, 'post/')))
                                        || ($child['to'] === 'scoring' && $currentUri === 'scoring')
                                        || ($child['to'] === 'scoring/logs' && (str_starts_with($currentUri, 'scoring/logs') || str_starts_with($currentUri, 'scoring/detail')))
                                        || ($child['to'] === 'embed-chart/template' && ($currentUri === 'embed-chart' || str_starts_with($currentUri, 'embed-chart/template')))
                                        || ($child['to'] === 'embed-chart/custom' && str_starts_with($currentUri, 'embed-chart/custom'))
                                        || ($child['to'] !== 'post' && $child['to'] !== 'scoring' && $child['to'] !== 'scoring/logs' && str_starts_with($currentUri, $child['to']))) {
                                        $isChildActive = true;
                                        break;
                                    }
                                }
                            ?>
                            <li class="sidebar-dropdown-wrapper <?= !$canAccessItem ? 'opacity-40 grayscale-[40%]' : '' ?>">
                                <?php if ($canAccessItem): ?>
                                    <button type="button" 
                                            onclick="toggleSidebarDropdown(this)"
                                            class="nav-item-link group relative flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-xs transition-colors duration-150 <?= $isChildActive ? 'bg-sidebar-accent font-semibold text-sidebar-accent-foreground' : 'text-sidebar-foreground/75 hover:bg-sidebar-accent/50 hover:text-sidebar-accent-foreground' ?>">
                                        
                                        <div class="flex items-center gap-3 min-w-0">
                                            <i data-lucide="<?= esc($item['icon']) ?>" class="size-4.5 shrink-0 <?= $isChildActive ? 'text-sidebar-primary' : '' ?>" aria-hidden="true"></i>
                                            <span class="nav-label truncate font-medium text-xs sm:text-[13px]"><?= esc($item['label']) ?></span>
                                        </div>
                                        <i data-lucide="chevron-down" class="size-3.5 shrink-0 transition-transform duration-200 text-sidebar-foreground/50 dropdown-arrow <?= $isChildActive ? 'rotate-180' : '' ?>" aria-hidden="true"></i>
                                    </button>
                                    
                                    <div class="dropdown-menu <?= $isChildActive ? 'dropdown-open' : '' ?>">
                                        <ul class="dropdown-menu-inner pl-7 pr-1 py-1 space-y-1">
                                            <?php foreach ($item['children'] as $child): ?>
                                                <?php 
                                                    $canAccessChild = $checkAccess($child['roles'] ?? ['all']);
                                                    $isSubActive = false;
                                                    if ($child['to'] === 'post') {
                                                        $isSubActive = ($currentUri === 'post' || str_starts_with($currentUri, 'post/edit') || str_starts_with($currentUri, 'post/preview'));
                                                    } elseif ($child['to'] === 'post/new') {
                                                        $isSubActive = ($currentUri === 'post/new');
                                                    } elseif ($child['to'] === 'post/history') {
                                                        $isSubActive = str_starts_with($currentUri, 'post/history');
                                                    } elseif ($child['to'] === 'scoring') {
                                                        $isSubActive = ($currentUri === 'scoring');
                                                    } elseif ($child['to'] === 'scoring/logs') {
                                                        $isSubActive = (str_starts_with($currentUri, 'scoring/logs') || str_starts_with($currentUri, 'scoring/detail'));
                                                    } elseif ($child['to'] === 'headline') {
                                                        $isSubActive = ($currentUri === 'headline');
                                                    } elseif ($child['to'] === 'headline/history') {
                                                        $isSubActive = str_starts_with($currentUri, 'headline/history');
                                                    } elseif ($child['to'] === 'statistic') {
                                                        $isSubActive = ($currentUri === 'statistic');
                                                    } elseif ($child['to'] === 'embed-chart/template') {
                                                        $isSubActive = ($currentUri === 'embed-chart' || $currentUri === 'embed-chart/template');
                                                    } elseif ($child['to'] === 'embed-chart/custom') {
                                                        $isSubActive = str_starts_with($currentUri, 'embed-chart/custom');
                                                    } else {
                                                        $isSubActive = ($currentUri === $child['to'] || str_starts_with($currentUri, $child['to'] . '/'));
                                                    }
                                                ?>
                                                <li>
                                                    <?php if ($canAccessChild): ?>
                                                        <a href="<?= base_url($child['to']) ?>"
                                                           class="flex items-center gap-2.5 rounded-md px-2.5 py-1.5 text-xs transition-colors <?= $isSubActive ? 'font-bold text-sidebar-primary bg-sidebar-accent/50 shadow-2xs' : 'text-sidebar-foreground/70 hover:text-sidebar-accent-foreground hover:bg-sidebar-accent/30' ?>">
                                                            <span class="size-1.5 rounded-full <?= $isSubActive ? 'bg-sidebar-primary' : 'bg-sidebar-foreground/40' ?>"></span>
                                                            <span><?= esc($child['label']) ?></span>
                                                        </a>
                                                    <?php else: ?>
                                                        <div title="Akses terbatas untuk Administrator" class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-sidebar-foreground/40 opacity-50 cursor-not-allowed select-none">
                                                            <div class="flex items-center gap-2.5">
                                                                <span class="size-1.5 rounded-full bg-sidebar-foreground/30"></span>
                                                                <span><?= esc($child['label']) ?></span>
                                                            </div>
                                                            <i data-lucide="lock" class="size-3 text-sidebar-foreground/40"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <!-- Inactive / Disabled Dropdown Container for Unauthorized Roles -->
                                    <div title="Akses menu <?= esc($item['label']) ?> terbatas untuk Administrator" class="nav-item-link relative flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-xs text-sidebar-foreground/40 cursor-not-allowed select-none">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <i data-lucide="<?= esc($item['icon']) ?>" class="size-4.5 shrink-0 text-sidebar-foreground/40" aria-hidden="true"></i>
                                            <span class="nav-label truncate font-medium text-xs sm:text-[13px]"><?= esc($item['label']) ?></span>
                                        </div>
                                        <i data-lucide="lock" class="size-3.5 shrink-0 text-sidebar-foreground/40" aria-hidden="true"></i>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php else: ?>
                            <?php 
                                $isActive = ($item['to'] === 'dashboard' && ($currentUri === 'dashboard' || $currentUri === '')) 
                                    || ($item['to'] === 'statistic' && $currentUri === 'statistic')
                                    || ($item['to'] !== 'dashboard' && $item['to'] !== 'statistic' && str_starts_with($currentUri, $item['to']));
                            ?>
                            <li>
                                <?php if ($canAccessItem): ?>
                                    <a href="<?= base_url($item['to']) ?>"
                                       title="<?= esc($item['label']) ?>"
                                       class="nav-item-link group relative flex items-center gap-3 rounded-lg px-3 py-2 text-xs transition-colors duration-150 <?= $isActive ? 'bg-sidebar-accent font-semibold text-sidebar-accent-foreground shadow-xs' : 'text-sidebar-foreground/75 hover:bg-sidebar-accent/50 hover:text-sidebar-accent-foreground' ?>">
                                        
                                        <?php if ($isActive): ?>
                                            <span class="absolute top-1 bottom-1 -left-2 w-0.5 rounded-full bg-sidebar-primary" aria-hidden="true"></span>
                                        <?php endif; ?>

                                        <i data-lucide="<?= esc($item['icon']) ?>" class="size-4.5 shrink-0 <?= $isActive ? 'text-sidebar-primary' : '' ?>" aria-hidden="true"></i>
                                        <span class="nav-label truncate font-medium text-xs sm:text-[13px]"><?= esc($item['label']) ?></span>
                                    </a>
                                <?php else: ?>
                                    <!-- Inactive / Disabled Single Menu Link for Unauthorized Roles -->
                                    <div title="Akses menu <?= esc($item['label']) ?> terbatas untuk Administrator" class="nav-item-link relative flex items-center justify-between rounded-lg px-3 py-2 text-xs text-sidebar-foreground/40 opacity-40 grayscale-[40%] cursor-not-allowed select-none">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <i data-lucide="<?= esc($item['icon']) ?>" class="size-4.5 shrink-0 text-sidebar-foreground/40" aria-hidden="true"></i>
                                            <span class="nav-label truncate font-medium text-xs sm:text-[13px]"><?= esc($item['label']) ?></span>
                                        </div>
                                        <i data-lucide="lock" class="size-3.5 text-sidebar-foreground/40 shrink-0" aria-hidden="true"></i>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </nav>

    <!-- Sidebar Footer: Stick to bottom with user info and Logout Button -->
    <div class="shrink-0 border-t border-sidebar-border bg-sidebar/95 p-3.5 space-y-2">
        <?php if (session()->has('user_name')): ?>
            <a href="<?= base_url('user/profile') ?>" title="Lihat Profil Saya" class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg transition-colors hover:bg-sidebar-accent/50 group cursor-pointer">
                <span class="grid size-7 shrink-0 place-items-center rounded-full bg-primary/20 text-primary text-[11px] font-bold group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
                    <?= esc(session('user_initials') ?? 'ID') ?>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold text-sidebar-accent-foreground leading-tight group-hover:text-primary transition-colors"><?= esc(session('user_name')) ?></p>
                    <p class="truncate text-[10px] text-sidebar-foreground/60 capitalize"><?= esc(session('user_role')) ?></p>
                </div>
                <i data-lucide="chevron-right" class="size-3.5 text-sidebar-foreground/40 group-hover:text-sidebar-foreground/80 transition-colors shrink-0" aria-hidden="true"></i>
            </a>
        <?php endif; ?>

        <button type="button" onclick="openLogoutModal()" class="nav-item-link flex w-full items-center gap-3 rounded-lg px-3 py-2 text-xs font-medium text-sidebar-foreground/75 transition-colors duration-150 hover:bg-destructive/15 hover:text-destructive">
            <i data-lucide="log-out" class="size-4 shrink-0 text-destructive" aria-hidden="true"></i>
            <span class="nav-label">Keluar Akun</span>
        </button>
    </div>
</div>

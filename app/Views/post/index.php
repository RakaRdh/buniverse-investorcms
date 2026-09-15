<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="/css/pages/post.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- PageHeader Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Posts &amp; News Articles</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Manage, review, filter, and publish articles across the Investor.id editorial network.</p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <a href="<?= base_url('post/export') ?>" class="inline-flex flex-1 sm:flex-initial items-center justify-center gap-1.5 rounded-md border border-input bg-background px-3 py-2 text-xs sm:text-sm font-medium text-foreground shadow-sm hover:bg-muted transition-colors">
                <i data-lucide="download" class="size-4"></i>
                <span>Download List</span>
            </a>
            <a href="<?= base_url('post/new') ?>" class="inline-flex flex-1 sm:flex-initial items-center justify-center gap-1.5 rounded-md bg-primary px-3.5 py-2 text-xs sm:text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 transition-colors">
                <i data-lucide="plus" class="size-4"></i>
                <span>Add New Post</span>
            </a>
        </div>
    </div>
</div>

<?php
// Compute boolean states for toggleable quick filters
$isDefaultFilter = (($filters['status'] ?? 'all') === 'all' && ($filters['premium'] ?? 'all') === 'all' && empty($filters['q']) && ($filters['kanal'] ?? 'all') === 'all' && ($filters['author'] ?? 'all') === 'all' && ($filters['editor'] ?? 'all') === 'all' && empty($filters['date_from']) && empty($filters['date_to']));
$isPublishActive = (in_array(strtolower($filters['status'] ?? ''), ['publish', 'published']) && ($filters['premium'] ?? 'all') === 'all');
$isDraftActive = (strcasecmp($filters['status'] ?? '', 'Draft') === 0 && ($filters['premium'] ?? 'all') === 'all');
$isHoldActive = (strcasecmp($filters['status'] ?? '', 'Hold') === 0 && ($filters['premium'] ?? 'all') === 'all');
$isDeletedActive = (strcasecmp($filters['status'] ?? '', 'Deleted') === 0 && ($filters['premium'] ?? 'all') === 'all');
$isPremiumActive = (($filters['premium'] ?? '') === '1');
$canViewDeleted = in_array(strtolower($userRole ?? session('user_role') ?? ''), ['administrator', 'redpel', 'admin']);
?>

<div class="surface-card p-4 mb-6">
    <form method="get" action="<?= base_url('post') ?>" id="postFilterForm">
        <!-- Row 1: Primary Filters -->
        <div class="flex flex-wrap gap-3 items-center justify-between">
            <!-- Keyword Search -->
            <div class="relative flex-1 min-w-[220px]">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground"></i>
                <input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>" class="flex h-9 w-full rounded-md border border-input bg-background pl-9 pr-3 text-xs sm:text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Cari judul artikel atau author...">
            </div>

            <!-- Kanal Select -->
            <div class="flex-1 sm:flex-initial min-w-[130px]">
                <select name="kanal" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs sm:text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" onchange="this.form.submit()">
                    <option value="all">Kanal: Semua</option>
                    <?php 
                        $filterKanalNorm = strtolower(trim((string)($filters['kanal'] ?? '')));
                    ?>
                    <?php foreach ($kanals as $kn): ?>
                        <?php 
                            $knName = strtolower(trim($kn['name']));
                            $knSlug = strtolower(trim($kn['slug'] ?? ''));
                            $isSelected = ($filterKanalNorm === $knName || $filterKanalNorm === $knSlug || $filterKanalNorm === str_replace('-', ' ', $knName));
                        ?>
                        <option value="<?= esc($kn['name']) ?>" <?= $isSelected ? 'selected' : '' ?>>
                            <?= esc($kn['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Select -->
            <div class="flex-1 sm:flex-initial min-w-[120px]">
                <select name="status" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs sm:text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" onchange="this.form.submit()">
                    <option value="all" <?= ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' ?>>Status: Semua</option>
                    <?php if (!empty($statuses)): ?>
                        <?php foreach ($statuses as $st): ?>
                            <?php 
                                $stLabel = $st['label'];
                                $isSelected = (strcasecmp($filters['status'] ?? '', $stLabel) === 0) || 
                                              ($stLabel === 'Publish' && strcasecmp($filters['status'] ?? '', 'Published') === 0);
                                $optVal = ($stLabel === 'Publish' && strcasecmp($filters['status'] ?? '', 'Published') === 0) ? 'Published' : $stLabel;
                            ?>
                            <option value="<?= esc($optVal) ?>" <?= $isSelected ? 'selected' : '' ?>><?= esc($stLabel) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php $pubVal = (strcasecmp($filters['status'] ?? '', 'Published') === 0) ? 'Published' : 'Publish'; ?>
                        <option value="<?= $pubVal ?>" <?= in_array(strtolower($filters['status'] ?? ''), ['publish', 'published']) ? 'selected' : '' ?>>Publish</option>
                        <option value="Draft" <?= strcasecmp($filters['status'] ?? '', 'Draft') === 0 ? 'selected' : '' ?>>Draft</option>
                        <option value="Hold" <?= strcasecmp($filters['status'] ?? '', 'Hold') === 0 ? 'selected' : '' ?>>Hold</option>
                        <?php if ($canViewDeleted): ?>
                            <option value="Deleted" <?= strcasecmp($filters['status'] ?? '', 'Deleted') === 0 ? 'selected' : '' ?>>Deleted</option>
                        <?php endif; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Premium Select -->
            <div class="flex-1 sm:flex-initial min-w-[110px]">
                <select name="premium" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs sm:text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" onchange="this.form.submit()">
                    <option value="all" <?= $filters['premium'] === 'all' ? 'selected' : '' ?>>Tier: Semua</option>
                    <option value="1" <?= $filters['premium'] === '1' ? 'selected' : '' ?>>Premium Saja</option>
                    <option value="0" <?= $filters['premium'] === '0' ? 'selected' : '' ?>>Reguler Saja</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <?php
                    $hasActiveMoreFilters = (!empty($filters['author']) && $filters['author'] !== 'all') 
                        || (!empty($filters['editor']) && $filters['editor'] !== 'all') 
                        || !empty($filters['date_from']) 
                        || !empty($filters['date_to']);
                ?>
                <button type="button" onclick="toggleMoreFilters()" id="moreFilterBtn" class="inline-flex flex-1 sm:flex-initial items-center justify-center gap-1 rounded-md border border-input bg-background px-3 py-2 text-xs font-medium <?= $hasActiveMoreFilters ? 'text-primary font-semibold' : 'text-muted-foreground' ?> hover:text-foreground transition-colors">
                    <i data-lucide="sliders-horizontal" class="size-3.5"></i>
                    <span id="moreFilterText"><?= $hasActiveMoreFilters ? 'Less Filter' : 'More Filter' ?></span>
                    <i data-lucide="chevron-down" id="moreFilterArrow" class="size-3 transition-transform <?= $hasActiveMoreFilters ? 'rotate-180' : '' ?>"></i>
                </button>
                <button type="submit" class="inline-flex flex-1 sm:flex-initial items-center justify-center gap-1.5 rounded-md bg-[#2B6CB0] px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow-xs hover:bg-[#2B6CB0]/90 transition-colors">
                    <i data-lucide="filter" class="size-3.5"></i>
                    <span>Terapkan</span>
                </button>
                <?php if (!$isDefaultFilter): ?>
                    <a href="<?= base_url('post') ?>" class="inline-flex items-center justify-center rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-xs font-medium text-destructive hover:bg-destructive/20 transition-colors" title="Reset All Filters">
                        <i data-lucide="rotate-ccw" class="size-3.5"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Row 2: Secondary Filters (Collapsible) -->
        <div id="moreFilterSection" class="<?= $hasActiveMoreFilters ? '' : 'hidden' ?> mt-4 pt-4 border-t border-border grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 animate-in fade-in duration-150">
            <!-- Author Filter -->
            <div>
                <label class="block text-[11px] font-bold text-foreground uppercase tracking-wider mb-1">Author</label>
                <select name="author" class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="all">Semua Author</option>
                    <?php 
                        $authorsList = array_unique(array_column($users, 'name'));
                        foreach ($authorsList as $auth): 
                    ?>
                        <option value="<?= esc($auth) ?>" <?= ($filters['author'] === $auth) ? 'selected' : '' ?>><?= esc($auth) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Editor Filter -->
            <div>
                <label class="block text-[11px] font-bold text-foreground uppercase tracking-wider mb-1">Editor / Approver</label>
                <select name="editor" class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="all">Semua Editor</option>
                    <?php 
                        $editorsList = array_unique(array_column($users, 'username'));
                        foreach ($editorsList as $ed): 
                    ?>
                        <option value="<?= esc($ed) ?>" <?= ($filters['editor'] === $ed) ? 'selected' : '' ?>><?= esc($ed) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-[11px] font-bold text-foreground uppercase tracking-wider mb-1">Dari Tanggal</label>
                <input type="text" name="date_from" id="filterDateFrom" value="<?= esc($filters['date_from'] ?? '') ?>" placeholder="YYYY-MM-DD" class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-[11px] font-bold text-foreground uppercase tracking-wider mb-1">Sampai Tanggal</label>
                <input type="text" name="date_to" id="filterDateTo" value="<?= esc($filters['date_to'] ?? '') ?>" placeholder="YYYY-MM-DD" class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
        </div>

        <!-- Quick Filter Chips Row -->
        <div class="flex flex-wrap items-center gap-1.5 mt-3 pt-3 border-t border-border/60 text-xs">
            <span class="text-[11px] font-semibold text-muted-foreground mr-1">Quick Filter:</span>

            <!-- All -->
            <a href="<?= base_url('post') ?>" class="rounded-md px-2.5 py-1 font-medium transition-colors <?= $isDefaultFilter ? 'bg-primary text-primary-foreground font-bold shadow-xs' : 'bg-muted/60 text-muted-foreground hover:text-foreground' ?>">
                All Posts
            </a>

            <!-- Publish -->
            <a href="<?= $isPublishActive ? base_url('post') : base_url('post?status=Publish') ?>" class="rounded-md px-2.5 py-1 font-medium transition-colors <?= $isPublishActive ? 'bg-success/15 text-success font-semibold border border-success/30' : 'bg-muted/60 text-muted-foreground hover:text-foreground' ?>">
                Publish
            </a>

            <!-- Drafts -->
            <a href="<?= $isDraftActive ? base_url('post') : base_url('post?status=Draft') ?>" class="rounded-md px-2.5 py-1 font-medium transition-colors <?= $isDraftActive ? 'bg-muted text-foreground font-semibold border border-border' : 'bg-muted/60 text-muted-foreground hover:text-foreground' ?>">
                Drafts
            </a>

            <!-- Hold -->
            <a href="<?= $isHoldActive ? base_url('post') : base_url('post?status=Hold') ?>" class="rounded-md px-2.5 py-1 font-medium transition-colors <?= $isHoldActive ? 'bg-amber-500/15 text-amber-700 font-semibold border border-amber-500/30' : 'bg-muted/60 text-muted-foreground hover:text-foreground' ?>">
                Hold
            </a>

            <?php if ($canViewDeleted): ?>
            <!-- Deleted (Trash) -->
            <a href="<?= $isDeletedActive ? base_url('post') : base_url('post?status=Deleted') ?>" class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 font-medium transition-colors <?= $isDeletedActive ? 'bg-destructive/15 text-destructive font-semibold border border-destructive/30' : 'bg-muted/60 text-muted-foreground hover:text-foreground' ?>">
                <i data-lucide="trash-2" class="size-3"></i>
                <span>Deleted</span>
            </a>
            <?php endif; ?>

            <!-- Premium Content -->
            <a href="<?= $isPremiumActive ? base_url('post') : base_url('post?premium=1') ?>" class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 font-medium transition-colors <?= $isPremiumActive ? 'bg-amber-500/15 text-amber-700 font-bold border border-amber-500/40' : 'bg-muted/60 text-muted-foreground hover:text-foreground' ?>">
                <i data-lucide="star" class="size-3 text-amber-600"></i>
                <span>Premium Content</span>
            </a>
        </div>
    </form>
</div>

<!-- Header Top Counter -->
<div class="flex flex-wrap items-center justify-between gap-1 mb-3 px-1 text-xs text-muted-foreground">
    <div>
        Menampilkan <strong class="text-foreground"><?= $pagination['from'] ?>–<?= $pagination['to'] ?></strong> dari <strong class="text-foreground"><?= $pagination['totalItems'] ?></strong> total artikel
    </div>
    <div class="text-[11px] text-muted-foreground/80">
        Halaman <?= $pagination['page'] ?> dari <?= $pagination['totalPages'] ?> &bull; 25 per page
    </div>
</div>

<!-- Articles Container (Mobile Cards vs Desktop Table) -->
<div class="surface-card overflow-hidden">
    <!-- 1. Mobile Cards Layout -->
    <div class="divide-y divide-border block md:hidden">
        <?php if (empty($posts)): ?>
            <div class="py-10 text-center px-4">
                <div class="flex flex-col items-center justify-center gap-2">
                    <i data-lucide="file-question" class="size-8 text-muted-foreground"></i>
                    <p class="text-sm font-medium text-foreground">No articles found</p>
                    <p class="text-xs text-muted-foreground">Try adjusting your active search keyword or filter chips.</p>
                    <a href="<?= base_url('post') ?>" class="mt-2 inline-flex rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium text-foreground hover:bg-muted">Reset Filters</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
            <?php 
                $status = strtolower($post['status']);
                $badgeClass = match($status) {
                    'publish', 'published' => 'bg-success/12 text-success border-success/25',
                    'draft' => 'bg-muted text-muted-foreground border-border',
                    'hold' => 'bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-500/30',
                    'deleted' => 'bg-destructive/15 text-destructive border-destructive/30',
                    default => 'bg-muted text-muted-foreground border-border'
                };
            ?>
            <div class="p-3.5 space-y-2.5">
                <div class="space-y-1">
                    <div class="min-w-0">
                        <a href="<?= base_url('post/edit/' . $post['id']) ?>" class="block font-medium text-xs text-foreground hover:text-primary transition-colors leading-snug line-clamp-2">
                            <?= esc($post['title']) ?>
                        </a>
                        <?php if (!empty($post['editlock'])): ?>
                            <div class="flex items-center gap-1.5 mt-1">
                                <span class="inline-flex items-center gap-1 text-[10px] text-rose-600 dark:text-rose-400 font-medium">
                                    <i data-lucide="lock" class="size-3"></i>
                                    <span>Diedit: <?= esc($post['locked_by_name'] ?: ($post['locked_by_username'] ?: 'User #' . $post['editlock'])) ?></span>
                                </span>
                                <?php if ($canViewDeleted): ?>
                                    <button type="button" onclick="forceUnlockItem(<?= (int)$post['id'] ?>)" class="text-[10px] text-amber-600 hover:text-amber-700 underline font-medium">Unlock</button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                            <span class="inline-flex items-center gap-1 rounded-md border px-1.5 py-0.2 text-[10px] font-medium capitalize <?= $badgeClass ?>">
                                <span class="size-1 rounded-full bg-current"></span>
                                <?= esc($post['status']) ?>
                            </span>
                            <?php if (!empty($post['is_premium'])): ?>
                                <span class="inline-flex items-center gap-0.5 rounded-sm bg-amber-500/15 px-1 py-0.2 text-[8px] font-bold text-amber-700 uppercase">
                                    <i data-lucide="star" class="size-2 text-amber-600"></i>
                                    <span>Premium</span>
                                </span>
                            <?php endif; ?>
                            <span class="text-[10px] text-muted-foreground font-mono">
                                Publish: <?= !empty($post['publish_date']) ? esc(date('d/m/y H:i', strtotime($post['publish_date']))) : '-' ?>
                            </span>
                            <?php if (!empty($post['post_by_name']) || !empty($post['post_by'])): ?>
                                <span class="inline-flex items-center gap-1 text-[10px] text-muted-foreground font-medium">
                                    <span>Ed: <?= esc($post['post_by_alias'] ?: ($post['post_by_name'] ?: $post['post_by'])) ?></span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-muted-foreground pt-1 border-t border-border/50">
                    <div class="truncate text-[10px] font-mono">
                        <span class="font-sans font-semibold text-foreground"><?= esc($post['author']) ?></span>
                        <span class="mx-1">&bull;</span>
                        <span>Dibuat: <?= !empty($post['create_date']) ? esc(date('d/m/y H:i', strtotime($post['create_date']))) : '-' ?></span>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <span class="font-bold text-foreground mr-1"><?= number_format($post['views']) ?> views</span>
                        <a href="<?= base_url('post/preview/' . $post['id']) ?>" target="_blank" class="inline-flex size-6.5 items-center justify-center rounded border border-input bg-background text-primary hover:bg-primary hover:text-white" title="Preview">
                            <i data-lucide="eye" class="size-3"></i>
                        </a>
                        <a href="<?= base_url('post/edit/' . $post['id']) ?>" class="inline-flex size-6.5 items-center justify-center rounded border border-input bg-background text-muted-foreground hover:text-foreground" title="Edit">
                            <i data-lucide="edit" class="size-3"></i>
                        </a>
                        <button type="button" onclick="openDeleteArticleModal(<?= $post['id'] ?>, '<?= esc(addslashes($post['title'])) ?>')" class="inline-flex size-6.5 items-center justify-center rounded border border-input bg-background text-destructive hover:bg-destructive/10 cursor-pointer" title="Hapus Naskah">
                            <i data-lucide="trash-2" class="size-3"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- 2. Desktop Table Layout -->
    <div class="w-full hidden md:block">
        <table class="w-full text-left text-xs table-fixed">
            <thead class="border-b border-border bg-muted/40 text-muted-foreground uppercase tracking-wider font-bold">
                <tr>
                    <th class="py-3 px-3 w-[38%]">Article / Headline</th>
                    <th class="py-3 px-2.5 w-[14%]">Author</th>
                    <th class="py-3 px-2.5 w-[12%]">Editor</th>
                    <th class="py-3 px-2 w-[11%]">Created At</th>
                    <th class="py-3 px-2 w-[11%]">Publish At</th>
                    <th class="py-3 px-1 w-[6%] text-center">Status</th>
                    <th class="py-3 px-2 w-[4%] text-right">Views</th>
                    <th class="py-3 px-3 w-[4%] text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php if (empty($posts)): ?>
                    <tr>
                        <td colspan="8" class="py-10 text-center">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <i data-lucide="file-question" class="size-8 text-muted-foreground"></i>
                                <p class="text-sm font-medium text-foreground">No articles found</p>
                                <a href="<?= base_url('post') ?>" class="mt-2 inline-flex rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium text-foreground hover:bg-muted">Reset Filters</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                    <tr class="transition-colors hover:bg-muted/50">
                        <td class="py-3 px-3">
                            <div class="min-w-0">
                                <a href="<?= base_url('post/edit/' . $post['id']) ?>" class="block font-medium text-foreground truncate hover:text-primary transition-colors text-xs leading-snug" title="<?= esc($post['title']) ?>">
                                    <?= esc($post['title']) ?>
                                </a>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="text-[10px] text-muted-foreground font-mono">#<?= esc($post['id']) ?></span>
                                    <?php if (!empty($post['editlock'])): ?>
                                        <span class="inline-flex items-center gap-1 text-[10px] text-rose-600 dark:text-rose-400 font-medium">
                                            <i data-lucide="lock" class="size-2.5"></i>
                                            <span>Diedit: <?= esc($post['locked_by_name'] ?: ($post['locked_by_username'] ?: 'User #' . $post['editlock'])) ?></span>
                                        </span>
                                        <?php if ($canViewDeleted): ?>
                                            <button type="button" onclick="forceUnlockItem(<?= (int)$post['id'] ?>)" class="text-[10px] text-amber-600 hover:text-amber-700 underline font-medium" title="Buka gembok artikel">Unlock</button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($post['is_premium'])): ?>
                                        <span class="inline-flex items-center gap-0.5 rounded-sm bg-amber-500/15 px-1 py-0.2 text-[8px] font-bold text-amber-700 uppercase">
                                            <i data-lucide="star" class="size-2 text-amber-600"></i>
                                            <span>Premium</span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-2.5 text-foreground font-medium truncate" title="<?= esc($post['author']) ?>"><?= esc($post['author']) ?></td>
                        <td class="py-3 px-2.5 text-muted-foreground truncate" title="<?= esc($post['post_by_name'] ?: $post['post_by']) ?>">
                            <span class="font-medium text-foreground"><?= esc($post['post_by_alias'] ?: ($post['post_by_name'] ?: ($post['post_by'] ?: '-'))) ?></span>
                            <?php if (!empty($post['post_by_alias']) && !empty($post['post_by_name'])): ?>
                                <span class="block text-[10px] text-muted-foreground/70 truncate"><?= esc($post['post_by_name']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-2 text-muted-foreground whitespace-nowrap text-[11px] font-mono">
                            <?= !empty($post['create_date']) ? esc(date('Y-m-d H:i', strtotime($post['create_date']))) : '-' ?>
                        </td>
                        <td class="py-3 px-2 text-muted-foreground whitespace-nowrap text-[11px] font-mono">
                            <?= !empty($post['publish_date']) ? esc(date('Y-m-d H:i', strtotime($post['publish_date']))) : '-' ?>
                        </td>
                        <td class="py-3 px-1 text-center">
                            <?php 
                                $status = strtolower($post['status']);
                                $badgeClass = match($status) {
                                    'publish', 'published' => 'bg-success/12 text-success border-success/25',
                                    'draft' => 'bg-muted text-muted-foreground border-border',
                                    'hold' => 'bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-500/30',
                                    'deleted' => 'bg-destructive/15 text-destructive border-destructive/30',
                                    default => 'bg-muted text-muted-foreground border-border'
                                };
                            ?>
                            <span class="inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 text-[11px] font-medium capitalize <?= $badgeClass ?>">
                                <span class="size-1 rounded-full bg-current"></span>
                                <?= esc($post['status']) ?>
                            </span>
                        </td>
                        <td class="py-3 px-2 text-right font-semibold text-foreground"><?= number_format($post['views']) ?></td>
                        <td class="py-3 px-3 text-right">
                            <div class="inline-flex items-center gap-1 justify-end">
                                <a href="<?= base_url('post/preview/' . $post['id']) ?>" target="_blank" class="inline-flex size-7 items-center justify-center rounded-md border border-input bg-background text-primary hover:text-primary-foreground hover:bg-primary" title="Preview">
                                    <i data-lucide="eye" class="size-3"></i>
                                </a>
                                <a href="<?= base_url('post/edit/' . $post['id']) ?>" class="inline-flex size-7 items-center justify-center rounded-md border border-input bg-background text-muted-foreground hover:text-foreground hover:bg-muted" title="Edit">
                                    <i data-lucide="edit" class="size-3"></i>
                                </a>
                                <button type="button" onclick="openDeleteArticleModal(<?= $post['id'] ?>, '<?= esc(addslashes($post['title'])) ?>')" class="inline-flex size-7 items-center justify-center rounded-md border border-input bg-background text-destructive hover:bg-destructive/10 cursor-pointer" title="Hapus Naskah">
                                    <i data-lucide="trash-2" class="size-3"></i>
                                </button>
                                <a href="<?= base_url('post/delete/' . $post['id']) ?>" class="hidden" aria-hidden="true" title="Hapus Naskah Direct"></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <?php
        $queryParams = $_GET;
        $buildPageUrl = function($pageNum) use ($queryParams) {
            $params = $queryParams;
            $params['page'] = $pageNum;
            return base_url('post') . '?' . http_build_query($params);
        };
    ?>
    <div class="flex items-center justify-between border-t border-border px-4 py-2.5 text-xs text-muted-foreground">
        <div>
            Page <strong class="text-foreground"><?= $pagination['page'] ?></strong> of <?= $pagination['totalPages'] ?>
        </div>
        <div class="flex items-center gap-1">
            <?php if ($pagination['page'] > 1): ?>
                <a href="<?= $buildPageUrl($pagination['page'] - 1) ?>" class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-foreground hover:bg-muted transition-colors">Previous</a>
            <?php else: ?>
                <span class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-muted-foreground opacity-50 cursor-not-allowed">Previous</span>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $pagination['totalPages']; $p++): ?>
                <?php if ($p == $pagination['page']): ?>
                    <span class="rounded-md bg-primary px-2.5 py-1 font-bold text-primary-foreground"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= $buildPageUrl($p) ?>" class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-foreground hover:bg-muted transition-colors"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                <a href="<?= $buildPageUrl($pagination['page'] + 1) ?>" class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-foreground hover:bg-muted transition-colors">Next</a>
            <?php else: ?>
                <span class="rounded-md border border-input bg-background px-2.5 py-1 font-medium text-muted-foreground opacity-50 cursor-not-allowed">Next</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Soft Delete Berbobot Alasan Redaksi -->
<div id="deleteArticleModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="surface-card w-full max-w-md p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150 border border-border rounded-xl">
        <div class="flex items-center gap-3 text-destructive">
            <div class="grid size-10 place-items-center rounded-full bg-destructive/10 shrink-0">
                <i data-lucide="trash-2" class="size-5"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-foreground">Konfirmasi Hapus Naskah</h3>
                <p class="text-xs text-muted-foreground">Artikel akan dipindahkan ke status <span class="font-bold text-destructive">Deleted</span> (Soft Delete).</p>
            </div>
        </div>

        <div class="p-3 bg-muted/40 rounded-lg border border-border/80">
            <span class="text-[10px] uppercase font-bold text-muted-foreground block mb-1">Judul Artikel:</span>
            <p id="deleteModalArticleTitle" class="text-xs font-semibold text-foreground line-clamp-2"></p>
        </div>

        <form id="deleteArticleForm" method="post" action="" class="space-y-3">
            <?= csrf_field() ?>
            <div>
                <label for="deleteReasonSelect" class="block text-xs font-bold text-foreground mb-1">Alasan Penghapusan <span class="text-destructive">*</span></label>
                <select name="reason" id="deleteReasonSelect" required class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs focus:ring-1 focus:ring-destructive">
                    <option value="Salah Ketik / Isi Keliru">Salah Ketik / Isi Keliru Fatal</option>
                    <option value="Duplikasi Naskah Berita">Duplikasi Naskah Berita</option>
                    <option value="Permintaan Narasumber / Legal">Permintaan Narasumber / Legal</option>
                    <option value="Embargo Belum Waktunya">Embargo Publikasi Belum Waktunya</option>
                    <option value="Perubahan Kebijakan Redaksi">Perubahan Kebijakan Redaksi</option>
                    <option value="Lainnya">Alasan Lainnya</option>
                </select>
            </div>

            <div>
                <label for="deleteNotesText" class="block text-xs font-bold text-foreground mb-1">Catatan Tambahan (Opsional)</label>
                <textarea name="notes" id="deleteNotesText" rows="2" placeholder="Tuliskan catatan rinci untuk log audit revisi..." class="w-full rounded-md border border-input bg-background p-2 text-xs focus:ring-1 focus:ring-destructive placeholder:text-muted-foreground"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
                <button type="button" onclick="closeDeleteArticleModal()" class="rounded-md border border-border bg-background px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-muted transition-colors">
                    Batal
                </button>
                <button type="submit" class="rounded-md bg-destructive px-3.5 py-1.5 text-xs font-bold text-destructive-foreground hover:bg-destructive/90 transition-colors shadow-xs">
                    Hapus Naskah (Soft Delete)
                </button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="/js/pages/post.js"></script>
<script>
function forceUnlockItem(articleId) {
    if (!confirm('Yakin ingin membuka gembok artikel ini secara paksa?')) return;
    fetch('<?= base_url('api/post/force-unlock/') ?>' + articleId, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Gagal membuka gembok.');
        }
    })
    .catch(err => alert('Terjadi kesalahan: ' + err.message));
}

function openDeleteArticleModal(articleId, articleTitle) {
    const modal = document.getElementById('deleteArticleModal');
    const titleEl = document.getElementById('deleteModalArticleTitle');
    const form = document.getElementById('deleteArticleForm');
    
    if (titleEl) titleEl.textContent = articleTitle;
    if (form) form.action = '<?= base_url('post/delete/') ?>' + articleId;
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeDeleteArticleModal() {
    const modal = document.getElementById('deleteArticleModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}
</script>
<?= $this->endSection() ?>

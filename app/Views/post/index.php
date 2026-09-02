<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="/css/pages/post.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- PageHeader -->
<div class="mb-6">
    <div class="grid grid-cols-[minmax(0,1fr)_auto] items-start gap-4 sm:flex sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h1 class="truncate text-2xl font-bold tracking-tight text-foreground">Posts & News Articles</h1>
            <p class="mt-1 text-sm text-muted-foreground">Manage, review, filter, and publish articles across the Investor.id editorial network.</p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <a href="<?= base_url('post/export') ?>" class="inline-flex items-center justify-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted transition-colors">
                <i data-lucide="download" class="size-4"></i>
                <span>Download Artikel List</span>
            </a>
            <a href="<?= base_url('post/new') ?>" class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 transition-colors">
                <i data-lucide="plus" class="size-4"></i>
                <span>Add New Post</span>
            </a>
        </div>
    </div>
</div>

<?php
// Compute boolean states for toggleable quick filters
$isDefaultFilter = (($filters['status'] ?? 'all') === 'all' && ($filters['premium'] ?? 'all') === 'all' && empty($filters['q']) && ($filters['kanal'] ?? 'all') === 'all' && ($filters['author'] ?? 'all') === 'all' && ($filters['editor'] ?? 'all') === 'all' && empty($filters['date_from']) && empty($filters['date_to']));
$isPublishedActive = (($filters['status'] ?? '') === 'Published' && ($filters['premium'] ?? 'all') === 'all');
$isDraftActive = (($filters['status'] ?? '') === 'Draft' && ($filters['premium'] ?? 'all') === 'all');
$isScheduledActive = (($filters['status'] ?? '') === 'Scheduled' && ($filters['premium'] ?? 'all') === 'all');
$isPremiumActive = (($filters['premium'] ?? '') === '1');
?>

<!-- Modern Filter Toolbar -->
<div class="surface-card p-4 mb-6">
    <form method="get" action="<?= base_url('post') ?>" id="postFilterForm">
        <!-- Row 1: Primary Filters -->
        <div class="flex flex-wrap gap-3 items-center justify-between">
            <!-- Keyword Search -->
            <div class="relative flex-1 min-w-[240px]">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground"></i>
                <input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>" class="flex h-9 w-full rounded-md border border-input bg-background pl-9 pr-3 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Cari judul artikel atau author...">
            </div>

            <!-- Kanal Select -->
            <div class="min-w-[150px]">
                <select name="kanal" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" onchange="this.form.submit()">
                    <option value="all">Kanal: Semua</option>
                    <?php foreach ($kanals as $kn): ?>
                        <option value="<?= esc($kn['name']) ?>" <?= ($filters['kanal'] === $kn['name']) ? 'selected' : '' ?>>
                            <?= esc($kn['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Select -->
            <div class="min-w-[130px]">
                <select name="status" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" onchange="this.form.submit()">
                    <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>>Status: Semua</option>
                    <option value="Published" <?= $filters['status'] === 'Published' ? 'selected' : '' ?>>Published</option>
                    <option value="Draft" <?= $filters['status'] === 'Draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="Scheduled" <?= $filters['status'] === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="Review" <?= $filters['status'] === 'Review' ? 'selected' : '' ?>>Review</option>
                </select>
            </div>

            <!-- Premium Select -->
            <div class="min-w-[130px]">
                <select name="premium" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" onchange="this.form.submit()">
                    <option value="all" <?= $filters['premium'] === 'all' ? 'selected' : '' ?>>Tier: Semua</option>
                    <option value="1" <?= $filters['premium'] === '1' ? 'selected' : '' ?>>Premium Only</option>
                    <option value="0" <?= $filters['premium'] === '0' ? 'selected' : '' ?>>Free / Public</option>
                </select>
            </div>

            <!-- Action & Toggle More Filter -->
            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-md bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 transition-colors">
                    <i data-lucide="filter" class="size-3.5"></i>
                    <span>Terapkan</span>
                </button>
                
                <button type="button" onclick="toggleMoreFilter()" id="btnMoreFilter" class="inline-flex items-center justify-center gap-1 rounded-md border border-input bg-background px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-muted hover:text-foreground transition-colors">
                    <i data-lucide="sliders-horizontal" class="size-3.5"></i>
                    <span>More Filter</span>
                    <i data-lucide="chevron-down" class="size-3.5 transition-transform duration-200" id="moreFilterIcon"></i>
                </button>

                <?php if (!empty($filters['q']) || $filters['kanal'] !== 'all' || $filters['status'] !== 'all' || $filters['premium'] !== 'all' || ($filters['author'] ?? 'all') !== 'all' || ($filters['editor'] ?? 'all') !== 'all' || !empty($filters['date_from']) || !empty($filters['date_to'])): ?>
                    <a href="<?= base_url('post') ?>" class="inline-flex items-center justify-center rounded-md border border-input bg-background p-2 text-sm text-muted-foreground hover:bg-muted hover:text-foreground" title="Reset Semua Filter">
                        <i data-lucide="x" class="size-4"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Row 2: Collapsible More Filter (Author, Editor, Date Range) -->
        <?php 
            $hasMoreFilterActive = ($filters['author'] !== 'all' || $filters['editor'] !== 'all' || !empty($filters['date_from']) || !empty($filters['date_to']));
        ?>
        <div id="moreFilterPanel" class="<?= $hasMoreFilterActive ? '' : 'hidden' ?> pt-3 mt-3 border-t border-border grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 animate-in fade-in duration-150">
            <!-- Filter Author -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Filter Author</label>
                <select name="author" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" onchange="this.form.submit()">
                    <option value="all">Semua Author</option>
                    <?php 
                        $authors = array_filter($users, fn($u) => in_array($u['role'], ['Author', 'Contributor']));
                        foreach ($authors as $u): 
                    ?>
                        <option value="<?= esc($u['name']) ?>" <?= ($filters['author'] === $u['name']) ? 'selected' : '' ?>>
                            <?= esc($u['name']) ?> (<?= esc($u['role']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Editor -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Filter Editor / Poster</label>
                <select name="editor" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" onchange="this.form.submit()">
                    <option value="all">Semua Editor</option>
                    <?php 
                        $editors = array_filter($users, fn($u) => in_array($u['role'], ['Editor', 'Admin']));
                        foreach ($editors as $u): 
                    ?>
                        <option value="<?= esc($u['username']) ?>" <?= ($filters['editor'] === $u['username']) ? 'selected' : '' ?>>
                            <?= esc($u['name']) ?> (@<?= esc($u['username']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tanggal Dari -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Tanggal Dari</label>
                <input type="text" name="date_from" id="postFilterDateFrom" value="<?= esc($filters['date_from'] ?? '') ?>" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring cursor-pointer" placeholder="YYYY-MM-DD">
            </div>

            <!-- Tanggal Sampai -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Tanggal Sampai</label>
                <input type="text" name="date_to" id="postFilterDateTo" value="<?= esc($filters['date_to'] ?? '') ?>" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring cursor-pointer" placeholder="YYYY-MM-DD">
            </div>
        </div>

        <!-- Quick Status Chips Bar -->
        <div class="flex items-center gap-2 mt-3 pt-3 border-t border-border flex-wrap text-xs">
            <span class="font-semibold text-muted-foreground">Quick Filter:</span>
            
            <!-- All Posts -->
            <a href="<?= base_url('post') ?>" class="rounded-md px-2.5 py-1 font-medium transition-colors <?= $isDefaultFilter ? 'bg-primary/10 text-primary font-semibold border border-primary/30' : 'bg-muted/60 text-muted-foreground hover:text-foreground' ?>">
                All Posts
            </a>

            <!-- Published -->
            <a href="<?= $isPublishedActive ? base_url('post') : base_url('post?status=Published') ?>" class="rounded-md px-2.5 py-1 font-medium transition-colors <?= $isPublishedActive ? 'bg-primary/10 text-primary font-semibold border border-primary/30' : 'bg-muted/60 text-muted-foreground hover:text-foreground' ?>">
                Published
            </a>

            <!-- Drafts -->
            <a href="<?= $isDraftActive ? base_url('post') : base_url('post?status=Draft') ?>" class="rounded-md px-2.5 py-1 font-medium transition-colors <?= $isDraftActive ? 'bg-primary/10 text-primary font-semibold border border-primary/30' : 'bg-muted/60 text-muted-foreground hover:text-foreground' ?>">
                Drafts
            </a>

            <!-- Scheduled -->
            <a href="<?= $isScheduledActive ? base_url('post') : base_url('post?status=Scheduled') ?>" class="rounded-md px-2.5 py-1 font-medium transition-colors <?= $isScheduledActive ? 'bg-primary/10 text-primary font-semibold border border-primary/30' : 'bg-muted/60 text-muted-foreground hover:text-foreground' ?>">
                Scheduled
            </a>

            <!-- Premium Content -->
            <a href="<?= $isPremiumActive ? base_url('post') : base_url('post?premium=1') ?>" class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 font-medium transition-colors <?= $isPremiumActive ? 'bg-amber-500/15 text-amber-700 font-bold border border-amber-500/40' : 'bg-muted/60 text-muted-foreground hover:text-foreground' ?>">
                <i data-lucide="star" class="size-3 text-amber-600"></i>
                <span>Premium Content</span>
            </a>
        </div>
    </form>
</div>

<!-- Header Top Counter -->
<div class="flex items-center justify-between mb-3 px-1 text-xs text-muted-foreground">
    <div>
        Menampilkan <strong class="text-foreground"><?= $pagination['from'] ?>–<?= $pagination['to'] ?></strong> dari <strong class="text-foreground"><?= $pagination['totalItems'] ?></strong> total artikel
    </div>
    <div class="text-[11px] text-muted-foreground/80">
        Halaman <?= $pagination['page'] ?> dari <?= $pagination['totalPages'] ?> &bull; 25 per page
    </div>
</div>

<!-- DataTable Component -->
<div class="surface-card overflow-hidden">
    <div class="w-full">
        <table class="w-full text-left text-xs table-fixed">
            <thead class="border-b border-border bg-muted/40 text-muted-foreground uppercase tracking-wider font-bold">
                <tr>
                    <th class="py-3 px-3 w-[38%]">Article / Headline</th>
                    <th class="py-3 px-2 w-[14%]">Kanal / Sub-Kanal</th>
                    <th class="py-3 px-2 w-[14%]">Author</th>
                    <th class="py-3 px-2 w-[13%]">Publish Date</th>
                    <th class="py-3 px-2 w-[10%]">Status</th>
                    <th class="py-3 px-2 w-[5%] text-right">Views</th>
                    <th class="py-3 px-3 w-[6%] text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php if (empty($posts)): ?>
                    <tr>
                        <td colspan="7" class="py-10 text-center">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <i data-lucide="file-question" class="size-8 text-muted-foreground"></i>
                                <p class="text-sm font-medium text-foreground">No articles found</p>
                                <p class="text-xs text-muted-foreground">Try adjusting your active search keyword or filter chips.</p>
                                <a href="<?= base_url('post') ?>" class="mt-2 inline-flex rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium text-foreground hover:bg-muted">Reset Filters</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                    <tr class="transition-colors hover:bg-muted/50">
                        <!-- Article / Headline -->
                        <td class="py-3 px-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <img src="<?= esc($post['thumbnail']) ?>" alt="" loading="lazy" decoding="async" class="size-9 rounded-md object-cover shrink-0 border border-border">
                                <div class="min-w-0 flex-1">
                                    <a href="<?= base_url('post/edit/' . $post['id']) ?>" class="block font-medium text-foreground truncate hover:text-primary transition-colors text-xs leading-snug" title="<?= esc($post['title']) ?>">
                                        <?= esc($post['title']) ?>
                                    </a>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <?php if (!empty($post['is_premium'])): ?>
                                            <span class="inline-flex items-center gap-0.5 rounded-sm bg-amber-500/15 px-1 py-0.2 text-[8px] font-bold text-amber-700 uppercase">
                                                <i data-lucide="star" class="size-2 text-amber-600"></i>
                                                <span>Premium</span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Kanal / Sub-Kanal -->
                        <td class="py-3 px-2 text-muted-foreground truncate">
                            <span class="font-semibold text-foreground"><?= esc($post['kanal'] ?? $post['category'] ?? 'Market') ?></span>
                            <?php if (!empty($post['sub_kanal'])): ?>
                                <span class="text-[10px] text-muted-foreground block truncate">&bull; <?= esc($post['sub_kanal']) ?></span>
                            <?php endif; ?>
                        </td>

                        <!-- Author -->
                        <td class="py-3 px-2 text-foreground font-medium truncate">
                            <?= esc($post['author']) ?>
                        </td>

                        <!-- Publish Date -->
                        <td class="py-3 px-2 text-muted-foreground whitespace-nowrap">
                            <?= esc($post['publish_date']) ?>
                        </td>

                        <!-- Status -->
                        <td class="py-3 px-2">
                            <?php 
                                $status = strtolower($post['status']);
                                $badgeClass = match($status) {
                                    'published' => 'bg-success/12 text-success border-success/25',
                                    'draft' => 'bg-muted text-muted-foreground border-border',
                                    'scheduled' => 'bg-accent/12 text-accent border-accent/25',
                                    'review' => 'bg-warning/15 text-warning border-warning/30',
                                    default => 'bg-muted text-muted-foreground border-border'
                                };
                            ?>
                            <span class="inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 text-[11px] font-medium capitalize <?= $badgeClass ?>">
                                <span class="size-1 rounded-full bg-current"></span>
                                <?= esc($post['status']) ?>
                            </span>
                        </td>

                        <!-- Views -->
                        <td class="py-3 px-2 text-right font-semibold text-foreground">
                            <?= number_format($post['views']) ?>
                        </td>

                        <!-- Action -->
                        <td class="py-3 px-3 text-right">
                            <div class="inline-flex items-center gap-1 justify-end">
                                <a href="<?= base_url('post/preview/' . $post['id']) ?>" target="_blank" class="inline-flex size-7 items-center justify-center rounded-md border border-input bg-background text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50" title="Preview Artikel">
                                    <i data-lucide="eye" class="size-3"></i>
                                </a>
                                <a href="<?= base_url('post/edit/' . $post['id']) ?>" class="inline-flex size-7 items-center justify-center rounded-md border border-input bg-background text-muted-foreground hover:text-foreground hover:bg-muted" title="Edit Post">
                                    <i data-lucide="edit" class="size-3"></i>
                                </a>
                                <a href="<?= base_url('post/delete/' . $post['id']) ?>" class="inline-flex size-7 items-center justify-center rounded-md border border-input bg-background text-destructive hover:bg-destructive/10" title="Delete Post" onclick="return confirm('Delete this article?')">
                                    <i data-lucide="trash-2" class="size-3"></i>
                                </a>
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

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="/js/pages/post.js"></script>
<?= $this->endSection() ?>

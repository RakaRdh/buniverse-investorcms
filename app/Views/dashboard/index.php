<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/css/pages/dashboard.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$hour = (int)date('H');
if ($hour < 11) {
    $timeGreeting = 'Selamat pagi';
} elseif ($hour < 15) {
    $timeGreeting = 'Selamat siang';
} elseif ($hour < 18) {
    $timeGreeting = 'Selamat sore';
} else {
    $timeGreeting = 'Selamat malam';
}
$userName = session('user_name') ? explode(' ', session('user_name'))[0] : 'Editor';
?>

<!-- PageHeader Responsive Layout -->
<div class="mb-6 sm:mb-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl lg:text-[28px] leading-tight">
                <?= $timeGreeting ?>, <?= esc($userName) ?>
            </h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Berikut ikhtisar terkini redaksi Investor.id Newsroom hari ini.</p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <a href="<?= base_url('post/new') ?>" class="inline-flex flex-1 sm:flex-initial items-center justify-center gap-1.5 rounded-md bg-primary px-3.5 py-2 text-xs sm:text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 transition-colors">
                <i data-lucide="plus" class="size-4"></i>
                <span>New Post</span>
            </a>
            <a href="<?= base_url('headline') ?>" class="inline-flex flex-1 sm:flex-initial items-center justify-center gap-1.5 rounded-md border border-input bg-background px-3.5 py-2 text-xs sm:text-sm font-medium text-foreground shadow-sm hover:bg-muted transition-colors">
                <i data-lucide="newspaper" class="size-4"></i>
                <span>Curate Headline</span>
            </a>
        </div>
    </div>
</div>

<!-- MetricCard Grid (2x2 on Mobile, 4 Cols on Desktop) -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 sm:mb-8">
    <!-- Card 1 -->
    <div class="surface-card p-3.5 sm:p-5 transition-shadow duration-150 hover:shadow-raised">
        <div class="flex items-center justify-between gap-2">
            <span class="text-[10px] sm:text-xs font-semibold tracking-wide text-muted-foreground uppercase truncate">Today's Articles</span>
            <span class="grid size-7 sm:size-8 shrink-0 place-items-center rounded-lg bg-primary/8 text-primary">
                <i data-lucide="file-text" class="size-4 sm:size-[18px]"></i>
            </span>
        </div>
        <p class="mt-2 sm:mt-3 text-2xl sm:text-3xl font-semibold tracking-tight text-foreground"><?= $metrics['todayArticles'] ?></p>
        <p class="mt-1 sm:mt-2 inline-flex items-center gap-1 text-[11px] sm:text-xs font-medium text-success">
            <i data-lucide="arrow-up-right" class="size-3"></i>
            <span>+12%</span>
            <span class="hidden sm:inline text-muted-foreground">vs yesterday</span>
        </p>
    </div>

    <!-- Card 2 -->
    <div class="surface-card p-3.5 sm:p-5 transition-shadow duration-150 hover:shadow-raised">
        <div class="flex items-center justify-between gap-2">
            <span class="text-[10px] sm:text-xs font-semibold tracking-wide text-muted-foreground uppercase truncate">Published</span>
            <span class="grid size-7 sm:size-8 shrink-0 place-items-center rounded-lg bg-success/10 text-success">
                <i data-lucide="check-circle" class="size-4 sm:size-[18px]"></i>
            </span>
        </div>
        <p class="mt-2 sm:mt-3 text-2xl sm:text-3xl font-semibold tracking-tight text-foreground"><?= $metrics['published'] ?></p>
        <p class="mt-1 sm:mt-2 text-[11px] sm:text-xs text-muted-foreground truncate">
            94% on-time delivery
        </p>
    </div>

    <!-- Card 3 -->
    <div class="surface-card p-3.5 sm:p-5 transition-shadow duration-150 hover:shadow-raised">
        <div class="flex items-center justify-between gap-2">
            <span class="text-[10px] sm:text-xs font-semibold tracking-wide text-muted-foreground uppercase truncate">In Review</span>
            <span class="grid size-7 sm:size-8 shrink-0 place-items-center rounded-lg bg-warning/15 text-warning">
                <i data-lucide="clock" class="size-4 sm:size-[18px]"></i>
            </span>
        </div>
        <p class="mt-2 sm:mt-3 text-2xl sm:text-3xl font-semibold tracking-tight text-foreground"><?= $metrics['drafts'] + $metrics['review'] ?></p>
        <p class="mt-1 sm:mt-2 text-[11px] sm:text-xs font-medium text-warning truncate">
            <?= $metrics['review'] ?> pending review
        </p>
    </div>

    <!-- Card 4 -->
    <div class="surface-card p-3.5 sm:p-5 transition-shadow duration-150 hover:shadow-raised">
        <div class="flex items-center justify-between gap-2">
            <span class="text-[10px] sm:text-xs font-semibold tracking-wide text-muted-foreground uppercase truncate">Total Views</span>
            <span class="grid size-7 sm:size-8 shrink-0 place-items-center rounded-lg bg-accent/15 text-accent">
                <i data-lucide="eye" class="size-4 sm:size-[18px]"></i>
            </span>
        </div>
        <p class="mt-2 sm:mt-3 text-2xl sm:text-3xl font-semibold tracking-tight text-foreground"><?= $metrics['totalViews'] ?></p>
        <p class="mt-1 sm:mt-2 inline-flex items-center gap-1 text-[11px] sm:text-xs font-medium text-success">
            <i data-lucide="arrow-up-right" class="size-3"></i>
            <span>+18.4%</span>
            <span class="hidden sm:inline text-muted-foreground">growth</span>
        </p>
    </div>
</div>

<!-- Section 2: Charts & Quick Editorial Stream -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Chart Card (SectionCard.tsx) -->
    <div class="surface-card lg:col-span-2">
        <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-4 border-b border-border">
            <div>
                <h2 class="text-sm font-semibold text-foreground">Editorial Publishing Activity</h2>
                <p class="mt-0.5 text-xs text-muted-foreground">Volume artikel dan draft yang diproduksi</p>
            </div>
            <div class="flex items-center gap-1.5 rounded-lg border border-border bg-muted/40 p-1 text-xs font-medium">
                <button type="button" id="btnPeriodToday" onclick="switchDashboardPeriod('today')" class="period-btn rounded-md bg-background px-2.5 py-1 text-foreground shadow-xs font-semibold transition-all">Today</button>
                <button type="button" id="btnPeriod7d" onclick="switchDashboardPeriod('7d')" class="period-btn rounded-md px-2.5 py-1 text-muted-foreground hover:text-foreground transition-all">7 Days</button>
                <button type="button" id="btnPeriod30d" onclick="switchDashboardPeriod('30d')" class="period-btn rounded-md px-2.5 py-1 text-muted-foreground hover:text-foreground transition-all">30 Days</button>
            </div>
        </div>
        <div class="p-5">
            <div id="publishingChart" class="min-h-[280px]"></div>
        </div>
    </div>

    <!-- Quick Actions & Live Stream (Restored to original design) -->
    <div class="surface-card flex flex-col justify-between">
        <div>
            <div class="px-5 py-4 border-b border-border">
                <h2 class="text-sm font-semibold text-foreground">Quick Actions</h2>
            </div>
            <div class="p-5 grid grid-cols-2 gap-2.5">
                <a href="<?= base_url('post/new') ?>" class="flex items-center gap-2.5 rounded-lg border border-border bg-background p-3 text-xs font-medium text-foreground hover:bg-muted transition-colors">
                    <i data-lucide="file-plus" class="size-4 text-primary"></i>
                    <span>New Post</span>
                </a>
                <a href="<?= base_url('headline') ?>" class="flex items-center gap-2.5 rounded-lg border border-border bg-background p-3 text-xs font-medium text-foreground hover:bg-muted transition-colors">
                    <i data-lucide="newspaper" class="size-4 text-accent"></i>
                    <span>Curate Cover</span>
                </a>
                <a href="<?= base_url('media') ?>" class="flex items-center gap-2.5 rounded-lg border border-border bg-background p-3 text-xs font-medium text-foreground hover:bg-muted transition-colors">
                    <i data-lucide="upload-cloud" class="size-4 text-success"></i>
                    <span>Upload Media</span>
                </a>
                <a href="<?= base_url('embed-chart') ?>" class="flex items-center gap-2.5 rounded-lg border border-border bg-background p-3 text-xs font-medium text-foreground hover:bg-muted transition-colors">
                    <i data-lucide="line-chart" class="size-4 text-warning"></i>
                    <span>Embed Chart</span>
                </a>
            </div>

            <div class="px-5 py-3 border-t border-border">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Editorial Stream</h3>
            </div>
            <div class="px-5 pb-5 space-y-3 text-xs">
                <?php 
                    $latestStream = array_slice($posts, 0, 3);
                    foreach ($latestStream as $st): 
                        $timeOnly = substr($st['create_date'], -5);
                        $authorFirst = explode(' ', $st['author'])[0];
                ?>
                <div class="flex items-start gap-2.5">
                    <span class="font-semibold text-muted-foreground whitespace-nowrap"><?= esc($timeOnly) ?></span>
                    <p class="text-foreground truncate">
                        <strong><?= esc($authorFirst) ?></strong> published 
                        <a href="<?= base_url('post/edit/' . $st['id']) ?>" class="hover:text-primary transition-colors italic truncate" title="<?= esc($st['title']) ?>">
                            "<?= esc(mb_strimwidth($st['title'], 0, 32, '...')) ?>"
                        </a>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="border-t border-border px-5 py-3 text-center">
            <a href="<?= base_url('post') ?>" class="text-xs font-medium text-primary hover:underline">View full editorial log &rarr;</a>
        </div>
    </div>
</div>

<!-- Section 3: Top Performing Articles (Responsive Cards on Mobile, Table on Desktop) -->
<div class="surface-card overflow-hidden">
    <div class="flex items-center justify-between px-4 sm:px-5 py-3.5 sm:py-4 border-b border-border">
        <div>
            <h2 class="text-sm font-semibold text-foreground">Top Performing Articles Today</h2>
            <p class="mt-0.5 text-xs text-muted-foreground">Real-time reader traffic, engagement, and ranking</p>
        </div>
        <a href="<?= base_url('statistic') ?>" class="inline-flex items-center justify-center rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium text-foreground shadow-xs hover:bg-muted">
            Full Analytics
        </a>
    </div>

    <!-- Mobile Cards Layout (Matching Gambar 3) -->
    <div class="divide-y divide-border block sm:hidden">
        <?php $rank = 1; foreach ($topArticles as $art): ?>
        <?php 
            $status = strtolower($art['status']);
            $badgeClass = match($status) {
                'published' => 'bg-success/12 text-success border-success/25',
                'draft' => 'bg-muted text-muted-foreground border-border',
                'scheduled' => 'bg-accent/12 text-accent border-accent/25',
                'review' => 'bg-warning/15 text-warning border-warning/30',
                default => 'bg-muted text-muted-foreground border-border'
            };
        ?>
        <div class="p-3.5 space-y-2.5">
            <div class="flex items-start justify-between gap-2">
                <a href="<?= base_url('post/edit/' . $art['id']) ?>" class="font-medium text-xs leading-snug text-foreground hover:text-primary transition-colors flex-1 line-clamp-2">
                    <?= esc($art['title']) ?>
                </a>
                <a href="<?= base_url('post/edit/' . $art['id']) ?>" class="inline-flex size-7 shrink-0 items-center justify-center rounded-md border border-input bg-background text-muted-foreground" title="Edit Post">
                    <i data-lucide="more-horizontal" class="size-3.5"></i>
                </a>
            </div>

            <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                <span class="inline-flex items-center gap-1 rounded-md border px-1.5 py-0.2 font-medium capitalize <?= $badgeClass ?>">
                    <span class="size-1 rounded-full bg-current"></span>
                    <?= esc($art['status']) ?>
                </span>
                <?php if ($art['is_premium']): ?>
                    <span class="inline-flex items-center rounded-sm bg-premium px-1.5 py-0.2 text-[9px] font-semibold tracking-wide text-premium-foreground uppercase">
                        Premium
                    </span>
                <?php endif; ?>
                <span class="text-muted-foreground font-medium"><?= esc($art['category']) ?></span>
            </div>

            <div class="flex items-center justify-between text-[11px] text-muted-foreground pt-0.5">
                <span class="truncate">Oleh <?= esc($art['author']) ?></span>
                <span class="font-bold text-foreground"><?= number_format($art['views']) ?> views</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Desktop Table Layout -->
    <div class="relative w-full overflow-auto hidden sm:block">
        <table class="w-full caption-bottom text-sm">
            <thead class="[&_tr]:border-b border-border bg-muted/40">
                <tr class="text-left font-medium text-muted-foreground text-xs uppercase tracking-wider">
                    <th class="h-10 px-4 w-12">Rank</th>
                    <th class="h-10 px-4">Article</th>
                    <th class="h-10 px-4">Category</th>
                    <th class="h-10 px-4">Author</th>
                    <th class="h-10 px-4">Views</th>
                    <th class="h-10 px-4">Status</th>
                    <th class="h-10 px-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php $rank = 1; foreach ($topArticles as $art): ?>
                <tr class="transition-colors hover:bg-muted/50">
                    <td class="p-4 font-bold <?= $rank <= 3 ? 'text-primary' : 'text-muted-foreground' ?>">
                        #<?= $rank++ ?>
                    </td>
                    <td class="p-4">
                        <div class="flex items-center gap-3 max-w-md">
                            <img src="<?= esc($art['thumbnail']) ?>" alt="" class="size-10 rounded-md object-cover shrink-0 border border-border">
                            <div class="min-w-0">
                                <a href="<?= base_url('post/edit/' . $art['id']) ?>" class="block font-medium text-foreground truncate hover:text-primary transition-colors">
                                    <?= esc($art['title']) ?>
                                </a>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <?php if ($art['is_premium']): ?>
                                        <span class="inline-flex items-center rounded-sm bg-premium px-1.5 py-0.2 text-[9px] font-semibold tracking-wide text-premium-foreground uppercase">
                                            Premium
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($art['is_breaking'])): ?>
                                        <span class="inline-flex items-center rounded-sm bg-destructive px-1.5 py-0.2 text-[9px] font-semibold tracking-wide text-destructive-foreground uppercase">
                                            Breaking
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="p-4 text-xs font-medium text-muted-foreground"><?= esc($art['category']) ?></td>
                    <td class="p-4 text-xs text-foreground"><?= esc($art['author']) ?></td>
                    <td class="p-4 text-xs font-semibold text-foreground"><?= number_format($art['views']) ?></td>
                    <td class="p-4">
                        <?php 
                            $status = strtolower($art['status']);
                            $badgeClass = match($status) {
                                'published' => 'bg-success/12 text-success border-success/25',
                                'draft' => 'bg-muted text-muted-foreground border-border',
                                'scheduled' => 'bg-accent/12 text-accent border-accent/25',
                                'review' => 'bg-warning/15 text-warning border-warning/30',
                                default => 'bg-muted text-muted-foreground border-border'
                            };
                        ?>
                        <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-xs font-medium capitalize <?= $badgeClass ?>">
                            <span class="size-1.5 rounded-full bg-current"></span>
                            <?= esc($art['status']) ?>
                        </span>
                    </td>
                    <td class="p-4 text-right">
                        <a href="<?= base_url('post/edit/' . $art['id']) ?>" class="inline-flex size-8 items-center justify-center rounded-md border border-input bg-background text-muted-foreground hover:text-foreground hover:bg-muted" title="Edit Post">
                            <i data-lucide="edit-3" class="size-3.5"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="/js/pages/dashboard.js"></script>
<?= $this->endSection() ?>

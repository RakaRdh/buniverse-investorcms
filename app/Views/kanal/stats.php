<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- PageHeader Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Performa Kanal</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Komparasi produktivitas naskah berita, sebaran views, dan proporsi konten premium antar kanal.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
            <span class="rounded-md border border-border bg-card px-3 py-1 text-xs font-semibold text-foreground shadow-2xs">
                Total Kanal: <strong><?= count($kanalPerformance) ?></strong>
            </span>
            <span class="rounded-md border border-border bg-card px-3 py-1 text-xs font-semibold text-foreground shadow-2xs">
                Total Views: <strong><?= number_format($totalViews) ?></strong>
            </span>
        </div>
    </div>
</div>

<div class="surface-card overflow-hidden">
    <div class="relative w-full overflow-auto">
        <table class="w-full caption-bottom text-xs">
            <thead class="border-b border-border bg-muted/40 font-bold text-muted-foreground uppercase">
                <tr>
                    <th class="h-10 px-4 text-left">Nama Kanal</th>
                    <th class="h-10 px-4 text-center">Sub-Kanal</th>
                    <th class="h-10 px-4 text-center">Volume Naskah</th>
                    <th class="h-10 px-4 text-center">Artikel Published</th>
                    <th class="h-10 px-4 text-center">Konten Premium</th>
                    <th class="h-10 px-4 text-right">Akumulasi Views</th>
                    <th class="h-10 px-4 text-right">Avg. Views / Artikel</th>
                    <th class="h-10 px-4 text-center">Share Trafik</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php foreach ($kanalPerformance as $idx => $kp): ?>
                <?php
                    $pct = $totalViews > 0 ? round(($kp['total_views'] / $totalViews) * 100, 1) : 0;
                ?>
                <tr class="hover:bg-muted/40 transition-colors">
                    <td class="p-4 font-bold text-foreground text-xs">
                        <div class="flex items-center gap-2.5">
                            <span class="grid size-6 shrink-0 place-items-center rounded-full bg-primary/10 text-primary font-bold text-[10px]">
                                #<?= $idx + 1 ?>
                            </span>
                            <span class="font-bold text-sm"><?= esc($kp['name']) ?></span>
                        </div>
                    </td>
                    <td class="p-4 text-center text-muted-foreground">
                        <?= esc($kp['sub_count']) ?>
                    </td>
                    <td class="p-4 text-center font-medium text-foreground">
                        <?= esc($kp['total_articles']) ?>
                    </td>
                    <td class="p-4 text-center font-bold text-primary">
                        <span class="inline-flex rounded bg-primary/10 border border-primary/25 px-2 py-0.5 text-[11px]">
                            <?= esc($kp['published_count']) ?>
                        </span>
                    </td>
                    <td class="p-4 text-center font-semibold text-amber-700">
                        <?php if ($kp['premium_count'] > 0): ?>
                            <span class="inline-flex items-center gap-1 rounded bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold">
                                <i data-lucide="star" class="size-2.5 text-amber-600"></i>
                                <span><?= $kp['premium_count'] ?></span>
                            </span>
                        <?php else: ?>
                            <span class="text-muted-foreground/40 font-mono">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4 text-right font-mono font-bold text-foreground text-xs">
                        <?= number_format($kp['total_views']) ?>
                    </td>
                    <td class="p-4 text-right font-mono text-muted-foreground">
                        <?= number_format($kp['avg_views']) ?>
                    </td>
                    <td class="p-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <div class="w-16 h-1.5 bg-muted rounded-full overflow-hidden">
                                <div class="h-full bg-primary rounded-full" style="width: <?= min(100, $pct * 2) ?>%"></div>
                            </div>
                            <span class="font-bold text-[11px] text-foreground font-mono"><?= $pct ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>

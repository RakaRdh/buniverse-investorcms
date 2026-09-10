<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Page Header Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="truncate text-lg sm:text-2xl font-bold tracking-tight text-foreground leading-tight">Master Template Charts</h1>
                <span class="inline-flex items-center rounded-md bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary border border-primary/20">Master Templates</span>
            </div>
            <p class="mt-0.5 text-xs sm:text-sm text-muted-foreground truncate">Katalog master template grafik bawaan sistem (Bar Vertikal, Bar Horizontal, Line, Pie) untuk penulisan artikel redaksi.</p>
        </div>
        <div class="flex items-center gap-2 self-stretch sm:self-auto flex-wrap">
            <a href="<?= base_url('embed-chart/custom') ?>" class="inline-flex flex-1 sm:flex-initial items-center justify-center gap-1.5 rounded-md border border-input bg-background px-3.5 py-2 text-xs font-semibold text-foreground hover:bg-muted transition-colors shadow-xs">
                <i data-lucide="layers" class="size-4 text-muted-foreground"></i>
                <span>Custom Data</span>
            </a>
            <a href="<?= base_url('embed-chart/custom/create') ?>" class="inline-flex flex-1 sm:flex-initial items-center justify-center gap-1.5 rounded-md bg-[#2B6CB0] px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90 transition-colors">
                <i data-lucide="plus" class="size-4"></i>
                <span>Add Custom Chart</span>
            </a>
        </div>
    </div>
</div>

<!-- Real Interactive Visual Previews Matching Each Chart Type -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php foreach ($templates as $tmpl): ?>
    <div class="surface-card p-5 rounded-xl border border-border shadow-xs flex flex-col justify-between space-y-4">
        <div>
            <!-- Header Card -->
            <div class="flex items-start justify-between gap-3 border-b border-border pb-3">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center rounded-sm bg-primary/10 px-2 py-0.5 text-[10px] font-bold text-primary">
                            <?= esc($tmpl['symbol']) ?>
                        </span>
                        <span class="text-[11px] font-semibold uppercase text-muted-foreground font-mono">
                            #<?= esc($tmpl['id']) ?>
                        </span>
                    </div>
                    <h3 class="font-bold text-foreground text-sm sm:text-base leading-snug"><?= esc($tmpl['title']) ?></h3>
                    <p class="text-[11px] text-muted-foreground capitalize font-medium">Tipe: <?= esc($tmpl['type']) ?> &bull; <?= esc($tmpl['source']) ?></p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-md border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400 capitalize">
                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                    Template Aktif
                </span>
            </div>

            <!-- Canvas Container with Fixed Aspect Ratio -->
            <div class="my-4 rounded-xl border border-border bg-card p-4 relative" style="height: 270px;">
                <canvas id="chart_canvas_<?= esc($tmpl['id']) ?>"></canvas>
            </div>

            <!-- Categories pills -->
            <div class="space-y-1.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Kategori Sumbu X (<?= count($tmpl['labels']) ?> Titik):</span>
                <div class="flex flex-wrap gap-1 max-h-16 overflow-y-auto pr-1">
                    <?php foreach (array_slice($tmpl['labels'], 0, 8) as $lbl): ?>
                        <span class="rounded bg-muted/60 px-1.5 py-0.5 text-[10px] text-muted-foreground border border-border"><?= esc($lbl) ?></span>
                    <?php endforeach; ?>
                    <?php if (count($tmpl['labels']) > 8): ?>
                        <span class="rounded bg-muted/40 px-1.5 py-0.5 text-[10px] text-muted-foreground italic">+<?= count($tmpl['labels']) - 8 ?> lainnya</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Card Footer -->
        <div class="flex items-center justify-between border-t border-border pt-3 text-xs text-muted-foreground">
            <span>Sistem Master Bawaan</span>
            <span class="text-[11px]">Terakhir Sinkron: <?= esc(date('d M Y', strtotime($tmpl['updated']))) ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?= $this->section('scripts') ?>
<!-- Chart.js Engine -->
<script src="/js/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js failed to load.');
        return;
    }

    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.05)';

    const templatesData = <?= json_encode($templates) ?>;

    templatesData.forEach(tmpl => {
        const canvas = document.getElementById('chart_canvas_' + tmpl.id);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const labels = tmpl.labels && tmpl.labels.length ? tmpl.labels : ["A", "B", "C", "D", "E"];
        const values = tmpl.values && tmpl.values.length ? tmpl.values : [45, 65, 80, 50, 70];
        const type = tmpl.type;

        let config = {};

        if (type === 'bar-vertical') {
            config = {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: tmpl.yAxisTitle || 'Nilai',
                        data: values,
                        backgroundColor: 'rgba(37, 99, 235, 0.82)',
                        hoverBackgroundColor: 'rgba(29, 78, 216, 0.95)',
                        borderRadius: 4,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { padding: 8, cornerRadius: 6 }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: textColor, font: { size: 10 } } },
                        y: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 } } }
                    }
                }
            };
        } else if (type === 'bar-horizontal') {
            config = {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: tmpl.yAxisTitle || 'Nilai',
                        data: values,
                        backgroundColor: 'rgba(13, 148, 136, 0.82)',
                        hoverBackgroundColor: 'rgba(15, 118, 110, 0.95)',
                        borderRadius: 4,
                        borderSkipped: false,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { padding: 8, cornerRadius: 6 }
                    },
                    scales: {
                        x: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 } } },
                        y: { grid: { display: false }, ticks: { color: textColor, font: { size: 10 } } }
                    }
                }
            };
        } else if (type === 'line') {
            config = {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: tmpl.yAxisTitle || 'Nilai',
                        data: values,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.12)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#f59e0b',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { padding: 8, cornerRadius: 6 }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: textColor, font: { size: 10 } } },
                        y: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 } } }
                    }
                }
            };
        } else if (type === 'pie') {
            const palette = [
                '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', 
                '#ec4899', '#06b6d4', '#f97316', '#6366f1',
                '#14b8a6', '#e11d48'
            ];
            config = {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: palette.slice(0, labels.length),
                        borderWidth: 2,
                        borderColor: isDark ? '#1e293b' : '#ffffff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: textColor,
                                boxWidth: 10,
                                font: { size: 10 }
                            }
                        },
                        tooltip: { padding: 8, cornerRadius: 6 }
                    },
                    cutout: '55%'
                }
            };
        }

        new Chart(ctx, config);
    });
});
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isEdit = $isEdit ?? false;
$chart = $chart ?? null;
$chartId = $chart['id'] ?? ($chart['chartID'] ?? 0);

$actionUrl = $isEdit 
    ? base_url('embed-chart/custom/update/' . $chartId) 
    : base_url('embed-chart/custom/save');

$currentStyle = $chart['chartStyle'] ?? ($chart['type'] ?? 'bar-vertical');
$currentTitle = $chart['chartTitle'] ?? ($chart['title'] ?? '');
$currentSource = $chart['source'] ?? 'Investor Market Data';
$currentXTitle = $chart['xAxisTitle'] ?? ($chart['symbol'] ?? 'Kategori');
$currentYTitle = $chart['yAxisTitle'] ?? 'Nilai';

// Prepare comma-separated string for inputs
$labelsArr = $chart['labels'] ?? ['Kuartal I', 'Kuartal II', 'Kuartal III', 'Kuartal IV'];
$valuesArr = $chart['values'] ?? [120, 165, 190, 240];

$labelsText = implode(", ", $labelsArr);
$valuesText = implode(", ", $valuesArr);
?>

<!-- Page Header / Top Action Bar Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2.5 min-w-0">
            <a href="<?= base_url('embed-chart/custom') ?>" class="inline-flex size-9 shrink-0 items-center justify-center rounded-md border border-input bg-background text-muted-foreground hover:text-foreground hover:bg-muted transition-colors shadow-xs" title="Kembali ke Custom Data">
                <i data-lucide="arrow-left" class="size-4"></i>
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="truncate text-lg sm:text-2xl font-bold tracking-tight text-foreground leading-tight">
                        <?= $isEdit ? 'Edit Custom Chart' : 'Create Custom Chart' ?>
                    </h1>
                    <span class="inline-flex items-center rounded-md bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary border border-primary/20">
                        <?= $isEdit ? 'ID #' . esc($chartId) : 'New' ?>
                    </span>
                </div>
                <p class="mt-0.5 text-xs sm:text-sm text-muted-foreground truncate">
                    Atur parameter grafik, pilih template visual, dan sesuaikan data sumbu X &amp; Y.
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 self-stretch sm:self-auto flex-wrap">
            <a href="<?= base_url('embed-chart/custom') ?>" class="inline-flex flex-1 sm:flex-initial items-center justify-center gap-1.5 rounded-md border border-input bg-background px-3.5 py-2 text-xs font-semibold text-foreground hover:bg-muted transition-colors shadow-xs">
                <span>Batal</span>
            </a>
            <button type="submit" form="customChartForm" class="inline-flex flex-1 sm:flex-initial items-center justify-center gap-1.5 rounded-md bg-[#2B6CB0] px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90 transition-colors">
                <i data-lucide="save" class="size-4"></i>
                <span><?= $isEdit ? 'Simpan' : 'Publish Chart' ?></span>
            </button>
        </div>
    </div>
</div>

<div class="space-y-6 max-w-4xl">
    <!-- FORM CONFIGURATION CARD -->
    <div class="surface-card p-6 rounded-xl border border-border shadow-xs">
        <form id="customChartForm" method="post" action="<?= $actionUrl ?>" enctype="multipart/form-data" class="space-y-5">
            <?= csrf_field() ?>

            <!-- Field 1: Judul Grafik -->
            <div>
                <label for="chartTitle" class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1.5">
                    Judul Grafik (Chart Title) <span class="text-destructive">*</span>
                </label>
                <input type="text" 
                       id="chartTitle" 
                       name="title" 
                       required 
                       value="<?= esc($currentTitle) ?>" 
                       placeholder="Contoh: Tren Laba Bersih Perbankan Big 4 (2025-2026)" 
                       class="flex h-10 w-full rounded-lg border border-input bg-background px-3.5 py-2 text-sm shadow-2xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>

            <!-- Field 2: Dropdown Template & Label Sumbu X -->
            <div class="chart-form-row">
                <div class="chart-form-col">
                    <label for="chartStyle" class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1.5">
                        Pilihan Template Grafik (Chart Style) <span class="text-destructive">*</span>
                    </label>
                    <select id="chartStyle" 
                            name="chartStyle" 
                            class="flex h-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-xs sm:text-sm shadow-2xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        <option value="bar-vertical" <?= $currentStyle === 'bar-vertical' ? 'selected' : '' ?>>Template Bar Vertikal</option>
                        <option value="bar-horizontal" <?= $currentStyle === 'bar-horizontal' ? 'selected' : '' ?>>Template Bar Horizontal</option>
                        <option value="line" <?= $currentStyle === 'line' ? 'selected' : '' ?>>Template Line Chart (Garis)</option>
                        <option value="pie" <?= $currentStyle === 'pie' ? 'selected' : '' ?>>Template Pie / Donut Chart</option>
                    </select>
                </div>

                <div class="chart-form-col">
                    <label for="xAxisTitle" class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1.5">
                        Label Sumbu X (Kategori / Dimensi)
                    </label>
                    <input type="text" 
                           id="xAxisTitle" 
                           name="xAxisTitle" 
                           value="<?= esc($currentXTitle) ?>" 
                           placeholder="Contoh: Periode Kuartal / Provinsi / Emiten" 
                           class="flex h-10 w-full rounded-lg border border-input bg-background px-3.5 py-2 text-sm shadow-2xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                </div>
            </div>

            <!-- Field 3: Satuan Sumbu Y -->
            <div>
                <label for="yAxisTitle" class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1.5">
                    Satuan Sumbu Y (Metrik / Unit)
                </label>
                <input type="text" 
                       id="yAxisTitle" 
                       name="yAxisTitle" 
                       value="<?= esc($currentYTitle) ?>" 
                       placeholder="Contoh: Miliar Rupiah / Persentase (%) / Indeks" 
                       class="flex h-10 w-full rounded-lg border border-input bg-background px-3.5 py-2 text-sm shadow-2xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>

            <!-- Field 4: Unggah Berkas Data CSV (Legacy parity) -->
            <div class="rounded-lg border border-dashed border-input bg-muted/20 p-4 space-y-2">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-foreground">Import dari Berkas CSV</span>
                        <p class="text-[11px] text-muted-foreground">Unggah file .csv untuk mengisi data sumbu X dan Y secara otomatis (Kolom 1: Kategori, Kolom 2: Nilai).</p>
                    </div>
                    <label for="csvFileInput" class="cursor-pointer inline-flex items-center gap-1.5 rounded-md border border-input bg-background px-3 py-1.5 text-xs font-semibold hover:bg-muted transition-colors shadow-2xs">
                        <span>Pilih Berkas CSV</span>
                    </label>
                    <input type="file" id="csvFileInput" name="csv_file" accept=".csv" class="hidden" onchange="handleCsvUpload(this)">
                </div>
                <div id="csvFileInfo" class="hidden text-xs text-emerald-600 dark:text-emerald-400 font-medium"></div>
            </div>

            <!-- Field 5: Pengaturan Values X & Y (Berdampingan Rapi) -->
            <div class="chart-form-row">
                <div class="chart-form-col">
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="xLabels" class="block text-xs font-bold uppercase tracking-wider text-muted-foreground">
                            Data Kategori Sumbu X <span class="text-destructive">*</span>
                        </label>
                        <span class="text-[10px] text-muted-foreground font-mono">Pisahkan koma</span>
                    </div>
                    <textarea id="xLabels" 
                              name="xLabels" 
                              rows="4" 
                              required 
                              class="flex w-full rounded-lg border border-input bg-background px-3.5 py-2 text-xs sm:text-sm font-mono shadow-2xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring leading-relaxed"
                              placeholder="Kuartal I 2025, Kuartal II 2025, Kuartal III 2025, Kuartal IV 2025"><?= esc($labelsText) ?></textarea>
                </div>

                <div class="chart-form-col">
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="yValues" class="block text-xs font-bold uppercase tracking-wider text-muted-foreground">
                            Data Nilai Sumbu Y <span class="text-destructive">*</span>
                        </label>
                        <span class="text-[10px] text-muted-foreground font-mono">Angka, pisahkan koma</span>
                    </div>
                    <textarea id="yValues" 
                              name="yValues" 
                              rows="4" 
                              required 
                              class="flex w-full rounded-lg border border-input bg-background px-3.5 py-2 text-xs sm:text-sm font-mono shadow-2xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring leading-relaxed"
                              placeholder="120.5, 165.2, 190.8, 240.0"><?= esc($valuesText) ?></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-border">
                <a href="<?= base_url('embed-chart/custom') ?>" class="rounded-lg border border-input bg-background px-4 py-2 text-xs font-semibold text-foreground hover:bg-muted shadow-2xs transition-colors">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow hover:bg-primary/90 transition-colors">
                    <i data-lucide="check" class="size-4"></i>
                    <span><?= $isEdit ? 'Simpan Perubahan' : 'Publish Chart' ?></span>
                </button>
            </div>
        </form>
    </div>

    <!-- LIVE CHART PREVIEW SECTION (PLACED UNDER FORM) -->
    <div class="surface-card p-6 rounded-xl border border-border shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <div class="flex items-center gap-2">
                <span class="relative flex size-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full size-2 bg-emerald-500"></span>
                </span>
                <h2 class="text-xs font-bold uppercase tracking-wider text-foreground">Live Chart Preview</h2>
            </div>
            <span id="previewStyleBadge" class="rounded-md border border-primary/20 bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary uppercase font-mono">
                <?= esc($currentStyle) ?>
            </span>
        </div>

        <div>
            <h3 id="previewTitleDisplay" class="text-base sm:text-lg font-bold text-foreground">
                <?= esc($currentTitle ?: 'Judul Grafik Preview') ?>
            </h3>
            <div class="flex items-center gap-2 text-xs text-muted-foreground mt-0.5">
                <span id="previewMetricDisplay"><?= esc($currentYTitle ?: 'Nilai') ?></span>
            </div>
        </div>

        <!-- Canvas Chart Container with Full Width & Generous Height -->
        <div class="rounded-xl border border-border bg-card p-4 relative shadow-inner" style="height: 360px;">
            <canvas id="liveChartCanvas"></canvas>
        </div>

        <!-- Quick Summary Stats (Arranged Horizontally Side-by-Side) -->
        <div class="chart-stats-row border-t border-border pt-4">
            <div class="chart-stat-item">
                <span class="text-[10px] text-muted-foreground uppercase font-bold tracking-wider mb-1">TOTAL TITIK DATA</span>
                <span id="statPointsCount" class="font-mono text-base font-bold text-foreground"><?= count($labelsArr) ?></span>
            </div>
            <div class="chart-stat-item">
                <span class="text-[10px] text-muted-foreground uppercase font-bold tracking-wider mb-1">NILAI MAKSIMUM</span>
                <span id="statMaxValue" class="font-mono text-base font-bold text-emerald-600 dark:text-emerald-400"><?= !empty($valuesArr) ? max($valuesArr) : 0 ?></span>
            </div>
            <div class="chart-stat-item">
                <span class="text-[10px] text-muted-foreground uppercase font-bold tracking-wider mb-1">NILAI RATA-RATA</span>
                <span id="statAvgValue" class="font-mono text-base font-bold text-primary"><?= !empty($valuesArr) ? round(array_sum($valuesArr) / count($valuesArr), 1) : 0 ?></span>
            </div>
        </div>
    </div>
</div>

<?= $this->section('scripts') ?>
<!-- Chart.js Engine -->
<script src="/js/chart.umd.min.js"></script>
<script>
let liveChartInstance = null;

function parseRawText(text) {
    if (!text) return [];
    return text
        .split(/[\n,]+/)
        .map(s => s.trim())
        .filter(s => s.length > 0);
}

function parseRawNumbers(text) {
    const list = parseRawText(text);
    return list.map(item => {
        const cleaned = item.replace(/[^\d\.\-]/g, '');
        const num = parseFloat(cleaned);
        return isNaN(num) ? 0 : num;
    });
}

function updateLivePreview() {
    const titleInput   = document.getElementById('chartTitle')?.value.trim() || 'Judul Grafik Preview';
    const styleSelect  = document.getElementById('chartStyle')?.value || 'bar-vertical';
    const xTitleInput  = document.getElementById('xAxisTitle')?.value.trim() || 'Kategori';
    const yTitleInput  = document.getElementById('yAxisTitle')?.value.trim() || 'Nilai';
    const xLabelsText  = document.getElementById('xLabels')?.value || '';
    const yValuesText  = document.getElementById('yValues')?.value || '';

    // Update Text Previews
    document.getElementById('previewTitleDisplay').textContent = titleInput;
    document.getElementById('previewStyleBadge').textContent = styleSelect;
    document.getElementById('previewMetricDisplay').textContent = yTitleInput;

    const labels = parseRawText(xLabelsText);
    const values = parseRawNumbers(yValuesText);

    // Update Quick Stats
    document.getElementById('statPointsCount').textContent = labels.length;
    if (values.length > 0) {
        document.getElementById('statMaxValue').textContent = Math.max(...values);
        const avg = values.reduce((a, b) => a + b, 0) / values.length;
        document.getElementById('statAvgValue').textContent = avg.toFixed(1);
    } else {
        document.getElementById('statMaxValue').textContent = 0;
        document.getElementById('statAvgValue').textContent = 0;
    }

    // Chart.js render
    const canvas = document.getElementById('liveChartCanvas');
    if (!canvas || typeof Chart === 'undefined') return;

    if (liveChartInstance) {
        liveChartInstance.destroy();
    }

    const ctx = canvas.getContext('2d');
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.05)';

    const effectiveLabels = labels.length ? labels : ['Contoh 1', 'Contoh 2', 'Contoh 3'];
    const effectiveValues = values.length ? values : [50, 75, 100];

    let chartConfig = {};

    if (styleSelect === 'bar-vertical') {
        chartConfig = {
            type: 'bar',
            data: {
                labels: effectiveLabels,
                datasets: [{
                    label: yTitleInput,
                    data: effectiveValues,
                    backgroundColor: 'rgba(37, 99, 235, 0.85)',
                    hoverBackgroundColor: '#1d4ed8',
                    borderRadius: 5,
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
                    x: {
                        title: { display: !!xTitleInput, text: xTitleInput, color: textColor, font: { size: 10, weight: 'bold' } },
                        grid: { display: false },
                        ticks: { color: textColor, font: { size: 10 } }
                    },
                    y: {
                        title: { display: !!yTitleInput, text: yTitleInput, color: textColor, font: { size: 10, weight: 'bold' } },
                        grid: { color: gridColor },
                        ticks: { color: textColor, font: { size: 10 } }
                    }
                }
            }
        };
    } else if (styleSelect === 'bar-horizontal') {
        chartConfig = {
            type: 'bar',
            data: {
                labels: effectiveLabels,
                datasets: [{
                    label: yTitleInput,
                    data: effectiveValues,
                    backgroundColor: 'rgba(13, 148, 136, 0.85)',
                    hoverBackgroundColor: '#0f766e',
                    borderRadius: 5,
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
                    x: {
                        title: { display: !!yTitleInput, text: yTitleInput, color: textColor, font: { size: 10, weight: 'bold' } },
                        grid: { color: gridColor },
                        ticks: { color: textColor, font: { size: 10 } }
                    },
                    y: {
                        title: { display: !!xTitleInput, text: xTitleInput, color: textColor, font: { size: 10, weight: 'bold' } },
                        grid: { display: false },
                        ticks: { color: textColor, font: { size: 10 } }
                    }
                }
            }
        };
    } else if (styleSelect === 'line') {
        chartConfig = {
            type: 'line',
            data: {
                labels: effectiveLabels,
                datasets: [{
                    label: yTitleInput,
                    data: effectiveValues,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.15)',
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
                    x: {
                        title: { display: !!xTitleInput, text: xTitleInput, color: textColor, font: { size: 10, weight: 'bold' } },
                        grid: { display: false },
                        ticks: { color: textColor, font: { size: 10 } }
                    },
                    y: {
                        title: { display: !!yTitleInput, text: yTitleInput, color: textColor, font: { size: 10, weight: 'bold' } },
                        grid: { color: gridColor },
                        ticks: { color: textColor, font: { size: 10 } }
                    }
                }
            }
        };
    } else if (styleSelect === 'pie') {
        const palette = [
            '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', 
            '#ec4899', '#06b6d4', '#f97316', '#6366f1',
            '#14b8a6', '#e11d48'
        ];
        chartConfig = {
            type: 'doughnut',
            data: {
                labels: effectiveLabels,
                datasets: [{
                    data: effectiveValues,
                    backgroundColor: palette.slice(0, effectiveLabels.length),
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
                cutout: '50%'
            }
        };
    }

    liveChartInstance = new Chart(ctx, chartConfig);
}

function handleCsvUpload(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const infoEl = document.getElementById('csvFileInfo');
    
    if (infoEl) {
        infoEl.textContent = `Memproses: ${file.name}...`;
        infoEl.classList.remove('hidden');
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const text = e.target.result;
        const lines = text.split(/\r\n|\n/).map(l => l.trim()).filter(Boolean);
        if (!lines.length) return;

        // Detect delimiter
        const delimiter = lines[0].includes(';') ? ';' : ',';
        const rows = lines.map(line => line.split(delimiter).map(c => c.trim().replace(/^["']|["']$/g, '')));

        if (!rows.length) return;

        let labels = [];
        let values = [];

        // Check if row 0 has header
        let startIndex = 0;
        if (rows.length > 1 && rows[0].length >= 2) {
            const secondVal = parseFloat(rows[0][1].replace(/[^\d.-]/g, ''));
            if (isNaN(secondVal)) {
                startIndex = 1;
                const xAxisInput = document.getElementById('xAxisTitle');
                if (xAxisInput && !xAxisInput.value) {
                    xAxisInput.value = rows[0][0];
                }
            }
        }

        if (rows[0].length >= 2 && rows.length >= 2) {
            for (let i = startIndex; i < rows.length; i++) {
                if (rows[i].length >= 2) {
                    labels.push(rows[i][0]);
                    const cleanVal = rows[i][1].replace(/[^\d.-]/g, '');
                    values.push(cleanVal || '0');
                }
            }
        } else if (rows.length >= 2) {
            labels = rows[0];
            values = rows[1].map(v => v.replace(/[^\d.-]/g, '') || '0');
        }

        if (labels.length && values.length) {
            const xLabelsEl = document.getElementById('xLabels');
            const yValuesEl = document.getElementById('yValues');
            if (xLabelsEl) xLabelsEl.value = labels.join(', ');
            if (yValuesEl) yValuesEl.value = values.join(', ');

            if (infoEl) {
                infoEl.textContent = `Berhasil mengimpor ${labels.length} titik data dari "${file.name}"`;
                infoEl.classList.remove('hidden');
            }
            updateLivePreview();
        } else {
            if (infoEl) {
                infoEl.textContent = 'Format CSV tidak dikenali. Pastikan berisi minimal 2 kolom (Kategori, Nilai).';
                infoEl.classList.remove('text-emerald-600', 'dark:text-emerald-400');
                infoEl.classList.add('text-destructive');
            }
        }
    };
    reader.readAsText(file);
}

document.addEventListener('DOMContentLoaded', function() {
    updateLivePreview();

    // Attach dynamic live input listeners
    ['chartTitle', 'chartStyle', 'xAxisTitle', 'yAxisTitle', 'xLabels', 'yValues'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', updateLivePreview);
            el.addEventListener('change', updateLivePreview);
        }
    });
});
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>

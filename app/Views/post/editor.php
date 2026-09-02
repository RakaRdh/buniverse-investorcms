<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<!-- Quill.js CDN & Flatpickr CDN -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="/css/pages/post.css">
<link rel="stylesheet" href="/css/pages/editor.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<form id="postEditorForm" method="post" action="<?= $isEdit ? base_url('post/update/' . $article['id']) : base_url('post/save') ?>">
    <!-- Top Action Bar & Header -->
    <div class="mb-6">
        <div class="grid grid-cols-[minmax(0,1fr)_auto] items-start gap-4 sm:flex sm:items-center sm:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-3">
                    <a href="<?= base_url('post') ?>" class="inline-flex items-center justify-center rounded-md border border-input bg-background p-2 text-muted-foreground hover:text-foreground hover:bg-muted transition-colors" title="Kembali ke Daftar Artikel">
                        <i data-lucide="arrow-left" class="size-4"></i>
                    </a>
                    <div>
                        <h1 class="truncate text-xl sm:text-2xl font-bold tracking-tight text-foreground">
                            <?= $isEdit ? 'Edit Artikel Berita' : 'Tambah Artikel Baru' ?>
                        </h1>
                        <p class="text-xs text-muted-foreground">
                            <?= $isEdit ? 'Perbarui konten, metadata editorial, dan jadwal penayangan' : 'Tulis naskah berita baru dan distribusikan ke kanal Investor.id' ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <!-- Preview Button (Opens New Tab) -->
                <button type="button" onclick="previewArticlePage()" class="inline-flex items-center justify-center gap-1.5 rounded-md border border-input bg-background px-3.5 py-2 text-xs font-semibold text-foreground hover:bg-muted hover:text-primary transition-colors shadow-xs">
                    <i data-lucide="eye" class="size-4 text-emerald-600"></i>
                    <span>Preview Artikel</span>
                </button>

                <!-- Save Draft Button -->
                <button type="submit" name="status" value="Draft" class="inline-flex items-center justify-center gap-1.5 rounded-md border border-input bg-background px-3.5 py-2 text-xs font-semibold text-foreground hover:bg-muted transition-colors shadow-xs">
                    <i data-lucide="file-down" class="size-4 text-slate-500"></i>
                    <span>Simpan Draft</span>
                </button>

                <!-- Primary Publish / Save Button -->
                <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-md bg-[#2B6CB0] px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90 transition-colors">
                    <i data-lucide="save" class="size-4"></i>
                    <span><?= $isEdit ? 'Simpan Perubahan' : 'Terbitkan Artikel' ?></span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2-Column Canvas Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        <!-- Left: Main Composition Canvas -->
        <div class="surface-card p-5 sm:p-6 lg:col-span-2 space-y-5">
            
            <!-- A. Scrape Artikel dari URL Eksternal (Placeholder Box) -->
            <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/70 space-y-2">
                <div class="flex items-center gap-2 text-emerald-800">
                    <i data-lucide="link" class="size-4 text-emerald-600"></i>
                    <span class="text-xs font-bold tracking-tight">Scrape Artikel dari URL Berita Eksternal</span>
                </div>
                <p class="text-[11px] text-emerald-700/80">Tempel link artikel dari situs berita yang diizinkan (Antara News). AI akan mengekstrak isi berita dan gambar thumbnail secara otomatis.</p>
                <div class="flex items-center gap-2 pt-1">
                    <input type="url" id="scrapeUrlInput" placeholder="https://www.antaranews.com/berita/..." class="flex h-9 flex-1 rounded-md border border-emerald-300 bg-white px-3 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-emerald-500">
                    <button type="button" onclick="handleScrapePlaceholder()" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md bg-emerald-600 px-4 text-xs font-bold text-white shadow hover:bg-emerald-700 transition-colors shrink-0">
                        <i data-lucide="rotate-cw" class="size-3.5"></i>
                        <span>Scrape Artikel</span>
                    </button>
                </div>
            </div>

            <!-- B. Upper Deck & Premium Flag -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground mb-1.5">Upper Deck (Opsional)</label>
                    <input type="text" name="upper_deck" value="<?= esc($article['upper_deck'] ?? '') ?>" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Contoh: LAPORAN KHUSUS / EKSKLUSIF / KINERJA EMITEN">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground mb-1.5">Artikel Premium</label>
                    <select name="is_premium" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        <option value="0" <?= empty($article['is_premium']) ? 'selected' : '' ?>>Tidak (Gratis)</option>
                        <option value="1" <?= !empty($article['is_premium']) ? 'selected' : '' ?>>Ya (Member Only)</option>
                    </select>
                </div>
            </div>

            <!-- C. Judul Artikel (Uniform Font Size + AI Assistance Button) -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground">
                        Judul Artikel <span class="text-destructive">*</span>
                    </label>
                    <button type="button" onclick="handleAiAssistance('judul')" class="inline-flex items-center gap-1 rounded bg-emerald-500/15 border border-emerald-500/30 px-2 py-0.5 text-[10px] font-bold text-emerald-700 hover:bg-emerald-500/25 transition-colors">
                        <i data-lucide="sparkles" class="size-3 text-emerald-600"></i>
                        <span>GENERATE JUDUL VIA AI</span>
                    </button>
                </div>
                <input type="text" name="title" required value="<?= esc($article['title'] ?? '') ?>" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Masukkan judul artikel berita yang lugas dan menarik...">
            </div>

            <!-- D. Editor & Penulis (Author Byline) Inline -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground mb-1.5">Editor</label>
                    <input type="text" readonly value="<?= esc(session('user_name') ?? 'Raka Herdika') ?>" class="flex h-9 w-full rounded-md border border-input bg-muted px-3 py-1 text-xs font-medium text-muted-foreground shadow-sm cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground mb-1.5">
                        Penulis (Author By Line) <span class="text-destructive">*</span>
                    </label>
                    <select name="author" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" required>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= esc($u['name']) ?>" <?= ($article['author'] ?? session('user_name') ?? 'Raka Herdika') === $u['name'] ? 'selected' : '' ?>>
                                <?= esc($u['name']) ?> (<?= esc($u['role']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="text-[10px] text-muted-foreground mt-0.5 block">*) Penulis tercantum di byline artikel</span>
                </div>
            </div>

            <!-- E. Uraian Singkat (Lead / Summary) Full Width + AI Button -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground">Uraian Singkat (Lead / Summary)</label>
                    <button type="button" onclick="handleAiAssistance('summary')" class="inline-flex items-center gap-1 rounded bg-emerald-500/15 border border-emerald-500/30 px-2 py-0.5 text-[10px] font-bold text-emerald-700 hover:bg-emerald-500/25 transition-colors">
                        <i data-lucide="sparkles" class="size-3 text-emerald-600"></i>
                        <span>GENERATE SUMMARY VIA AI</span>
                    </button>
                </div>
                <textarea name="summary" rows="3" maxlength="280" class="flex w-full rounded-md border border-input bg-background p-3 text-xs leading-relaxed shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Ringkasan 1-2 kalimat pengantar artikel untuk cuplikan sosial media dan feed..."><?= esc($article['summary'] ?? '') ?></textarea>
                <span class="text-[10px] text-muted-foreground mt-0.5 block font-medium">Maksimal 280 karakter.</span>
            </div>

            <!-- F. Kategori / Kanal, Sub-Kanal, dan Sumber Berita Berurutan (Di bawah summary) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-xl border border-border bg-muted/20">
                <!-- Dropdown Kanal -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground mb-1.5">
                        Kanal <span class="text-destructive">*</span>
                    </label>
                    <select name="kanal" id="kanalSelect" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" onchange="onKanalChange(this.value)" required>
                        <option value="">-- Pilih Kanal --</option>
                        <?php foreach ($kanals as $kn): ?>
                            <?php 
                                $currKanal = $article['kanal'] ?? ($article['category'] ?? '');
                                $isSelected = ($currKanal === $kn['name']) || (str_starts_with($currKanal, $kn['name']));
                            ?>
                            <option value="<?= esc($kn['name']) ?>" <?= $isSelected ? 'selected' : '' ?>>
                                <?= esc($kn['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Dropdown Sub-Kanal (Disabled jika kanal belum dipilih) -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground mb-1.5">
                        Sub Kanal
                    </label>
                    <select name="sub_kanal" id="subKanalSelect" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:bg-muted disabled:text-muted-foreground disabled:cursor-not-allowed">
                        <option value="">-- Silahkan Pilih Kanal Terlebih Dahulu --</option>
                    </select>
                </div>

                <!-- Sumber Berita -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground mb-1.5">Sumber Berita</label>
                    <input type="text" name="source" value="<?= esc($article['source'] ?? $article['sumber_berita'] ?? 'Investor.id / Riset') ?>" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Contoh: IDX, BI, Reuters">
                </div>
            </div>

            <!-- G. 3 Poin Utama Artikel (Points + AI Button) -->
            <div class="p-4 rounded-xl border border-primary/20 bg-primary/5 space-y-3">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground flex items-center gap-1.5">
                        <i data-lucide="list-ordered" class="size-4 text-primary"></i>
                        <span>3 Poin Utama Artikel (Points)</span>
                    </label>
                    <button type="button" onclick="handleAiAssistance('points')" class="inline-flex items-center gap-1 rounded bg-emerald-500/15 border border-emerald-500/30 px-2 py-0.5 text-[10px] font-bold text-emerald-700 hover:bg-emerald-500/25 transition-colors">
                        <i data-lucide="sparkles" class="size-3 text-emerald-600"></i>
                        <span>GENERATE 3 POINT VIA AI</span>
                    </button>
                </div>
                
                <?php 
                    $pts = $article['points'] ?? [
                        'Poin ringkasan pertama',
                        'Poin ringkasan kedua',
                        'Poin ringkasan ketiga'
                    ];
                ?>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="grid size-6 shrink-0 place-items-center rounded bg-primary/20 text-primary text-[11px] font-bold">1</span>
                        <input type="text" name="point_1" id="pointInput1" value="<?= esc($pts[0] ?? '') ?>" class="flex h-8 flex-1 rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Poin penting pertama artikel...">
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="grid size-6 shrink-0 place-items-center rounded bg-primary/20 text-primary text-[11px] font-bold">2</span>
                        <input type="text" name="point_2" id="pointInput2" value="<?= esc($pts[1] ?? '') ?>" class="flex h-8 flex-1 rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Poin penting kedua artikel...">
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="grid size-6 shrink-0 place-items-center rounded bg-primary/20 text-primary text-[11px] font-bold">3</span>
                        <input type="text" name="point_3" id="pointInput3" value="<?= esc($pts[2] ?? '') ?>" class="flex h-8 flex-1 rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Poin penting ketiga artikel...">
                    </div>
                </div>
            </div>

            <!-- H. Body Artikel with Enhanced Toolkit & Extended Length (480px min-height) -->
            <div class="space-y-2 pt-2 border-t border-border">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold uppercase tracking-wider text-foreground">
                        Body Artikel <span class="text-destructive">*</span>
                    </label>
                </div>

                <!-- Custom Embed Toolbar Row -->
                <div class="flex flex-wrap items-center gap-1.5 p-2 rounded-lg bg-muted/70 border border-border">
                    <span class="text-[10px] font-bold text-muted-foreground uppercase mr-1 px-1">Sisipkan:</span>
                    <button type="button" onclick="openArticleLinkModal()" class="inline-flex items-center gap-1 rounded bg-background border border-border px-2.5 py-1 text-[11px] font-medium text-foreground hover:bg-primary/10 hover:text-primary hover:border-primary/30 transition-colors shadow-2xs">
                        <i data-lucide="link-2" class="size-3 text-primary"></i>
                        <span>Add Article Link</span>
                    </button>
                    <button type="button" onclick="insertEmbedTool('embed_chart')" class="inline-flex items-center gap-1 rounded bg-background border border-border px-2.5 py-1 text-[11px] font-medium text-foreground hover:bg-primary/10 hover:text-primary hover:border-primary/30 transition-colors shadow-2xs">
                        <i data-lucide="line-chart" class="size-3 text-accent"></i>
                        <span>Embed Chart</span>
                    </button>
                    <button type="button" onclick="openMediaChooserModal('quill')" class="inline-flex items-center gap-1 rounded bg-background border border-border px-2.5 py-1 text-[11px] font-medium text-foreground hover:bg-primary/10 hover:text-primary hover:border-primary/30 transition-colors shadow-2xs">
                        <i data-lucide="image" class="size-3 text-emerald-600"></i>
                        <span>Insert Media</span>
                    </button>
                    <button type="button" onclick="insertEmbedTool('embed_socmed')" class="inline-flex items-center gap-1 rounded bg-background border border-border px-2.5 py-1 text-[11px] font-medium text-foreground hover:bg-primary/10 hover:text-primary hover:border-primary/30 transition-colors shadow-2xs">
                        <i data-lucide="code-2" class="size-3 text-amber-600"></i>
                        <span>Embed Socmed/Doc</span>
                    </button>
                    <button type="button" onclick="insertEmbedTool('stock_chart')" class="inline-flex items-center gap-1 rounded bg-background border border-border px-2.5 py-1 text-[11px] font-medium text-foreground hover:bg-primary/10 hover:text-primary hover:border-primary/30 transition-colors shadow-2xs">
                        <i data-lucide="trending-up" class="size-3 text-indigo-600"></i>
                        <span>Stock Chart</span>
                    </button>
                    <button type="button" onclick="insertEmbedTool('page_break')" class="inline-flex items-center gap-1 rounded bg-background border border-border px-2.5 py-1 text-[11px] font-medium text-foreground hover:bg-primary/10 hover:text-primary hover:border-primary/30 transition-colors shadow-2xs">
                        <i data-lucide="scissors" class="size-3 text-slate-500"></i>
                        <span>Page Break</span>
                    </button>
                </div>

                <!-- Quill WYSIWYG Container (Min height 480px) -->
                <div id="quillEditor" class="min-h-[480px]"></div>
                <!-- Hidden input that holds the HTML value for POST submit -->
                <input type="hidden" name="content" id="hiddenContentInput" value="<?= esc($article['content'] ?? '') ?>">
            </div>

            <!-- I. Kata Kunci (Tags) Dipindahkan ke Bawah Body + AI Button -->
            <div class="pt-2 border-t border-border">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground">Kata Kunci (Tags)</label>
                    <button type="button" onclick="handleAiAssistance('tags')" class="inline-flex items-center gap-1 rounded bg-emerald-500/15 border border-emerald-500/30 px-2 py-0.5 text-[10px] font-bold text-emerald-700 hover:bg-emerald-500/25 transition-colors">
                        <i data-lucide="sparkles" class="size-3 text-emerald-600"></i>
                        <span>GENERATE TAGS VIA AI</span>
                    </button>
                </div>
                <input type="text" name="tags" id="tagsInput" value="<?= esc($article['tags'] ?? '') ?>" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="IHSG, Saham, Dividen, Bank Mandiri">
                <span class="text-[10px] text-muted-foreground mt-0.5 block">Pisahkan setiap kata kunci dengan tanda koma (,).</span>
            </div>

            <!-- Bottom Action Footer -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-border">
                <button type="button" onclick="previewArticlePage()" class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-600 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-emerald-700 transition-colors">
                    <i data-lucide="eye" class="size-4"></i>
                    <span>PREVIEW ARTIKEL</span>
                </button>
                
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-[#2B6CB0] px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90 transition-colors">
                    <i data-lucide="save" class="size-4"></i>
                    <span>SIMPAN ARTIKEL</span>
                </button>
            </div>
        </div>

        <!-- Right: Publishing Settings & Media Sidebar -->
        <div class="space-y-5">
            
            <!-- 1. Featured Image & Cover Preview Card -->
            <div class="surface-card p-5 space-y-3">
                <div class="flex items-center justify-between border-b border-border pb-2.5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-foreground">Featured Image / Cover</h3>
                    <span class="text-[10px] text-muted-foreground font-mono">16:9 Ratio</span>
                </div>

                <!-- Cover Preview Box -->
                <div class="relative h-44 w-full overflow-hidden rounded-lg border border-border bg-slate-900 shadow-inner group">
                    <img id="imagePreview" src="<?= esc($article['thumbnail'] ?? '/img/article1.jpg') ?>" alt="Cover Preview" class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-200">
                    <div class="absolute bottom-2 left-2 right-2 flex items-center justify-between rounded bg-black/60 backdrop-blur-xs px-2.5 py-1 text-[10px] text-white">
                        <span class="truncate">Preview Cover Utama</span>
                        <i data-lucide="sparkles" class="size-3 text-amber-400"></i>
                    </div>
                </div>

                <!-- Thumbnail Input & Action Buttons (Pilih dari Media & Upload Lokal) -->
                <div class="space-y-2 pt-1">
                    <input type="text" name="thumbnail" id="thumbnailInput" value="<?= esc($article['thumbnail'] ?? '/img/article1.jpg') ?>" class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="URL Foto Gambar...">
                    
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="openMediaChooserModal('cover')" class="inline-flex items-center justify-center gap-1.5 rounded-md bg-[#2B6CB0] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#2B6CB0]/90 transition-colors shadow-2xs">
                            <i data-lucide="images" class="size-3.5"></i>
                            <span>Pilih dari Media</span>
                        </button>
                        
                        <label class="inline-flex items-center justify-center gap-1.5 rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium text-foreground hover:bg-muted transition-colors cursor-pointer">
                            <i data-lucide="upload" class="size-3.5"></i>
                            <span>Upload Lokal</span>
                            <input type="file" id="localFileInput" accept="image/*" class="hidden" onchange="handleLocalImageUpload(this)">
                        </label>
                    </div>
                </div>
            </div>

            <!-- 2. Publish Setting & Scheduled Date/Time with Editable Manual Inputs -->
            <div class="surface-card p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-border pb-2.5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-foreground">Publish Setting</h3>
                    <i data-lucide="calendar-clock" class="size-4 text-primary"></i>
                </div>

                <!-- Status Workflow -->
                <div>
                    <label class="block text-xs font-bold text-foreground mb-1.5">Status Penayangan</label>
                    <select name="status" id="workflowStatusSelect" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        <option value="Published" <?= ($article['status'] ?? 'Published') === 'Published' ? 'selected' : '' ?>>Langsung Publish (Published)</option>
                        <option value="Scheduled" <?= ($article['status'] ?? '') === 'Scheduled' ? 'selected' : '' ?>>Terjadwal (Scheduled)</option>
                        <option value="Draft" <?= ($article['status'] ?? '') === 'Draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="Review" <?= ($article['status'] ?? '') === 'Review' ? 'selected' : '' ?>>Review Redaksi</option>
                    </select>
                </div>

                <!-- Scheduled Date & Time Controls (Slider + Manual Editable Inputs) -->
                <div id="scheduledSettingBox" class="p-3.5 rounded-lg border border-primary/30 bg-primary/5 space-y-3.5" style="display: none;">
                    <div>
                        <label class="block text-xs font-bold text-primary mb-1 flex items-center justify-between">
                            <span>Pilih Tanggal Tayang:</span>
                            <i data-lucide="calendar" class="size-3.5"></i>
                        </label>
                        <input type="text" id="scheduledDateInput" class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1 text-xs shadow-sm font-medium" placeholder="Pilih tanggal...">
                    </div>

                    <!-- Time: Jam (Slider + Number input) -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs font-medium">
                            <span class="text-muted-foreground">Jam Tayang (00–23):</span>
                            <div class="flex items-center gap-1">
                                <input type="number" id="hourInputNumber" min="0" max="23" value="9" onchange="onHourInputChange(this.value)" class="h-6 w-12 rounded border border-input bg-background px-1 text-center font-bold text-primary font-mono text-xs">
                                <span class="text-muted-foreground text-[10px]">WIB</span>
                            </div>
                        </div>
                        <input type="range" id="hourSlider" min="0" max="23" value="9" oninput="onHourSliderChange(this.value)" class="w-full h-1.5 bg-slate-200 rounded-lg cursor-pointer">
                    </div>

                    <!-- Time: Menit (Slider + Number input) -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs font-medium">
                            <span class="text-muted-foreground">Menit Tayang (00–59):</span>
                            <div class="flex items-center gap-1">
                                <input type="number" id="minuteInputNumber" min="0" max="59" value="0" onchange="onMinuteInputChange(this.value)" class="h-6 w-12 rounded border border-input bg-background px-1 text-center font-bold text-primary font-mono text-xs">
                                <span class="text-muted-foreground text-[10px]">m</span>
                            </div>
                        </div>
                        <input type="range" id="minuteSlider" min="0" max="59" value="0" oninput="onMinuteSliderChange(this.value)" class="w-full h-1.5 bg-slate-200 rounded-lg cursor-pointer">
                    </div>

                    <div class="p-2 rounded bg-background border border-border text-[11px] flex items-center justify-between">
                        <span class="text-muted-foreground">Waktu Dijadwalkan:</span>
                        <span id="publishDateDisplay" class="font-bold text-foreground font-mono">01 Sep 2026 09:00</span>
                    </div>
                </div>

                <!-- Hidden Input holding full Publish Date formatted -->
                <input type="hidden" name="publish_date" id="publishDateHiddenInput" value="<?= esc($article['publish_date'] ?? date('d M Y H:i')) ?>">
            </div>

        </div>
    </div>
</form>

<!-- Modal Media Library Chooser (2 Column Horizontal Cards Grid) -->
<div id="mediaChooserModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); z-index: 999; align-items: center; justify-content: center; padding: 1rem;">
    <div class="surface-card w-full max-w-5xl max-h-[88vh] flex flex-col p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <div>
                <h3 class="text-sm font-bold text-foreground">Media Library — Pilih Gambar Berita</h3>
                <p class="text-xs text-muted-foreground">Pilih media untuk dijadikan featured cover atau sisipkan ke dalam body teks.</p>
            </div>
            <button type="button" onclick="closeMediaChooserModal()" class="text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>

        <!-- 2 Column Grid of Horizontal Media Cards -->
        <div class="overflow-y-auto flex-1 grid grid-cols-1 md:grid-cols-2 gap-3 p-1 custom-sidebar-scroll">
            <?php foreach ($media as $m): ?>
            <div class="flex rounded-lg border border-border overflow-hidden bg-background hover:border-primary transition-all shadow-xs">
                <!-- Left: Media Photo -->
                <div class="w-36 shrink-0 bg-slate-900 relative">
                    <img src="<?= esc($m['image']) ?>" alt="" class="h-full w-full object-cover">
                </div>
                
                <!-- Right: Media Details & Actions -->
                <div class="flex-1 p-3 flex flex-col justify-between min-w-0">
                    <div>
                        <h4 class="text-xs font-bold text-foreground truncate" title="<?= esc($m['title']) ?>"><?= esc($m['title']) ?></h4>
                        <p class="text-[11px] text-muted-foreground line-clamp-2 mt-0.5"><?= esc($m['caption'] ?? '') ?></p>
                        <div class="flex items-center gap-2 mt-2 text-[10px] text-muted-foreground font-mono">
                            <span><i data-lucide="camera" class="size-3 inline mr-0.5"></i> <?= esc($m['source'] ?? 'Investor.id') ?></span>
                            <span>&bull;</span>
                            <span><?= esc($m['date'] ?? '') ?></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 mt-3 pt-2 border-t border-border">
                        <button type="button" onclick="selectMediaImage('<?= esc($m['image']) ?>')" class="flex-1 rounded bg-[#2B6CB0] text-white px-2 py-1 text-[10px] font-bold shadow-xs hover:bg-[#2B6CB0]/90 transition-colors">
                            Pilih Gambar
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-end pt-3 border-t border-border">
            <button type="button" onclick="closeMediaChooserModal()" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted">Tutup</button>
        </div>
    </div>
</div>

<!-- Modal Add Article Link (with Tag Filters) -->
<div id="articleLinkModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); z-index: 999; align-items: center; justify-content: center; padding: 1rem;">
    <div class="surface-card w-full max-w-3xl max-h-[85vh] flex flex-col p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <div>
                <h3 class="text-sm font-bold text-foreground">Sisipkan Link Artikel ("Baca Juga")</h3>
                <p class="text-xs text-muted-foreground">Pilih artikel berita untuk disisipkan sebagai referensi bacaan di dalam naskah.</p>
            </div>
            <button type="button" onclick="closeArticleLinkModal()" class="text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>

        <!-- Tag Filter Chips Bar -->
        <div class="flex items-center gap-1.5 flex-wrap text-xs pb-1">
            <span class="text-[11px] font-bold text-muted-foreground mr-1">Filter Tag:</span>
            <button type="button" onclick="filterArticleModalByTag('all')" class="modal-tag-chip rounded-full bg-primary/10 text-primary border border-primary/30 px-2.5 py-0.5 text-[10px] font-semibold">Semua</button>
            <button type="button" onclick="filterArticleModalByTag('IHSG')" class="modal-tag-chip rounded-full bg-muted text-muted-foreground px-2.5 py-0.5 text-[10px] font-medium hover:text-foreground">IHSG</button>
            <button type="button" onclick="filterArticleModalByTag('Saham')" class="modal-tag-chip rounded-full bg-muted text-muted-foreground px-2.5 py-0.5 text-[10px] font-medium hover:text-foreground">Saham</button>
            <button type="button" onclick="filterArticleModalByTag('Perbankan')" class="modal-tag-chip rounded-full bg-muted text-muted-foreground px-2.5 py-0.5 text-[10px] font-medium hover:text-foreground">Perbankan</button>
            <button type="button" onclick="filterArticleModalByTag('Komoditas')" class="modal-tag-chip rounded-full bg-muted text-muted-foreground px-2.5 py-0.5 text-[10px] font-medium hover:text-foreground">Komoditas</button>
        </div>

        <!-- Search input in modal -->
        <div class="relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 size-3.5 text-muted-foreground"></i>
            <input type="text" id="articleModalSearch" onkeyup="filterArticleModalList()" placeholder="Cari judul artikel..." class="flex h-8 w-full rounded-md border border-input bg-background pl-8 pr-3 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
        </div>

        <!-- Article Rows List -->
        <div id="articleModalList" class="overflow-y-auto flex-1 space-y-2 p-1 max-h-[350px] custom-sidebar-scroll">
            <?php foreach (($allPosts ?? []) as $ap): ?>
            <div class="article-modal-row flex items-center justify-between gap-3 p-2.5 rounded-lg border border-border hover:border-primary hover:bg-muted/40 transition-colors" data-title="<?= esc(strtolower($ap['title'])) ?>" data-tags="<?= esc(strtolower($ap['tags'] ?? '')) ?>" data-kanal="<?= esc(strtolower($ap['kanal'] ?? '')) ?>">
                <div class="flex items-center gap-3 min-w-0">
                    <img src="<?= esc($ap['thumbnail']) ?>" alt="" class="size-10 rounded object-cover border border-border shrink-0">
                    <div class="min-w-0">
                        <h4 class="text-xs font-bold text-foreground truncate"><?= esc($ap['title']) ?></h4>
                        <span class="text-[10px] text-muted-foreground block"><?= esc($ap['kanal'] ?? 'Market') ?> &bull; <?= esc($ap['publish_date']) ?></span>
                    </div>
                </div>
                <button type="button" onclick="insertSelectedArticleLink('<?= esc(addslashes($ap['title'])) ?>', '<?= base_url('post/' . $ap['id']) ?>', '<?= esc(addslashes($ap['summary'] ?? '')) ?>')" class="inline-flex shrink-0 items-center gap-1 rounded bg-[#2B6CB0] px-2.5 py-1 text-[11px] font-bold text-white hover:bg-[#2B6CB0]/90 shadow-2xs">
                    <span>Sisipkan</span>
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-end pt-3 border-t border-border">
            <button type="button" onclick="closeArticleLinkModal()" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted">Tutup</button>
        </div>
    </div>
</div>

<!-- Embedded Kanals Data for Client-Side Cascading Dropdown -->
<script>
window.__KANALS_DATA__ = <?= json_encode($kanals ?? []) ?>;
window.__ARTICLE_CURRENT_SUBKANAL__ = <?= json_encode($article['sub_kanal'] ?? '') ?>;
</script>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Quill.js CDN & Flatpickr CDN -->
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="/js/pages/editor.js"></script>
<?= $this->endSection() ?>

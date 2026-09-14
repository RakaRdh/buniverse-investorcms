<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<!-- Flatpickr CDN & Editor Styles -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="/css/pages/post.css">
<link rel="stylesheet" href="/css/pages/editor.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<form id="postEditorForm" method="post" action="<?= $isEdit ? base_url('post/update/' . $article['id']) : base_url('post/save') ?>">
    <input type="hidden" name="id" id="articleIdInput" value="<?= $isEdit ? (int)$article['id'] : '' ?>">
    <!-- Top Action Bar & Header Responsive -->
    <div class="mb-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2.5 min-w-0">
                <a href="<?= base_url('post') ?>" class="inline-flex size-9 shrink-0 items-center justify-center rounded-md border border-input bg-background text-muted-foreground hover:text-foreground hover:bg-muted transition-colors" title="Kembali ke Daftar Artikel">
                    <i data-lucide="arrow-left" class="size-4"></i>
                </a>
                <div class="min-w-0">
                    <h1 class="truncate text-lg sm:text-2xl font-bold tracking-tight text-foreground leading-tight">
                        <?= $isEdit ? 'Edit Artikel Berita' : 'Tambah Artikel Baru' ?>
                    </h1>
                    <p class="text-xs sm:text-sm text-muted-foreground truncate">
                        <?= $isEdit ? 'Perbarui konten, metadata editorial, dan jadwal penayangan' : 'Tulis naskah berita baru dan distribusikan ke kanal Investor.id' ?>
                    </p>
                </div>
            </div>
            
            <div class="flex items-center gap-2 self-stretch sm:self-auto flex-wrap">
                <!-- Auto-Save Status Indicator Badge -->
                <div id="autosaveStatusBadge" class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium text-muted-foreground bg-muted/60 border border-border/80">
                    <span id="autosaveStatusDot" class="size-2 rounded-full bg-slate-400"></span>
                    <span id="autosaveStatusText">Auto-save aktif</span>
                </div>

                <!-- Save Draft Button -->
                <button type="submit" name="status" value="Draft" class="inline-flex flex-1 sm:flex-initial items-center justify-center gap-1.5 rounded-md border border-input bg-background px-3.5 py-2 text-xs font-semibold text-foreground hover:bg-muted transition-colors shadow-xs">
                    <i data-lucide="file-down" class="size-4 text-muted-foreground"></i>
                    <span>Simpan Draft</span>
                </button>

                <!-- Primary Publish / Save Button -->
                <button type="submit" class="inline-flex flex-1 sm:flex-initial items-center justify-center gap-1.5 rounded-md bg-[#2B6CB0] px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90 transition-colors">
                    <i data-lucide="save" class="size-4"></i>
                    <span><?= $isEdit ? 'Simpan' : 'Terbitkan Artikel' ?></span>
                </button>
            </div>
        </div>
    </div>

    <?php if (!empty($lockInfo) && !empty($lockInfo['locked']) && empty($lockInfo['is_owner'])): ?>
    <!-- Edit Lock Warning Banner -->
    <div id="lockWarningBanner" class="mb-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-3.5 rounded-lg bg-amber-500/15 border border-amber-500/35 text-amber-900 dark:text-amber-200 text-xs shadow-xs animate-in fade-in duration-200">
        <div class="flex items-center gap-2.5">
            <i data-lucide="lock" class="size-4 text-amber-600 shrink-0"></i>
            <div>
                <strong>Perhatian:</strong> Artikel ini sedang dibuka dan diedit oleh <span class="font-bold underline decoration-amber-500"><?= esc($lockInfo['locked_by']) ?></span>.
                Untuk mencegah tabrakan data, disarankan untuk tidak mengubah artikel ini bersamaan.
            </div>
        </div>
        <?php if (in_array(strtolower(session('user_role') ?? ''), ['administrator', 'redpel', 'admin'])): ?>
            <button type="button" onclick="forceUnlockArticle(<?= (int)$article['id'] ?>)" class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-amber-600 text-white font-semibold hover:bg-amber-700 transition-colors text-xs shadow-2xs">
                <i data-lucide="unlock" class="size-3.5"></i>
                <span>Buka Gembok Paksa</span>
            </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- 2-Column Canvas Layout -->
    <div class="flex flex-col lg:grid lg:grid-cols-3 gap-6 items-start">
        
        <!-- Right Column on Desktop (Order 2): Only Publish Setting & Primary Preview Button -->
        <div class="w-full space-y-5 lg:order-2 lg:col-span-1 lg:sticky lg:top-20">
            
            <!-- 1. Publish Setting & Scheduled Date/Time with Editable Manual Inputs -->
            <div class="surface-card p-5 space-y-4 shadow-sm border border-border">
                <div class="flex items-center justify-between border-b border-border pb-2.5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-foreground">Publish Setting</h3>
                    <i data-lucide="calendar-clock" class="size-4 text-primary"></i>
                </div>

                <!-- Status Workflow -->
                <div>
                    <label class="block text-xs font-bold text-foreground mb-1.5">Status Penayangan</label>
                    <?php $isAuthorRole = in_array(strtolower(session('user_role') ?? ''), ['author', 'contributor', 'support']); ?>
                    <select name="status" id="workflowStatusSelect" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        <option value="Draft" <?= strcasecmp($article['status'] ?? 'Draft', 'Draft') === 0 ? 'selected' : '' ?>>Draft</option>
                        <?php if (!$isAuthorRole): ?>
                            <option value="Publish" <?= in_array(strtolower($article['status'] ?? ''), ['publish', 'published']) ? 'selected' : '' ?>>Publish (Tayang)</option>
                        <?php endif; ?>
                        <option value="Hold" <?= strcasecmp($article['status'] ?? '', 'Hold') === 0 ? 'selected' : '' ?>>Hold (Review / Pending)</option>
                        <?php if (in_array(strtolower(session('user_role') ?? ''), ['administrator', 'redpel'])): ?>
                            <option value="Deleted" <?= strcasecmp($article['status'] ?? '', 'Deleted') === 0 ? 'selected' : '' ?>>Deleted (Soft Delete)</option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Scheduled Date & Time Controls (Slider + Manual Editable Inputs) -->
                <div id="scheduledSettingBox" class="p-3.5 rounded-lg border border-primary/30 bg-primary/5 space-y-3.5" style="display: none;">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-bold text-primary flex items-center gap-1">
                                <span>Pilih Tanggal Tayang:</span>
                                <i data-lucide="calendar" class="size-3.5"></i>
                            </label>
                            <button type="button" id="btnPublishNow" onclick="setPublishNow()" class="inline-flex items-center gap-1 rounded bg-primary text-primary-foreground px-2 py-0.5 text-[10px] font-bold hover:bg-primary/90 transition-colors shadow-2xs">
                                <i data-lucide="zap" class="size-2.5"></i>
                                <span>Publish Now</span>
                            </button>
                        </div>
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
                        <input type="range" id="hourSlider" min="0" max="23" value="9" oninput="onHourSliderChange(this.value)" class="w-full h-1.5 bg-slate-200 dark:bg-slate-700 rounded-lg cursor-pointer">
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
                        <input type="range" id="minuteSlider" min="0" max="59" value="0" oninput="onMinuteSliderChange(this.value)" class="w-full h-1.5 bg-slate-200 dark:bg-slate-700 rounded-lg cursor-pointer">
                    </div>

                    <div class="p-2 rounded bg-background border border-border text-[11px] flex items-center justify-between">
                        <span class="text-muted-foreground">Waktu Publikasi:</span>
                        <span id="publishDateDisplay" class="font-bold text-foreground font-mono">01 Sep 2026 09:00</span>
                    </div>
                </div>

                <!-- Hidden Input holding full Publish Date formatted (standard MySQL Y-m-d H:i:s) -->
                <input type="hidden" name="publish_date" id="publishDateHiddenInput" value="<?= esc(!empty($article['publish_date']) && strtotime($article['publish_date']) ? date('Y-m-d H:i:s', strtotime($article['publish_date'])) : date('Y-m-d H:i:s')) ?>">

                <!-- Preview Article Button in Right Sidebar -->
                <div class="pt-3 border-t border-border">
                    <button type="button" onclick="previewArticlePage()" class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-primary/40 bg-primary/10 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-primary shadow-2xs hover:bg-primary hover:text-white transition-colors">
                        <i data-lucide="eye" class="size-4"></i>
                        <span>Preview Artikel</span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Main Composition Canvas (Order 1 on Desktop, Left Column) -->
        <div class="surface-card p-5 sm:p-6 w-full lg:order-1 lg:col-span-2 space-y-5 shadow-sm border border-border">
            
            <!-- Featured Image / Cover Card in Main Left Column -->
            <div class="p-4 sm:p-5 rounded-xl border border-border bg-muted/30 space-y-4">
                <div class="flex items-center justify-between border-b border-border/60 pb-2.5">
                    <div class="flex items-center gap-2">
                        <i data-lucide="image" class="size-4 text-primary"></i>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-foreground">Featured Image / Cover Utama</h3>
                    </div>
                    <span class="text-[10px] text-muted-foreground font-mono bg-background px-2.5 py-0.5 rounded border border-border">16:9 Ratio</span>
                </div>

                <div class="flex flex-col lg:flex-row gap-5 items-stretch w-full">
                    <!-- Sisi Kiri: Preview Cover 16:9 & Tombol Pilih Media -->
                    <div class="w-full lg:w-64 xl:w-72 shrink-0 flex flex-col justify-between space-y-2.5">
                        <div id="coverPreviewBox" class="relative aspect-video w-full overflow-hidden rounded-lg border-2 border-border bg-slate-900 shadow-sm group shrink-0">
                            <img id="imagePreview" src="<?= esc($article['thumbnail'] ?? '/img/investor_placeholder.jpg') ?>" alt="Cover Preview" class="h-full w-full object-cover transition-transform duration-200" onerror="this.onerror=null;this.src='/img/investor_placeholder.jpg';">
                            
                            <!-- Uploading spinner overlay -->
                            <div id="coverUploadSpinner" class="absolute inset-0 bg-black/75 backdrop-blur-xs flex flex-col items-center justify-center text-white hidden z-20">
                                <svg class="animate-spin size-6 text-white mb-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-[11px] font-medium">Standarisasi & Upload...</span>
                            </div>

                            <div class="absolute bottom-1.5 left-1.5 right-1.5 flex items-center justify-between rounded bg-black/60 backdrop-blur-xs px-2 py-0.5 text-[10px] text-white pointer-events-none">
                                <span class="truncate font-medium">Cover Aktif</span>
                                <i data-lucide="sparkles" class="size-3 text-amber-400"></i>
                            </div>
                        </div>

                        <!-- Button: Pilih dari Pustaka Media -->
                        <button type="button" onclick="openMediaChooserModal('cover')" class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-[#2B6CB0] px-3.5 py-2 text-xs font-bold text-white hover:bg-[#2B6CB0]/90 transition-colors shadow-2xs">
                            <i data-lucide="images" class="size-4"></i>
                            <span>Pilih dari Media Library</span>
                        </button>
                    </div>

                    <!-- Sisi Kanan: Area Upload Dropzone Lebar Penuh (Menjangkau sampai ujung kanan) -->
                    <div class="flex-1 min-w-0 flex flex-col justify-between space-y-2.5">
                        <input type="hidden" name="thumbnail" id="thumbnailInput" value="<?= esc($article['thumbnail'] ?? '/img/investor_placeholder.jpg') ?>">

                        <!-- Wide Dropzone Container (Identik 100% dengan Halaman Upload Media) -->
                        <div id="coverDropzone" class="relative flex-1 w-full rounded-xl border-2 border-dashed border-primary/40 hover:border-primary bg-primary/5 hover:bg-primary/10 transition-all p-6 text-center space-y-3 flex flex-col items-center justify-center cursor-pointer group min-h-[140px]">
                            <input type="file" id="localFileInput" accept="image/jpeg,image/png,image/webp" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="handleLocalImageUpload(this)">

                            <!-- Drag-over overlay -->
                            <div id="coverDropOverlay" class="absolute inset-0 bg-[#2B6CB0]/90 backdrop-blur-xs rounded-xl flex flex-col items-center justify-center text-white hidden z-20 pointer-events-none transition-all">
                                <i data-lucide="upload-cloud" class="size-10 animate-bounce mb-1.5"></i>
                                <span class="text-xs font-bold">Lepaskan file gambar di sini</span>
                            </div>

                            <!-- Center Content: Cloud Upload Icon + Teks Utama + Subtitle (Identik dengan Upload Media) -->
                            <div class="mx-auto size-12 rounded-full bg-primary/15 flex items-center justify-center text-primary group-hover:scale-110 transition-transform pointer-events-none">
                                <i data-lucide="upload-cloud" class="size-6"></i>
                            </div>
                            <div class="pointer-events-none">
                                <div class="text-sm font-bold text-foreground">
                                    <span class="text-primary hover:underline">Klik untuk pilih gambar</span> atau seret file ke sini
                                </div>
                                <p class="text-xs text-muted-foreground mt-1">
                                    Mendukung format JPG, PNG, atau WebP (Rekomendasi min. 1200×675 px, max 5MB)
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2 pt-1">
                            <div>
                                <label class="block text-[11px] font-bold text-foreground mb-1">
                                    Deskripsi Cover <span class="text-destructive">*</span>
                                </label>
                                <textarea name="thumbnail_description" id="thumbnailDescriptionInput" rows="2" class="flex w-full rounded-md border border-input bg-background px-3 py-1.5 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary" placeholder="Masukkan deskripsi gambar cover..."><?= esc($article['thumbnail_description'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-foreground mb-1">
                                    Caption Cover
                                </label>
                                <input type="text" name="thumbnail_caption" id="thumbnailCaptionInput" value="<?= esc($article['thumbnail_caption'] ?? '') ?>" class="flex h-8 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary" placeholder="Caption gambar cover (otomatis mengikuti deskripsi)...">
                            </div>
                        </div>

                        <p class="text-[11px] text-muted-foreground leading-normal">
                            Format disarankan: <strong>JPG, PNG, atau WebP</strong>. Gambar otomatis dioptimasi dan distandarisasi ke resolusi max 1200px JPEG 85%.
                        </p>
                    </div>
                </div>
            </div>

            <!-- A. Scrape Artikel dari URL Eksternal (Placeholder Box) -->
            <div class="p-4 rounded-xl border border-primary/25 bg-primary/5 space-y-2">
                <div class="flex items-center gap-2 text-primary">
                    <i data-lucide="link" class="size-4 text-primary"></i>
                    <span class="text-xs font-bold tracking-tight">Scrape Artikel dari URL Berita Eksternal</span>
                </div>
                <p class="text-[11px] text-muted-foreground">Tempel link artikel dari situs berita yang diizinkan (Antara News). AI akan mengekstrak isi berita dan gambar thumbnail secara otomatis.</p>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 pt-1">
                    <input type="url" id="scrapeUrlInput" placeholder="https://www.antaranews.com/berita/..." class="flex h-9 flex-1 rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary">
                    <button type="button" onclick="executeScrapeArticle()" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md bg-[#2B6CB0] px-4 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90 transition-colors shrink-0">
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

            <!-- C. Judul Artikel (Character Counter Max 70 + Mobile Bottom AI Button) -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground">
                        Judul Artikel <span class="text-destructive">*</span>
                    </label>
                    <!-- Desktop AI Button -->
                    <button type="button" onclick="handleAiAssistance('judul')" class="hidden sm:inline-flex items-center gap-1 rounded bg-accent/15 border border-accent/30 px-2 py-0.5 text-[10px] font-bold text-accent hover:bg-accent/25 transition-colors">
                        <i data-lucide="sparkles" class="size-3 text-accent"></i>
                        <span>GENERATE JUDUL VIA AI</span>
                    </button>
                </div>
                <input type="text" name="title" id="articleTitleInput" required maxlength="70" value="<?= esc($article['title'] ?? '') ?>" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Masukkan judul artikel berita yang lugas dan menarik...">
                
                <div class="flex items-center justify-between text-[11px] pt-0.5">
                    <!-- Mobile Bottom AI Button -->
                    <button type="button" onclick="handleAiAssistance('judul')" class="inline-flex sm:hidden items-center gap-1 rounded bg-accent/15 border border-accent/30 px-2 py-1 text-[10px] font-bold text-accent hover:bg-accent/25 transition-colors">
                        <i data-lucide="sparkles" class="size-3 text-accent"></i>
                        <span>Generate Judul via AI</span>
                    </button>
                    <div class="ml-auto text-muted-foreground font-mono" id="titleCharCounter">
                        <span id="titleCharCount">0</span>/70 karakter
                    </div>
                </div>
            </div>

            <!-- D. Editor & Penulis (Author Byline) Inline with Autocomplete -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground mb-1.5">Editor</label>
                    <input type="text" readonly value="<?= esc(session('user_name') ?? 'Raka Herdika') ?>" class="flex h-9 w-full rounded-md border border-input bg-muted px-3 py-1 text-xs font-medium text-muted-foreground shadow-sm cursor-not-allowed">
                </div>
                <div class="relative" id="authorAutosuggestContainer">
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground mb-1.5" for="authorSearchInput">
                        Penulis (Author By Line) <span class="text-destructive">*</span>
                    </label>
                    <?php
                        $currentAuthor = trim(rtrim($article['author'] ?? session('user_name') ?? 'Natasha Khairunisa', '*)'));
                    ?>
                    <!-- Multi-Author Integrated Tag-Like Wrapper (Finding D2) -->
                    <div id="authorInputWrapper" class="flex flex-wrap items-center gap-1.5 p-1.5 border border-input rounded-md focus-within:border-primary focus-within:ring-1 focus-within:ring-primary bg-background min-h-[38px] transition-all cursor-text shadow-2xs" onclick="document.getElementById('authorSearchInput').focus()">
                        <div id="authorBadgesContainer" class="flex flex-wrap items-center gap-1.5">
                            <!-- Badges rendered dynamically via JS -->
                        </div>
                        <div class="flex-1 flex items-center min-w-[140px]">
                            <input type="text" 
                                   id="authorSearchInput" 
                                   autocomplete="off" 
                                   placeholder="Ketik atau pilih nama penulis..." 
                                   onfocus="openAuthorDropdown()" 
                                   oninput="filterAuthorDropdown(this.value)" 
                                   class="w-full bg-transparent border-none p-0 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-0">
                            <button type="button" 
                                    onclick="toggleAuthorDropdown(event)" 
                                    class="ml-1 size-6 shrink-0 inline-flex items-center justify-center text-muted-foreground hover:text-foreground rounded transition-colors" 
                                    title="Buka daftar penulis">
                                <i data-lucide="chevron-down" id="authorDropdownArrow" class="size-3.5 transition-transform duration-150"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Hidden input submitted with the form -->
                    <input type="hidden" name="author" id="authorHiddenInput" value="<?= esc($currentAuthor) ?>" required>

                    <!-- Autosuggest Dropdown Menu -->
                    <div id="authorSuggestionsDropdown" class="hidden absolute left-0 right-0 top-full mt-1 bg-background border border-border rounded-lg shadow-xl z-50 max-h-56 overflow-y-auto divide-y divide-border/60 custom-sidebar-scroll animate-in fade-in duration-150">
                        <?php if (!empty($authors) && is_array($authors)): ?>
                            <?php foreach ($authors as $a): 
                                $authorCleanName = trim(rtrim($a['name'], '*)'));
                            ?>
                                <div class="author-option-item px-3 py-2 text-xs hover:bg-primary/10 hover:text-primary cursor-pointer flex items-center justify-between transition-colors" 
                                     data-author="<?= esc(strtolower($authorCleanName)) ?>" 
                                     onclick="selectAuthorName('<?= esc(addslashes($authorCleanName)) ?>')">
                                    <span class="font-medium"><?= esc($authorCleanName) ?></span>
                                    <i data-lucide="plus" class="size-3 text-primary"></i>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div id="authorNoResults" class="p-3 text-xs text-muted-foreground italic text-center hidden">
                            Penulis tidak ditemukan. Tekan Enter untuk menambahkan nama yang diketik.
                        </div>
                    </div>
                    <span class="text-[10px] text-muted-foreground mt-0.5 block">Pilih satu atau beberapa penulis dari dropdown atau ketik lalu tekan Enter.</span>
                </div>
            </div>

            <!-- E. Uraian Singkat (Lead / Summary) (Min 110, Max 160 Char Counter + Mobile Bottom AI Button) -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground">Uraian Singkat (Lead / Summary)</label>
                    <!-- Desktop AI Button -->
                    <button type="button" onclick="handleAiAssistance('summary')" class="hidden sm:inline-flex items-center gap-1 rounded bg-accent/15 border border-accent/30 px-2 py-0.5 text-[10px] font-bold text-accent hover:bg-accent/25 transition-colors">
                        <i data-lucide="sparkles" class="size-3 text-accent"></i>
                        <span>GENERATE SUMMARY VIA AI</span>
                    </button>
                </div>
                <textarea name="summary" id="articleSummaryInput" rows="3" maxlength="160" class="flex w-full rounded-md border border-input bg-background p-3 text-xs leading-relaxed shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Ringkasan 1-2 kalimat pengantar artikel untuk cuplikan sosial media dan feed..."><?= esc($article['summary'] ?? '') ?></textarea>
                
                <div class="flex items-center justify-between text-[11px] pt-0.5">
                    <!-- Mobile Bottom AI Button -->
                    <button type="button" onclick="handleAiAssistance('summary')" class="inline-flex sm:hidden items-center gap-1 rounded bg-accent/15 border border-accent/30 px-2 py-1 text-[10px] font-bold text-accent hover:bg-accent/25 transition-colors">
                        <i data-lucide="sparkles" class="size-3 text-accent"></i>
                        <span>Generate Summary via AI</span>
                    </button>
                    <div class="ml-auto text-muted-foreground font-mono" id="summaryCharCounter">
                        <span id="summaryCharCount">0</span>/160 karakter <span id="summaryCharHint" class="text-[10px] font-sans text-amber-500">(Min. 110 char)</span>
                    </div>
                </div>
            </div>

            <!-- F. Kategori / Kanal, Sub-Kanal, dan Sumber Berita Berurutan (Di bawah summary) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-xl border border-border bg-muted/20">
                <!-- Dropdown Kanal -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground mb-1.5">
                        Kanal <span class="text-destructive">*</span>
                    </label>
                    <select name="kanal" id="kanalSelect" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" onchange="onKanalChange(this.value)" required>
                        <option value="" disabled <?= empty($article['kanal']) ? 'selected' : '' ?>>-- Pilih Kanal (Wajib) --</option>
                        <?php 
                            $sessionRole = strtolower(session('user_role') ?? 'admin');
                            foreach ($kanals as $kn): 
                                // Legacy rule: non-administrator cannot select commercial channel
                                if ($sessionRole !== 'administrator' && (strtolower($kn['name']) === 'commercial' || strtolower($kn['slug'] ?? '') === 'commercial')) {
                                    continue;
                                }
                                $currKanal = strtolower(trim($article['kanal'] ?? ($article['category'] ?? '')));
                                $knName = strtolower(trim($kn['name']));
                                $knSlug = strtolower(trim($kn['slug'] ?? ''));
                                $knNameHyphen = str_replace(' ', '-', $knName);
                                $isSelected = ($currKanal === $knName || $currKanal === $knSlug || $currKanal === $knNameHyphen || str_starts_with($currKanal, $knName) || str_starts_with($knName, $currKanal));
                        ?>
                            <option value="<?= esc($kn['name']) ?>" <?= $isSelected ? 'selected' : '' ?>>
                                <?= esc($kn['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sub-Kanal Multi-Select Checkbox Dropdown (Disabled & Greyed out until master kanal chosen) -->
                <div class="relative" id="subKanalContainer">
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground mb-1.5">
                        Sub Kanal <span class="text-[10px] text-muted-foreground font-normal">(Bisa pilih &gt; 1)</span>
                    </label>
                    
                    <button type="button" 
                            id="subKanalDropdownBtn" 
                            disabled 
                            onclick="toggleSubKanalMenu()"
                            class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-muted/60 px-3 py-1 text-xs text-muted-foreground cursor-not-allowed shadow-sm transition-colors focus-visible:outline-none">
                        <span id="subKanalSelectedLabel" class="truncate">-- Pilih Master Kanal Dulu --</span>
                        <i data-lucide="chevron-down" id="subKanalDropdownArrow" class="size-3.5 shrink-0 text-muted-foreground/60 transition-transform duration-150"></i>
                    </button>

                    <!-- Checkbox options dropdown list -->
                    <div id="subKanalMenu" class="absolute left-0 top-full z-40 mt-1 hidden w-full rounded-md border border-border bg-card p-2 shadow-lg max-h-48 overflow-y-auto custom-sidebar-scroll space-y-1">
                        <div id="subKanalOptionsList" class="space-y-1">
                            <!-- Populated dynamically via JS based on selected master kanal -->
                        </div>
                    </div>
                </div>

                <!-- Sumber Berita (Legacy Structured Dropdown & Custom Input) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-foreground">Sumber Berita</label>
                        <span id="customSourceBadge" class="<?= (!empty($currentSource) && !in_array($currentSource, ['Investor Daily', 'Investor Daily TV', 'Investor.id', 'BeritaSatu.com', 'Majalah Investor', 'BTV', 'ANTARA', 'REUTERS', 'AFP', 'Bloomberg', 'Bursa Efek Indonesia (IDX)', 'Bank Indonesia', 'WWW', 'World Wide Web (WWW)'])) ? '' : 'hidden' ?> text-[10px] font-medium text-primary bg-primary/10 px-2 py-0.5 rounded">Sumber Manual</span>
                    </div>
                    <?php
                        $currentSource = $article['source'] ?? $article['sumber_berita'] ?? 'Investor Daily';
                        $internalSources = [
                            'Investor Daily',
                            'Investor Daily TV',
                            'Investor.id',
                            'BeritaSatu.com',
                            'Majalah Investor',
                            'BTV'
                        ];
                        $externalSources = [
                            'ANTARA',
                            'REUTERS',
                            'AFP',
                            'Bloomberg',
                            'Bursa Efek Indonesia (IDX)',
                            'Bank Indonesia',
                            'WWW' => 'World Wide Web (WWW)'
                        ];

                        $matchedSource = false;
                        foreach ($internalSources as $s) {
                            if (strcasecmp($currentSource, $s) === 0) {
                                $matchedSource = $s;
                                break;
                            }
                        }
                        if (!$matchedSource) {
                            foreach ($externalSources as $val => $label) {
                                $target = is_string($val) ? $val : $label;
                                if (strcasecmp($currentSource, $target) === 0 || strcasecmp($currentSource, $label) === 0) {
                                    $matchedSource = $target;
                                    break;
                                }
                            }
                        }
                        $isCustom = ($matchedSource === false);
                    ?>
                    <select id="sourceSelect" onchange="handleSourceChange(this.value)" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring font-medium text-foreground">
                        <optgroup label="INTERNAL">
                            <?php foreach ($internalSources as $sSource): ?>
                                <option value="<?= esc($sSource) ?>" <?= ($matchedSource === $sSource) ? 'selected' : '' ?>><?= esc($sSource) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="EXTERNAL">
                            <?php foreach ($externalSources as $val => $label): 
                                $optVal = is_string($val) ? $val : $label;
                                $optLabel = $label;
                            ?>
                                <option value="<?= esc($optVal) ?>" <?= ($matchedSource === $optVal) ? 'selected' : '' ?>><?= esc($optLabel) ?></option>
                            <?php endforeach; ?>
                            <option value="OTHERS" <?= $isCustom ? 'selected' : '' ?>>OTHERS (Ketik Sumber Lain)...</option>
                        </optgroup>
                    </select>

                    <div id="customSourceWrapper" class="<?= $isCustom ? '' : 'hidden' ?> mt-2">
                        <div class="relative">
                            <input type="text" 
                                   id="customSourceInput" 
                                   name="source" 
                                   value="<?= esc($currentSource) ?>" 
                                   placeholder="Ketik nama sumber berita lain..." 
                                   class="flex h-8 w-full rounded-md border border-input bg-background pl-8 pr-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            <i data-lucide="edit-3" class="size-3.5 text-muted-foreground absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        </div>
                        <p class="text-[10px] text-muted-foreground mt-1">Isi sumber berita manual jika tidak tertera pada daftar di atas.</p>
                    </div>
                </div>
            </div>

            <!-- G. Poin Utama Artikel (Dynamic Points Repeater + AI Button) -->
            <div class="p-4 rounded-xl border border-primary/20 bg-primary/5 space-y-3">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground flex items-center gap-1.5">
                        <i data-lucide="list-ordered" class="size-4 text-primary"></i>
                        <span>Poin Utama Artikel (Points)</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="addPointRow()" class="inline-flex items-center gap-1 rounded bg-primary/10 border border-primary/20 px-2 py-0.5 text-[10px] font-bold text-primary hover:bg-primary/20 transition-colors">
                            <i data-lucide="plus" class="size-3"></i>
                            <span>Tambah Poin</span>
                        </button>
                        <button type="button" onclick="handleAiAssistance('points')" class="inline-flex items-center gap-1 rounded bg-accent/15 border border-accent/30 px-2 py-0.5 text-[10px] font-bold text-accent hover:bg-accent/25 transition-colors">
                            <i data-lucide="sparkles" class="size-3 text-accent"></i>
                            <span>GENERATE VIA AI</span>
                        </button>
                    </div>
                </div>
                
                <?php
                    $pts = $article['points'] ?? ['', '', ''];
                    if (!is_array($pts) || empty($pts)) $pts = ['', '', ''];
                ?>
                <div id="pointsListContainer" class="space-y-2">
                    <?php foreach ($pts as $pIdx => $pVal): ?>
                    <div class="point-row flex items-center gap-2">
                        <span class="point-number grid size-6 shrink-0 place-items-center rounded-full bg-primary/20 text-primary text-[10px] font-bold"><?= $pIdx + 1 ?></span>
                        <input type="text" name="points[]" value="<?= esc($pVal) ?>" class="point-input flex h-8 flex-1 rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Poin penting artikel...">
                        <button type="button" onclick="removePointRow(this)" class="point-remove-btn grid size-8 shrink-0 place-items-center rounded-md text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors" title="Hapus poin ini">
                            <i data-lucide="trash-2" class="size-3.5"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- H. Body Artikel (Expanded Quill WYSIWYG Editor - Min Height 550px, No Label) -->
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
                    <button type="button" onclick="openMediaChooserModal('ckeditor')" class="inline-flex items-center gap-1 rounded bg-background border border-border px-2.5 py-1 text-[11px] font-medium text-foreground hover:bg-primary/10 hover:text-primary hover:border-primary/30 transition-colors shadow-2xs">
                        <i data-lucide="image" class="size-3 text-primary"></i>
                        <span>Insert Media</span>
                    </button>
                    <button type="button" onclick="openEmbedGoogleDocModal()" class="inline-flex items-center gap-1 rounded bg-background border border-border px-2.5 py-1 text-[11px] font-medium text-foreground hover:bg-primary/10 hover:text-primary hover:border-primary/30 transition-colors shadow-2xs">
                        <i data-lucide="file-spreadsheet" class="size-3 text-emerald-600"></i>
                        <span>Google Docs/Sheets</span>
                    </button>
                    <button type="button" onclick="insertEmbedTool('embed_socmed')" class="inline-flex items-center gap-1 rounded bg-background border border-border px-2.5 py-1 text-[11px] font-medium text-foreground hover:bg-primary/10 hover:text-primary hover:border-primary/30 transition-colors shadow-2xs">
                        <i data-lucide="code-2" class="size-3 text-amber-600"></i>
                        <span>Embed Socmed</span>
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

                <!-- CKEditor 4 WYSIWYG Editor Container (100% Identical Toolbar to Pasted image 20260911143610.png) -->
                <div class="rounded-lg overflow-hidden border border-border bg-card shadow-xs">
                    <textarea id="articleEditor" name="content" class="w-full min-h-[550px]"><?= esc($article['content'] ?? '') ?></textarea>
                </div>
                <!-- Hidden input for backward compatibility if needed -->
                <input type="hidden" id="hiddenContentInput" value="<?= esc($article['content'] ?? '') ?>">
            </div>

            <!-- I. Kata Kunci / Keyword Integrated Input System (Matching PortalBerita) -->
            <div class="pt-2 border-t border-border space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground flex items-center gap-1.5">
                        <i data-lucide="tag" class="size-3.5 text-primary"></i>
                        <span>Kata Kunci / Keyword</span>
                    </label>
                    <button type="button" onclick="handleAiAssistance('tags')" class="inline-flex items-center gap-1 rounded bg-accent/15 border border-accent/30 px-2 py-0.5 text-[10px] font-bold text-accent hover:bg-accent/25 transition-colors">
                        <i data-lucide="sparkles" class="size-3 text-accent"></i>
                        <span>GENERATE TAGS VIA AI</span>
                    </button>
                </div>
                
                <div class="relative">
                    <!-- Integrated Tags Input Wrapper -->
                    <div id="tagsInputWrapper" class="flex flex-wrap items-center gap-1.5 p-2 border border-input rounded-md focus-within:border-primary focus-within:ring-1 focus-within:ring-primary bg-background min-h-[42px] transition-all cursor-text shadow-2xs" onclick="document.getElementById('artTagInput').focus()">
                        <div id="tags-badge-container" class="flex flex-wrap items-center gap-1.5">
                            <!-- Badges rendered dynamically via JS -->
                        </div>
                        <input id="artTagInput" type="text" autocomplete="off" placeholder="Ketik kata kunci lalu tekan Enter..." class="flex-grow min-w-[140px] focus:outline-none text-xs bg-transparent border-none p-0 text-foreground placeholder:text-muted-foreground">
                    </div>

                    <!-- Hidden input submitted with the form -->
                    <input type="hidden" name="tags" id="articleTagsInput" value="<?= esc($article['tags'] ?? '') ?>">

                    <!-- Autocomplete Suggestions Dropdown -->
                    <div id="tag-suggestions-dropdown" class="hidden absolute left-0 right-0 mt-1 bg-card border border-border rounded-lg shadow-xl z-50 max-h-48 overflow-y-auto divide-y divide-border/60 animate-in fade-in duration-150">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>

                <span class="text-[10px] text-muted-foreground block">Ketik kata kunci dan tekan <strong>Enter</strong> atau <strong>koma (,)</strong> untuk menambahkan. Tekan <strong>Backspace</strong> untuk menghapus badge terakhir.</span>
            </div>

            <!-- Bottom Action Footer -->
            <div class="flex flex-wrap items-center justify-end gap-3 pt-4 border-t border-border">
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-[#2B6CB0] px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90 transition-colors">
                    <i data-lucide="save" class="size-4"></i>
                    <span>SIMPAN ARTIKEL</span>
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Modal Media Library Chooser (Standardized max-w-2xl with clean responsive grid) -->
<div id="mediaChooserModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1.25rem;">
    <div class="surface-card w-full max-w-2xl max-h-[82vh] flex flex-col p-5 shadow-2xl space-y-3.5 animate-in fade-in zoom-in-95 duration-150 rounded-xl border border-border">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-border pb-3">
            <div>
                <h3 class="text-sm sm:text-base font-bold text-foreground flex items-center gap-2">
                    <i data-lucide="images" class="size-4 text-primary"></i>
                    <span>Media Library — Pilih Gambar Berita</span>
                </h3>
                <p class="text-[11px] text-muted-foreground mt-0.5">Pilih media dari bank aset editorial untuk featured cover atau disisipkan ke naskah.</p>
            </div>
            <button type="button" onclick="closeMediaChooserModal()" class="rounded p-1 text-muted-foreground hover:text-foreground hover:bg-muted transition-colors">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>

        <!-- Search Bar -->
        <div class="relative">
            <i data-lucide="search" class="size-3.5 text-muted-foreground absolute left-3 top-2.5"></i>
            <input type="text" id="mediaModalSearchInput" oninput="filterMediaModalCards(this.value)" placeholder="Cari judul gambar, topik, atau caption..." class="flex h-8 w-full rounded-md border border-input bg-background pl-8 pr-3 text-xs shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary">
        </div>

        <!-- Vertical Responsive Card Grid (2x2 layout, 2 columns) -->
        <div id="mediaModalCardsGrid" class="overflow-y-auto flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4 p-1 custom-sidebar-scroll max-h-[480px]">
            <?php foreach ($media as $m): ?>
            <div class="media-card-item flex flex-col rounded-xl border border-border overflow-hidden bg-card hover:border-primary/70 hover:shadow-lg transition-all group" data-search="<?= strtolower(esc($m['title'] . ' ' . ($m['caption'] ?? '') . ' ' . ($m['source'] ?? ''))) ?>" style="min-height: 270px;">
                <!-- Bagian 1 (Atas): Gambar Media -->
                <div class="relative w-full bg-slate-900 overflow-hidden border-b border-border" style="height: 150px;">
                    <img src="<?= esc($m['image']) ?>" alt="<?= esc($m['title']) ?>" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" onerror="this.onerror=null;this.src='/img/investor_placeholder.jpg';">
                    <span class="absolute bottom-2 right-2 rounded bg-black/80 backdrop-blur-xs px-2 py-0.5 text-[9px] font-mono font-medium text-white z-10 shadow-xs">
                        <?= esc($m['source'] ?? 'Investor.id') ?>
                    </span>
                </div>
                
                <!-- Bagian 2 (Bawah): Deskripsi Gambar & Aksi -->
                <div class="p-3 bg-muted/20 flex flex-col flex-1 justify-between gap-2.5">
                    <div>
                        <h4 class="text-xs font-bold text-foreground line-clamp-1 leading-snug group-hover:text-primary transition-colors" title="<?= esc($m['title']) ?>">
                            <?= esc($m['title']) ?>
                        </h4>
                        <p class="text-[11px] text-muted-foreground line-clamp-2 mt-1 leading-relaxed">
                            <?= esc($m['caption'] ?? '-') ?>
                        </p>
                    </div>

                    <div class="pt-2 border-t border-border/70 flex items-center justify-between gap-2">
                        <span class="text-[9px] text-muted-foreground font-mono truncate">
                            <i data-lucide="calendar" class="size-2.5 inline-block mr-0.5"></i><?= esc($m['date'] ?? '') ?>
                        </span>
                        <button type="button" onclick="selectMediaImage('<?= esc(addslashes($m['image'])) ?>', '<?= esc(addslashes($m['caption'] ?? 'Dokumentasi Investor.id')) ?>', '<?= esc(addslashes($m['description'] ?? ($m['caption'] ?? 'Dokumentasi Investor.id'))) ?>')" class="inline-flex items-center gap-1.5 rounded-md bg-[#2B6CB0] text-white px-3 py-1.5 text-xs font-bold shadow-2xs hover:bg-[#2B6CB0]/90 transition-colors shrink-0">
                            <i data-lucide="check" class="size-3"></i>
                            <span>Pilih Gambar</span>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-between pt-2.5 border-t border-border">
            <span class="text-[11px] text-muted-foreground"><span id="mediaModalCount"><?= count($media) ?></span> media tersedia</span>
            <button type="button" onclick="closeMediaChooserModal()" class="rounded-md border border-input bg-background px-3.5 py-1.5 text-xs font-medium text-foreground hover:bg-muted transition-colors">Tutup</button>
        </div>
    </div>
</div>

<!-- Modal Add Article Link (Standardized max-w-2xl) -->
<div id="articleLinkModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1.25rem;">
    <div class="surface-card w-full max-w-2xl max-h-[82vh] flex flex-col p-5 shadow-2xl space-y-3.5 animate-in fade-in zoom-in-95 duration-150 rounded-xl border border-border">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <div>
                <h3 class="text-sm sm:text-base font-bold text-foreground flex items-center gap-2">
                    <i data-lucide="link-2" class="size-4 text-primary"></i>
                    <span>Sisipkan Link Artikel ("Baca Juga")</span>
                </h3>
                <p class="text-[11px] text-muted-foreground mt-0.5">Pilih artikel berita untuk disisipkan sebagai referensi bacaan di dalam naskah.</p>
            </div>
            <button type="button" onclick="closeArticleLinkModal()" class="rounded p-1 text-muted-foreground hover:text-foreground hover:bg-muted transition-colors">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>

        <!-- Tag Filter Chips Bar -->
        <div class="flex items-center gap-1.5 flex-wrap text-xs pb-0.5">
            <span class="text-[11px] font-bold text-muted-foreground mr-1">Filter Tag:</span>
            <button type="button" onclick="filterArticleModalByTag('all')" class="modal-tag-chip rounded-full bg-primary/10 text-primary border border-primary/30 px-2 py-0.5 text-[10px] font-semibold">Semua</button>
            <button type="button" onclick="filterArticleModalByTag('IHSG')" class="modal-tag-chip rounded-full bg-muted text-muted-foreground px-2 py-0.5 text-[10px] font-medium hover:text-foreground">IHSG</button>
            <button type="button" onclick="filterArticleModalByTag('Saham')" class="modal-tag-chip rounded-full bg-muted text-muted-foreground px-2 py-0.5 text-[10px] font-medium hover:text-foreground">Saham</button>
            <button type="button" onclick="filterArticleModalByTag('Perbankan')" class="modal-tag-chip rounded-full bg-muted text-muted-foreground px-2 py-0.5 text-[10px] font-medium hover:text-foreground">Perbankan</button>
            <button type="button" onclick="filterArticleModalByTag('Komoditas')" class="modal-tag-chip rounded-full bg-muted text-muted-foreground px-2 py-0.5 text-[10px] font-medium hover:text-foreground">Komoditas</button>
        </div>

        <!-- Search input in modal -->
        <div class="relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 size-3 text-muted-foreground"></i>
            <input type="text" id="articleModalSearch" onkeyup="filterArticleModalList()" placeholder="Cari judul artikel..." class="flex h-8 w-full rounded-md border border-input bg-background pl-8 pr-3 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
        </div>

        <!-- Article Rows List -->
        <div id="articleModalList" class="overflow-y-auto flex-1 space-y-2 p-1 max-h-[300px] custom-sidebar-scroll">
            <?php foreach (($allPosts ?? []) as $ap): ?>
            <div class="article-modal-row flex items-center justify-between gap-3 p-2.5 rounded-lg border border-border hover:border-primary hover:bg-muted/40 transition-colors" data-title="<?= esc(strtolower($ap['title'])) ?>" data-tags="<?= esc(strtolower($ap['tags'] ?? '')) ?>" data-kanal="<?= esc(strtolower($ap['kanal'] ?? '')) ?>">
                <div class="flex items-center gap-3 min-w-0">
                    <img src="<?= esc($ap['thumbnail']) ?>" alt="" loading="lazy" decoding="async" class="size-9 rounded object-cover border border-border shrink-0">
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

        <div class="flex justify-end pt-2.5 border-t border-border">
            <button type="button" onclick="closeArticleLinkModal()" class="rounded-md border border-input bg-background px-3.5 py-1.5 text-xs font-medium text-foreground hover:bg-muted">Tutup</button>
        </div>
    </div>
</div>

<!-- Modal 1: Embed Chart from Database (Standardized max-w-lg) -->
<div id="embedChartModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1.25rem;">
    <div class="surface-card w-full max-w-lg max-h-[82vh] flex flex-col p-5 shadow-2xl space-y-3.5 animate-in fade-in zoom-in-95 duration-150 rounded-xl border border-border">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <div>
                <h3 class="text-sm sm:text-base font-bold text-foreground flex items-center gap-2">
                    <i data-lucide="line-chart" class="size-4 text-accent"></i>
                    <span>Pilih Financial Chart dari Database</span>
                </h3>
                <p class="text-[11px] text-muted-foreground mt-0.5">Pilih chart yang sudah dibuat di menu Embed Chart.</p>
            </div>
            <button type="button" onclick="closeEmbedChartModal()" class="rounded p-1 text-muted-foreground hover:text-foreground hover:bg-muted transition-colors">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>

        <div class="relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 size-3 text-muted-foreground"></i>
            <input type="text" id="chartSearchInput" oninput="filterChartModalList(this.value)" placeholder="Cari judul chart atau simbol saham..." class="flex h-8 w-full rounded-md border border-input bg-background pl-8 pr-3 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary">
        </div>

        <div id="chartModalList" class="overflow-y-auto flex-1 space-y-2 p-1 max-h-[280px] custom-sidebar-scroll">
            <?php if (empty($charts)): ?>
                <div class="p-6 text-center text-xs text-muted-foreground">
                    Belum ada chart konfigurasi di database. Silakan buat di menu <a href="/embed-chart" target="_blank" class="text-primary underline">Embed Chart</a>.
                </div>
            <?php else: ?>
                <?php foreach ($charts as $ch): ?>
                <div class="chart-modal-row flex items-center justify-between gap-3 p-2.5 rounded-lg border border-border hover:border-primary hover:bg-muted/40 transition-colors" data-search="<?= strtolower(esc(($ch['title'] ?? '') . ' ' . ($ch['symbol'] ?? '') . ' ' . ($ch['source'] ?? ''))) ?>">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="rounded bg-accent/15 px-1.5 py-0.2 text-[9px] font-bold text-accent"><?= esc($ch['type'] ?? 'Area') ?></span>
                            <h4 class="text-xs font-bold text-foreground truncate"><?= esc($ch['title']) ?></h4>
                        </div>
                        <span class="text-[10px] text-muted-foreground block mt-0.5">Simbol: <strong><?= esc($ch['symbol'] ?? 'IDX') ?></strong> &bull; Sumber: <?= esc($ch['source'] ?? 'Investor.id') ?></span>
                    </div>
                    <button type="button" onclick="insertSelectedChart('<?= esc($ch['id'] ?? '') ?>', '<?= esc(addslashes($ch['title'])) ?>', '<?= esc(addslashes($ch['symbol'] ?? 'IDX')) ?>', '<?= esc(addslashes($ch['type'] ?? 'Area')) ?>', '<?= esc(addslashes($ch['source'] ?? 'Investor.id')) ?>')" class="inline-flex shrink-0 items-center gap-1 rounded bg-[#2B6CB0] px-2.5 py-1 text-xs font-bold text-white hover:bg-[#2B6CB0]/90 shadow-2xs">
                        <span>Sisipkan</span>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-between pt-2.5 border-t border-border">
            <a href="/embed-chart" target="_blank" class="text-xs text-primary hover:underline flex items-center gap-1">
                <i data-lucide="plus" class="size-3"></i>
                <span>Kelola Chart Baru</span>
            </a>
            <button type="button" onclick="closeEmbedChartModal()" class="rounded-md border border-input bg-background px-3.5 py-1.5 text-xs font-medium text-foreground hover:bg-muted">Tutup</button>
        </div>
    </div>
</div>

<!-- Modal 2: Embed Socmed / External Document (Standardized max-w-lg) -->
<div id="embedSocmedModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1.25rem;">
    <div class="surface-card w-full max-w-lg max-h-[85vh] flex flex-col p-5 shadow-2xl space-y-3.5 animate-in fade-in zoom-in-95 duration-150 rounded-xl border border-border">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <div>
                <h3 class="text-sm sm:text-base font-bold text-foreground flex items-center gap-2">
                    <i data-lucide="code-2" class="size-4 text-amber-600"></i>
                    <span>Sisipkan Embed Media Sosial / Dokumen</span>
                </h3>
                <p class="text-[11px] text-muted-foreground mt-0.5">Pilih platform dan masukkan link konten eksternal.</p>
            </div>
            <button type="button" onclick="closeEmbedSocmedModal()" class="rounded p-1 text-muted-foreground hover:text-foreground hover:bg-muted transition-colors">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>

        <div class="space-y-3">
            <div>
                <label class="block text-xs font-bold text-foreground mb-1">Pilih Platform</label>
                <select id="socmedPlatformSelect" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary">
                    <option value="Instagram">Instagram Post / Reel</option>
                    <option value="Twitter / X">X (Twitter) Post</option>
                    <option value="YouTube">YouTube Video</option>
                    <option value="TikTok">TikTok Video</option>
                    <option value="Google Drive / PDF">Google Drive / PDF Document</option>
                    <option value="Scribd">Scribd Financial Report</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-foreground mb-1">URL / Link Konten</label>
                <input type="url" id="socmedUrlInput" placeholder="https://www.instagram.com/p/... atau https://youtu.be/..." class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary">
            </div>

            <div>
                <label class="block text-xs font-bold text-foreground mb-1">Keterangan / Caption (Opsional)</label>
                <input type="text" id="socmedCaptionInput" placeholder="Keterangan singkat unggahan..." class="flex h-8 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary">
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-border">
            <button type="button" onclick="closeEmbedSocmedModal()" class="rounded-md border border-input bg-background px-3.5 py-1.5 text-xs font-medium text-foreground hover:bg-muted">Batal</button>
            <button type="button" onclick="submitSocmedEmbed()" class="inline-flex items-center gap-1.5 rounded-md bg-[#2B6CB0] px-4 py-1.5 text-xs font-bold text-white hover:bg-[#2B6CB0]/90">
                <i data-lucide="plus" class="size-3.5"></i>
                <span>Sisipkan Embed</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal 3: Stock Chart with Real-Time Ticker Autocomplete (Standardized max-w-lg) -->
<div id="embedStockModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1.25rem;">
    <div class="surface-card w-full max-w-lg max-h-[85vh] flex flex-col p-5 shadow-2xl space-y-3.5 animate-in fade-in zoom-in-95 duration-150 rounded-xl border border-border">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <div>
                <h3 class="text-sm sm:text-base font-bold text-foreground flex items-center gap-2">
                    <i data-lucide="trending-up" class="size-4 text-indigo-600"></i>
                    <span>Sisipkan Live Stock Chart Widget</span>
                </h3>
                <p class="text-[11px] text-muted-foreground mt-0.5">Widget grafik real-time bursa IDX terintegrasi TradingView.</p>
            </div>
            <button type="button" onclick="closeEmbedStockModal()" class="rounded p-1 text-muted-foreground hover:text-foreground hover:bg-muted transition-colors">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>

        <div class="space-y-3">
            <!-- Stock Ticker Input with Real-Time Autocomplete Dropdown -->
            <div class="relative">
                <label class="block text-xs font-bold text-foreground mb-1">Cari Kode Emiten atau Nama Perusahaan (IDX)</label>
                <div class="relative">
                    <input type="text" id="stockTickerInput" value="" autocomplete="off" placeholder="Ketik kode (misal: BBCA, TLKM) atau nama perusahaan..." class="flex h-9 w-full rounded-md border border-input bg-background font-mono font-bold px-3 py-1 text-sm shadow-sm placeholder:normal-case placeholder:font-sans focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary uppercase">
                </div>
                <!-- Real-Time Stock Dropdown List -->
                <div id="stockAutocompleteDropdown" class="absolute z-50 left-0 right-0 top-full mt-1 bg-background border border-border rounded-lg shadow-xl overflow-hidden hidden max-h-52 overflow-y-auto">
                    <!-- Populated dynamically via JS -->
                </div>
                <span class="text-[10px] text-muted-foreground mt-1 block">Ketik 1 atau 2 huruf untuk memunculkan saran instan dari Bursa Efek Indonesia (IDX).</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-foreground mb-1">Pilihan Cepat Emiten Populer:</label>
                <div class="flex flex-wrap gap-1.5">
                    <?php 
                    $popularStocks = ['BBCA', 'BBRI', 'BMRI', 'TLKM', 'ASII', 'AADI', 'BREN', 'AMMN', 'GOTO', 'ESSA'];
                    foreach ($popularStocks as $stk):
                    ?>
                    <button type="button" onclick="setQuickStockTicker('<?= $stk ?>')" class="rounded border border-border bg-background px-2.5 py-1 text-xs font-mono font-bold hover:bg-primary/10 hover:text-primary hover:border-primary/40 transition-colors">
                        <?= $stk ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-foreground mb-1">Tipe Tampilan Chart</label>
                <select id="stockChartTypeSelect" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary">
                    <option value="tradingview_full" selected>TradingView Full Interactive Live Chart (Rekomendasi)</option>
                    <option value="widget_card">Mini Ticker Card Live IDX</option>
                </select>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-border">
            <button type="button" onclick="closeEmbedStockModal()" class="rounded-md border border-input bg-background px-3.5 py-1.5 text-xs font-medium text-foreground hover:bg-muted">Batal</button>
            <button type="button" onclick="submitStockEmbed()" class="inline-flex items-center gap-1.5 rounded-md bg-[#2B6CB0] px-4 py-1.5 text-xs font-bold text-white hover:bg-[#2B6CB0]/90">
                <i data-lucide="plus" class="size-3.5"></i>
                <span>Sisipkan Stock Chart</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal 4: Embed Google Docs / Spreadsheet (Standardized max-w-lg) -->
<div id="embedGoogleDocModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1.25rem;">
    <div class="surface-card w-full max-w-lg max-h-[85vh] flex flex-col p-5 shadow-2xl space-y-3.5 animate-in fade-in zoom-in-95 duration-150 rounded-xl border border-border">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <div>
                <h3 class="text-sm sm:text-base font-bold text-foreground flex items-center gap-2">
                    <i data-lucide="file-spreadsheet" class="size-4 text-emerald-600"></i>
                    <span>Sisipkan Google Docs / Spreadsheet</span>
                </h3>
                <p class="text-[11px] text-muted-foreground mt-0.5">Embed dokumen atau spreadsheet publik dari Google Workspace.</p>
            </div>
            <button type="button" onclick="closeEmbedGoogleDocModal()" class="rounded p-1 text-muted-foreground hover:text-foreground hover:bg-muted transition-colors">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>

        <div class="space-y-3">
            <div>
                <label class="block text-xs font-bold text-foreground mb-1">Tipe Google File</label>
                <select id="googleDocTypeSelect" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary">
                    <option value="Doc">Google Docs (Dokumen Teks / Riset)</option>
                    <option value="Sheet">Google Sheets (Spreadsheet / Tabel Data)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-foreground mb-1">URL Publik Google Doc / Sheet <span class="text-destructive">*</span></label>
                <input type="url" id="googleDocUrlInput" placeholder="https://docs.google.com/document/d/... atau https://docs.google.com/spreadsheets/d/..." class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary">
                <span class="text-[10px] text-muted-foreground mt-1 block">Pastikan dokumen diset publik (Share &gt; Anyone with the link can view).</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-foreground mb-1">Judul / Keterangan Dokumen (Opsional)</label>
                <input type="text" id="googleDocTitleInput" placeholder="Misal: Laporan Analisis Keuangan Kuartal II..." class="flex h-8 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary">
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-border">
            <button type="button" onclick="closeEmbedGoogleDocModal()" class="rounded-md border border-input bg-background px-3.5 py-1.5 text-xs font-medium text-foreground hover:bg-muted">Batal</button>
            <button type="button" onclick="submitGoogleDocEmbed()" class="inline-flex items-center gap-1.5 rounded-md bg-[#2B6CB0] px-4 py-1.5 text-xs font-bold text-white hover:bg-[#2B6CB0]/90">
                <i data-lucide="plus" class="size-3.5"></i>
                <span>Sisipkan Dokumen</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal 5: Unsaved Changes Confirmation Modal -->
<div id="unsavedChangesModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; padding: 1.25rem;">
    <div class="surface-card w-full max-w-md p-5 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150 rounded-xl border border-amber-500/30">
        <div class="flex items-start gap-3">
            <div class="size-10 rounded-full bg-amber-500/15 text-amber-600 flex items-center justify-center shrink-0">
                <i data-lucide="alert-triangle" class="size-5"></i>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-bold text-foreground">Perubahan Belum Disimpan</h3>
                <p class="text-xs text-muted-foreground mt-1 leading-relaxed">
                    Anda memiliki draft atau perubahan artikel yang belum disimpan ke database. Yakin ingin meninggalkan halaman ini?
                </p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
            <button type="button" onclick="closeUnsavedChangesModal()" class="rounded-md border border-input bg-background px-3.5 py-1.5 text-xs font-semibold text-foreground hover:bg-muted transition-colors">
                Tetap di Halaman Ini
            </button>
            <button type="button" id="confirmLeavePageBtn" class="inline-flex items-center gap-1.5 rounded-md bg-destructive px-3.5 py-1.5 text-xs font-bold text-destructive-foreground hover:bg-destructive/90 transition-colors">
                <span>Tinggalkan Halaman</span>
            </button>
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
<!-- CKEditor 4 Full Suite CDN & Flatpickr CDN -->
<script src="https://cdn.ckeditor.com/4.22.1/full-all/ckeditor.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="/js/pages/editor.js?v=<?= time() ?>"></script>
<?php if ($isEdit && !empty($article['id'])): ?>
<script>
// Auto release edit lock when navigating away or closing window
window.addEventListener('beforeunload', function() {
    navigator.sendBeacon('<?= base_url('api/post/release-lock/' . $article['id']) ?>');
});

function forceUnlockArticle(articleId) {
    if (!confirm('Yakin ingin membuka gembok artikel ini secara paksa?')) return;
    fetch('<?= base_url('api/post/force-unlock/') ?>' + articleId, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const banner = document.getElementById('lockWarningBanner');
            if (banner) banner.remove();
            alert('Gembok berhasil dibuka. Anda sekarang dapat mengedit artikel ini.');
        } else {
            alert(data.error || 'Gagal membuka gembok.');
        }
    })
    .catch(err => alert('Terjadi kesalahan: ' + err.message));
}
</script>
<?php endif; ?>
<?= $this->endSection() ?>

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
                <!-- Preview Button -->
                <button type="button" onclick="openArticlePreview()" class="inline-flex items-center justify-center gap-1.5 rounded-md border border-input bg-background px-3.5 py-2 text-xs font-semibold text-foreground hover:bg-muted hover:text-primary transition-colors shadow-xs">
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

    <!-- 2-Column Canvas Layout (Fresh & Modern Newsroom Workspace) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        <!-- Left: Main Composition Canvas -->
        <div class="surface-card p-5 sm:p-6 lg:col-span-2 space-y-5">
            
            <!-- Upper Deck & Premium Flag (Header bar in form) -->
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

            <!-- Headline Title -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-foreground mb-1.5">
                    Judul Artikel <span class="text-destructive">*</span>
                </label>
                <input type="text" name="title" required value="<?= esc($article['title'] ?? '') ?>" class="flex h-11 w-full rounded-md border border-input bg-background px-3.5 py-2 text-base font-bold shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Masukkan judul artikel berita yang lugas dan menarik...">
            </div>

            <!-- Editor & Penulis (Author Byline) Inline -->
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

            <!-- Uraian Singkat / Deck Lead & Sumber -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground mb-1.5">Uraian Singkat (Lead / Summary)</label>
                    <textarea name="summary" rows="3" maxlength="280" class="flex w-full rounded-md border border-input bg-background p-3 text-xs leading-relaxed shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Ringkasan 1-2 kalimat pengantar artikel untuk cuplikan sosial media dan feed..."><?= esc($article['summary'] ?? '') ?></textarea>
                    <span class="text-[10px] text-muted-foreground mt-0.5 block font-medium">Maksimal 280 karakter.</span>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground mb-1.5">Sumber Berita</label>
                    <input type="text" name="source" value="<?= esc($article['source'] ?? 'Investor.id / Riset') ?>" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Contoh: IDX, BI, Reuters">
                    <label class="block text-xs font-bold uppercase tracking-wider text-foreground mt-3 mb-1.5">Kata Kunci (Tags)</label>
                    <input type="text" name="tags" value="<?= esc($article['tags'] ?? '') ?>" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="IHSG, Saham, Dividen">
                </div>
            </div>

            <!-- Body Artikel with Toolkit & WYSIWYG Editor -->
            <div class="space-y-2 pt-2 border-t border-border">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-foreground">
                        Body Artikel <span class="text-destructive">*</span>
                    </label>
                    <span class="text-[11px] text-muted-foreground font-mono">Quill WYSIWYG Editor</span>
                </div>

                <!-- Custom Embed Toolbar Row (Toolkit Khusus Portal Berita) -->
                <div class="flex flex-wrap items-center gap-1.5 p-2 rounded-lg bg-muted/70 border border-border">
                    <span class="text-[10px] font-bold text-muted-foreground uppercase mr-1 px-1">Sisipkan:</span>
                    <button type="button" onclick="insertEmbedTool('article_link')" class="inline-flex items-center gap-1 rounded bg-background border border-border px-2.5 py-1 text-[11px] font-medium text-foreground hover:bg-primary/10 hover:text-primary hover:border-primary/30 transition-colors shadow-2xs">
                        <i data-lucide="link-2" class="size-3 text-primary"></i>
                        <span>Add Article Link</span>
                    </button>
                    <button type="button" onclick="insertEmbedTool('embed_chart')" class="inline-flex items-center gap-1 rounded bg-background border border-border px-2.5 py-1 text-[11px] font-medium text-foreground hover:bg-primary/10 hover:text-primary hover:border-primary/30 transition-colors shadow-2xs">
                        <i data-lucide="line-chart" class="size-3 text-accent"></i>
                        <span>Embed Chart</span>
                    </button>
                    <button type="button" onclick="insertEmbedTool('insert_media')" class="inline-flex items-center gap-1 rounded bg-background border border-border px-2.5 py-1 text-[11px] font-medium text-foreground hover:bg-primary/10 hover:text-primary hover:border-primary/30 transition-colors shadow-2xs">
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

                <!-- Quill WYSIWYG Container -->
                <div id="quillEditor" class="min-h-[300px]"></div>
                <!-- Hidden input that holds the HTML value for POST submit -->
                <input type="hidden" name="content" id="hiddenContentInput" value="<?= esc($article['content'] ?? '') ?>">
            </div>

            <!-- Bottom Action Footer -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-border">
                <button type="button" onclick="openArticlePreview()" class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-600 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-emerald-700 transition-colors">
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
            
            <!-- 1. Featured Image & Cover Preview Card (Prominent Top Position) -->
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

                <!-- Thumbnail Input & Action Buttons -->
                <div class="space-y-2 pt-1">
                    <input type="text" name="thumbnail" id="thumbnailInput" value="<?= esc($article['thumbnail'] ?? '/img/article1.jpg') ?>" class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="URL Foto Gambar...">
                    
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="openMediaChooserModal()" class="inline-flex items-center justify-center gap-1.5 rounded-md bg-[#2B6CB0] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#2B6CB0]/90 transition-colors shadow-2xs">
                            <i data-lucide="images" class="size-3.5"></i>
                            <span>Pilih dari Media</span>
                        </button>
                        <a href="<?= base_url('media/new') ?>" target="_blank" class="inline-flex items-center justify-center gap-1.5 rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium text-foreground hover:bg-muted transition-colors">
                            <i data-lucide="upload" class="size-3.5"></i>
                            <span>Upload Baru</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- 2. Publish Setting & Scheduled Date/Time Slider Card -->
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

                <!-- Scheduled Date & Time Sliders (Appears dynamically when status == 'Scheduled') -->
                <div id="scheduledSettingBox" class="p-3.5 rounded-lg border border-primary/30 bg-primary/5 space-y-3.5" style="display: none;">
                    <div>
                        <label class="block text-xs font-bold text-primary mb-1 flex items-center justify-between">
                            <span>Pilih Tanggal Tayang:</span>
                            <i data-lucide="calendar" class="size-3.5"></i>
                        </label>
                        <input type="text" id="scheduledDateInput" class="flex h-8 w-full rounded-md border border-input bg-background px-2.5 py-1 text-xs shadow-sm font-medium" placeholder="Pilih tanggal...">
                    </div>

                    <!-- Time Slider: Jam -->
                    <div class="space-y-1">
                        <div class="flex justify-between text-xs font-medium">
                            <span class="text-muted-foreground">Jam Tayang:</span>
                            <span id="hourValueLabel" class="font-bold text-primary font-mono">09</span>
                        </div>
                        <input type="range" min="0" max="23" value="9" oninput="onHourSliderChange(this.value)" class="w-full h-1.5 bg-slate-200 rounded-lg cursor-pointer">
                    </div>

                    <!-- Time Slider: Menit -->
                    <div class="space-y-1">
                        <div class="flex justify-between text-xs font-medium">
                            <span class="text-muted-foreground">Menit Tayang:</span>
                            <span id="minuteValueLabel" class="font-bold text-primary font-mono">00</span>
                        </div>
                        <input type="range" min="0" max="59" value="0" oninput="onMinuteSliderChange(this.value)" class="w-full h-1.5 bg-slate-200 rounded-lg cursor-pointer">
                    </div>

                    <div class="p-2 rounded bg-background border border-border text-[11px] flex items-center justify-between">
                        <span class="text-muted-foreground">Waktu Dijadwalkan:</span>
                        <span id="publishDateDisplay" class="font-bold text-foreground font-mono">01 Sep 2026 09:00</span>
                    </div>
                </div>

                <!-- Hidden Input holding full Publish Date formatted -->
                <input type="hidden" name="publish_date" id="publishDateHiddenInput" value="<?= esc($article['publish_date'] ?? date('d M Y H:i')) ?>">

                <!-- Category Selector -->
                <div>
                    <label class="block text-xs font-bold text-foreground mb-1.5">
                        Kategori Artikel <span class="text-destructive">*</span>
                    </label>
                    <select name="category" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= esc($cat['name']) ?>" <?= ($article['category'] ?? 'Market Stock') === $cat['name'] ? 'selected' : '' ?>>
                                <?= esc($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- 3. Article Distribution & Special Tags -->
            <div class="surface-card p-5 space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-foreground border-b border-border pb-2.5">Distribusi & Highlight</h3>
                
                <label class="flex items-center gap-2.5 cursor-pointer p-2 rounded-lg border border-border hover:bg-muted/50 transition-colors">
                    <input type="checkbox" name="is_breaking" value="1" <?= !empty($article['is_breaking']) ? 'checked' : '' ?> class="size-4 rounded border-input text-destructive focus:ring-destructive">
                    <div>
                        <span class="text-xs font-bold text-destructive block flex items-center gap-1">
                            <i data-lucide="zap" class="size-3.5"></i>
                            <span>Breaking News Alert</span>
                        </span>
                        <span class="text-[10px] text-muted-foreground">Kirim ke ticker bar utama portal</span>
                    </div>
                </label>
            </div>

        </div>
    </div>
</form>

<!-- Modal Media Library Chooser -->
<div id="mediaChooserModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); z-index: 999; align-items: center; justify-content: center; padding: 1rem;">
    <div class="surface-card w-full max-w-3xl max-h-[85vh] flex flex-col p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <div>
                <h3 class="text-sm font-bold text-foreground">Media Library — Pilih Gambar Berita</h3>
                <p class="text-xs text-muted-foreground">Pilih gambar untuk featured cover atau sisipkan ke dalam naskah berita.</p>
            </div>
            <button type="button" onclick="closeMediaChooserModal()" class="text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>

        <div class="overflow-y-auto flex-1 grid grid-cols-2 sm:grid-cols-3 gap-3 p-1">
            <?php foreach ($media as $m): ?>
            <div class="group relative rounded-lg border border-border overflow-hidden bg-muted transition-all hover:border-[#2B6CB0]">
                <img src="<?= esc($m['image']) ?>" alt="" class="h-28 w-full object-cover group-hover:scale-105 transition-transform duration-150">
                <div class="p-2 bg-background">
                    <p class="text-xs font-bold text-foreground truncate"><?= esc($m['title']) ?></p>
                    <p class="text-[10px] text-muted-foreground truncate"><?= esc($m['caption']) ?></p>
                </div>
                <div class="absolute inset-0 bg-primary/80 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center gap-2 p-2 transition-opacity">
                    <button type="button" onclick="selectMediaImage('<?= esc($m['image']) ?>')" class="w-full rounded bg-white text-[#2B6CB0] px-2 py-1 text-[10px] font-bold shadow hover:bg-slate-100">
                        Jadikan Cover Utama
                    </button>
                    <button type="button" onclick="insertMediaIntoQuill('<?= esc($m['image']) ?>', '<?= esc(addslashes($m['title'])) ?>')" class="w-full rounded bg-emerald-600 text-white px-2 py-1 text-[10px] font-bold shadow hover:bg-emerald-700">
                        Sisipkan ke Teks Body
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-end pt-3 border-t border-border">
            <button type="button" onclick="closeMediaChooserModal()" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted">Tutup</button>
        </div>
    </div>
</div>

<!-- Modal Fullscreen: Live Frontend Article Preview -->
<div id="articlePreviewModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 1000; align-items: center; justify-content: center; padding: 1.5rem; overflow-y: auto;">
    <div class="surface-card w-full max-w-4xl max-h-[92vh] flex flex-col shadow-2xl rounded-xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Preview Modal Topbar -->
        <div class="flex items-center justify-between px-6 py-3.5 bg-slate-900 text-white border-b border-slate-800 shrink-0">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center rounded bg-red-600 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-white">LIVE PREVIEW</span>
                <span class="text-xs text-slate-300 font-sans">Simulasi Tampilan Portal Pembaca (Frontend Investor.id)</span>
            </div>
            <button type="button" onclick="closeArticlePreview()" class="rounded p-1 text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                <i data-lucide="x" class="size-5"></i>
            </button>
        </div>

        <!-- Preview Article Container (Simulasi styling portal pembaca) -->
        <div class="overflow-y-auto p-6 sm:p-10 bg-white text-slate-900 font-sans space-y-6">
            
            <!-- Category & Upper Deck -->
            <div class="flex flex-wrap items-center gap-2">
                <span id="prevCategory" class="inline-block text-xs font-extrabold uppercase tracking-widest text-[#2B6CB0]">MARKET STOCK</span>
                <span id="prevUpperDeck" class="hidden inline-block rounded bg-amber-500/15 px-2 py-0.5 text-[11px] font-bold text-amber-700 uppercase"></span>
                <span id="prevPremiumBadge" class="hidden inline-flex items-center gap-1 rounded bg-amber-500 px-2 py-0.5 text-[10px] font-bold uppercase text-white shadow-2xs">
                    <i data-lucide="star" class="size-3"></i> PREMIUM
                </span>
            </div>

            <!-- Title -->
            <h1 id="prevTitle" class="text-2xl sm:text-4xl font-extrabold tracking-tight text-slate-950 leading-tight"></h1>

            <!-- Summary Lead -->
            <p id="prevSummary" class="text-base sm:text-lg font-medium text-slate-600 leading-relaxed border-l-4 border-[#2B6CB0] pl-4 italic"></p>

            <!-- Metadata / Byline -->
            <div class="flex flex-wrap items-center justify-between gap-3 py-3 border-y border-slate-200 text-xs text-slate-500">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-800" id="prevAuthor">Raka Herdika</span>
                    <span>&bull;</span>
                    <span id="prevDate">01 Sep 2026 09:00 WIB</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1 text-slate-400"><i data-lucide="share-2" class="size-3.5"></i> Share</span>
                    <span class="flex items-center gap-1 text-slate-400"><i data-lucide="bookmark" class="size-3.5"></i> Simpan</span>
                </div>
            </div>

            <!-- Hero Image Banner -->
            <div class="relative rounded-xl overflow-hidden bg-slate-100 shadow-md">
                <img id="prevImage" src="/img/article1.jpg" alt="Preview Image" class="w-full max-h-[460px] object-cover">
            </div>

            <!-- Body HTML -->
            <div id="prevBody" class="prose prose-slate max-w-none text-base leading-relaxed text-slate-800 pt-2 space-y-4"></div>
        </div>

        <!-- Preview Modal Footer -->
        <div class="flex items-center justify-between px-6 py-3 bg-slate-50 border-t border-border shrink-0">
            <span class="text-xs text-muted-foreground">Tampilan ini mencerminkan hasil akhir artikel ketika dipublish ke pembaca.</span>
            <button type="button" onclick="closeArticlePreview()" class="rounded-md bg-slate-800 px-4 py-1.5 text-xs font-semibold text-white hover:bg-slate-900 transition-colors">
                Tutup Preview
            </button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Quill.js CDN & Flatpickr CDN -->
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="/js/pages/editor.js"></script>
<?= $this->endSection() ?>

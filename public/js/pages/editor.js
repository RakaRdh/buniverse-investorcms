/**
 * Investor.id Newsroom CMS Post Editor Interactive Engine
 */

let quill = null;
let fpPicker = null;
let mediaChooserTarget = 'cover'; // 'cover' or 'quill'

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Quill WYSIWYG Editor with standard editorial toolbar
    if (document.getElementById('quillEditor') && window.Quill) {
        const toolbarOptions = [
            [{ 'header': [1, 2, 3, 4, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            [{ 'align': [] }],
            [{ 'direction': 'rtl' }],
            ['link', 'image', 'video'],
            ['clean']
        ];

        quill = new Quill('#quillEditor', {
            theme: 'snow',
            placeholder: 'Tulis atau paste isi konten berita secara lengkap di sini...',
            modules: {
                toolbar: toolbarOptions
            }
        });

        // Sync initial content if editing
        const hiddenContent = document.getElementById('hiddenContentInput');
        if (hiddenContent && hiddenContent.value.trim()) {
            quill.root.innerHTML = hiddenContent.value;
        }

        // On text change, update hidden input & mark dirty
        quill.on('text-change', () => {
            if (hiddenContent) {
                hiddenContent.value = quill.root.innerHTML;
            }
            markEditorDirty();
        });
    }

    // 2. Initialize Flatpickr Calendar for Scheduled Publish
    const dateInput = document.getElementById('scheduledDateInput');
    if (dateInput && window.flatpickr) {
        fpPicker = flatpickr(dateInput, {
            dateFormat: "d M Y",
            defaultDate: dateInput.value || new Date(),
            onChange: function(selectedDates, dateStr) {
                updateFullPublishDate();
                markEditorDirty();
            }
        });
    }

    // 3. Status Change Listener (Show/Hide Scheduled Date & Time Sliders)
    const statusSelect = document.getElementById('workflowStatusSelect');
    if (statusSelect) {
        statusSelect.addEventListener('change', (e) => {
            toggleScheduledSection(e.target.value === 'Scheduled');
            markEditorDirty();
        });
        toggleScheduledSection(statusSelect.value === 'Scheduled');
    }

    // 4. Form Submit sync guard & dirty state
    const form = document.getElementById('postEditorForm');
    if (form) {
        form.addEventListener('input', () => {
            markEditorDirty();
        });
        form.addEventListener('change', () => {
            markEditorDirty();
        });
        form.addEventListener('submit', (e) => {
            if (isFormSubmitting) {
                e.preventDefault();
                return false;
            }
            isFormSubmitting = true;
            if (quill) {
                const hiddenContent = document.getElementById('hiddenContentInput');
                if (hiddenContent) {
                    hiddenContent.value = quill.root.innerHTML;
                }
            }
            // Disable submit buttons to prevent double submissions
            form.querySelectorAll('button[type="submit"]').forEach(btn => {
                btn.disabled = true;
                btn.classList.add('opacity-70', 'cursor-not-allowed');
            });
        });
    }

    // Clean up any legacy or stale scrape cover cache from localStorage
    try {
        localStorage.removeItem('cms_cover_cache_new');
        Object.keys(localStorage).forEach(k => {
            if (k && k.startsWith('cms_cover_cache_')) {
                localStorage.removeItem(k);
            }
        });
    } catch (e) {}

    // 5. Initialize Cascading Kanal -> Sub-Kanal
    const kanalSelect = document.getElementById('kanalSelect');
    if (kanalSelect && kanalSelect.value) {
        onKanalChange(kanalSelect.value, window.__ARTICLE_CURRENT_SUBKANAL__ || '');
    }

    // 6. Initialize Realtime Character Counters (Judul & Summary)
    initCharCounters();

    // 7. Initialize Cover Image Drag-and-Drop & Auto-Save
    initCoverDropzone();
    initAutoSave();
    initTagAutocomplete();
});

function initCharCounters() {
    const titleInput = document.getElementById('articleTitleInput');
    const titleCount = document.getElementById('titleCharCount');
    const summaryInput = document.getElementById('articleSummaryInput');
    const summaryCount = document.getElementById('summaryCharCount');
    const summaryHint = document.getElementById('summaryCharHint');

    function updateTitleCount() {
        if (!titleInput || !titleCount) return;
        const len = titleInput.value.length;
        titleCount.textContent = len;
        if (len >= 70) {
            titleCount.classList.add('text-destructive', 'font-bold');
        } else {
            titleCount.classList.remove('text-destructive', 'font-bold');
        }
    }

    function updateSummaryCount() {
        if (!summaryInput || !summaryCount) return;
        const len = summaryInput.value.length;
        summaryCount.textContent = len;

        if (summaryHint) {
            if (len === 0) {
                summaryHint.textContent = '(Min. 110 char)';
                summaryHint.className = 'text-[10px] font-sans text-amber-500';
            } else if (len < 110) {
                summaryHint.textContent = `(Kurang ${110 - len} char)`;
                summaryHint.className = 'text-[10px] font-sans text-amber-500 font-semibold';
                summaryCount.classList.remove('text-success');
            } else if (len <= 160) {
                summaryHint.textContent = '✓ Pas (110–160 char)';
                summaryHint.className = 'text-[10px] font-sans text-success font-semibold';
                summaryCount.classList.add('text-success');
                summaryCount.classList.remove('text-destructive');
            } else {
                summaryHint.textContent = '(Maksimal 160 char)';
                summaryHint.className = 'text-[10px] font-sans text-destructive font-semibold';
                summaryCount.classList.add('text-destructive');
            }
        }
    }

    if (titleInput) {
        titleInput.addEventListener('input', updateTitleCount);
        updateTitleCount();
    }

    if (summaryInput) {
        summaryInput.addEventListener('input', updateSummaryCount);
        updateSummaryCount();
    }
}

// Toggle Scheduled Box
function toggleScheduledSection(show) {
    const scheduledBox = document.getElementById('scheduledSettingBox');
    if (scheduledBox) {
        scheduledBox.style.display = show ? 'block' : 'none';
    }
}

// Sliders Handler: Update Hour & Minute with 2-Way Sync & Number Validation
function onHourSliderChange(val) {
    let num = parseInt(val, 10) || 0;
    if (num < 0) num = 0;
    if (num > 23) num = 23;
    
    const numInput = document.getElementById('hourInputNumber');
    if (numInput) numInput.value = num;
    updateFullPublishDate();
}

function onHourInputChange(val) {
    let num = parseInt(val, 10);
    if (isNaN(num) || num < 0) num = 0;
    if (num > 23) num = 23;

    const numInput = document.getElementById('hourInputNumber');
    const slider = document.getElementById('hourSlider');
    if (numInput) numInput.value = num;
    if (slider) slider.value = num;
    updateFullPublishDate();
}

function onMinuteSliderChange(val) {
    let num = parseInt(val, 10) || 0;
    if (num < 0) num = 0;
    if (num > 59) num = 59;

    const numInput = document.getElementById('minuteInputNumber');
    if (numInput) numInput.value = num;
    updateFullPublishDate();
}

function onMinuteInputChange(val) {
    let num = parseInt(val, 10);
    if (isNaN(num) || num < 0) num = 0;
    if (num > 59) num = 59;

    const numInput = document.getElementById('minuteInputNumber');
    const slider = document.getElementById('minuteSlider');
    if (numInput) numInput.value = num;
    if (slider) slider.value = num;
    updateFullPublishDate();
}

function updateFullPublishDate() {
    const dateInput = document.getElementById('scheduledDateInput');
    const hourInput = document.getElementById('hourInputNumber');
    const minuteInput = document.getElementById('minuteInputNumber');
    const fullPublishInput = document.getElementById('publishDateHiddenInput');
    const displayLabel = document.getElementById('publishDateDisplay');

    const dateStr = dateInput ? dateInput.value : '';
    const hourVal = hourInput ? String(hourInput.value).padStart(2, '0') : '09';
    const minVal = minuteInput ? String(minuteInput.value).padStart(2, '0') : '00';

    const fullStr = `${dateStr} ${hourVal}:${minVal}`;
    if (fullPublishInput) fullPublishInput.value = fullStr;
    if (displayLabel) displayLabel.textContent = fullStr;
}

// 6. Cascading Kanal -> Sub-Kanal Multi-Select Checkbox Dropdown Handler
function onKanalChange(kanalName, preselectSub = '') {
    const btn = document.getElementById('subKanalDropdownBtn');
    const label = document.getElementById('subKanalSelectedLabel');
    const menu = document.getElementById('subKanalMenu');
    const optionsList = document.getElementById('subKanalOptionsList');

    if (!btn || !label || !optionsList) return;

    // Reset sub-kanal menu
    if (menu) menu.classList.add('hidden');
    const arrow = document.getElementById('subKanalDropdownArrow');
    if (arrow) arrow.classList.remove('rotate-180');

    if (!kanalName) {
        btn.disabled = true;
        btn.classList.add('bg-muted/60', 'text-muted-foreground', 'cursor-not-allowed');
        btn.classList.remove('bg-background', 'text-foreground', 'cursor-pointer', 'border-input');
        label.textContent = '-- Pilih Master Kanal Dulu --';
        optionsList.innerHTML = '';
        return;
    }

    const kanals = window.__KANALS_DATA__ || [];
    const found = kanals.find(k => k.name.toLowerCase() === kanalName.toLowerCase());

    if (found && Array.isArray(found.sub_kanals) && found.sub_kanals.length > 0) {
        btn.disabled = false;
        btn.classList.remove('bg-muted/60', 'text-muted-foreground', 'cursor-not-allowed');
        btn.classList.add('bg-background', 'text-foreground', 'cursor-pointer', 'border-input');

        // Parse preselected items (can be comma-separated or single string)
        const preselectedArr = preselectSub ? preselectSub.split(',').map(s => s.trim().toLowerCase()) : [];

        let html = '';
        found.sub_kanals.forEach(sub => {
            const isChecked = preselectedArr.includes(sub.name.toLowerCase()) ? 'checked' : '';
            html += `
                <label class="flex items-center gap-2 p-1.5 rounded hover:bg-muted/60 cursor-pointer text-xs select-none">
                    <input type="checkbox" name="sub_kanal[]" value="${sub.name}" ${isChecked} onchange="updateSubKanalLabel()" class="rounded border-input text-primary focus:ring-primary size-3.5">
                    <span class="text-foreground font-medium">${sub.name}</span>
                </label>
            `;
        });
        optionsList.innerHTML = html;
        updateSubKanalLabel();
    } else {
        btn.disabled = true;
        btn.classList.add('bg-muted/60', 'text-muted-foreground', 'cursor-not-allowed');
        btn.classList.remove('bg-background', 'text-foreground', 'cursor-pointer', 'border-input');
        label.textContent = '(Tidak Ada Sub-Kanal)';
        optionsList.innerHTML = '';
    }
}

function toggleSubKanalMenu() {
    const btn = document.getElementById('subKanalDropdownBtn');
    if (btn && btn.disabled) return;

    const menu = document.getElementById('subKanalMenu');
    const arrow = document.getElementById('subKanalDropdownArrow');
    if (menu) {
        menu.classList.toggle('hidden');
    }
    if (arrow) {
        arrow.classList.toggle('rotate-180');
    }
}

// Close subkanal menu when clicking outside
document.addEventListener('click', (e) => {
    const container = document.getElementById('subKanalContainer');
    const menu = document.getElementById('subKanalMenu');
    const arrow = document.getElementById('subKanalDropdownArrow');
    if (container && menu && !menu.classList.contains('hidden')) {
        if (!container.contains(e.target)) {
            menu.classList.add('hidden');
            if (arrow) arrow.classList.remove('rotate-180');
        }
    }
});

function updateSubKanalLabel() {
    const label = document.getElementById('subKanalSelectedLabel');
    if (!label) return;

    const checkedBoxes = Array.from(document.querySelectorAll('input[name="sub_kanal[]"]:checked'));
    if (checkedBoxes.length === 0) {
        label.textContent = '-- Pilih Sub-Kanal (Bisa > 1) --';
        label.classList.add('text-muted-foreground');
        label.classList.remove('font-bold', 'text-primary');
    } else if (checkedBoxes.length === 1) {
        label.textContent = checkedBoxes[0].value;
        label.classList.remove('text-muted-foreground');
        label.classList.add('font-bold', 'text-primary');
    } else {
        label.textContent = `${checkedBoxes.length} Sub-Kanal Dipilih (${checkedBoxes.map(c => c.value).join(', ')})`;
        label.classList.remove('text-muted-foreground');
        label.classList.add('font-bold', 'text-primary');
    }
}

// 7. Interactive Embed Tools & Modals
function insertEmbedTool(type) {
    if (!quill) return;

    if (type === 'embed_chart') {
        openEmbedChartModal();
        return;
    }

    if (type === 'embed_socmed') {
        openEmbedSocmedModal();
        return;
    }

    if (type === 'stock_chart') {
        openEmbedStockModal();
        return;
    }

    if (type === 'page_break') {
        const range = quill.getSelection(true);
        const index = range ? range.index : quill.getLength();
        const embedHtml = `
            <div class="article-page-break my-6 py-2.5 px-4 rounded-lg bg-amber-500/10 border border-dashed border-amber-500/40 text-center text-xs font-mono font-bold text-amber-700 dark:text-amber-300" data-page-break="true">
                --- HALAMAN BERIKUTNYA (PAGE BREAK) ---
            </div>
            <p><br></p>
        `;
        quill.clipboard.dangerouslyPasteHTML(index, embedHtml);
        quill.setSelection(index + 1);
        markEditorDirty();
        if (typeof showToast === 'function') {
            showToast('Page break (halaman berikutnya) berhasil disisipkan.', 'success');
        }
    }
}

// 7A. Embed Chart Modal Handlers
function openEmbedChartModal() {
    const modal = document.getElementById('embedChartModal');
    if (modal) {
        modal.style.display = 'flex';
        const search = document.getElementById('chartSearchInput');
        if (search) {
            search.value = '';
            filterChartModalList('');
        }
    }
}

function closeEmbedChartModal() {
    const modal = document.getElementById('embedChartModal');
    if (modal) modal.style.display = 'none';
}

function filterChartModalList(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.chart-modal-row');
    rows.forEach(r => {
        const txt = r.getAttribute('data-search') || '';
        r.style.display = (!q || txt.includes(q)) ? '' : 'none';
    });
}

function insertSelectedChart(title, symbol, type, source) {
    if (!quill) return;
    const range = quill.getSelection(true);
    const index = range ? range.index : quill.getLength();
    
    // Output clean user-requested shortcode: ::(keterangan chart)::
    const chartLabel = title ? title.trim() : (symbol ? ('Grafik ' + symbol) : 'Grafik Finansial');
    const embedCode = `::(${chartLabel})::`;

    quill.insertText(index, `\n${embedCode}\n`);
    quill.setSelection(index + embedCode.length + 2);
    markEditorDirty();
    closeEmbedChartModal();
    if (typeof showToast === 'function') {
        showToast(`Chart "${chartLabel}" berhasil disisipkan.`, 'success');
    }
}

// 7B. Embed Socmed / Doc Handlers
function openEmbedSocmedModal() {
    const modal = document.getElementById('embedSocmedModal');
    if (modal) {
        modal.style.display = 'flex';
        const urlInput = document.getElementById('socmedUrlInput');
        if (urlInput) urlInput.value = '';
        const capInput = document.getElementById('socmedCaptionInput');
        if (capInput) capInput.value = '';
    }
}

function closeEmbedSocmedModal() {
    const modal = document.getElementById('embedSocmedModal');
    if (modal) modal.style.display = 'none';
}

function submitSocmedEmbed() {
    if (!quill) return;
    const platform = document.getElementById('socmedPlatformSelect')?.value || 'Social Media';
    const url = document.getElementById('socmedUrlInput')?.value?.trim();
    const caption = document.getElementById('socmedCaptionInput')?.value?.trim();

    if (!url) {
        alert('Silakan masukkan link/URL konten terlebih dahulu.');
        return;
    }

    const range = quill.getSelection(true);
    const index = range ? range.index : quill.getLength();

    const embedHtml = `
        <div class="cms-embed-placeholder my-3 p-3 rounded-lg border border-amber-500/30 bg-amber-500/5 text-xs text-amber-700 dark:text-amber-300 font-sans flex items-center justify-between" data-embed-type="socmed" data-platform="${escapeHtml(platform)}" data-url="${escapeHtml(url)}" data-caption="${escapeHtml(caption)}">
            <div class="flex items-center gap-2 min-w-0 pr-2">
                <span class="rounded bg-amber-500 text-white px-2 py-0.5 text-[10px] font-bold uppercase shrink-0">${escapeHtml(platform)}</span>
                <span class="truncate font-medium">${escapeHtml(caption || url)}</span>
            </div>
            <span class="text-[10px] font-mono text-amber-600/80 shrink-0">::Embed Socmed: ${escapeHtml(platform)}::</span>
        </div>
        <p><br></p>
    `;

    quill.clipboard.dangerouslyPasteHTML(index, embedHtml);
    quill.setSelection(index + 1);
    closeEmbedSocmedModal();
    if (typeof showToast === 'function') {
        showToast(`Embed ${platform} berhasil disisipkan.`, 'success');
    }
}

// 7B2. Embed Google Docs / Sheets Handlers
function openEmbedGoogleDocModal() {
    const modal = document.getElementById('embedGoogleDocModal');
    if (modal) {
        modal.style.display = 'flex';
        const urlInput = document.getElementById('googleDocUrlInput');
        if (urlInput) urlInput.value = '';
        const titleInput = document.getElementById('googleDocTitleInput');
        if (titleInput) titleInput.value = '';
    }
}

function closeEmbedGoogleDocModal() {
    const modal = document.getElementById('embedGoogleDocModal');
    if (modal) modal.style.display = 'none';
}

function submitGoogleDocEmbed() {
    if (!quill) return;
    const docType = document.getElementById('googleDocTypeSelect')?.value || 'Doc';
    const url = document.getElementById('googleDocUrlInput')?.value?.trim();
    const title = document.getElementById('googleDocTitleInput')?.value?.trim() || '';

    if (!url) {
        alert('Silakan masukkan link/URL Google Doc atau Google Sheet terlebih dahulu.');
        return;
    }

    if (!url.includes('docs.google.com')) {
        alert('URL harus berasal dari docs.google.com.');
        return;
    }

    const range = quill.getSelection(true);
    const index = range ? range.index : quill.getLength();

    // Shortcode format: ::Google Doc: URL | Title:: or ::Google Sheet: URL | Title::
    const titlePart = title ? ` | ${title}` : '';
    const shortcode = `::Google ${docType}: ${url}${titlePart}::`;

    quill.insertText(index, `\n${shortcode}\n`);
    quill.setSelection(index + shortcode.length + 2);
    markEditorDirty();
    closeEmbedGoogleDocModal();
    if (typeof showToast === 'function') {
        showToast(`Embed Google ${docType} berhasil disisipkan.`, 'success');
    }
}

// 7C. Stock Chart Interactive TradingView Handlers
let stockSearchDebounceTimer = null;

async function fetchStockSearch(query) {
    const dropdown = document.getElementById('stockAutocompleteDropdown');
    if (!dropdown) return;

    const q = (query || '').trim().toUpperCase();
    if (q.length < 1) {
        dropdown.innerHTML = '';
        dropdown.classList.add('hidden');
        return;
    }

    try {
        const res = await fetch('/api/stocks/search?q=' + encodeURIComponent(q));
        if (!res.ok) return;
        const data = await res.json();
        if (data && data.length > 0) {
            let html = '';
            data.forEach(item => {
                html += `
                    <div class="px-3 py-2 text-xs hover:bg-indigo-500/10 hover:text-indigo-600 cursor-pointer flex items-center justify-between border-b border-border/40 last:border-0 transition-colors" onclick="selectStockTicker('${escapeHtml(item.ticker)}')">
                        <div class="min-w-0 pr-2">
                            <div class="flex items-center gap-1.5">
                                <span class="font-mono font-bold text-foreground text-xs">${escapeHtml(item.ticker)}</span>
                                <span class="text-[10px] rounded bg-indigo-500/15 text-indigo-600 px-1.5 py-0.2 font-mono font-semibold">IDX</span>
                            </div>
                            <div class="text-[11px] text-muted-foreground truncate mt-0.5">${escapeHtml(item.name)}</div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-[10px] font-mono text-muted-foreground bg-muted px-2 py-0.5 rounded">${escapeHtml(item.sector || 'IDX')}</span>
                        </div>
                    </div>
                `;
            });
            dropdown.innerHTML = html;
            dropdown.classList.remove('hidden');
        } else {
            dropdown.innerHTML = `<div class="p-3 text-xs text-muted-foreground italic">Kode emiten "${escapeHtml(q)}" tidak ditemukan di direktori IDX.</div>`;
            dropdown.classList.remove('hidden');
        }
    } catch (err) {
        console.error('Stock search failed', err);
    }
}

function initStockAutocomplete() {
    const input = document.getElementById('stockTickerInput');
    const dropdown = document.getElementById('stockAutocompleteDropdown');
    if (!input || !dropdown) return;

    input.addEventListener('input', (e) => {
        clearTimeout(stockSearchDebounceTimer);
        const val = input.value.trim().toUpperCase();
        if (val.length < 1) {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
            return;
        }
        stockSearchDebounceTimer = setTimeout(() => {
            fetchStockSearch(val);
        }, 150);
    });

    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            dropdown.classList.add('hidden');
        }
    });
}

function selectStockTicker(ticker) {
    const input = document.getElementById('stockTickerInput');
    const dropdown = document.getElementById('stockAutocompleteDropdown');
    if (input) {
        input.value = ticker.toUpperCase();
        input.focus();
    }
    if (dropdown) dropdown.classList.add('hidden');
}

function setQuickStockTicker(ticker) {
    const input = document.getElementById('stockTickerInput');
    const dropdown = document.getElementById('stockAutocompleteDropdown');
    if (input) {
        input.value = ticker.toUpperCase();
    }
    if (dropdown) dropdown.classList.add('hidden');
}

function openEmbedStockModal() {
    const modal = document.getElementById('embedStockModal');
    if (modal) {
        modal.style.display = 'flex';
        const input = document.getElementById('stockTickerInput');
        const dropdown = document.getElementById('stockAutocompleteDropdown');
        if (dropdown) {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
        }
        if (input) {
            input.focus();
        }
    }
}

function closeEmbedStockModal() {
    const modal = document.getElementById('embedStockModal');
    const dropdown = document.getElementById('stockAutocompleteDropdown');
    if (modal) modal.style.display = 'none';
    if (dropdown) dropdown.classList.add('hidden');
}

function submitStockEmbed() {
    if (!quill) return;
    const ticker = (document.getElementById('stockTickerInput')?.value || '').toUpperCase().trim();
    const styleType = document.getElementById('stockChartTypeSelect')?.value || 'tradingview_full';

    if (!ticker) {
        alert('Silakan masukkan ticker saham.');
        return;
    }

    const range = quill.getSelection(true);
    const index = range ? range.index : quill.getLength();

    const embedHtml = `
        <div class="cms-embed-placeholder my-3 p-3 rounded-lg border border-indigo-500/30 bg-indigo-500/5 text-xs text-indigo-700 dark:text-indigo-300 font-sans flex items-center justify-between" data-embed-type="stock_chart" data-symbol="${escapeHtml(ticker)}" data-style="${escapeHtml(styleType)}">
            <div class="flex items-center gap-2">
                <span class="rounded bg-indigo-600 text-white px-2 py-0.5 text-[10px] font-bold font-mono">IDX:${escapeHtml(ticker)}</span>
                <span class="font-semibold">Live Stock Chart Widget</span>
            </div>
            <span class="text-[10px] font-mono text-indigo-500/80">::Stock Chart: ${escapeHtml(ticker)}::</span>
        </div>
        <p><br></p>
    `;

    quill.clipboard.dangerouslyPasteHTML(index, embedHtml);
    quill.setSelection(index + 1);
    closeEmbedStockModal();
    if (typeof showToast === 'function') {
        showToast(`Placeholder Stock Chart "${ticker}" berhasil disisipkan.`, 'success');
    }
}

// 8. Media Library Chooser Modal
function openMediaChooserModal(target = 'cover') {
    mediaChooserTarget = target;
    const modal = document.getElementById('mediaChooserModal');
    if (modal) {
        modal.style.display = 'flex';
        // Reset search filter on open
        const searchInput = document.getElementById('mediaModalSearchInput');
        if (searchInput) {
            searchInput.value = '';
            filterMediaModalCards('');
        }
    }
}

function closeMediaChooserModal() {
    const modal = document.getElementById('mediaChooserModal');
    if (modal) modal.style.display = 'none';
}

function filterMediaModalCards(query) {
    const q = (query || '').toLowerCase().trim();
    const items = document.querySelectorAll('.media-card-item');
    let visibleCount = 0;
    items.forEach(item => {
        const text = item.getAttribute('data-search') || '';
        if (!q || text.includes(q)) {
            item.style.display = '';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    const countEl = document.getElementById('mediaModalCount');
    if (countEl) countEl.textContent = visibleCount;
}

function selectMediaImage(url, caption = '') {
    if (mediaChooserTarget === 'quill') {
        if (quill) {
            const range = quill.getSelection(true);
            const index = range ? range.index : quill.getLength();
            const capText = (caption && caption.trim()) ? caption.trim() : 'Dokumentasi Investor.id / Riset';
            const html = `
                <p class="ql-align-center my-3"><img src="${url}" class="rounded-lg max-w-full h-auto mx-auto shadow-sm" onerror="this.onerror=null;this.src='/img/investor_placeholder.jpg';"></p>
                <p class="ql-align-center" style="margin-top: -4px;"><em style="font-size: 11px; color: #64748b; font-style: italic; display: inline-block;">${escapeHtml(capText)}</em></p>
                <p><br></p>
            `;
            quill.clipboard.dangerouslyPasteHTML(index, html);
            quill.setSelection(index + 2);
            markEditorDirty();
        }
    } else {
        const input = document.getElementById('thumbnailInput');
        const preview = document.getElementById('imagePreview');
        if (input) input.value = url;
        if (preview) preview.src = url;
        markEditorDirty();
    }

    closeMediaChooserModal();
    if (typeof showToast === 'function') {
        showToast('Gambar berhasil disisipkan ke artikel.', 'success');
    }
}

// 9. Local File Upload Handler
function handleLocalImageUpload(inputEl) {
    if (inputEl.files && inputEl.files[0]) {
        const file = inputEl.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const dataUrl = e.target.result;
            const thumbInput = document.getElementById('thumbnailInput');
            const preview = document.getElementById('imagePreview');
            if (thumbInput) thumbInput.value = dataUrl;
            if (preview) preview.src = dataUrl;

            if (typeof showToast === 'function') {
                showToast(`Foto lokal "${file.name}" berhasil dimuat sebagai Featured Image.`, 'success');
            }
        };
        reader.readAsDataURL(file);
    }
}

// 10. Modal "Add Article Link"
function openArticleLinkModal() {
    const modal = document.getElementById('articleLinkModal');
    if (modal) modal.style.display = 'flex';
}

function closeArticleLinkModal() {
    const modal = document.getElementById('articleLinkModal');
    if (modal) modal.style.display = 'none';
}

function filterArticleModalByTag(tag) {
    document.querySelectorAll('.modal-tag-chip').forEach(btn => {
        btn.classList.remove('bg-primary/10', 'text-primary', 'border-primary/30', 'font-semibold');
        btn.classList.add('bg-muted', 'text-muted-foreground');
    });

    event.target.classList.remove('bg-muted', 'text-muted-foreground');
    event.target.classList.add('bg-primary/10', 'text-primary', 'border-primary/30', 'font-semibold');

    const rows = document.querySelectorAll('.article-modal-row');
    rows.forEach(row => {
        if (tag === 'all') {
            row.style.display = 'flex';
        } else {
            const tags = row.getAttribute('data-tags') || '';
            const kanal = row.getAttribute('data-kanal') || '';
            const title = row.getAttribute('data-title') || '';
            if (tags.includes(tag.toLowerCase()) || kanal.includes(tag.toLowerCase()) || title.includes(tag.toLowerCase())) {
                row.style.display = 'flex';
            } else {
                row.style.display = 'none';
            }
        }
    });
}

function filterArticleModalList() {
    const query = document.getElementById('articleModalSearch').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.article-modal-row');
    rows.forEach(row => {
        const title = row.getAttribute('data-title') || '';
        const tags = row.getAttribute('data-tags') || '';
        if (title.includes(query) || tags.includes(query)) {
            row.style.display = 'flex';
        } else {
            row.style.display = 'none';
        }
    });
}

function insertSelectedArticleLink(title, url, summary) {
    if (quill) {
        const range = quill.getSelection(true);
        const index = range ? range.index : quill.getLength();
        const html = `<blockquote><p><strong>Baca Juga: </strong><a href="${url}" target="_blank">${title}</a></p></blockquote><p><br></p>`;
        quill.clipboard.dangerouslyPasteHTML(index, html);
        quill.setSelection(index + 1);
    }
    closeArticleLinkModal();
    if (typeof showToast === 'function') {
        showToast('Link artikel "Baca Juga" berhasil disisipkan.', 'success');
    }
}

// 10B. Sumber Berita & Poin Utama Handlers
function handleSourceChange(val) {
    const customInput = document.getElementById('customSourceInput');
    if (!customInput) return;
    if (val === 'Lainnya') {
        customInput.classList.remove('hidden');
        customInput.focus();
    } else {
        customInput.classList.add('hidden');
        customInput.value = val;
    }
}

function addPointRow(val = '') {
    const container = document.getElementById('pointsListContainer');
    if (!container) return;
    const count = container.querySelectorAll('.point-row').length + 1;
    const row = document.createElement('div');
    row.className = 'point-row flex items-center gap-2';
    row.innerHTML = `
        <span class="point-number grid size-6 shrink-0 place-items-center rounded-full bg-primary/20 text-primary text-[10px] font-bold">${count}</span>
        <input type="text" name="points[]" value="${escapeHtml(val)}" class="point-input flex h-8 flex-1 rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Poin penting artikel...">
        <button type="button" onclick="removePointRow(this)" class="point-remove-btn grid size-8 shrink-0 place-items-center rounded-md text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors" title="Hapus poin ini">
            <i data-lucide="trash-2" class="size-3.5"></i>
        </button>
    `;
    container.appendChild(row);
    if (window.lucide) lucide.createIcons();
    reindexPoints();
}

function removePointRow(btn) {
    const container = document.getElementById('pointsListContainer');
    if (!container) return;
    const rows = container.querySelectorAll('.point-row');
    if (rows.length <= 1) {
        if (typeof showToast === 'function') {
            showToast('Minimal harus ada 1 poin utama artikel.', 'warning');
        }
        return;
    }
    const row = btn.closest('.point-row');
    if (row) {
        row.remove();
        reindexPoints();
    }
}

function reindexPoints() {
    const container = document.getElementById('pointsListContainer');
    if (!container) return;
    const rows = container.querySelectorAll('.point-row');
    rows.forEach((r, idx) => {
        const num = r.querySelector('.point-number');
        if (num) num.textContent = (idx + 1);
        const input = r.querySelector('.point-input');
        if (input) input.placeholder = `Poin penting ke-${idx + 1} artikel...`;
    });
}

// 11. Live AI Assistance via Gemini API Backend
async function handleAiAssistance(field) {
    let bodyText = '';
    if (quill) {
        bodyText = quill.getText().trim();
    }
    if (!bodyText) {
        const titleVal = document.getElementById('articleTitleInput')?.value;
        bodyText = document.getElementById('hiddenContentInput')?.value || titleVal || '';
    }

    if (!bodyText || bodyText.length < 5) {
        if (typeof showToast === 'function') {
            showToast('Tuliskan naskah atau isi berita terlebih dahulu di editor untuk diproses AI.', 'warning');
        }
        return;
    }

    // Capture triggering buttons for inline loading indicator
    const btnList = document.querySelectorAll(`button[onclick*="handleAiAssistance('${field}')"]`);
    const originalBtnStates = [];
    btnList.forEach(b => {
        originalBtnStates.push({ btn: b, html: b.innerHTML, disabled: b.disabled });
        b.disabled = true;
        b.innerHTML = `<span class="inline-block animate-spin mr-1"><svg class="size-3 inline-block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/></svg></span><span>MEMPROSES...</span>`;
    });

    const aiMessages = {
        'judul': 'Sedang menghasilkan judul berita via Gemini AI...',
        'summary': 'Sedang menghasilkan ringkasan berita via Gemini AI...',
        'tags': 'Sedang menghasilkan tag kata kunci via Gemini AI...',
        'points': 'Sedang menghasilkan poin penting via Gemini AI...'
    };

    if (typeof showAiLoading === 'function') {
        showAiLoading(aiMessages[field] || 'Sedang memproses AI Assistant...');
    }

    try {
        if (field === 'judul') {
            const res = await fetch('/api/ai/title', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ body: bodyText })
            });
            const json = await res.json();
            if (json.status === 'success') {
                const titleInput = document.getElementById('articleTitleInput');
                if (titleInput) {
                    let cleanTitle = (json.data.title || '').replace(/^["'“”«»]+|["'“”«»]+$/g, '').trim();
                    if (cleanTitle.length > 70) {
                        cleanTitle = cleanTitle.substring(0, 70).trim();
                    }
                    titleInput.value = cleanTitle;
                    titleInput.dispatchEvent(new Event('input'));
                }
                if (typeof showToast === 'function') {
                    showToast('Judul berhasil di-generate via Gemini AI (' + (json.data.model || 'Flash') + ')!', 'success');
                }
            } else {
                throw new Error(json.data || 'Gagal generate judul');
            }
        } else if (field === 'summary') {
            const titleVal = document.getElementById('articleTitleInput')?.value || '';
            const res = await fetch('/api/ai/summary', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title: titleVal, body: bodyText })
            });
            const json = await res.json();
            if (json.status === 'success') {
                const summaryInput = document.getElementById('articleSummaryInput');
                if (summaryInput) {
                    let cleanSummary = (json.data.summary || '').replace(/^["'“”«»]+|["'“”«»]+$/g, '').trim();
                    if (cleanSummary.length > 160) {
                        cleanSummary = cleanSummary.substring(0, 160).trim();
                    }
                    summaryInput.value = cleanSummary;
                    summaryInput.dispatchEvent(new Event('input'));
                }
                if (typeof showToast === 'function') {
                    showToast('Ringkasan berhasil di-generate via Gemini AI!', 'success');
                }
            } else {
                throw new Error(json.data || 'Gagal generate summary');
            }
        } else if (field === 'tags') {
            const titleVal = document.getElementById('articleTitleInput')?.value || '';
            const articleIdInput = document.querySelector('input[name="id"]') || document.querySelector('input[name="article_id"]');
            const res = await fetch('/api/ai/tags', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    title: titleVal,
                    body: bodyText,
                    article_id: articleIdInput ? articleIdInput.value : null
                })
            });
            const json = await res.json();
            if (json.status === 'success') {
                if (Array.isArray(json.data.tags)) {
                    json.data.tags.forEach(t => {
                        const clean = t.replace(/^#/, '').trim();
                        if (clean && !chosenTags.includes(clean)) chosenTags.push(clean);
                    });
                    syncTagsToInput();
                    renderTags();
                    markEditorDirty();
                }
                if (typeof showToast === 'function') {
                    const matchedCount = (json.data.matched_from_db || []).length;
                    const newCount = (json.data.new_generated || []).length;
                    let msg = 'Tag kata kunci berhasil di-generate!';
                    if (matchedCount > 0 && newCount > 0) {
                        msg = `Tag diselaraskan: ${matchedCount} dari DB, ${newCount} baru via AI!`;
                    } else if (matchedCount > 0 && newCount === 0) {
                        msg = `Semua ${matchedCount} tag berhasil dicocokkan dari database!`;
                    } else if (newCount > 0) {
                        msg = `${newCount} tag baru berhasil di-generate via Gemini AI!`;
                    }
                    showToast(msg, 'success');
                }
            } else {
                throw new Error(json.data || 'Gagal generate tags');
            }
        } else if (field === 'points') {
            const res = await fetch('/api/ai/points', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ body: bodyText })
            });
            const json = await res.json();
            if (json.status === 'success') {
                const lines = (json.data.points || '').split('\n')
                    .map(l => l.replace(/^[•\-\*\d\.\s]+/, '').trim())
                    .filter(l => l.length > 0);
                const container = document.getElementById('pointsListContainer');
                if (container && lines.length > 0) {
                    container.innerHTML = '';
                    lines.forEach(line => addPointRow(line));
                }
                if (typeof showToast === 'function') {
                    showToast(`${lines.length} Poin penting berhasil dirangkum!`, 'success');
                }
            } else {
                throw new Error(json.data || 'Gagal generate points');
            }
        }
    } catch (err) {
        if (typeof showAiErrorAlert === 'function') {
            showAiErrorAlert(err.message);
        } else if (typeof showToast === 'function') {
            showToast('AI Error: ' + err.message, 'error');
        }
    } finally {
        originalBtnStates.forEach(s => {
            s.btn.disabled = s.disabled;
            s.btn.innerHTML = s.html;
        });
        if (window.lucide) lucide.createIcons();
        if (typeof hideAiLoading === 'function') {
            hideAiLoading();
        }
    }
}

// 12. Live Scrape Article from External URL (e.g. Antara News)
async function executeScrapeArticle() {
    const urlInput = document.getElementById('scrapeUrlInput');
    const url = urlInput ? urlInput.value.trim() : '';

    if (!url) {
        if (typeof showToast === 'function') {
            showToast('Silakan masukkan link artikel berita eksternal yang ingin di-scrape.', 'warning');
        }
        if (urlInput) urlInput.focus();
        return;
    }

    if (typeof showAiLoading === 'function') {
        showAiLoading('Sedang mengekstrak konten artikel dari URL via AI...');
    }

    try {
        const res = await fetch('/api/ai/scrape', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ url: url })
        });

        const json = await res.json();

        if (json.status === 'success') {
            const data = json.data;

            // 1. Insert content to Quill editor
            if (quill && data.body) {
                quill.clipboard.dangerouslyPasteHTML(data.body);
                const hiddenContent = document.getElementById('hiddenContentInput');
                if (hiddenContent) {
                    hiddenContent.value = quill.root.innerHTML;
                }
            }

            // 2. Set thumbnail / cover image & persist cache
            if (data.thumbnail_url) {
                const coverInput = document.getElementById('thumbnailInput');
                const coverPreview = document.getElementById('imagePreview');

                if (coverInput) coverInput.value = data.thumbnail_url;
                if (coverPreview) {
                    coverPreview.src = data.thumbnail_url;
                }

                markEditorDirty();
            }

            // 3. Set source if input exists
            const sourceInput = document.querySelector('input[name="source"]');
            if (sourceInput && !sourceInput.value) {
                sourceInput.value = 'Antara News / Rilis Pers';
            }

            if (typeof showToast === 'function') {
                showToast('Artikel berhasil di-scrape! Isi naskah dan cover telah diisi ke editor.', 'success');
            }
        } else {
            throw new Error(json.data || 'Gagal melakukan scraping artikel');
        }
    } catch (err) {
        if (typeof showAiErrorAlert === 'function') {
            showAiErrorAlert(err.message);
        } else if (typeof showToast === 'function') {
            showToast('Scrape Gagal: ' + err.message, 'error');
        }
    } finally {
        if (typeof hideAiLoading === 'function') {
            hideAiLoading();
        }
    }
}

// 12. Preview Article Page (Open in New Tab or Submits Temp POST)
function previewArticlePage() {
    // Collect all form data and submit to /post/preview in new tab
    const form = document.getElementById('postEditorForm');
    if (!form) return;

    // Sync content
    if (quill) {
        const hiddenContent = document.getElementById('hiddenContentInput');
        if (hiddenContent) hiddenContent.value = quill.root.innerHTML;
    }

    const tempForm = document.createElement('form');
    tempForm.method = 'POST';
    tempForm.action = '/post/preview';
    tempForm.target = '_blank';
    tempForm.style.display = 'none';

    // Copy all input elements
    const formData = new FormData(form);
    for (const [key, value] of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        tempForm.appendChild(input);
    }

    document.body.appendChild(tempForm);
    tempForm.submit();
    setTimeout(() => tempForm.remove(), 500);
}

// 13. Tag Autocomplete & Tag Badges Manager (Matching PortalBerita)
let chosenTags = [];
let tagSearchDebounceTimer = null;

function initTagAutocomplete() {
    const hiddenInput = document.getElementById('articleTagsInput');
    const tagInput = document.getElementById('artTagInput');
    const dropdown = document.getElementById('tag-suggestions-dropdown');

    if (!hiddenInput) return;

    // Initialize chosenTags from hidden input value
    if (hiddenInput.value && hiddenInput.value.trim()) {
        chosenTags = hiddenInput.value
            .split(',')
            .map(t => t.replace(/^#/, '').trim())
            .filter(Boolean);
    }
    renderTags();

    if (!tagInput || !dropdown) return;

    // Debounced search on input
    tagInput.addEventListener('input', (e) => {
        clearTimeout(tagSearchDebounceTimer);
        const rawVal = e.target.value;

        // If comma entered, commit immediately
        if (rawVal.includes(',')) {
            const clean = rawVal.replace(/,/g, '').trim();
            if (clean) addTag(clean);
            tagInput.value = '';
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
            return;
        }

        const q = rawVal.trim();
        if (q.length < 1) {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
            return;
        }

        tagSearchDebounceTimer = setTimeout(async () => {
            try {
                const res = await fetch('/api/tags/search?q=' + encodeURIComponent(q));
                if (!res.ok) return;
                const data = await res.json();
                dropdown.innerHTML = '';

                if (data && data.length > 0) {
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = "px-3 py-2 text-xs hover:bg-primary/10 hover:text-primary cursor-pointer flex items-center justify-between transition-colors";
                        div.innerHTML = `
                            <span class="font-semibold text-foreground">${escapeHtml(item.label)}</span>
                            <span class="text-[10px] text-muted-foreground font-mono">#${escapeHtml(item.slug || '')}</span>
                        `;
                        div.onclick = () => addTag(item.label);
                        dropdown.appendChild(div);
                    });
                }

                // Add write-in item suggestion matching PortalBerita
                const freeForm = document.createElement('div');
                freeForm.className = "px-3 py-2 bg-muted/40 hover:bg-primary/10 hover:text-primary cursor-pointer font-bold text-primary text-xs flex items-center gap-1.5 border-t border-border/40 transition-colors";
                freeForm.innerHTML = `<i data-lucide="plus" class="size-3"></i><span>Buat tag baru: "${escapeHtml(q)}"</span>`;
                freeForm.onclick = () => addTag(q);
                dropdown.appendChild(freeForm);
                if (window.lucide) lucide.createIcons();

                dropdown.classList.remove('hidden');
            } catch (err) {
                console.error('Tag search failed', err);
            }
        }, 200);
    });

    // Keydown navigation: Enter adds tag, Backspace on empty removes last tag
    tagInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = tagInput.value.replace(/,/g, '').trim();
            if (val) addTag(val);
        } else if (e.key === 'Backspace' && tagInput.value === '' && chosenTags.length > 0) {
            removeTag(chosenTags.length - 1);
        }
    });

    // Close dropdown on click outside
    document.addEventListener('click', (e) => {
        if (!tagInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
}

function renderTags() {
    const container = document.getElementById('tags-badge-container');
    if (!container) return;
    container.innerHTML = '';

    chosenTags.forEach((tag, idx) => {
        const badge = document.createElement('span');
        badge.className = "inline-flex items-center gap-1.5 px-2.5 py-1 bg-primary/10 border border-primary/25 text-primary rounded-md font-semibold text-xs animate-in fade-in zoom-in-95 duration-150 select-none";
        badge.innerHTML = `
            <span>#${escapeHtml(tag)}</span>
            <button type="button" onclick="removeTag(${idx})" class="text-primary/70 hover:text-primary hover:scale-125 transition-transform font-black text-sm leading-none ml-0.5">&times;</button>
        `;
        container.appendChild(badge);
    });
}

function addTag(tag) {
    tag = tag.replace(/^#/, '').trim();
    if (tag && !chosenTags.includes(tag)) {
        chosenTags.push(tag);
        syncTagsToInput();
        renderTags();
        markEditorDirty();
    }
    const input = document.getElementById('artTagInput');
    const dropdown = document.getElementById('tag-suggestions-dropdown');
    if (input) input.value = '';
    if (dropdown) {
        dropdown.classList.add('hidden');
        dropdown.innerHTML = '';
    }
}

function removeTag(idx) {
    if (idx >= 0 && idx < chosenTags.length) {
        chosenTags.splice(idx, 1);
        syncTagsToInput();
        renderTags();
        markEditorDirty();
    }
}

function syncTagsToInput() {
    const hidden = document.getElementById('articleTagsInput');
    if (hidden) {
        hidden.value = chosenTags.join(', ');
    }
}

function escapeHtml(str) {
    return (str || '').replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}

// Auto init on page load
document.addEventListener('DOMContentLoaded', () => {
    initTagAutocomplete();
    initStockAutocomplete();
});

// =========================================================================
// 9. Auto-Save Engine (AJAX draft persistence & unsaved changes guard)
// =========================================================================
let isEditorDirty = false;
let isFormSubmitting = false;
let autoSaveTimer = null;
let lastAutoSavedContent = '';

function markEditorDirty() {
    isEditorDirty = true;
    const dot = document.getElementById('autosaveStatusDot');
    const text = document.getElementById('autosaveStatusText');
    if (dot) dot.className = 'size-2 rounded-full bg-amber-400';
    if (text) text.textContent = 'Perubahan belum disimpan';
}

function initAutoSave() {
    // Check auto-save every 30 seconds
    autoSaveTimer = setInterval(() => {
        performAutoSave();
    }, 30000);

    // Unsaved changes beforeunload protection
    window.addEventListener('beforeunload', (e) => {
        if (isEditorDirty && !isFormSubmitting) {
            e.preventDefault();
            e.returnValue = 'Perubahan artikel belum disimpan. Yakin ingin menutup halaman?';
            return e.returnValue;
        }
    });
}

async function performAutoSave() {
    if (!isEditorDirty || isFormSubmitting) return;

    const titleInput = document.getElementById('articleTitleInput') || document.querySelector('input[name="title"]');
    const title = titleInput ? titleInput.value.trim() : '';
    const content = quill ? quill.root.innerHTML.trim() : '';
    const plainText = quill ? quill.getText().trim() : '';

    // Only auto-save if author has written meaningful content
    if (!title && (!plainText || plainText.length < 5)) return;

    // Check if anything actually changed since last autosave
    const currentFingerprint = title + '||' + content;
    if (currentFingerprint === lastAutoSavedContent) {
        isEditorDirty = false;
        return;
    }

    const idInput = document.getElementById('articleIdInput');
    const summaryInput = document.getElementById('articleSummaryInput');
    const kanalSelect = document.getElementById('kanalSelect');
    const thumbInput = document.getElementById('thumbnailInput');
    const tagsInput = document.getElementById('articleTagsInput');
    const authorInput = document.getElementById('authorInput') || document.getElementById('authorSelect');

    const dot = document.getElementById('autosaveStatusDot');
    const text = document.getElementById('autosaveStatusText');

    if (dot) dot.className = 'size-2 rounded-full bg-blue-500 animate-pulse';
    if (text) text.textContent = 'Menyimpan draft...';

    const payload = {
        id: idInput ? idInput.value : '',
        title: title,
        summary: summaryInput ? summaryInput.value : '',
        content: content,
        thumbnail: thumbInput ? thumbInput.value : '',
        kanal: kanalSelect ? kanalSelect.value : 'Market',
        author: authorInput ? authorInput.value : '',
        tags: tagsInput ? tagsInput.value : ''
    };

    try {
        const response = await fetch('/api/autosave', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (result.success) {
            isEditorDirty = false;
            lastAutoSavedContent = currentFingerprint;
            if (idInput && result.id) {
                idInput.value = result.id;
                const formEl = document.getElementById('postEditorForm');
                if (formEl && formEl.action.includes('/post/save')) {
                    formEl.action = '/post/update/' + result.id;
                }
            }
            if (dot) dot.className = 'size-2 rounded-full bg-emerald-500';
            if (text) text.textContent = `Tersimpan otomatis ${result.saved_at}`;
        } else {
            if (dot) dot.className = 'size-2 rounded-full bg-red-500';
            if (text) text.textContent = 'Gagal menyimpan draft';
        }
    } catch (err) {
        console.error('AutoSave error:', err);
        if (dot) dot.className = 'size-2 rounded-full bg-red-500';
        if (text) text.textContent = 'Auto-save offline';
    }
}

// =========================================================================
// 10. Author Combobox / Autosuggest Engine
// =========================================================================
function openAuthorDropdown() {
    const dd = document.getElementById('authorSuggestionsDropdown');
    const arrow = document.getElementById('authorDropdownArrow');
    if (dd) dd.classList.remove('hidden');
    if (arrow) arrow.classList.add('rotate-180');
}

function closeAuthorDropdown() {
    const dd = document.getElementById('authorSuggestionsDropdown');
    const arrow = document.getElementById('authorDropdownArrow');
    if (dd) dd.classList.add('hidden');
    if (arrow) arrow.classList.remove('rotate-180');
}

function toggleAuthorDropdown() {
    const dd = document.getElementById('authorSuggestionsDropdown');
    if (dd && dd.classList.contains('hidden')) {
        openAuthorDropdown();
        const input = document.getElementById('authorInput');
        if (input) input.focus();
    } else {
        closeAuthorDropdown();
    }
}

function filterAuthorDropdown(val) {
    const q = (val || '').toLowerCase().trim();
    const items = document.querySelectorAll('.author-option-item');
    let hasMatch = false;

    items.forEach(item => {
        const name = (item.getAttribute('data-author') || '').toLowerCase();
        if (!q || name.includes(q)) {
            item.classList.remove('hidden');
            hasMatch = true;
        } else {
            item.classList.add('hidden');
        }
    });

    const noResult = document.getElementById('authorNoResults');
    if (noResult) {
        noResult.classList.toggle('hidden', hasMatch);
    }
    openAuthorDropdown();
    markEditorDirty();
}

function selectAuthorName(name) {
    const input = document.getElementById('authorInput');
    if (input) {
        const currentAuthors = input.value.split(',').map(a => a.trim()).filter(a => a.length > 0);
        const existsIndex = currentAuthors.findIndex(a => a.toLowerCase() === name.toLowerCase());

        if (existsIndex >= 0) {
            // If already selected, toggle off (unless it's the only one)
            if (currentAuthors.length > 1) {
                currentAuthors.splice(existsIndex, 1);
            }
        } else {
            // Add as primary or co-author
            currentAuthors.push(name);
        }

        input.value = currentAuthors.join(', ');
        markEditorDirty();
    }

    updateAuthorDropdownChecks();
}

function updateAuthorDropdownChecks() {
    const input = document.getElementById('authorInput');
    const selected = (input?.value || '').split(',').map(a => a.trim().toLowerCase()).filter(Boolean);
    const items = document.querySelectorAll('.author-option-item');
    items.forEach(item => {
        const icon = item.querySelector('.author-check-icon');
        const itemAuthor = (item.getAttribute('data-author') || '').toLowerCase();
        if (icon) {
            icon.classList.toggle('opacity-0', !selected.includes(itemAuthor));
        }
    });
}

// Click outside author dropdown to dismiss
document.addEventListener('click', (e) => {
    const container = document.getElementById('authorInput')?.closest('.relative');
    if (container && !container.contains(e.target)) {
        closeAuthorDropdown();
    }
});

// =========================================================================
// 11. Unsaved Changes Guard Modal
// =========================================================================
let pendingNavigationUrl = null;

function openUnsavedChangesModal(url) {
    pendingNavigationUrl = url;
    const modal = document.getElementById('unsavedChangesModal');
    if (modal) modal.style.display = 'flex';
}

function closeUnsavedChangesModal() {
    pendingNavigationUrl = null;
    const modal = document.getElementById('unsavedChangesModal');
    if (modal) modal.style.display = 'none';
}

// Confirm leave button handler
document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('confirmLeavePageBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            isEditorDirty = false;
            closeUnsavedChangesModal();
            if (pendingNavigationUrl) {
                window.location.href = pendingNavigationUrl;
            }
        });
    }

    // Intercept navigation links across sidebar & topbar if editor is dirty
    document.querySelectorAll('a[href]').forEach(link => {
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || link.target === '_blank') return;

        link.addEventListener('click', (e) => {
            if (isEditorDirty && !isFormSubmitting) {
                e.preventDefault();
                e.stopPropagation();
                openUnsavedChangesModal(href);
            }
        });
    });
});

// =========================================================================
// 12. Cover Image Drag & Drop & Device Upload Engine (ImageProcessor)
// =========================================================================
function initCoverDropzone() {
    const dropzone = document.getElementById('coverDropzone');
    const previewBox = document.getElementById('coverPreviewBox');
    const overlay = document.getElementById('coverDropOverlay');

    const elements = [dropzone, previewBox].filter(Boolean);
    if (!elements.length) return;

    elements.forEach(el => {
        ['dragenter', 'dragover'].forEach(eventName => {
            el.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (overlay) {
                    overlay.classList.remove('hidden');
                    overlay.classList.add('flex');
                }
                if (dropzone) dropzone.classList.add('border-primary');
            }, false);
        });

        ['dragleave'].forEach(eventName => {
            el.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (overlay) {
                    overlay.classList.remove('flex');
                    overlay.classList.add('hidden');
                }
                if (dropzone) dropzone.classList.remove('border-primary');
            }, false);
        });

        el.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (overlay) {
                overlay.classList.remove('flex');
                overlay.classList.add('hidden');
            }
            if (dropzone) dropzone.classList.remove('border-primary');
            const dt = e.dataTransfer;
            const files = dt ? dt.files : null;
            if (files && files.length > 0) {
                uploadCoverFile(files[0]);
            }
        }, false);
    });
}

function handleLocalImageUpload(input) {
    if (input.files && input.files[0]) {
        uploadCoverFile(input.files[0]);
        input.value = ''; // Reset file input so same file can be re-selected if needed
    }
}

async function uploadCoverFile(file) {
    if (!file.type.startsWith('image/')) {
        alert('File harus berupa gambar (JPG, PNG, atau WebP).');
        return;
    }

    const spinner = document.getElementById('coverUploadSpinner');
    if (spinner) {
        spinner.classList.remove('hidden');
        spinner.classList.add('flex');
    }

    const formData = new FormData();
    formData.append('image', file);

    try {
        const res = await fetch('/api/upload-image', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await res.json();
        if (data.success && data.url) {
            const preview = document.getElementById('imagePreview');
            const input = document.getElementById('thumbnailInput');
            if (preview) preview.src = data.url;
            if (input) input.value = data.url;
            markEditorDirty();
            if (typeof showToast === 'function') {
                showToast('Gambar cover berhasil distandarisasi dan diunggah!', 'success');
            }
        } else {
            alert(data.error || 'Gagal memproses gambar.');
        }
    } catch (err) {
        console.error('Upload cover error:', err);
        alert('Terjadi kesalahan jaringan saat mengunggah gambar cover.');
    } finally {
        if (spinner) {
            spinner.classList.remove('flex');
            spinner.classList.add('hidden');
        }
    }
}

// Register all editor modal dialogs with ModalUtils
document.addEventListener('DOMContentLoaded', () => {
    if (window.ModalUtils) {
        window.ModalUtils.register('mediaChooserModal', { onClose: () => closeMediaChooserModal() });
        window.ModalUtils.register('articleLinkModal', { onClose: () => closeArticleLinkModal() });
        window.ModalUtils.register('embedChartModal', { onClose: () => closeEmbedChartModal() });
        window.ModalUtils.register('embedGoogleDocModal', { onClose: () => closeEmbedGoogleDocModal() });
        window.ModalUtils.register('unsavedChangesModal', { onClose: () => closeUnsavedChangesModal() });
        window.ModalUtils.register('embedSocmedModal', { onClose: () => closeEmbedSocmedModal() });
        window.ModalUtils.register('embedStockModal', { onClose: () => closeEmbedStockModal() });
    }
});



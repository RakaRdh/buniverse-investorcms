/**
 * Investor.id Newsroom CMS Post Editor Interactive Engine
 */

let quill = null; // compatibility shim
let ckEditorInstance = null;
let fpPicker = null;
let mediaChooserTarget = 'cover'; // 'cover' or 'ckeditor'

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize CKEditor 4 WYSIWYG Editor (100% Identical Toolbar to Pasted image 20260911143610.png)
    const editorTextarea = document.getElementById('articleEditor');
    if (editorTextarea && window.CKEDITOR) {
        // Inject Dark Mode and Cursor CSS rules directly into CKEditor's iframe styles
        CKEDITOR.addCss(`
            html {
                color-scheme: light;
            }
            html.dark {
                color-scheme: dark;
            }
            body.cke_editable {
                font-family: Verdana, 'Lucida Grande', Tahoma, Arial, sans-serif;
                font-size: 13px;
                line-height: 1.75;
                padding: 20px;
                margin: 0;
                cursor: text;
                caret-color: #0f172a !important;
                transition: background-color 0.2s ease, color 0.2s ease;
            }
            body.cke_editable {
                background-color: #ffffff;
                color: #1e293b;
            }
            html.dark body.cke_editable,
            body.cke_editable.dark,
            body.cke_editable.dark-mode {
                background-color: #0f172a !important;
                color: #f1f5f9 !important;
                caret-color: #38bdf8 !important;
            }
            html.dark body.cke_editable a,
            body.cke_editable.dark-mode a {
                color: #60a5fa !important;
                text-decoration: underline;
            }
            body.cke_editable blockquote {
                background-color: #f0f7ff !important;
                border-left: 4px solid #2B6CB0 !important;
                color: #1e3a8a !important;
                padding: 10px 16px !important;
                margin: 14px 0 !important;
                border-radius: 0 6px 6px 0 !important;
                font-family: inherit !important;
                font-size: 13px !important;
                line-height: 1.6 !important;
                box-shadow: 0 1px 2px rgba(43, 108, 176, 0.05);
            }
            html.dark body.cke_editable blockquote,
            body.cke_editable.dark blockquote,
            body.cke_editable.dark-mode blockquote {
                background-color: rgba(30, 41, 59, 0.75) !important;
                border-left: 4px solid #60a5fa !important;
                color: #cbd5e1 !important;
                padding: 10px 16px !important;
                margin: 14px 0 !important;
                border-radius: 0 6px 6px 0 !important;
            }
            html.dark body.cke_editable table,
            body.cke_editable.dark-mode table {
                border-color: #334155 !important;
            }
            html.dark body.cke_editable th,
            html.dark body.cke_editable td,
            body.cke_editable.dark-mode th,
            body.cke_editable.dark-mode td {
                border-color: #334155 !important;
                color: #f1f5f9 !important;
            }
            html.dark body.cke_editable figcaption,
            body.cke_editable.dark-mode figcaption {
                color: #94a3b8 !important;
                font-size: 11px !important;
                margin-top: 6px;
            }
            html.dark body.cke_editable [style*="color: #333333"],
            html.dark body.cke_editable [style*="color:#333333"],
            html.dark body.cke_editable [style*="color: #000000"],
            html.dark body.cke_editable [style*="color:#000000"],
            html.dark body.cke_editable [style*="color: black"],
            body.cke_editable.dark-mode [style*="color: #333333"],
            body.cke_editable.dark-mode [style*="color:#333333"],
            body.cke_editable.dark-mode [style*="color: #000000"],
            body.cke_editable.dark-mode [style*="color:#000000"],
            body.cke_editable.dark-mode [style*="color: black"] {
                color: #e2e8f0 !important;
            }
        `);

        // Configure CKEditor 4 toolbar matching user screenshot (2 rows clean toolbar)
        ckEditorInstance = CKEDITOR.replace('articleEditor', {
            versionCheck: false,
            height: 520,
            contentsCss: ['/css/newsroom.css', '/css/pages/editor.css'],
            font_defaultLabel: 'Verdana',
            fontSize_defaultLabel: '11px',
            toolbar: [
                { name: 'clipboard', items: ['PasteText', 'PasteFromWord', '-', 'RemoveFormat', '-', 'Undo', 'Redo'] },
                '/',
                { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike'] },
                { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl'] },
                { name: 'links', items: ['Link', 'Unlink'] },
                { name: 'insert', items: ['Table', 'SpecialChar'] },
                { name: 'document', items: ['Source'] }
            ],
            allowedContent: true,
            extraAllowedContent: 'figure(*); figcaption(*); blockquote(*)[*]; div(*)[*]; iframe(*)[*]; span(*)[*]; p(*)[*]; table(*)[*];',
            disallowedContent: 'script; *[on*]; *[style*expression*]; link; meta; object; embed; applet; form; input; button; select;',
            removePlugins: 'exportpdf'
        });

        // Lapis 2: Client-side Sanitizer saat berpindah dari mode Source ke WYSIWYG
        ckEditorInstance.on('mode', function() {
            if (this.mode === 'wysiwyg') {
                let data = this.getData();
                if (data && (/<script\b|onerror=|onload=|onclick=|javascript:/i.test(data))) {
                    // Bersihkan tag script dan inline event
                    data = data.replace(/<script\b[^>]*>.*?<\/script>/gis, '');
                    data = data.replace(/<script\b[^>]*\/?>/gis, '');
                    data = data.replace(/\s*on[a-zA-Z0-9_-]+\s*=\s*(["']).*?\1/gis, '');
                    data = data.replace(/\s*on[a-zA-Z0-9_-]+\s*=\s*[^"'\s>]+/gis, '');
                    data = data.replace(/\b(href|src)\s*=\s*(["'])javascript:[^"']*\2/gis, '$1=$2#$2');
                    this.setData(data);
                }
            }
        });

        // Theme synchronization function for CKEditor 4
        function syncCkEditorTheme() {
            if (!ckEditorInstance) return;
            const isDark = document.documentElement.classList.contains('dark');
            
            if (ckEditorInstance.document && ckEditorInstance.document.$) {
                const iframeDoc = ckEditorInstance.document.$;
                const htmlEl = iframeDoc.documentElement;
                const bodyEl = iframeDoc.body;
                
                if (htmlEl) {
                    if (isDark) {
                        htmlEl.classList.add('dark');
                        htmlEl.style.colorScheme = 'dark';
                    } else {
                        htmlEl.classList.remove('dark');
                        htmlEl.style.colorScheme = 'light';
                    }
                }
                if (bodyEl) {
                    if (isDark) {
                        bodyEl.classList.add('dark', 'dark-mode');
                        bodyEl.style.backgroundColor = '#0f172a';
                        bodyEl.style.color = '#f1f5f9';
                        bodyEl.style.caretColor = '#38bdf8';
                    } else {
                        bodyEl.classList.remove('dark', 'dark-mode');
                        bodyEl.style.backgroundColor = '#ffffff';
                        bodyEl.style.color = '#1e293b';
                        bodyEl.style.caretColor = '#0f172a';
                    }
                }
            }
        }

        function ensureBlockquotesNonEditable() {
            if (!ckEditorInstance || !ckEditorInstance.document || !ckEditorInstance.document.$) return;
            const bqs = ckEditorInstance.document.$.querySelectorAll('blockquote');
            bqs.forEach(bq => {
                if (bq.getAttribute('contenteditable') !== 'false') {
                    bq.setAttribute('contenteditable', 'false');
                }
            });
        }

        ckEditorInstance.on('instanceReady', () => {
            syncCkEditorTheme();
            ensureBlockquotesNonEditable();
        });
        ckEditorInstance.on('mode', () => {
            syncCkEditorTheme();
            ensureBlockquotesNonEditable();
        });
        window.addEventListener('newsroom_theme_change', syncCkEditorTheme);

        // Observe class changes on html element to sync instantly
        const themeObserver = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                if (mutation.attributeName === 'class') {
                    syncCkEditorTheme();
                    break;
                }
            }
        });
        themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        // Sync initial content to hidden input
        const hiddenContent = document.getElementById('hiddenContentInput');

        ckEditorInstance.on('change', () => {
            const html = ckEditorInstance.getData();
            if (hiddenContent) hiddenContent.value = html;
            editorTextarea.value = html;
            markEditorDirty();
        });

        // Shim quill object for backward compatibility with helper methods
        quill = {
            root: {
                get innerHTML() { return ckEditorInstance ? ckEditorInstance.getData() : (editorTextarea ? editorTextarea.value : ''); },
                set innerHTML(val) { if (ckEditorInstance) ckEditorInstance.setData(val); }
            },
            getText: function() {
                if (!ckEditorInstance) return editorTextarea ? editorTextarea.value : '';
                const temp = document.createElement('div');
                temp.innerHTML = ckEditorInstance.getData();
                return temp.textContent || temp.innerText || '';
            },
            getSelection: function() { return { index: 0 }; },
            getLength: function() { return 0; },
            insertText: function(idx, text) {
                if (ckEditorInstance) ckEditorInstance.insertHtml(text.replace(/\n/g, '<br>'));
            },
            clipboard: {
                dangerouslyPasteHTML: function(idx, html) {
                    const content = (typeof idx === 'string' && html === undefined) ? idx : html;
                    if (ckEditorInstance && content) {
                        ckEditorInstance.insertHtml(content);
                    }
                }
            },
            setSelection: function() {}
        };
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

    // 3. Status Change Listener (Show Scheduled Date & Time Sliders when status === 'Publish')
    const statusSelect = document.getElementById('workflowStatusSelect');
    if (statusSelect) {
        statusSelect.addEventListener('change', (e) => {
            toggleScheduledSection(e.target.value === 'Publish' || e.target.value === 'Scheduled');
            markEditorDirty();
        });
        toggleScheduledSection(statusSelect.value === 'Publish' || statusSelect.value === 'Scheduled');
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
            
            // Sync CKEditor data directly
            if (ckEditorInstance) {
                const data = ckEditorInstance.getData();
                editorTextarea.value = data;
                const hiddenContent = document.getElementById('hiddenContentInput');
                if (hiddenContent) hiddenContent.value = data;
            }

            // Sync Author Hidden Input Guard
            syncAuthorBadgesToHidden();
            const authorVal = document.getElementById('authorHiddenInput')?.value || '';
            if (!authorVal.trim()) {
                e.preventDefault();
                alert('Silakan pilih atau ketik minimal satu Penulis (Author Byline).');
                return false;
            }

            // Validate required Kanal
            const kanalVal = document.getElementById('kanalSelect')?.value || '';
            if (!kanalVal.trim()) {
                e.preventDefault();
                alert('Silakan pilih Kanal berita terlebih dahulu.');
                return false;
            }

            isFormSubmitting = true;
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

    // 7. Initialize Cover Image Drag-and-Drop, Auto-Save, Tag & Author Badges
    initCoverDropzone();
    initAutoSave();
    initTagAutocomplete();
    initAuthorBadges();
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

function setPublishNow() {
    const now = new Date();
    const dateInput = document.getElementById('scheduledDateInput');
    if (fpPicker) {
        fpPicker.setDate(now);
    } else if (dateInput) {
        const d = String(now.getDate()).padStart(2, '0');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        dateInput.value = `${d} ${months[now.getMonth()]} ${now.getFullYear()}`;
    }

    const hour = now.getHours();
    const minute = now.getMinutes();

    const hourInput = document.getElementById('hourInputNumber');
    const hourSlider = document.getElementById('hourSlider');
    if (hourInput) hourInput.value = hour;
    if (hourSlider) hourSlider.value = hour;

    const minuteInput = document.getElementById('minuteInputNumber');
    const minuteSlider = document.getElementById('minuteSlider');
    if (minuteInput) minuteInput.value = minute;
    if (minuteSlider) minuteSlider.value = minute;

    updateFullPublishDate();
    markEditorDirty();
    if (typeof showToast === 'function') {
        showToast('Waktu publikasi disetel ke saat ini (Publish Now).', 'info');
    }
}

function updateFullPublishDate() {
    const dateInput = document.getElementById('scheduledDateInput');
    const hourInput = document.getElementById('hourInputNumber');
    const minuteInput = document.getElementById('minuteInputNumber');
    const fullPublishInput = document.getElementById('publishDateHiddenInput');
    const displayLabel = document.getElementById('publishDateDisplay');

    let dateVal = dateInput ? dateInput.value.trim() : '';
    if (!dateVal) {
        const now = new Date();
        const d = String(now.getDate()).padStart(2, '0');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        dateVal = `${d} ${months[now.getMonth()]} ${now.getFullYear()}`;
    }
    const hourVal = hourInput ? String(hourInput.value).padStart(2, '0') : '09';
    const minVal = minuteInput ? String(minuteInput.value).padStart(2, '0') : '00';

    const fullStr = `${dateVal} ${hourVal}:${minVal}`;
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
        const embedHtml = `<blockquote class="article-page-break" data-page-break="true" contenteditable="false"><p><strong>[PAGE BREAK]</strong></p></blockquote><p><br></p>`;
        
        if (ckEditorInstance) {
            ckEditorInstance.insertHtml(embedHtml);
        } else if (quill) {
            const range = quill.getSelection(true);
            const index = range ? range.index : quill.getLength();
            quill.clipboard.dangerouslyPasteHTML(index, embedHtml);
            quill.setSelection(index + 1);
        }
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

function insertSelectedChart(idOrTitle, title, symbol, type, source) {
    let chartId = '';
    let chartTitle = '';
    let chartSymbol = '';

    // Handle both 5-param (id, title, symbol, type, source) and legacy 4-param (title, symbol, type, source)
    if (arguments.length >= 5 || (typeof idOrTitle === 'number' || (typeof idOrTitle === 'string' && /^\d+$/.test(idOrTitle)))) {
        chartId = idOrTitle || '';
        chartTitle = (title || '').trim();
        chartSymbol = (symbol || '').trim();
    } else {
        chartTitle = (idOrTitle || '').trim();
        chartSymbol = (title || '').trim();
    }

    if (!chartTitle) {
        chartTitle = chartSymbol ? `Grafik ${chartSymbol}` : 'Grafik Finansial';
    }

    const idAttr = chartId ? ` id="${chartId}"` : '';
    const symbolAttr = chartSymbol ? ` symbol="${chartSymbol}"` : '';
    const embedCode = `<blockquote class="article-embed-box embed-chart" contenteditable="false">[embed type=chart${idAttr}${symbolAttr} title="${chartTitle}"]${chartTitle}[/embed]</blockquote>`;

    if (ckEditorInstance) {
        ckEditorInstance.insertHtml(`${embedCode}<p><br></p>`);
    } else if (quill) {
        const range = quill.getSelection(true);
        const index = range ? range.index : quill.getLength();
        quill.clipboard.dangerouslyPasteHTML(index, `${embedCode}<p><br></p>`);
    }
    markEditorDirty();
    closeEmbedChartModal();
    if (typeof showToast === 'function') {
        showToast(`Chart "${chartTitle}" berhasil disisipkan.`, 'success');
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
    const platform = document.getElementById('socmedPlatformSelect')?.value || 'Social Media';
    const url = document.getElementById('socmedUrlInput')?.value?.trim();
    const caption = document.getElementById('socmedCaptionInput')?.value?.trim();

    if (!url) {
        alert('Silakan masukkan link/URL konten terlebih dahulu.');
        return;
    }

    // Determine normalized embed type matching production
    let embedType = 'socmed';
    const pLower = platform.toLowerCase();
    if (pLower.includes('youtube')) embedType = 'youtube';
    else if (pLower.includes('instagram')) embedType = 'instagram';
    else if (pLower.includes('twitter') || pLower.includes('x')) embedType = 'twitter';
    else if (pLower.includes('tiktok')) embedType = 'tiktok';
    else if (pLower.includes('drive')) embedType = 'googledrive';
    else if (pLower.includes('scribd')) embedType = 'scribd';

    const embedCode = `<blockquote class="article-embed-box embed-socmed" contenteditable="false">[embed type=${embedType}]${url}${caption ? ' | ' + caption : ''}[/embed]</blockquote>`;

    if (ckEditorInstance) {
        ckEditorInstance.insertHtml(`${embedCode}<p><br></p>`);
    } else if (quill) {
        const range = quill.getSelection(true);
        const index = range ? range.index : quill.getLength();
        quill.clipboard.dangerouslyPasteHTML(index, `${embedCode}<p><br></p>`);
    }
    markEditorDirty();
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
        switchDocTab('google');
    }
}

function closeEmbedGoogleDocModal() {
    const modal = document.getElementById('embedGoogleDocModal');
    if (modal) modal.style.display = 'none';
}

function switchDocTab(tab) {
    const googleBtn = document.getElementById('docTabGoogleBtn');
    const localBtn = document.getElementById('docTabLocalBtn');
    const googleContent = document.getElementById('docTabGoogleContent');
    const localContent = document.getElementById('docTabLocalContent');

    if (tab === 'google') {
        if (googleBtn) {
            googleBtn.className = 'px-3 py-1.5 font-bold border-b-2 border-primary text-primary flex items-center gap-1.5 transition-colors';
        }
        if (localBtn) {
            localBtn.className = 'px-3 py-1.5 font-semibold text-muted-foreground hover:text-foreground flex items-center gap-1.5 transition-colors';
        }
        if (googleContent) googleContent.classList.remove('hidden');
        if (localContent) localContent.classList.add('hidden');
    } else {
        if (localBtn) {
            localBtn.className = 'px-3 py-1.5 font-bold border-b-2 border-primary text-primary flex items-center gap-1.5 transition-colors';
        }
        if (googleBtn) {
            googleBtn.className = 'px-3 py-1.5 font-semibold text-muted-foreground hover:text-foreground flex items-center gap-1.5 transition-colors';
        }
        if (localContent) localContent.classList.remove('hidden');
        if (googleContent) googleContent.classList.add('hidden');
    }
}

function filterLocalFilesList(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#localFilesModalList .local-file-item');
    rows.forEach(r => {
        const title = r.getAttribute('data-title') || '';
        const uploader = r.getAttribute('data-uploader') || '';
        r.style.display = (!q || title.includes(q) || uploader.includes(q)) ? '' : 'none';
    });
}

function insertSelectedLocalFile(fileName, fileUrl) {
    if (!fileUrl) {
        alert('URL berkas tidak valid.');
        return;
    }

    const title = fileName || 'Unduh Berkas';
    const embedCode = `<blockquote class="article-embed-box embed-file" contenteditable="false">[embed type=file url="${fileUrl}"]${title}[/embed]</blockquote>`;

    if (ckEditorInstance) {
        ckEditorInstance.insertHtml(`${embedCode}<p><br></p>`);
    } else if (quill) {
        const range = quill.getSelection(true);
        const index = range ? range.index : quill.getLength();
        quill.clipboard.dangerouslyPasteHTML(index, `${embedCode}<p><br></p>`);
    }
    markEditorDirty();
    closeEmbedGoogleDocModal();
    if (typeof showToast === 'function') {
        showToast(`Berkas "${title}" berhasil disisipkan.`, 'success');
    }
}

function submitGoogleDocEmbed() {
    const docType = document.getElementById('googleDocTypeSelect')?.value || 'Doc';
    const url = document.getElementById('googleDocUrlInput')?.value?.trim();
    const title = document.getElementById('googleDocTitleInput')?.value?.trim() || (docType === 'Doc' ? 'Google Document' : 'Google Spreadsheet');

    if (!url) {
        alert('Silakan masukkan link/URL Google Doc atau Google Sheet terlebih dahulu.');
        return;
    }

    if (!url.includes('docs.google.com')) {
        alert('URL harus berasal dari docs.google.com.');
        return;
    }

    const embedType = docType === 'Sheet' ? 'googlesheet' : 'googledoc';
    const embedCode = `<blockquote class="article-embed-box embed-googledoc" contenteditable="false">[embed type=${embedType} url="${url}"]${title}[/embed]</blockquote>`;

    if (ckEditorInstance) {
        ckEditorInstance.insertHtml(`${embedCode}<p><br></p>`);
    } else if (quill) {
        const range = quill.getSelection(true);
        const index = range ? range.index : quill.getLength();
        quill.clipboard.dangerouslyPasteHTML(index, `${embedCode}<p><br></p>`);
    }
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
        if (input) {
            input.value = '';
            input.focus();
        }
        if (dropdown) {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
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
    const ticker = (document.getElementById('stockTickerInput')?.value || '').toUpperCase().trim();
    const styleType = document.getElementById('stockChartTypeSelect')?.value || 'tradingview_full';

    if (!ticker) {
        alert('Silakan masukkan ticker saham.');
        return;
    }

    const embedCode = `<blockquote>[embed type=stock]${ticker}[/embed]</blockquote>`;

    if (ckEditorInstance) {
        ckEditorInstance.insertHtml(`${embedCode}<p><br></p>`);
    } else if (quill) {
        const range = quill.getSelection(true);
        const index = range ? range.index : quill.getLength();
        quill.clipboard.dangerouslyPasteHTML(index, `${embedCode}<p><br></p>`);
    }
    markEditorDirty();
    closeEmbedStockModal();
    if (typeof showToast === 'function') {
        showToast(`Stock Chart "${ticker}" berhasil disisipkan.`, 'success');
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

function selectMediaImage(url, caption = '', description = '', mediaId = '') {
    const rawCaption = (caption && caption.trim()) ? caption.trim() : '';
    const rawDesc = (description && description.trim()) ? description.trim() : rawCaption;
    const finalCaption = rawCaption || rawDesc || 'Dokumentasi Investor.id';
    const finalDesc = rawDesc || rawCaption || 'Dokumentasi Investor.id';

    if (mediaChooserTarget === 'wysiwyg_modal') {
        const urlInput = document.getElementById('wysiwygImageUrlInput');
        const descInput = document.getElementById('wysiwygImageDescInput');
        const capInput = document.getElementById('wysiwygImageCaptionInput');
        const previewEl = document.getElementById('wysiwygImagePreviewEl');
        const previewContainer = document.getElementById('wysiwygImagePreviewContainer');
        const promptEl = document.getElementById('wysiwygDropzonePrompt');

        if (urlInput) urlInput.value = url;
        if (descInput && !descInput.value) descInput.value = finalDesc;
        if (capInput && !capInput.value) capInput.value = finalCaption;
        if (previewEl) previewEl.src = url;
        if (previewContainer) previewContainer.classList.remove('hidden');
        if (promptEl) promptEl.classList.add('hidden');

        closeMediaChooserModal();
        if (typeof showToast === 'function') {
            showToast('Foto dari Media Library dipilih ke dialog sisipan.', 'success');
        }
        return;
    }

    if (mediaChooserTarget === 'quill' || mediaChooserTarget === 'ckeditor') {
        const figureHtml = `
            <figure style="text-align: center; margin: 18px auto; display: block; max-width: 100%;">
                <img src="${url}" alt="${escapeHtml(finalCaption)}" style="max-width: 100%; height: auto; border-radius: 8px; display: block; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" onerror="this.onerror=null;this.src='/img/investor_placeholder.jpg';">
                <figcaption style="font-size: 11px; font-style: italic; color: #64748b; margin-top: 6px; text-align: center; font-family: Verdana, sans-serif; display: block;">${escapeHtml(finalCaption)}</figcaption>
            </figure>
            <p><br></p>
        `;
        if (ckEditorInstance) {
            ckEditorInstance.insertHtml(figureHtml);
        } else if (quill) {
            const range = quill.getSelection(true);
            const index = range ? range.index : quill.getLength();
            quill.clipboard.dangerouslyPasteHTML(index, figureHtml);
        }
        markEditorDirty();
    } else {
        const input = document.getElementById('thumbnailInput');
        const mediaIdInput = document.getElementById('mediaIdInput');
        const preview = document.getElementById('imagePreview');
        const descInput = document.getElementById('thumbnailDescriptionInput');
        const capInput = document.getElementById('thumbnailCaptionInput');

        if (input) input.value = url;
        if (mediaIdInput) mediaIdInput.value = mediaId || '';
        if (preview) preview.src = url;
        if (descInput) descInput.value = finalDesc;
        if (capInput) capInput.value = finalCaption;

        markEditorDirty();
    }

    closeMediaChooserModal();
    if (typeof showToast === 'function') {
        showToast('Gambar berhasil dipilih.', 'success');
    }
}

// 8B. WYSIWYG Insert Image Modal Handlers (Legacy Parity + Drag & Drop)
function openWysiwygImageModal() {
    const modal = document.getElementById('wysiwygImageModal');
    if (modal) {
        modal.style.display = 'flex';
        // Reset inputs on fresh open
        clearWysiwygImageSelection();
        const descInput = document.getElementById('wysiwygImageDescInput');
        const capInput = document.getElementById('wysiwygImageCaptionInput');
        const sizeSelect = document.getElementById('wysiwygImageSizeSelect');
        const alignSelect = document.getElementById('wysiwygImageAlignSelect');

        if (descInput) descInput.value = '';
        if (capInput) capInput.value = '';
        if (sizeSelect) sizeSelect.value = '100';
        if (alignSelect) alignSelect.value = 'center';
    }
}

function closeWysiwygImageModal() {
    const modal = document.getElementById('wysiwygImageModal');
    if (modal) modal.style.display = 'none';
}

function clearWysiwygImageSelection(e) {
    if (e) e.stopPropagation();
    const urlInput = document.getElementById('wysiwygImageUrlInput');
    const fileInput = document.getElementById('wysiwygImageFileInput');
    const previewContainer = document.getElementById('wysiwygImagePreviewContainer');
    const previewEl = document.getElementById('wysiwygImagePreviewEl');
    const promptEl = document.getElementById('wysiwygDropzonePrompt');

    if (urlInput) urlInput.value = '';
    if (fileInput) fileInput.value = '';
    if (previewEl) previewEl.src = '';
    if (previewContainer) previewContainer.classList.add('hidden');
    if (promptEl) promptEl.classList.remove('hidden');
}

async function handleWysiwygImageFileSelected(inputEl) {
    if (!inputEl || !inputEl.files || !inputEl.files[0]) return;
    const file = inputEl.files[0];

    // Allowed types check
    const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    if (!validTypes.includes(file.type)) {
        alert('Format berkas tidak didukung. Harap pilih gambar JPG, PNG, atau WebP.');
        inputEl.value = '';
        return;
    }

    // Instant local preview
    const previewContainer = document.getElementById('wysiwygImagePreviewContainer');
    const previewEl = document.getElementById('wysiwygImagePreviewEl');
    const promptEl = document.getElementById('wysiwygDropzonePrompt');
    const descInput = document.getElementById('wysiwygImageDescInput');
    const capInput = document.getElementById('wysiwygImageCaptionInput');

    const reader = new FileReader();
    reader.onload = (e) => {
        if (previewEl) previewEl.src = e.target.result;
        if (previewContainer) previewContainer.classList.remove('hidden');
        if (promptEl) promptEl.classList.add('hidden');
    };
    reader.readAsDataURL(file);

    // Auto-fill filename stem as default alt/desc if empty
    const fileNameStem = file.name.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');
    if (descInput && !descInput.value) descInput.value = fileNameStem;

    // Upload to server via /api/upload-image
    const submitBtn = document.getElementById('submitWysiwygImageBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="inline-block animate-spin mr-1">↻</span> Mengunggah...';
    }

    try {
        const formData = new FormData();
        formData.append('image', file);
        const res = await fetch('/api/upload-image', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success && data.url) {
            const urlInput = document.getElementById('wysiwygImageUrlInput');
            if (urlInput) urlInput.value = data.url;
            if (typeof showToast === 'function') {
                showToast('Foto berhasil diunggah dan siap disisipkan.', 'success');
            }
        } else {
            alert(data.error || 'Gagal mengunggah foto.');
            clearWysiwygImageSelection();
        }
    } catch (err) {
        console.error('Wysiwyg image upload error:', err);
        alert('Terjadi kesalahan jaringan saat mengunggah foto.');
        clearWysiwygImageSelection();
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i data-lucide="plus" class="size-3.5"></i><span>Sisipkan ke Naskah</span>';
            if (window.lucide) lucide.createIcons();
        }
    }
}

function submitWysiwygImageEmbed() {
    const imgUrl = (document.getElementById('wysiwygImageUrlInput')?.value || '').trim();
    const caption = (document.getElementById('wysiwygImageCaptionInput')?.value || '').trim();
    const desc = (document.getElementById('wysiwygImageDescInput')?.value || '').trim() || caption || 'Dokumentasi Investor.id';
    const size = document.getElementById('wysiwygImageSizeSelect')?.value || '100';
    const align = document.getElementById('wysiwygImageAlignSelect')?.value || 'center';

    if (!imgUrl) {
        alert('Silakan pilih atau unggah foto gambar terlebih dahulu.');
        return;
    }

    if (!caption) {
        alert('Silakan masukkan keterangan / caption foto terlebih dahulu.');
        document.getElementById('wysiwygImageCaptionInput')?.focus();
        return;
    }

    // Compute styles & width identical to legacy cms.embedToPost
    let style = 'margin: 14px auto; display: block; clear: both; ';
    let widthStyle = '100%';

    switch(size) {
        case "30":
            widthStyle = '30%';
            break;
        case "50":
            widthStyle = '50%';
            break;
        case "100":
            widthStyle = '100%';
            break;
        case "150": // Original
            widthStyle = 'auto';
            break;
        default:
            widthStyle = '100%';
            break;
    }

    if (align === 'left') {
        style = `float: left; margin: 10px 18px 14px 0; max-width: ${widthStyle === 'auto' ? '100%' : widthStyle}; `;
    } else if (align === 'right') {
        style = `float: right; margin: 10px 0 14px 18px; max-width: ${widthStyle === 'auto' ? '100%' : widthStyle}; `;
    } else { // Center
        style = `text-align: center; margin: 16px auto; display: block; clear: both; max-width: ${widthStyle === 'auto' ? '100%' : widthStyle}; `;
    }

    const altAttr = `alt="${escapeHtml(desc)}"`;
    const figureHtml = `
        <figure class="image" style="${style}">
            <img ${altAttr} src="${imgUrl}" width="${widthStyle}" height="auto" style="max-width: 100%; height: auto; border-radius: 6px; display: block; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" onerror="this.onerror=null;this.src='/img/investor_placeholder.jpg';">
            <figcaption style="font-size: 11px; font-style: italic; color: #64748b; margin-top: 6px; text-align: ${align === 'left' ? 'left' : (align === 'right' ? 'right' : 'center')}; font-family: Verdana, sans-serif; display: block;">${escapeHtml(caption)}</figcaption>
        </figure>
        <p><br></p>
    `;

    if (ckEditorInstance) {
        ckEditorInstance.insertHtml(figureHtml);
    } else if (quill) {
        const range = quill.getSelection(true);
        const index = range ? range.index : quill.getLength();
        quill.clipboard.dangerouslyPasteHTML(index, figureHtml);
    }

    markEditorDirty();
    closeWysiwygImageModal();
    if (typeof showToast === 'function') {
        showToast('Gambar naskah berhasil disisipkan ke editor.', 'success');
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
    const html = `<blockquote class="article-embed-box embed-baca-juga" contenteditable="false"><p><strong>Baca Juga: </strong><a href="${url}" target="_blank">${title}</a></p></blockquote><p><br></p>`;
    if (ckEditorInstance) {
        ckEditorInstance.insertHtml(html);
    } else if (quill) {
        const range = quill.getSelection(true);
        const index = range ? range.index : quill.getLength();
        quill.clipboard.dangerouslyPasteHTML(index, html);
        quill.setSelection(index + 1);
    }
    markEditorDirty();
    closeArticleLinkModal();
    if (typeof showToast === 'function') {
        showToast('Link artikel "Baca Juga" berhasil disisipkan.', 'success');
    }
}

// 10B. Sumber Berita & Poin Utama Handlers
function handleSourceChange(val) {
    const customWrapper = document.getElementById('customSourceWrapper');
    const customInput = document.getElementById('customSourceInput');
    const badge = document.getElementById('customSourceBadge');

    if (!customInput) return;

    if (val === 'OTHERS' || val === 'Lainnya') {
        if (customWrapper) customWrapper.classList.remove('hidden');
        if (badge) badge.classList.remove('hidden');
        customInput.focus();
    } else {
        if (customWrapper) customWrapper.classList.add('hidden');
        if (badge) badge.classList.add('hidden');
        customInput.value = val;
    }
    markEditorDirty();
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

            // 1. Set article body content into CKEditor 4
            if (data.body) {
                if (ckEditorInstance) {
                    ckEditorInstance.setData(data.body);
                } else if (quill) {
                    quill.clipboard.dangerouslyPasteHTML(data.body);
                }

                const editorEl = document.getElementById('articleEditor');
                const hiddenEl = document.getElementById('hiddenContentInput');
                if (editorEl) editorEl.value = data.body;
                if (hiddenEl) hiddenEl.value = data.body;
            }

            // 2. Set thumbnail / cover image & preview
            if (data.thumbnail_url) {
                const coverInput = document.getElementById('thumbnailInput');
                const coverPreview = document.getElementById('imagePreview');
                const mediaIdInput = document.getElementById('mediaIdInput');

                if (coverInput) coverInput.value = data.thumbnail_url;
                if (coverPreview) {
                    coverPreview.src = data.thumbnail_url;
                }
                if (mediaIdInput && data.media_id) {
                    mediaIdInput.value = data.media_id;
                }
            }

            // 3. Set thumbnail description & caption
            const descInput = document.getElementById('thumbnailDescriptionInput');
            const capInput = document.getElementById('thumbnailCaptionInput');

            if (descInput && data.thumbnail_description) {
                descInput.value = data.thumbnail_description;
            }
            if (capInput && data.thumbnail_caption) {
                capInput.value = data.thumbnail_caption;
            }

            // 4. Set article title if currently empty (limitasi 70 karakter di-comment out sementara)
            const titleInput = document.getElementById('articleTitleInput');
            if (titleInput && !titleInput.value.trim() && data.title) {
                // titleInput.value = data.title.substring(0, 70);
                titleInput.value = data.title;
                const countEl = document.getElementById('titleCharCount');
                if (countEl) countEl.textContent = titleInput.value.length;
            }

            // 5. Set source to ANTARA if empty
            const sourceSelect = document.getElementById('sourceSelect');
            const sourceInput = document.getElementById('customSourceInput') || document.querySelector('input[name="source"]');
            if (sourceSelect) {
                let foundAntara = false;
                for (let i = 0; i < sourceSelect.options.length; i++) {
                    if (sourceSelect.options[i].value.toUpperCase() === 'ANTARA') {
                        sourceSelect.selectedIndex = i;
                        handleSourceChange(sourceSelect.options[i].value);
                        foundAntara = true;
                        break;
                    }
                }
                if (!foundAntara && sourceInput) {
                    sourceInput.value = 'ANTARA';
                }
            } else if (sourceInput && !sourceInput.value) {
                sourceInput.value = 'ANTARA';
            }

            markEditorDirty();

            if (typeof showToast === 'function') {
                showToast('Artikel berhasil di-scrape! Isi naskah, cover, deskripsi, dan caption telah diisi ke editor.', 'success');
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

    // Track any input or change on the article form to flag dirty state
    const form = document.getElementById('postEditorForm') || document.querySelector('form');
    if (form) {
        form.addEventListener('input', () => markEditorDirty());
        form.addEventListener('change', () => markEditorDirty());
        form.addEventListener('submit', () => {
            isFormSubmitting = true;
        });
    }

    // Unsaved changes beforeunload protection
    window.addEventListener('beforeunload', (e) => {
        if (isEditorDirty && !isFormSubmitting) {
            e.preventDefault();
            e.returnValue = '';
            return '';
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
    const authorHidden = document.getElementById('authorHiddenInput');

    const dot = document.getElementById('autosaveStatusDot');
    const text = document.getElementById('autosaveStatusText');

    if (dot) dot.className = 'size-2 rounded-full bg-blue-500 animate-pulse';
    if (text) text.textContent = 'Menyimpan draft...';

    const mediaIdInput = document.getElementById('mediaIdInput');
    const descInput = document.getElementById('thumbnailDescriptionInput');
    const capInput = document.getElementById('thumbnailCaptionInput');

    const payload = {
        id: idInput ? idInput.value : '',
        title: title,
        summary: summaryInput ? summaryInput.value : '',
        content: content,
        thumbnail: thumbInput ? thumbInput.value : '',
        media_id: mediaIdInput ? mediaIdInput.value : '',
        thumbnail_description: descInput ? descInput.value : '',
        thumbnail_caption: capInput ? capInput.value : '',
        kanal: kanalSelect ? kanalSelect.value : 'Market',
        author: authorHidden ? authorHidden.value : '',
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
// 10. Author Multi-Select Badge Pills Engine (Finding D2)
// =========================================================================
let chosenAuthors = [];

function initAuthorBadges() {
    const hidden = document.getElementById('authorHiddenInput');
    if (hidden && hidden.value) {
        chosenAuthors = hidden.value.split(',').map(a => a.trim().replace(/\*\)$/, '')).filter(Boolean);
    }
    renderAuthorBadges();

    const searchInput = document.getElementById('authorSearchInput');
    if (searchInput) {
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const val = searchInput.value.trim();
                if (val) {
                    addAuthor(val);
                }
            } else if (e.key === 'Backspace' && searchInput.value === '' && chosenAuthors.length > 0) {
                removeAuthor(chosenAuthors.length - 1);
            }
        });
    }

    // Click outside author dropdown to dismiss
    document.addEventListener('click', (e) => {
        const container = document.getElementById('authorAutosuggestContainer');
        if (container && !container.contains(e.target)) {
            closeAuthorDropdown();
        }
    });
}

function renderAuthorBadges() {
    const container = document.getElementById('authorBadgesContainer');
    if (!container) return;
    container.innerHTML = '';

    chosenAuthors.forEach((author, idx) => {
        const badge = document.createElement('span');
        badge.className = "inline-flex items-center gap-1.5 px-2.5 py-1 bg-primary/10 border border-primary/25 text-primary rounded-md font-semibold text-xs animate-in fade-in zoom-in-95 duration-150 select-none";
        badge.innerHTML = `
            <span>${escapeHtml(author)}</span>
            <button type="button" onclick="removeAuthor(${idx})" class="text-primary/70 hover:text-primary hover:scale-125 transition-transform font-black text-sm leading-none ml-0.5" title="Hapus penulis">&times;</button>
        `;
        container.appendChild(badge);
    });

    syncAuthorBadgesToHidden();
}

function addAuthor(name) {
    name = (name || '').trim().replace(/\*\)$/, '');
    if (!name) return;

    const existsIndex = chosenAuthors.findIndex(a => a.toLowerCase() === name.toLowerCase());
    if (existsIndex === -1) {
        chosenAuthors.push(name);
        renderAuthorBadges();
        markEditorDirty();
    }

    const searchInput = document.getElementById('authorSearchInput');
    if (searchInput) searchInput.value = '';
    closeAuthorDropdown();
}

function removeAuthor(idx) {
    if (idx >= 0 && idx < chosenAuthors.length) {
        chosenAuthors.splice(idx, 1);
        renderAuthorBadges();
        markEditorDirty();
    }
}

function selectAuthorName(name) {
    addAuthor(name);
}

function syncAuthorBadgesToHidden() {
    const hidden = document.getElementById('authorHiddenInput');
    if (hidden) {
        hidden.value = chosenAuthors.join(', ');
    }
}

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

function toggleAuthorDropdown(e) {
    if (e) e.stopPropagation();
    const dd = document.getElementById('authorSuggestionsDropdown');
    if (dd && dd.classList.contains('hidden')) {
        openAuthorDropdown();
        const input = document.getElementById('authorSearchInput');
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
}

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
            const targetUrl = pendingNavigationUrl;
            isEditorDirty = false;
            isFormSubmitting = true; // Prevent beforeunload interception
            closeUnsavedChangesModal();
            if (targetUrl) {
                window.location.href = targetUrl;
            } else {
                window.location.href = '/post';
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
        window.ModalUtils.register('wysiwygImageModal', { onClose: () => closeWysiwygImageModal() });
        window.ModalUtils.register('articleLinkModal', { onClose: () => closeArticleLinkModal() });
        window.ModalUtils.register('embedChartModal', { onClose: () => closeEmbedChartModal() });
        window.ModalUtils.register('embedGoogleDocModal', { onClose: () => closeEmbedGoogleDocModal() });
        window.ModalUtils.register('unsavedChangesModal', { onClose: () => closeUnsavedChangesModal() });
        window.ModalUtils.register('embedSocmedModal', { onClose: () => closeEmbedSocmedModal() });
        window.ModalUtils.register('embedStockModal', { onClose: () => closeEmbedStockModal() });
        window.ModalUtils.register('revisionDiffModal', { onClose: () => closeRevisionDiffModal() });
    }

    // Attach click and drag-drop events for WYSIWYG modal image dropzone
    const wysiwygDropzone = document.getElementById('wysiwygImageDropzone');
    const wysiwygFileInput = document.getElementById('wysiwygImageFileInput');
    if (wysiwygDropzone && wysiwygFileInput) {
        wysiwygDropzone.addEventListener('click', (e) => {
            if (e.target.closest('button')) return;
            wysiwygFileInput.click();
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            wysiwygDropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                wysiwygDropzone.classList.add('border-primary', 'bg-primary/5');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            wysiwygDropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                wysiwygDropzone.classList.remove('border-primary', 'bg-primary/5');
            }, false);
        });

        wysiwygDropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            if (dt && dt.files && dt.files.length > 0) {
                wysiwygFileInput.files = dt.files;
                handleWysiwygImageFileSelected(wysiwygFileInput);
            }
        }, false);
    }
});

// =========================================================================
// 13. Revision History Diff & Live Post Helpers
// =========================================================================
async function openRevisionDiffModal(historyId) {
    const modal = document.getElementById('revisionDiffModal');
    if (!modal) return;
    
    if (window.ModalUtils) {
        window.ModalUtils.open('revisionDiffModal');
    } else {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    const titlePrev = document.getElementById('diffTitlePrev');
    const titleAfter = document.getElementById('diffTitleAfter');
    const statusPrev = document.getElementById('diffStatusPrev');
    const statusAfter = document.getElementById('diffStatusAfter');
    const contentPrev = document.getElementById('diffContentPrev');
    const contentAfter = document.getElementById('diffContentAfter');

    if (titlePrev) titlePrev.textContent = 'Memuat...';
    if (titleAfter) titleAfter.textContent = 'Memuat...';
    if (statusPrev) statusPrev.textContent = '...';
    if (statusAfter) statusAfter.textContent = '...';
    if (contentPrev) contentPrev.innerHTML = '<div class="text-xs text-muted-foreground italic">Memuat konten riwayat...</div>';
    if (contentAfter) contentAfter.innerHTML = '<div class="text-xs text-muted-foreground italic">Memuat konten riwayat...</div>';

    try {
        const res = await fetch(`/api/post/revision/${historyId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await res.json();
        if (result.success && result.data) {
            const d = result.data;
            if (titlePrev) titlePrev.textContent = d.title_before || '(Tidak ada judul)';
            if (titleAfter) titleAfter.textContent = d.title_after || '(Tidak ada judul)';
            if (statusPrev) statusPrev.textContent = d.status_before || '-';
            if (statusAfter) statusAfter.textContent = d.status_after || '-';
            if (contentPrev) contentPrev.innerHTML = d.content_before || '<span class="text-xs text-muted-foreground italic">(Kosong)</span>';
            if (contentAfter) contentAfter.innerHTML = d.content_after || '<span class="text-xs text-muted-foreground italic">(Kosong)</span>';
        } else {
            alert(result.message || 'Gagal memuat detail riwayat revisi.');
            closeRevisionDiffModal();
        }
    } catch (err) {
        console.error('Failed to load revision diff:', err);
        alert('Gagal mengambil data revisi.');
        closeRevisionDiffModal();
    }
}

function closeRevisionDiffModal() {
    const modal = document.getElementById('revisionDiffModal');
    if (!modal) return;
    if (window.ModalUtils) {
        window.ModalUtils.close('revisionDiffModal');
    } else {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function copyLivePostUrl(url) {
    if (!url) return;
    navigator.clipboard.writeText(url).then(() => {
        if (typeof showToast === 'function') {
            showToast('Tautan publik berhasil disalin ke clipboard!', 'success');
        } else {
            alert('Tautan publik berhasil disalin!');
        }
    }).catch(() => {
        const tempInput = document.createElement('input');
        tempInput.value = url;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        if (typeof showToast === 'function') {
            showToast('Tautan publik berhasil disalin ke clipboard!', 'success');
        } else {
            alert('Tautan publik berhasil disalin!');
        }
    });
}




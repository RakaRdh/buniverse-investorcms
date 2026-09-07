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

        // On text change, update hidden input
        quill.on('text-change', () => {
            if (hiddenContent) {
                hiddenContent.value = quill.root.innerHTML;
            }
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
            }
        });
    }

    // 3. Status Change Listener (Show/Hide Scheduled Date & Time Sliders)
    const statusSelect = document.getElementById('workflowStatusSelect');
    if (statusSelect) {
        statusSelect.addEventListener('change', (e) => {
            toggleScheduledSection(e.target.value === 'Scheduled');
        });
        toggleScheduledSection(statusSelect.value === 'Scheduled');
    }

    // 4. Form Submit sync guard
    const form = document.getElementById('postEditorForm');
    if (form) {
        form.addEventListener('submit', () => {
            if (quill) {
                const hiddenContent = document.getElementById('hiddenContentInput');
                if (hiddenContent) {
                    hiddenContent.value = quill.root.innerHTML;
                }
            }
        });
    }

    // 5. Initialize Cascading Kanal -> Sub-Kanal
    const kanalSelect = document.getElementById('kanalSelect');
    if (kanalSelect && kanalSelect.value) {
        onKanalChange(kanalSelect.value, window.__ARTICLE_CURRENT_SUBKANAL__ || '');
    }

    // 6. Initialize Realtime Character Counters (Judul & Summary)
    initCharCounters();
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
        const embedHtml = `<hr class="my-4"><p class="text-center text-xs text-muted-foreground tracking-widest font-mono">--- HALAMAN BERIKUTNYA (PAGE BREAK) ---</p><hr class="my-4"><p></p>`;
        quill.clipboard.dangerouslyPasteHTML(index, embedHtml);
        quill.setSelection(index + 1);
        if (typeof showToast === 'function') {
            showToast('Page break berhasil disisipkan.', 'success');
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
    
    const embedHtml = `
        <div class="article-embed-chart p-4 my-4 rounded-xl border border-primary/30 bg-primary/5 shadow-xs font-sans">
            <div class="flex items-center justify-between border-b border-primary/20 pb-2 mb-2.5">
                <div class="flex items-center gap-2">
                    <span class="rounded bg-primary text-white px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">${escapeHtml(type)} CHART</span>
                    <strong class="text-xs text-foreground">${escapeHtml(title)}</strong>
                </div>
                <span class="text-[10px] font-mono text-primary font-bold">IDX:${escapeHtml(symbol)}</span>
            </div>
            <div class="h-32 rounded-lg bg-background border border-border flex items-center justify-center text-center p-4">
                <div>
                    <div class="text-xs font-bold text-foreground mb-0.5">Interactive Chart: ${escapeHtml(symbol)}</div>
                    <div class="text-[11px] text-muted-foreground">Sumber data: ${escapeHtml(source)} &bull; Realtime Feed</div>
                </div>
            </div>
            <div class="text-[10px] text-muted-foreground text-right mt-1.5 font-mono">Powered by Investor.id Chart Engine</div>
        </div>
        <p></p>
    `;

    quill.clipboard.dangerouslyPasteHTML(index, embedHtml);
    quill.setSelection(index + 1);
    closeEmbedChartModal();
    if (typeof showToast === 'function') {
        showToast(`Chart "${title}" berhasil disisipkan dari database.`, 'success');
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
        <div class="article-embed-socmed p-4 my-4 rounded-xl border border-amber-500/30 bg-amber-500/5 shadow-xs font-sans">
            <div class="flex items-center justify-between border-b border-amber-500/20 pb-2 mb-2">
                <span class="rounded bg-amber-500 text-white px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">${escapeHtml(platform)}</span>
                <a href="${escapeHtml(url)}" target="_blank" class="text-[11px] text-amber-700 dark:text-amber-400 hover:underline flex items-center gap-1 font-medium">
                    <span>Buka Tautan Asli</span>
                    &nearr;
                </a>
            </div>
            ${caption ? `<p class="text-xs text-foreground font-medium mb-2">${escapeHtml(caption)}</p>` : ''}
            <div class="p-3 rounded bg-background border border-border text-xs text-muted-foreground break-all font-mono">
                ${escapeHtml(url)}
            </div>
            <div class="text-[10px] text-muted-foreground text-right mt-1.5 font-mono">Embedded Content &bull; Investor.id</div>
        </div>
        <p></p>
    `;

    quill.clipboard.dangerouslyPasteHTML(index, embedHtml);
    quill.setSelection(index + 1);
    closeEmbedSocmedModal();
    if (typeof showToast === 'function') {
        showToast(`Embed ${platform} berhasil disisipkan.`, 'success');
    }
}

// 7C. Stock Chart Interactive TradingView Handlers
let stockSearchDebounceTimer = null;

async function fetchStockSearch(query) {
    const input = document.getElementById('stockTickerInput');
    const dropdown = document.getElementById('stockAutocompleteDropdown');
    if (!dropdown) return;

    try {
        const res = await fetch('/api/stocks/search?q=' + encodeURIComponent(query || ''));
        if (!res.ok) return;
        const data = await res.json();
        if (data && data.length > 0) {
            let html = '';
            data.forEach(item => {
                html += `
                    <div class="px-3 py-2.5 text-xs hover:bg-indigo-500/10 hover:text-indigo-600 cursor-pointer flex items-center justify-between border-b border-border/40 last:border-0 transition-colors" onclick="selectStockTicker('${escapeHtml(item.ticker)}')">
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
            dropdown.innerHTML = `<div class="p-3 text-xs text-muted-foreground italic">Kode emiten "${escapeHtml(query)}" tidak ditemukan di direktori IDX.</div>`;
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

    input.addEventListener('focus', () => {
        fetchStockSearch(input.value.trim());
    });

    input.addEventListener('input', (e) => {
        clearTimeout(stockSearchDebounceTimer);
        const val = input.value.trim().toUpperCase();
        stockSearchDebounceTimer = setTimeout(() => {
            fetchStockSearch(val);
        }, 100);
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
        input.focus();
    }
    if (dropdown) dropdown.classList.add('hidden');
}

function openEmbedStockModal() {
    const modal = document.getElementById('embedStockModal');
    if (modal) {
        modal.style.display = 'flex';
        const input = document.getElementById('stockTickerInput');
        if (input) {
            input.focus();
            fetchStockSearch(input.value.trim());
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
    const ticker = (document.getElementById('stockTickerInput')?.value || 'BBCA').toUpperCase().trim();
    const styleType = document.getElementById('stockChartTypeSelect')?.value || 'tradingview_full';

    if (!ticker) {
        alert('Silakan masukkan ticker saham.');
        return;
    }

    const range = quill.getSelection(true);
    const index = range ? range.index : quill.getLength();

    let embedHtml = '';
    if (styleType === 'tradingview_full') {
        embedHtml = `
            <div class="article-embed-tradingview" style="background:#0b1329; border:1px solid #1e293b; border-radius:12px; padding:16px; margin:18px 0; color:#f8fafc; font-family:sans-serif;">
                <div style="display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:8px; margin-bottom:12px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="background:#4f46e5; color:#ffffff; padding:2px 8px; border-radius:4px; font-size:10px; font-weight:bold; letter-spacing:0.05em; text-transform:uppercase;">TRADINGVIEW LIVE</span>
                        <strong style="font-size:13px; color:#ffffff;">Grafik Saham IDX:${escapeHtml(ticker)}</strong>
                    </div>
                    <span style="font-size:11px; font-family:monospace; color:#818cf8; font-weight:bold;">Bursa Efek Indonesia</span>
                </div>
                <div style="height:260px; background:#020617; border:1px solid #1e293b; border-radius:8px; position:relative; overflow:hidden;">
                    <iframe src="https://s.tradingview.com/widgetembed/?symbol=IDX%3A${encodeURIComponent(ticker)}&interval=D&theme=dark&style=1&timezone=Asia%2FJakarta&locale=id" style="width:100%; height:100%; border:none;" frameborder="0" allowtransparency="true" scrolling="no"></iframe>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px; font-size:10px; color:#94a3b8; font-family:monospace;">
                    <span>Simbol: IDX:${escapeHtml(ticker)}</span>
                    <span>Data disediakan oleh TradingView IDX Feed</span>
                </div>
            </div>
            <p></p>
        `;
    } else {
        embedHtml = `
            <div class="article-embed-stock-card" style="background:#ffffff; border:1px solid #cbd5e1; border-left:4px solid #4f46e5; border-radius:8px; padding:14px 18px; margin:16px 0; box-shadow:0 1px 3px rgba(0,0,0,0.06); font-family:sans-serif; max-width:440px;">
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span style="font-size:13px; font-weight:bold; font-family:monospace; background:rgba(79,70,229,0.12); color:#4f46e5; padding:2px 6px; border-radius:4px;">${escapeHtml(ticker)}</span>
                            <span style="font-size:11px; color:#64748b; font-family:monospace;">IDX &bull; Saham Indonesia</span>
                        </div>
                        <div style="font-size:15px; font-weight:bold; color:#0f172a; margin-top:6px; font-family:monospace;">Live Price Tracker</div>
                    </div>
                    <div style="text-align:right;">
                        <span style="font-size:11px; font-weight:bold; color:#10b981; font-family:monospace; background:#ecfdf5; padding:3px 8px; border-radius:4px;">▲ IDX LIVE FEED</span>
                        <div style="font-size:10px; color:#94a3b8; margin-top:4px;">Investor.id Ticker</div>
                    </div>
                </div>
            </div>
            <p></p>
        `;
    }

    quill.clipboard.dangerouslyPasteHTML(index, embedHtml);
    quill.setSelection(index + 1);
    closeEmbedStockModal();
    if (typeof showToast === 'function') {
        showToast(`Stock widget untuk "${ticker}" berhasil disisipkan.`, 'success');
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

function selectMediaImage(url) {
    if (mediaChooserTarget === 'quill') {
        if (quill) {
            const range = quill.getSelection(true);
            const index = range ? range.index : quill.getLength();
            const html = `<figure class="my-4"><img src="${url}" class="rounded-md max-w-full" onerror="this.onerror=null;this.src='/img/article1.jpg';"><figcaption class="text-xs text-gray-500 mt-1">Dokumentasi Investor.id</figcaption></figure><p></p>`;
            quill.clipboard.dangerouslyPasteHTML(index, html);
        }
    } else {
        const input = document.getElementById('thumbnailInput');
        const preview = document.getElementById('imagePreview');
        if (input) input.value = url;
        if (preview) preview.src = url;
    }

    closeMediaChooserModal();
    if (typeof showToast === 'function') {
        showToast('Gambar berhasil dipilih dari Media Library', 'success');
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
        const html = `<p class="my-2 p-2.5 rounded bg-muted/40 border-l-4 border-[#2B6CB0]"><strong>Baca Juga: </strong><a href="${url}" target="_blank" class="text-[#2B6CB0] font-bold underline">${title}</a><br><em class="text-xs text-muted-foreground">${summary}</em></p><p></p>`;
        quill.clipboard.dangerouslyPasteHTML(index, html);
    }
    closeArticleLinkModal();
    if (typeof showToast === 'function') {
        showToast('Link artikel "Baca Juga" berhasil disisipkan.', 'success');
    }
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
            const res = await fetch('/api/ai/tags', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ body: bodyText })
            });
            const json = await res.json();
            if (json.status === 'success') {
                const tagsInput = document.getElementById('articleTagsInput') || document.getElementById('tagsInput');
                if (tagsInput && Array.isArray(json.data.tags)) {
                    tagsInput.value = json.data.tags.join(', ');
                    tagsInput.dispatchEvent(new Event('input'));
                }
                if (typeof showToast === 'function') {
                    showToast('Tag kata kunci berhasil di-generate!', 'success');
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
                const lines = json.data.points.split('\n').filter(l => l.trim().length > 0);
                lines.slice(0, 3).forEach((line, idx) => {
                    const cleanLine = line.replace(/^[•\-\*\d\.\s]+/, '').trim();
                    const ptInput = document.querySelector(`input[name="point_${idx + 1}"]`);
                    if (ptInput) ptInput.value = cleanLine;
                });
                if (typeof showToast === 'function') {
                    showToast('Poin-poin penting berhasil dirangkum!', 'success');
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

            // 2. Set thumbnail / cover image
            if (data.thumbnail_url) {
                const coverInput = document.getElementById('coverImageInput');
                const coverPreview = document.getElementById('coverImagePreview');
                const coverEmptyState = document.getElementById('coverEmptyState');

                if (coverInput) coverInput.value = data.thumbnail_url;
                if (coverPreview) {
                    coverPreview.src = data.thumbnail_url;
                    coverPreview.classList.remove('hidden');
                }
                if (coverEmptyState) {
                    coverEmptyState.classList.add('hidden');
                }
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

// 13. Tag Autocomplete & Tag Chips Manager
let tagSearchDebounceTimer = null;

function initTagAutocomplete() {
    const input = document.getElementById('articleTagsInput');
    const dropdown = document.getElementById('tagAutocompleteDropdown');
    const chipsContainer = document.getElementById('tagChipsContainer');
    if (!input || !dropdown) return;

    renderTagChips();

    // Debounced input search
    input.addEventListener('input', (e) => {
        renderTagChips();
        clearTimeout(tagSearchDebounceTimer);
        const val = input.value;
        // Search using the last token after the comma
        const tokens = val.split(',');
        const lastToken = tokens[tokens.length - 1].trim();

        if (lastToken.length < 2) {
            dropdown.innerHTML = '';
            dropdown.classList.add('hidden');
            return;
        }

        tagSearchDebounceTimer = setTimeout(async () => {
            try {
                const res = await fetch('/api/tags/search?q=' + encodeURIComponent(lastToken));
                if (!res.ok) return;
                const data = await res.json();
                if (data && data.length > 0) {
                    let html = '';
                    data.forEach(item => {
                        html += `
                            <div class="px-3 py-2 text-xs hover:bg-primary/10 hover:text-primary cursor-pointer flex items-center justify-between border-b border-border/40 last:border-0 transition-colors" onclick="selectSuggestedTag('${escapeHtml(item.label)}')">
                                <span class="font-medium text-foreground">${escapeHtml(item.label)}</span>
                                <span class="text-[10px] text-muted-foreground font-mono">#${escapeHtml(item.slug || '')}</span>
                            </div>
                        `;
                    });
                    dropdown.innerHTML = html;
                    dropdown.classList.remove('hidden');
                } else {
                    dropdown.innerHTML = `<div class="p-2.5 text-xs text-muted-foreground italic">Tag baru: "${escapeHtml(lastToken)}" (akan dibuat otomatis)</div>`;
                    dropdown.classList.remove('hidden');
                }
            } catch (err) {
                console.error('Tag search failed', err);
            }
        }, 250);
    });

    // Close dropdown on click outside
    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // Enter key creates tag chip
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = input.value.trim();
            if (val && !val.endsWith(',')) {
                input.value = val + ', ';
                renderTagChips();
            }
            dropdown.classList.add('hidden');
        }
    });
}

function selectSuggestedTag(tagName) {
    const input = document.getElementById('articleTagsInput');
    const dropdown = document.getElementById('tagAutocompleteDropdown');
    if (!input) return;

    let tokens = input.value.split(',').map(t => t.trim()).filter(Boolean);
    if (tokens.length > 0) {
        tokens.pop(); // Replace the partial token
    }
    if (!tokens.includes(tagName)) {
        tokens.push(tagName);
    }
    input.value = tokens.join(', ') + ', ';
    if (dropdown) dropdown.classList.add('hidden');
    renderTagChips();
    input.focus();
}

function removeTagChip(index) {
    const input = document.getElementById('articleTagsInput');
    if (!input) return;
    let tokens = input.value.split(',').map(t => t.trim()).filter(Boolean);
    tokens.splice(index, 1);
    input.value = tokens.length > 0 ? tokens.join(', ') + ', ' : '';
    renderTagChips();
}

function renderTagChips() {
    const input = document.getElementById('articleTagsInput');
    const chipsContainer = document.getElementById('tagChipsContainer');
    if (!input || !chipsContainer) return;

    const tokens = input.value.split(',').map(t => t.trim()).filter(Boolean);
    if (tokens.length === 0) {
        chipsContainer.innerHTML = '';
        return;
    }

    let chipsHtml = '';
    tokens.forEach((tag, idx) => {
        chipsHtml += `
            <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 border border-primary/25 px-2.5 py-0.5 text-[11px] font-medium text-primary shadow-2xs">
                <span>#${escapeHtml(tag)}</span>
                <button type="button" onclick="removeTagChip(${idx})" class="text-primary/60 hover:text-primary hover:scale-110 transition-transform">
                    <i data-lucide="x" class="size-3"></i>
                </button>
            </span>
        `;
    });
    chipsContainer.innerHTML = chipsHtml;
    if (window.lucide) lucide.createIcons();
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


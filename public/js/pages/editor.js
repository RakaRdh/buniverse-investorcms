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
});

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

// 6. Cascading Kanal -> Sub-Kanal Dropdown Handler
function onKanalChange(kanalName, preselectSub = '') {
    const subSelect = document.getElementById('subKanalSelect');
    if (!subSelect) return;

    if (!kanalName) {
        subSelect.innerHTML = '<option value="">-- Silahkan Pilih Kanal Terlebih Dahulu --</option>';
        subSelect.disabled = true;
        return;
    }

    const kanals = window.__KANALS_DATA__ || [];
    const found = kanals.find(k => k.name.toLowerCase() === kanalName.toLowerCase());

    if (found && Array.isArray(found.sub_kanals) && found.sub_kanals.length > 0) {
        subSelect.disabled = false;
        let html = '<option value="">-- Pilih Sub-Kanal --</option>';
        found.sub_kanals.forEach(sub => {
            const isSel = (preselectSub && preselectSub.toLowerCase() === sub.name.toLowerCase()) ? 'selected' : '';
            html += `<option value="${sub.name}" ${isSel}>${sub.name}</option>`;
        });
        subSelect.innerHTML = html;
    } else {
        subSelect.innerHTML = '<option value="">(Tidak Ada Sub-Kanal)</option>';
        subSelect.disabled = true;
    }
}

// 7. Interactive Embed Tools
function insertEmbedTool(type) {
    if (!quill) return;

    const range = quill.getSelection(true);
    const index = range ? range.index : quill.getLength();

    let embedHtml = '';
    switch (type) {
        case 'embed_chart':
            embedHtml = `<div class="article-embed-box p-3 my-3 bg-muted/60 border border-primary/20 rounded-md font-mono text-xs text-primary">📊 <strong>[EMBED CHART]:</strong> Pergerakan Saham IDX Composite &amp; Volume Live Feed</div><p></p>`;
            break;
        case 'embed_socmed':
            embedHtml = `<div class="article-embed-box p-3 my-3 bg-muted/60 border border-amber-500/20 rounded-md font-mono text-xs text-amber-700">📱 <strong>[EMBED SOCIAL MEDIA/DOC]:</strong> @investordaily ID Official Post Embed</div><p></p>`;
            break;
        case 'stock_chart':
            embedHtml = `<div class="article-embed-box p-3 my-3 bg-muted/60 border border-indigo-500/20 rounded-md font-mono text-xs text-indigo-700">📈 <strong>[STOCK WIDGET]:</strong> BBCA:IDX Rp 10.250 (+1.25%) | BBRI:IDX Rp 5.150 (+0.5%)</div><p></p>`;
            break;
        case 'page_break':
            embedHtml = `<hr class="my-4"><p class="text-center text-xs text-muted-foreground tracking-widest font-mono">--- HALAMAN BERIKUTNYA ---</p><hr class="my-4"><p></p>`;
            break;
    }

    if (embedHtml) {
        quill.clipboard.dangerouslyPasteHTML(index, embedHtml);
        quill.setSelection(index + 1);
        if (typeof showToast === 'function') {
            showToast(`Elemen embed "${type}" berhasil disisipkan.`, 'success');
        }
    }
}

// 8. Media Library Chooser Modal
function openMediaChooserModal(target = 'cover') {
    mediaChooserTarget = target;
    const modal = document.getElementById('mediaChooserModal');
    if (modal) modal.style.display = 'flex';
}

function closeMediaChooserModal() {
    const modal = document.getElementById('mediaChooserModal');
    if (modal) modal.style.display = 'none';
}

function selectMediaImage(url) {
    if (mediaChooserTarget === 'quill') {
        if (quill) {
            const range = quill.getSelection(true);
            const index = range ? range.index : quill.getLength();
            const html = `<figure class="my-4"><img src="${url}" class="rounded-md max-w-full"><figcaption class="text-xs text-gray-500 mt-1">Dokumentasi Investor.id</figcaption></figure><p></p>`;
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

// 11. Static AI Assistance Buttons & Scrape Placeholders
function handleAiAssistance(field) {
    // Static placeholder behavior as approved by user
    if (typeof showToast === 'function') {
        showToast(`Fitur AI Assistance (${field.toUpperCase()}) siap diintegrasikan.`, 'info');
    }
}

function handleScrapePlaceholder() {
    const url = document.getElementById('scrapeUrlInput')?.value;
    if (!url) {
        if (typeof showToast === 'function') showToast('Silahkan masukkan URL artikel yang ingin discrape.', 'error');
        return;
    }
    if (typeof showToast === 'function') {
        showToast(`Fitur Scrape Artikel (${url}) siap diintegrasikan.`, 'info');
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

/**
 * Post Editor Logic: Quill WYSIWYG, Embed Buttons Insertion, Scheduled Date/Time Slider, Live Preview
 */

let quill = null;
let fpPicker = null;

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Quill WYSIWYG Editor
    if (document.getElementById('quillEditor') && window.Quill) {
        const toolbarOptions = [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'align': [] }],
            ['link', 'image', 'video'],
            ['clean']
        ];

        quill = new Quill('#quillEditor', {
            theme: 'snow',
            placeholder: 'Tulis atau paste isi konten artikel di sini...',
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
        // Initial state
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
});

// Toggle Scheduled Box
function toggleScheduledSection(show) {
    const scheduledBox = document.getElementById('scheduledSettingBox');
    if (scheduledBox) {
        scheduledBox.style.display = show ? 'block' : 'none';
    }
}

// Sliders Handler: Update Hour & Minute
function onHourSliderChange(val) {
    const padded = String(val).padStart(2, '0');
    const label = document.getElementById('hourValueLabel');
    if (label) label.textContent = padded;
    updateFullPublishDate();
}

function onMinuteSliderChange(val) {
    const padded = String(val).padStart(2, '0');
    const label = document.getElementById('minuteValueLabel');
    if (label) label.textContent = padded;
    updateFullPublishDate();
}

function updateFullPublishDate() {
    const dateInput = document.getElementById('scheduledDateInput');
    const hourLabel = document.getElementById('hourValueLabel');
    const minuteLabel = document.getElementById('minuteValueLabel');
    const fullPublishInput = document.getElementById('publishDateHiddenInput');
    const displayLabel = document.getElementById('publishDateDisplay');

    const dateStr = dateInput ? dateInput.value : '';
    const hourStr = hourLabel ? hourLabel.textContent : '09';
    const minStr = minuteLabel ? minuteLabel.textContent : '00';

    const fullStr = `${dateStr} ${hourStr}:${minStr}`;
    if (fullPublishInput) fullPublishInput.value = fullStr;
    if (displayLabel) displayLabel.textContent = fullStr;
}

// 5. Interactive Embed Tools (Insert direct HTML/Embed into Quill Body)
function insertEmbedTool(type) {
    if (!quill) return;

    const range = quill.getSelection(true);
    const index = range ? range.index : quill.getLength();

    let embedHtml = '';
    switch (type) {
        case 'article_link':
            embedHtml = `<p><strong>Baca Juga: </strong><a href="#" class="text-blue-600 underline">IHSG Diprediksi Menguat, Cermati Rekomendasi Saham Pilihan Analis Hari Ini</a></p>`;
            break;
        case 'embed_chart':
            embedHtml = `<div class="article-embed-box">📊 <strong>[EMBED CHART]:</strong> Pergerakan Saham IDX Composite &amp; Volume Live Feed</div><p></p>`;
            break;
        case 'insert_media':
            openMediaChooserModal();
            return;
        case 'embed_socmed':
            embedHtml = `<div class="article-embed-box">📱 <strong>[EMBED SOCIAL MEDIA/DOC]:</strong> @investordaily ID Official Post Embed</div><p></p>`;
            break;
        case 'stock_chart':
            embedHtml = `<div class="article-embed-box">📈 <strong>[STOCK WIDGET]:</strong> BBCA:IDX Rp 10.250 (+1.25%) | BBRI:IDX Rp 5.150 (+0.5%)</div><p></p>`;
            break;
        case 'page_break':
            embedHtml = `<hr><p class="text-center text-xs text-muted-foreground tracking-widest font-mono">--- HALAMAN BERIKUTNYA ---</p><hr><p></p>`;
            break;
    }

    if (embedHtml) {
        quill.clipboard.dangerouslyPasteHTML(index, embedHtml);
        quill.setSelection(index + 1);
        if (typeof showToast === 'function') {
            showToast(`Elemen embed "${type}" berhasil ditambahkan ke dalam body artikel.`, 'success');
        }
    }
}

// 6. Media Library Chooser Modal
function openMediaChooserModal() {
    const modal = document.getElementById('mediaChooserModal');
    if (modal) modal.style.display = 'flex';
}

function closeMediaChooserModal() {
    const modal = document.getElementById('mediaChooserModal');
    if (modal) modal.style.display = 'none';
}

function selectMediaImage(url) {
    const input = document.getElementById('thumbnailInput');
    const preview = document.getElementById('imagePreview');
    if (input) input.value = url;
    if (preview) preview.src = url;
    closeMediaChooserModal();
    if (typeof showToast === 'function') {
        showToast('Featured Image berhasil diperbarui dari Media Library', 'success');
    }
}

function insertMediaIntoQuill(url, caption) {
    if (quill) {
        const range = quill.getSelection(true);
        const index = range ? range.index : quill.getLength();
        const html = `<figure class="my-4"><img src="${url}" alt="${caption}" class="rounded-md max-w-full"><figcaption class="text-xs text-gray-500 mt-1">${caption}</figcaption></figure><p></p>`;
        quill.clipboard.dangerouslyPasteHTML(index, html);
        closeMediaChooserModal();
        if (typeof showToast === 'function') {
            showToast('Foto berhasil disisipkan ke dalam isi teks artikel.', 'success');
        }
    }
}

// 7. Preview Article (Fullscreen Live Frontend Simulation)
function openArticlePreview() {
    const title = document.querySelector('input[name="title"]')?.value || 'Judul Artikel Masih Kosong';
    const upperDeck = document.querySelector('input[name="upper_deck"]')?.value || '';
    const summary = document.querySelector('textarea[name="summary"]')?.value || '';
    const author = document.querySelector('select[name="author"]')?.value || 'Redaksi Investor.id';
    const category = document.querySelector('select[name="category"]')?.value || 'Market Stock';
    const publishDate = document.getElementById('publishDateHiddenInput')?.value || '01 Sep 2026 09:00';
    const thumbnail = document.getElementById('thumbnailInput')?.value || '/img/article1.jpg';
    const isPremium = document.querySelector('input[name="is_premium"]')?.checked;
    const bodyHtml = quill ? quill.root.innerHTML : document.getElementById('hiddenContentInput')?.value || '';

    // Populate modal elements
    const modal = document.getElementById('articlePreviewModal');
    if (!modal) return;

    document.getElementById('prevCategory').textContent = category.toUpperCase();
    
    const upperDeckEl = document.getElementById('prevUpperDeck');
    if (upperDeck) {
        upperDeckEl.textContent = upperDeck;
        upperDeckEl.classList.remove('hidden');
    } else {
        upperDeckEl.classList.add('hidden');
    }

    document.getElementById('prevTitle').textContent = title;
    document.getElementById('prevSummary').textContent = summary;
    document.getElementById('prevAuthor').textContent = author;
    document.getElementById('prevDate').textContent = publishDate;
    document.getElementById('prevImage').src = thumbnail;
    document.getElementById('prevBody').innerHTML = bodyHtml;

    const premiumBadge = document.getElementById('prevPremiumBadge');
    if (premiumBadge) {
        premiumBadge.style.display = isPremium ? 'inline-flex' : 'none';
    }

    modal.style.display = 'flex';
}

function closeArticlePreview() {
    const modal = document.getElementById('articlePreviewModal');
    if (modal) modal.style.display = 'none';
}

/**
 * Headline Page Script with HTML5 Drag-and-Drop Batch Reordering
 */
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) {
        window.lucide.createIcons();
    }

    if (window.ModalUtils) {
        window.ModalUtils.register('confirmAddModal', { onClose: closeConfirmModals });
        window.ModalUtils.register('confirmRemoveModal', { onClose: closeConfirmModals });
        window.ModalUtils.register('alreadyHeadlineModal', { onClose: closeConfirmModals });
        window.ModalUtils.register('maxQuotaModal', { onClose: closeConfirmModals });
    }

    initHeadlineDragAndDrop();
    initHeadlineOrderSave();
});

function onTargetCategoryChange(selectedCategory) {
    const url = new URL(window.location.href);
    url.searchParams.set('target_category', selectedCategory);
    window.location.href = url.toString();
}

function checkAndConfirmAddHeadline(articleId, articleTitle, currentHeadlineCount, category) {
    if (currentHeadlineCount >= 5) {
        openMaxQuotaModal(category);
        return;
    }
    confirmAddHeadline(articleId, articleTitle);
}

function confirmAddHeadline(articleId, articleTitle) {
    const modal = document.getElementById('confirmAddModal');
    const titleEl = document.getElementById('addArticleTitle');
    const inputEl = document.getElementById('addArticleIdInput');
    
    if (titleEl) titleEl.textContent = articleTitle;
    if (inputEl) inputEl.value = articleId;
    if (modal) modal.style.display = 'flex';
}

function openAlreadyHeadlineModal(articleTitle, category, currentPos) {
    const modal = document.getElementById('alreadyHeadlineModal');
    const titleEl = document.getElementById('alreadyHeadlineTitle');
    const catEl = document.getElementById('alreadyHeadlineCategoryText');
    const posEl = document.getElementById('alreadyHeadlinePosBadge');

    if (titleEl) titleEl.textContent = articleTitle;
    if (catEl) catEl.textContent = category;
    if (posEl) posEl.textContent = `#${currentPos}`;
    if (modal) modal.style.display = 'flex';
}

function openMaxQuotaModal(category) {
    const modal = document.getElementById('maxQuotaModal');
    const catEl = document.getElementById('maxQuotaCategoryText');
    if (catEl) catEl.textContent = category;
    if (modal) modal.style.display = 'flex';
}

function confirmRemoveHeadline(articleId, articleTitle, category) {
    const modal = document.getElementById('confirmRemoveModal');
    const titleEl = document.getElementById('removeArticleTitle');
    const linkEl = document.getElementById('confirmRemoveLink');
    
    if (titleEl) titleEl.textContent = articleTitle;
    const catParam = category ? ('?target_category=' + encodeURIComponent(category)) : '';
    if (linkEl) linkEl.href = '/headline/remove/' + articleId + catParam;
    if (modal) modal.style.display = 'flex';
}

function closeConfirmModals() {
    const addModal = document.getElementById('confirmAddModal');
    const removeModal = document.getElementById('confirmRemoveModal');
    const alreadyModal = document.getElementById('alreadyHeadlineModal');
    const quotaModal = document.getElementById('maxQuotaModal');

    if (addModal) addModal.style.display = 'none';
    if (removeModal) removeModal.style.display = 'none';
    if (alreadyModal) alreadyModal.style.display = 'none';
    if (quotaModal) quotaModal.style.display = 'none';
}

/**
 * Initialize Drag-and-Drop for Headline Queue Rows
 */
function initHeadlineDragAndDrop() {
    const tbody = document.getElementById('headlineQueueTableBody');
    if (!tbody) return;

    let draggedRow = null;

    tbody.addEventListener('dragstart', (e) => {
        const row = e.target.closest('.headline-draggable-row');
        if (!row) return;

        draggedRow = row;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', row.dataset.articleId || '');

        setTimeout(() => {
            row.classList.add('opacity-40', 'bg-primary/10');
        }, 0);
    });

    tbody.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const targetRow = e.target.closest('.headline-draggable-row');
        if (!targetRow || targetRow === draggedRow) return;

        const rect = targetRow.getBoundingClientRect();
        const midY = rect.top + rect.height / 2;

        if (e.clientY < midY) {
            tbody.insertBefore(draggedRow, targetRow);
        } else {
            tbody.insertBefore(draggedRow, targetRow.nextSibling);
        }
    });

    tbody.addEventListener('drop', (e) => {
        e.preventDefault();
    });

    tbody.addEventListener('dragend', () => {
        if (draggedRow) {
            draggedRow.classList.remove('opacity-40', 'bg-primary/10');
            draggedRow = null;
        }
        updateHeadlineBadges();
    });
}

/**
 * Re-index the #1, #2, #3 badges live
 */
function updateHeadlineBadges() {
    const rows = document.querySelectorAll('#headlineQueueTableBody .headline-draggable-row');
    rows.forEach((row, idx) => {
        const badge = row.querySelector('.headline-pos-badge');
        if (badge) {
            badge.textContent = `#${idx + 1}`;
        }
    });
}

/**
 * Handle form submission / AJAX save for headline order
 */
function initHeadlineOrderSave() {
    const saveBtn = document.getElementById('saveHeadlineOrderBtn');
    if (!saveBtn) return;

    const form = saveBtn.closest('form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const rows = document.querySelectorAll('#headlineQueueTableBody .headline-draggable-row');
        const orderedIds = Array.from(rows).map(r => parseInt(r.dataset.articleId)).filter(id => !isNaN(id));

        const targetCategory = document.getElementById('targetCategorySelect')?.value || 'home';

        if (orderedIds.length === 0) {
            if (typeof showToast === 'function') {
                showToast('Tidak ada artikel dalam antrean headline.', 'info');
            }
            return;
        }

        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>MENYIMPAN URUTAN...</span>
        `;

        try {
            const res = await fetch('/headline/reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    category: targetCategory,
                    order: orderedIds
                })
            });

            const json = await res.json();
            if (json.status === 'success') {
                if (typeof showToast === 'function') {
                    showToast(json.message || 'Urutan headline berhasil disimpan!', 'success');
                } else {
                    alert(json.message || 'Urutan headline berhasil disimpan!');
                }
            } else {
                throw new Error(json.message || 'Gagal menyimpan urutan headline.');
            }
        } catch (err) {
            if (typeof showToast === 'function') {
                showToast('Error: ' + err.message, 'error');
            } else {
                alert('Error: ' + err.message);
            }
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
            if (window.lucide) window.lucide.createIcons();
        }
    });
}

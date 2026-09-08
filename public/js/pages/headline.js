/**
 * Headline Page Script
 */
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) {
        window.lucide.createIcons();
    }
});

function onTargetCategoryChange(selectedCategory) {
    const url = new URL(window.location.href);
    url.searchParams.set('target_category', selectedCategory);
    window.location.href = url.toString();
}

function confirmAddHeadline(articleId, articleTitle) {
    const modal = document.getElementById('confirmAddModal');
    const titleEl = document.getElementById('addArticleTitle');
    const inputEl = document.getElementById('addArticleIdInput');
    
    if (titleEl) titleEl.textContent = articleTitle;
    if (inputEl) inputEl.value = articleId;
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
    if (addModal) addModal.style.display = 'none';
    if (removeModal) removeModal.style.display = 'none';
}

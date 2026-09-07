/**
 * Post Page Specific Interactive JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // Select All Checkbox Logic
    const selectAllCheckbox = document.getElementById('selectAllRows');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkActionBar = document.getElementById('bulkActionBar');
    const selectedCountSpan = document.getElementById('selectedCount');

    function updateBulkState() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        const count = checked.length;
        if (selectedCountSpan) selectedCountSpan.textContent = count;
        if (bulkActionBar) {
            if (count > 0) {
                bulkActionBar.classList.remove('hidden');
                bulkActionBar.classList.add('flex');
            } else {
                bulkActionBar.classList.add('hidden');
                bulkActionBar.classList.remove('flex');
            }
        }
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = count > 0 && count === rowCheckboxes.length;
            selectAllCheckbox.indeterminate = count > 0 && count < rowCheckboxes.length;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', (e) => {
            rowCheckboxes.forEach(cb => {
                cb.checked = e.target.checked;
            });
            updateBulkState();
        });
    }

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkState);
    });

    // Image preview handler for Editor page
    const thumbnailInput = document.getElementById('thumbnailInput');
    const imagePreview = document.getElementById('imagePreview');
    if (thumbnailInput && imagePreview) {
        thumbnailInput.addEventListener('input', (e) => {
            if (e.target.value) {
                imagePreview.src = e.target.value;
            }
        });
    }

    // Flatpickr on More Filter date range
    if (window.flatpickr) {
        ['#filterDateFrom', '#filterDateTo', '#postFilterDateFrom', '#postFilterDateTo'].forEach(sel => {
            if (document.querySelector(sel)) {
                flatpickr(sel, {
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d M Y",
                    allowInput: true
                });
            }
        });
    }
});

function toggleMoreFilters() {
    const panel = document.getElementById('moreFilterSection') || document.getElementById('moreFilterPanel');
    const arrow = document.getElementById('moreFilterArrow') || document.getElementById('moreFilterIcon');
    const text = document.getElementById('moreFilterText');
    if (!panel) return;

    const isHidden = panel.classList.contains('hidden');
    if (isHidden) {
        panel.classList.remove('hidden');
        if (arrow) arrow.classList.add('rotate-180');
        if (text) text.textContent = 'Less Filter';
    } else {
        panel.classList.add('hidden');
        if (arrow) arrow.classList.remove('rotate-180');
        if (text) text.textContent = 'More Filter';
    }
}

// Global aliases to ensure both function signatures work seamlessly
window.toggleMoreFilters = toggleMoreFilters;
window.toggleMoreFilter = toggleMoreFilters;


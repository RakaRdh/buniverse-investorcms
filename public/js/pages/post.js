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
        flatpickr('#postFilterDateFrom', {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M Y"
        });
        flatpickr('#postFilterDateTo', {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M Y"
        });
    }
});

function toggleMoreFilter() {
    const panel = document.getElementById('moreFilterPanel');
    const icon = document.getElementById('moreFilterIcon');
    if (!panel) return;

    panel.classList.toggle('hidden');
    if (icon) {
        icon.classList.toggle('rotate-180');
    }
}


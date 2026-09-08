/**
 * Editorial Report: Live Name Filter & Active Tab CSV Export
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Live Filter by Name
    const searchInput = document.getElementById('reportSearchName');
    const tableBody = document.querySelector('.matrix-table tbody');
    const countBadge = document.getElementById('reportPersonnelCount');

    if (searchInput && tableBody) {
        const rows = tableBody.querySelectorAll('tr');
        const initialCount = rows.length;

        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            let visibleCount = 0;

            rows.forEach((row) => {
                // Skip empty state row if present
                if (row.querySelector('td[colspan]')) return;

                const nameCell = row.querySelector('td:first-child');
                const nameText = nameCell ? nameCell.textContent.toLowerCase() : '';

                if (nameText.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (countBadge) {
                countBadge.textContent = visibleCount + ' Personil Terdaftar';
            }
        });
    }

    // 2. Export CSV for Active Tab
    const exportBtn = document.getElementById('exportReportCsvBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            exportActiveTableToCsv();
        });
    }
});

function exportActiveTableToCsv() {
    const table = document.querySelector('.matrix-table');
    if (!table) return;

    const rows = [];
    
    // Header rows
    const headerRow = [];
    table.querySelectorAll('thead tr th').forEach((th) => {
        // Clean inner text (avoid newlines and extra spaces)
        let text = th.innerText.replace(/[\n\r]+/g, ' ').replace(/\s+/g, ' ').trim();
        headerRow.push('"' + text.replace(/"/g, '""') + '"');
    });
    rows.push(headerRow.join(','));

    // Body rows (only currently visible / matching search)
    table.querySelectorAll('tbody tr').forEach((tr) => {
        if (tr.style.display === 'none') return;
        if (tr.querySelector('td[colspan]')) return; // ignore empty message

        const rowData = [];
        tr.querySelectorAll('td').forEach((td) => {
            let text = td.innerText.replace(/[\n\r]+/g, ' ').replace(/\s+/g, ' ').trim();
            rowData.push('"' + text.replace(/"/g, '""') + '"');
        });
        if (rowData.length > 0) {
            rows.push(rowData.join(','));
        }
    });

    if (rows.length <= 1) {
        alert('Tidak ada data untuk diexport.');
        return;
    }

    const csvContent = '\uFEFF' + rows.join('\r\n'); // Add BOM for Excel UTF-8
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab') || 'author';
    const fromDate = urlParams.get('from') || '';
    const toDate = urlParams.get('to') || '';
    const filename = `editorial_report_${tab}_${fromDate}_${toDate}.csv`;

    const downloadLink = document.createElement('a');
    downloadLink.href = url;
    downloadLink.setAttribute('download', filename);
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
    URL.revokeObjectURL(url);
}

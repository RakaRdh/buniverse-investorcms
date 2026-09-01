/**
 * Push Notification Page Interactive Live Preview
 */

document.addEventListener('DOMContentLoaded', () => {
    const titleInput = document.getElementById('notifTitleInput');
    const bodyInput = document.getElementById('notifBodyInput');
    const previewTitle = document.getElementById('previewTitle');
    const previewBody = document.getElementById('previewBody');

    if (titleInput && previewTitle) {
        titleInput.addEventListener('input', (e) => {
            previewTitle.textContent = e.target.value || 'Judul pesan notifikasi....';
        });
    }

    if (bodyInput && previewBody) {
        bodyInput.addEventListener('input', (e) => {
            previewBody.textContent = e.target.value || 'Isi pesan notifikasi yang muncul di device user..';
        });
    }
});

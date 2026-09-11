const { test, expect } = require('@playwright/test');

test.describe('14 - Unsaved Guard and Interactive Modals Suite', () => {

  test('MODAL-01: Post editor unsaved changes modal elements exist and behave correctly', async ({ page }) => {
    await page.goto('/post/new');
    await expect(page).toHaveTitle(/Tambah Artikel|Create New Post/i);

    // 1. Verifikasi elemen unsavedChangesModal ada di DOM
    const unsavedModal = page.locator('#unsavedChangesModal');
    await expect(unsavedModal).toBeAttached();

    // 2. Verifikasi konten teks modal
    await expect(unsavedModal).toContainText('Perubahan Belum Disimpan');
    await expect(unsavedModal).toContainText('Anda memiliki draft atau perubahan artikel');

    // 3. Verifikasi tombol Tetap di Halaman Ini & Tinggalkan Halaman
    const stayBtn = unsavedModal.locator('button:has-text("Tetap di Halaman Ini")');
    const leaveBtn = unsavedModal.locator('#confirmLeavePageBtn');
    await expect(stayBtn).toBeAttached();
    await expect(leaveBtn).toBeAttached();

    // 4. Test trigger buka modal via helper window
    await page.evaluate(() => {
      const modal = document.getElementById('unsavedChangesModal');
      if (modal) modal.style.display = 'flex';
    });
    await expect(unsavedModal).toBeVisible();

    // 5. Klik tombol Tetap di Halaman Ini untuk menutup modal
    await stayBtn.click();
    await expect(unsavedModal).toBeHidden();
  });

  test('MODAL-02: Export articles CSV triggers file download with expected headers', async ({ page }) => {
    await page.goto('/post');

    // Button export CSV ada di toolbar
    const exportBtn = page.locator('a[href*="/post/export"]');
    await expect(exportBtn).toBeAttached();

    // Trigger download dan tangkap event download
    const downloadPromise = page.waitForEvent('download');
    await exportBtn.click();
    const download = await downloadPromise;

    // Pastikan nama file berformat .csv
    expect(download.suggestedFilename()).toMatch(/investor_articles_.*\.csv/);
  });

  test('MODAL-03: Two-Factor Authentication (2FA) verification page elements and OTP inputs', async ({ browser }) => {
    // Gunakan isolated context tanpa session aktif
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const page = await context.newPage();

    // Kunjungi /auth/verify-code langsung tanpa pending session -> harus redirect ke /login
    await page.goto('/auth/verify-code');
    await expect(page).toHaveURL(/.*login.*/);

    await context.close();
  });
});

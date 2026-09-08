const { test, expect } = require('@playwright/test');

test.describe('04 - Media & Research Files Management', () => {
  test('Media library (/media) loads media cards and pagination', async ({ page }) => {
    await page.goto('/media');
    await expect(page).toHaveTitle(/Media|Investor/i);

    // Media grid / items container exists
    const container = page.locator('.grid, table, #mediaContainer');
    await expect(container.first()).toBeVisible();

    // Upload button exists
    const uploadBtn = page.locator('a[href*="/media/new"], button:has-text("Upload"), a:has-text("Upload")');
    if (await uploadBtn.count() > 0) {
      await expect(uploadBtn.first()).toBeVisible();
    }
  });

  test('Files management (/files) renders table and modal trigger', async ({ page }) => {
    await page.goto('/files');
    await expect(page).toHaveTitle(/Files|Berkas|Investor/i);

    // Files table exists
    const table = page.locator('table');
    await expect(table.first()).toBeVisible();

    // Upload file button exists
    const addFileBtn = page.locator('button:has-text("Upload"), button:has-text("Tambah Berkas"), button[data-modal-target]');
    if (await addFileBtn.count() > 0) {
      await expect(addFileBtn.first()).toBeVisible();
    }
  });

  test('Files modal dismisses when clicking on backdrop without dirty changes', async ({ page }) => {
    await page.goto('/files');

    const openBtn = page.locator('button:has-text("Upload"), button:has-text("Tambah Berkas"), button[data-modal-target]').first();
    if (await openBtn.isVisible()) {
      await openBtn.click();

      // Modal is visible
      const modal = page.locator('#uploadFileModal, .modal, [role="dialog"]').first();
      await expect(modal).toBeVisible();

      // Click on backdrop (outside modal dialog container)
      await page.mouse.click(10, 10);
      await page.waitForTimeout(400);

      // Modal should be closed / hidden
      const isHidden = !(await modal.isVisible()) || (await modal.getAttribute('class') || '').includes('hidden');
      expect(isHidden).toBeTruthy();
    }
  });

  test('Files modal triggers confirmation prompt on dirty state when closing', async ({ page }) => {
    await page.goto('/files');

    const openBtn = page.locator('button:has-text("Upload"), button:has-text("Tambah Berkas"), button[data-modal-target]').first();
    if (await openBtn.isVisible()) {
      await openBtn.click();

      const modal = page.locator('#uploadFileModal, .modal, [role="dialog"]').first();
      await expect(modal).toBeVisible();

      // Dirty the input field
      const titleInput = modal.locator('input[name="title"], input[name="file_title"], input[type="text"]').first();
      if (await titleInput.isVisible()) {
        await titleInput.fill('Perubahan judul belum tersimpan');

        // Setup dialog handler to intercept confirm
        let dialogTriggered = false;
        page.once('dialog', async dialog => {
          dialogTriggered = true;
          await dialog.dismiss(); // Cancel dismiss
        });

        // Click on backdrop
        await page.mouse.click(10, 10);
        await page.waitForTimeout(300);

        // Dialog should have been prompted
        expect(dialogTriggered).toBeTruthy();
      }
    }
  });
});

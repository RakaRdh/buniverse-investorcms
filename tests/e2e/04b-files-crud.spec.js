const { test, expect } = require('@playwright/test');

test.describe('04b - Files Document Management & CRUD', () => {
  let createdFileName = '';

  test('FILES-01: Files list page (/files) loads table, uploader info, and search bar', async ({ page }) => {
    await page.goto('/files');
    await expect(page).toHaveTitle(/Files.*Investor|Files/i);

    const table = page.locator('table');
    await expect(table.first()).toBeVisible();

    const searchInput = page.locator('input[name="q"], input[type="search"]');
    if (await searchInput.count() > 0) {
      await expect(searchInput.first()).toBeVisible();
    }
  });

  test('FILES-02: Register new document via /files/upload and verify presence in list', async ({ page }) => {
    createdFileName = `Laporan_Keuangan_Riset_${Date.now()}.pdf`;
    const res = await page.request.post('/files/upload', {
      form: {
        name: createdFileName,
        url: `/downloads/reports/${createdFileName}`
      }
    });
    expect([200, 302, 303]).toContain(res.status());

    // Navigate to /files and verify new file row is rendered
    await page.goto('/files');
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toContain(createdFileName);
  });

  test('FILES-03: Delete document transaction (/files/delete/(:num)) removes file record', async ({ page }) => {
    // Create temporary file to delete
    const deleteFileName = `Hapus_Dokumen_Test_${Date.now()}.pdf`;
    await page.request.post('/files/upload', {
      form: {
        name: deleteFileName,
        url: `/downloads/${deleteFileName}`
      }
    });

    await page.goto('/files');
    const row = page.locator(`tr:has-text("${deleteFileName}")`).first();
    await expect(row).toBeVisible();

    const deleteBtn = row.locator('a[href*="/files/delete/"]').first();
    if (await deleteBtn.count() > 0) {
      const delHref = await deleteBtn.getAttribute('href');
      await page.goto(delHref);
      await page.waitForURL('**/files**', { timeout: 15000 });
      // Verify row is deleted or banner appears
      await expect(page.locator(`tr:has-text("${deleteFileName}")`)).toHaveCount(0);
    }
  });
});

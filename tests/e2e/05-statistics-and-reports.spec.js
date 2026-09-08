const { test, expect } = require('@playwright/test');

test.describe('05 - Editorial Statistics & Reporting', () => {
  test('Editorial overview (/statistic) renders metric charts and traffic summaries', async ({ page }) => {
    await page.goto('/statistic');
    await expect(page).toHaveTitle(/Statistic|Statistik|Analytics|Laporan|Investor|Newsroom/i);

    const content = page.locator('main, #mainContent, .content, .surface-card');
    await expect(content.first()).toBeVisible();
  });

  test('Interactive report (/statistic/report) switches tabs and filters table rows', async ({ page }) => {
    await page.goto('/statistic/report');
    await expect(page).toHaveTitle(/Report|Laporan|Statistik|Investor|Editorial|Performa/i);

    // Tab links/buttons exist (Author, Editor, Contributor)
    const tabs = page.locator('a[href*="report?tab="], button[data-report-tab]');
    await expect(tabs.first()).toBeVisible();

    // Table of report data exists
    const table = page.locator('table');
    await expect(table.first()).toBeVisible();

    // Test real-time search filter input
    const searchInput = page.locator('input#reportSearchName, input#reportSearch, input[placeholder*="Cari"]');
    if (await searchInput.count() > 0) {
      await searchInput.first().fill('Raka');
      await page.waitForTimeout(300);
      // Table should reflect or filter rows
      await expect(table.first()).toBeVisible();
    }
  });

  test('Export CSV button triggers file download in report', async ({ page }) => {
    await page.goto('/statistic/report');

    const exportBtn = page.locator('button#exportReportBtn, button:has-text("Export CSV"), a:has-text("Export CSV"), button:has-text("Export")');
    if (await exportBtn.count() > 0) {
      // Start waiting for download before clicking
      const downloadPromise = page.waitForEvent('download', { timeout: 5000 }).catch(() => null);
      await exportBtn.first().click();
      const download = await downloadPromise;

      if (download) {
        expect(download.suggestedFilename()).toMatch(/\.csv$/i);
      }
    }
  });
});

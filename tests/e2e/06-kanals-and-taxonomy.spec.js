const { test, expect } = require('@playwright/test');

test.describe('06 - Kanals & Taxonomy Structure', () => {
  test('Master kanal list (/kanal/master) renders existing categories', async ({ page }) => {
    await page.goto('/kanal/master');
    await expect(page).toHaveTitle(/Kanal|Kategori|Investor/i);

    const table = page.locator('table');
    await expect(table.first()).toBeVisible();

    // Verify key kanals exist (e.g. Market, Finance)
    const tableText = await table.first().innerText();
    expect(tableText).toMatch(/Market|Finance|Investasi|Saham/i);
  });

  test('Sub-kanal page (/kanal/sub) displays cascading parent channels', async ({ page }) => {
    await page.goto('/kanal/sub');
    await expect(page).toHaveTitle(/Sub Kanal|Kategori|Investor/i);

    const table = page.locator('table');
    await expect(table.first()).toBeVisible();
  });

  test('Kanal statistics (/kanal/stats) displays performance matrix', async ({ page }) => {
    await page.goto('/kanal/stats');
    await expect(page).toHaveTitle(/Kanal|Statistik|Investor/i);

    const content = page.locator('main, .content, table');
    await expect(content.first()).toBeVisible();
  });
});

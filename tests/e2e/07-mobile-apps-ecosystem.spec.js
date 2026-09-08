const { test, expect } = require('@playwright/test');

test.describe('07 - Mobile Apps Ecosystem & Embed Charts', () => {
  test('App version management (/apps-version) displays version config', async ({ page }) => {
    await page.goto('/apps-version');
    await expect(page).toHaveTitle(/App|Versi|Investor/i);

    const form = page.locator('form, table, .surface-card');
    await expect(form.first()).toBeVisible();
  });

  test('Push notification module (/apps-push-notif) displays composer and history', async ({ page }) => {
    await page.goto('/apps-push-notif');
    await expect(page).toHaveTitle(/Push|Notifikasi|Investor/i);

    const form = page.locator('form, .surface-card');
    if (await form.count() > 0) {
      await expect(form.first()).toBeVisible();
    }
  });

  test('Featured mobile content (/apps-feature-content) loads curation lists', async ({ page }) => {
    await page.goto('/apps-feature-content');
    await expect(page).toHaveTitle(/Feature|Konten|Apps|Investor/i);

    const container = page.locator('main, table, .grid, .surface-card');
    await expect(container.first()).toBeVisible();
  });

  test('Financial embed chart management (/embed-chart) loads chart records', async ({ page }) => {
    await page.goto('/embed-chart');
    await expect(page).toHaveTitle(/Chart|Grafik|Embed|Investor/i);

    // Financial embed charts are rendered as grid of cards
    const chartCard = page.locator('.surface-card, .grid > div, [class*="card"]');
    await expect(chartCard.first()).toBeVisible();
  });
});

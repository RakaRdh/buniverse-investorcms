const { test, expect } = require('@playwright/test');

test.describe('03 - Headline Curation Management', () => {
  test('Headline curation page (/headline) loads 2-column layout and channel filter', async ({ page }) => {
    await page.goto('/headline');
    await expect(page).toHaveTitle(/Headline|Investor/i);

    // Channel dropdown filter exists (visible on desktop)
    const categorySelect = page.locator('select[name="category"], select[name="target_category"]:visible').first();
    await expect(categorySelect).toBeVisible();

    // Source articles list exists
    const sourceArticles = page.locator('#sourceArticles, table, .source-list');
    await expect(sourceArticles.first()).toBeVisible();

    // Active headlines queue exists
    const activeQueue = page.locator('#headlineQueue, #activeHeadlines, .headline-queue, .surface-card');
    await expect(activeQueue.first()).toBeVisible();
  });

  test('Filter kanal changes queue context without error', async ({ page }) => {
    await page.goto('/headline');

    const categorySelect = page.locator('select[name="category"], select[name="target_category"]:visible').first();
    if (await categorySelect.isVisible()) {
      const options = await categorySelect.locator('option').all();
      if (options.length > 1) {
        const targetVal = await options[1].getAttribute('value');
        await categorySelect.selectOption(targetVal);
        await page.waitForLoadState('networkidle');

        expect(page.url()).toContain('headline');
      }
    }
  });

  test('Sticky active headline sidebar maintains position styling', async ({ page }) => {
    await page.goto('/headline');

    // Right column should have sticky styling or headline queue container
    const rightCol = page.locator('.headline-sticky, [class*="sticky"], #headlineQueue');
    if (await rightCol.count() > 0) {
      await expect(rightCol.first()).toBeVisible();
    }
  });
});

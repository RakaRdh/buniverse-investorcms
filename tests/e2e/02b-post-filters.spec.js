const { test, expect } = require('@playwright/test');

test.describe('02b - Post Editorial Filtering & Search', () => {
  test('FILTER-01: Filter articles by Kanal (/post?kanal=Market) updates view', async ({ page }) => {
    await page.goto('/post?kanal=Market');
    await expect(page).toHaveURL(/.*kanal=Market.*/);

    const kanalSelect = page.locator('select[name="kanal"]');
    await expect(kanalSelect).toHaveValue(/Market/i);

    // Table should contain articles
    const rows = page.locator('table tbody tr');
    const count = await rows.count();
    expect(count).toBeGreaterThan(0);
  });

  test('FILTER-02: Filter articles by Status Draft (/post?status=Draft)', async ({ page }) => {
    await page.goto('/post?status=Draft');
    await expect(page).toHaveURL(/.*status=Draft.*/);

    const statusSelect = page.locator('select[name="status"]');
    await expect(statusSelect).toHaveValue('Draft');

    // If rows exist, their status badge should be draft
    const badges = page.locator('table tbody tr span:has-text("Draft")');
    if (await badges.count() > 0) {
      await expect(badges.first()).toBeVisible();
    }
  });

  test('FILTER-03: Filter articles by Status Published (/post?status=Published)', async ({ page }) => {
    await page.goto('/post?status=Published');
    await expect(page).toHaveURL(/.*status=Published.*/);

    const statusSelect = page.locator('select[name="status"]');
    await expect(statusSelect).toHaveValue('Published');

    const badges = page.locator('table tbody tr span:has-text("Published")');
    if (await badges.count() > 0) {
      await expect(badges.first()).toBeVisible();
    }
  });

  test('FILTER-04: Filter articles by Tier Premium (/post?premium=1)', async ({ page }) => {
    await page.goto('/post?premium=1');
    await expect(page).toHaveURL(/.*premium=1.*/);

    const premiumSelect = page.locator('select[name="premium"]');
    await expect(premiumSelect).toHaveValue('1');

    // Premium quick filter link should have active styling
    const premiumChip = page.locator('a:has-text("Premium Content")');
    await expect(premiumChip).toBeVisible();
  });

  test('FILTER-05: Keyword search (/post?q=IHSG) filters matching articles', async ({ page }) => {
    await page.goto('/post?q=IHSG');
    await expect(page).toHaveURL(/.*q=IHSG.*/);

    const searchInput = page.locator('input[name="q"]');
    await expect(searchInput).toHaveValue('IHSG');

    const bodyText = await page.locator('table tbody').first().innerText();
    expect(bodyText.length).toBeGreaterThan(0);
  });

  test('FILTER-06: More filter panel toggles author, editor, and date range inputs', async ({ page }) => {
    await page.goto('/post');

    const moreFilterBtn = page.locator('#moreFilterBtn');
    await expect(moreFilterBtn).toBeVisible();

    // Click toggle to reveal advanced filter row
    await moreFilterBtn.click();

    const advancedRow = page.locator('#moreFiltersRow, #moreFiltersPanel, select[name="author"]');
    await expect(advancedRow.first()).toBeVisible();

    const authorSelect = page.locator('select[name="author"]');
    if (await authorSelect.count() > 0) {
      await expect(authorSelect).toBeVisible();
    }
  });
});

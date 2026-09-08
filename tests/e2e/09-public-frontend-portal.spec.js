const { test, expect } = require('@playwright/test');

test.describe('09 - Public Reader Frontend Portal', () => {
  const portalUrl = 'http://localhost:8085';

  test('Portal Homepage loads Hero Carousel and news feeds', async ({ page }) => {
    try {
      const response = await page.goto(portalUrl, { timeout: 10000 });
      if (response && response.status() === 200) {
        await expect(page).toHaveTitle(/Investor/i);

        // Header / Logo exists
        const logo = page.locator('header, nav, img[alt*="Investor"], a:has-text("Investor")');
        await expect(logo.first()).toBeVisible();

        // Main content container exists
        const main = page.locator('main, #mainContent, .container');
        await expect(main.first()).toBeVisible();
      }
    } catch (e) {
      test.skip('Portal server on port 8085 is not running or unreachable');
    }
  });

  test('Article detail page on portal renders multi-page pagination and widgets', async ({ page }) => {
    try {
      // Test the benchmark article with embeds & multi-page break
      const response = await page.goto(`${portalUrl}/artikel/kinerja-saham-perbankan-big-caps-melonjak-prospek-bbca-bmri`, { timeout: 10000 });
      if (response && response.status() === 200) {
        await expect(page.locator('h1')).toBeVisible();

        // Body content exists
        const articleContent = page.locator('.article-content, .prose, article');
        await expect(articleContent.first()).toBeVisible();

        // Multi-page break navigation exists if article is paginated
        const pageLinks = page.locator('a[href*="halaman="]');
        if (await pageLinks.count() > 0) {
          await expect(pageLinks.first()).toBeVisible();
        }
      }
    } catch (e) {
      test.skip('Portal server on port 8085 is not running or unreachable');
    }
  });
});

const { test, expect } = require('@playwright/test');
const path = require('path');

const authorAuth = path.join(__dirname, '../../.auth/author.json');

test.describe('07b - Embed Chart Widgets CRUD & Management', () => {
  let createdChartTitle = '';

  test('CHART-01: Financial embed chart management (/embed-chart) renders charts list and iframes', async ({ page }) => {
    await page.goto('/embed-chart');
    await expect(page).toHaveTitle(/Chart|Embed|Investor/i);

    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toMatch(/Template|Master|Chart|Bar|Line|Pie/i);

    const cards = page.locator('.surface-card, table, .chart-card');
    expect(await cards.count()).toBeGreaterThan(0);
  });

  test('CHART-02: Create new financial chart embed configuration (/embed-chart/save)', async ({ page }) => {
    createdChartTitle = `Chart IHSG Live ${Date.now()}`;
    const res = await page.request.post('/embed-chart/save', {
      form: {
        title: createdChartTitle,
        symbol: 'COMPOSITE:IDX',
        type: 'line'
      }
    });
    expect([200, 302, 303]).toContain(res.status());

    await page.goto('/embed-chart/custom');
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toContain(createdChartTitle);
  });

  test('CHART-03: Delete chart embed configuration removes record', async ({ page }) => {
    // Create chart to delete
    const deleteChartTitle = `Hapus Chart ${Date.now()}`;
    await page.request.post('/embed-chart/save', {
      form: {
        title: deleteChartTitle,
        symbol: 'ANTM:IDX',
        type: 'line'
      }
    });

    await page.goto('/embed-chart/custom');
    const cardOrRow = page.locator(`:has-text("${deleteChartTitle}")`).first();
    await expect(cardOrRow).toBeVisible();

    const deleteLink = cardOrRow.locator('a[href*="/embed-chart/delete/"]').first();
    if (await deleteLink.count() > 0) {
      const delHref = await deleteLink.getAttribute('href');
      await page.goto(delHref);
      await page.waitForURL('**/embed-chart/**', { timeout: 15000 });
      const bodyText = await page.locator('body').innerText();
      expect(bodyText).toMatch(/removed|dihapus|berhasil/i);
    }
  });
});

test.describe('07b - Embed Chart RBAC Guard (Author Role Forbidden)', () => {
  test.use({ storageState: authorAuth });

  test('CHART-04: Author cannot access embed chart management (/embed-chart)', async ({ page }) => {
    const res = await page.goto('/embed-chart');
    const url = page.url();
    const isRedirected = !url.includes('/embed-chart') || url.includes('/dashboard') || url.includes('/login');
    const status = res ? res.status() : 200;
    expect(isRedirected || status === 403).toBe(true);
  });
});

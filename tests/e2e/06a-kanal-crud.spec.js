const { test, expect } = require('@playwright/test');
const path = require('path');

const authorAuth = path.join(__dirname, '../../.auth/author.json');

test.describe('06a - Kanal & Taxonomy Hierarchy CRUD', () => {
  let createdMasterName = '';
  let createdSubName = '';

  test('KANAL-01: Master kanal management (/kanal/master) renders kanals and accepts new master kanal', async ({ page }) => {
    await page.goto('/kanal/master');
    await expect(page).toHaveTitle(/Master Kanal|Kanal/i);

    createdMasterName = `KanalTest${Date.now()}`;
    const res = await page.request.post('/kanal/master/save', {
      form: {
        name: createdMasterName
      }
    });
    expect([200, 302, 303]).toContain(res.status());

    // Verify presence on master kanal page
    await page.goto('/kanal/master');
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toContain(createdMasterName);
  });

  test('KANAL-02: Sub-kanal management (/kanal/sub) creates child kanal linked to parent', async ({ page }) => {
    await page.goto('/kanal/sub');
    await expect(page).toHaveTitle(/Sub Kanal|Kanal/i);

    createdSubName = `SubKanalTest${Date.now()}`;
    const res = await page.request.post('/kanal/sub/save', {
      form: {
        parent_id: '1', // Market master kanal
        name: createdSubName
      }
    });
    expect([200, 302, 303]).toContain(res.status());

    await page.goto('/kanal/sub');
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toContain(createdSubName);
  });

  test('KANAL-03: Kanal performance stats (/kanal/stats) renders metric distribution', async ({ page }) => {
    await page.goto('/kanal/stats');
    await expect(page).toHaveTitle(/Performa|Kanal/i);

    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toMatch(/Performa Kanal|Distribusi Audiens|Total/i);

    const table = page.locator('table');
    await expect(table.first()).toBeVisible();
  });
});

test.describe('06a - Kanal RBAC Guard (Author Role Forbidden)', () => {
  test.use({ storageState: authorAuth });

  test('KANAL-04: Author cannot access master kanal configuration (/kanal/master)', async ({ page }) => {
    const res = await page.goto('/kanal/master');
    // Role filter rejects Author and redirects or returns 403
    const url = page.url();
    const isRedirected = !url.includes('/kanal/master') || url.includes('/dashboard') || url.includes('/login');
    const status = res ? res.status() : 200;
    expect(isRedirected || status === 403).toBe(true);
  });
});

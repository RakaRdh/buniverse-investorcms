const { test, expect } = require('@playwright/test');
const path = require('path');
const fs = require('fs');

test.describe('04a - Media Asset Management & CRUD', () => {
  const fixtureImgPath = path.join(__dirname, '../fixtures/test_image_1x1.png');
  let createdMediaTitle = '';

  test('MEDIA-01: Media library (/media) renders assets grid, search bar, and pagination', async ({ page }) => {
    await page.goto('/media');
    await expect(page).toHaveTitle(/Media.*Investor|Media/i);

    // Search bar must be visible
    const searchInput = page.locator('input[name="q"], input[type="search"]');
    await expect(searchInput.first()).toBeVisible();

    // Media grid or cards must be present
    const mediaCards = page.locator('.media-card, .surface-card, .group.relative');
    expect(await mediaCards.count()).toBeGreaterThan(0);
  });

  test('MEDIA-02: Media search filters assets by query (/media?q=IHSG)', async ({ page }) => {
    await page.goto('/media?q=IHSG');
    await expect(page).toHaveURL(/.*q=IHSG.*/);

    const searchInput = page.locator('input[name="q"]');
    await expect(searchInput).toHaveValue('IHSG');
  });

  test('MEDIA-03: Create new media asset via /media/new with metadata form', async ({ page }) => {
    await page.goto('/media/new');
    await expect(page).toHaveTitle(/Upload.*Media|Media/i);

    createdMediaTitle = `Media Asset ${Date.now()}`;
    await page.fill('input[name="title"]', createdMediaTitle);

    const captionInput = page.locator('textarea[name="caption"], input[name="caption"]');
    if (await captionInput.count() > 0) {
      await captionInput.first().fill('Keterangan foto aset berita pasar modal.');
    }

    const sourceInput = page.locator('input[name="source"]');
    if (await sourceInput.count() > 0) {
      await sourceInput.first().fill('B-Universe / Investor.id');
    }

    // Submit form
    const submitBtn = page.locator('button[type="submit"]').first();
    await submitBtn.click();

    await page.waitForURL('**/media**', { timeout: 15000 });
    const bodyText = await page.locator('body').innerText();
    expect(bodyText.includes(createdMediaTitle) || /berhasil disimpan|sukses/i.test(bodyText)).toBeTruthy();
  });

  test('MEDIA-04: Upload media asset with multipart file attachment', async ({ page }) => {
    const fileBuffer = fs.readFileSync(fixtureImgPath);
    const res = await page.request.post('/media/save', {
      multipart: {
        title: `Upload Fixture Image ${Date.now()}`,
        caption: 'Automated test fixture image upload.',
        source: 'Automated Playwright Suite',
        media_file: {
          name: 'fixture_sample.png',
          mimeType: 'image/png',
          buffer: fileBuffer
        }
      }
    });
    expect([200, 302, 303]).toContain(res.status());
    const body = await res.text();
    expect(body).toMatch(/berhasil disimpan/i);
  });

  test('MEDIA-05: Edit existing media item updates title and metadata', async ({ page }) => {
    await page.goto('/media');
    const editLink = page.locator('a[href*="/media/edit/"]').first();
    
    if (await editLink.count() > 0) {
      const editHref = await editLink.getAttribute('href');
      await page.goto(editHref);

      const titleInput = page.locator('input[name="title"]');
      const updatedMediaTitle = `Updated Media ${Date.now()}`;
      await titleInput.fill(updatedMediaTitle);

      const submitBtn = page.locator('button[type="submit"]').first();
      await submitBtn.click();

      await page.waitForURL('**/media**', { timeout: 15000 });
      const bodyText = await page.locator('body').innerText();
      expect(bodyText).toMatch(/updated|berhasil/i);
    }
  });

  test('MEDIA-06: Delete media asset removes it and redirects to /media', async ({ page }) => {
    // Create a sacrificial media item to delete
    const res = await page.request.post('/media/save', {
      form: {
        title: `Delete Media Target ${Date.now()}`,
        caption: 'Aset media untuk dihapus.',
        source: 'Playwright Test'
      }
    });
    expect([200, 302, 303]).toContain(res.status());

    // Call delete on latest media item
    await page.goto('/media');
    const deleteLink = page.locator('a[href*="/media/delete/"]').first();
    if (await deleteLink.count() > 0) {
      const delHref = await deleteLink.getAttribute('href');
      await page.goto(delHref);
      await page.waitForURL('**/media**', { timeout: 15000 });
      const bodyText = await page.locator('body').innerText();
      expect(bodyText).toMatch(/removed|dihapus|berhasil/i);
    }
  });
});

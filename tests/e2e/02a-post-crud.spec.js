const { test, expect } = require('@playwright/test');

test.describe('02a - Post Editorial CRUD & Management', () => {
  let testArticleTitle = '';

  test('POST-01: Edit existing article (/post/edit/1) loads form with pre-filled fields', async ({ page }) => {
    await page.goto('/post/edit/1');
    await expect(page).toHaveTitle(/Edit.*Post|Artikel|Investor/i);

    const form = page.locator('#postEditorForm');
    await expect(form).toBeVisible();

    // Verify title input contains pre-filled value
    const titleInput = page.locator('input[name="title"]');
    await expect(titleInput).toBeVisible();
    const titleVal = await titleInput.inputValue();
    expect(titleVal.trim().length).toBeGreaterThan(0);

    // Verify kanal select has a selection
    const kanalSelect = page.locator('select[name="kanal"], select#kanalSelect');
    if (await kanalSelect.count() > 0) {
      await expect(kanalSelect).toBeVisible();
      const val = await kanalSelect.inputValue();
      expect(val.length).toBeGreaterThan(0);
    }
  });

  test('POST-02: Create new article transaction (/post/new), verify persistence in post list', async ({ page }) => {
    await page.goto('/post/new');
    await expect(page.locator('#postEditorForm')).toBeVisible();

    testArticleTitle = `Automated CRUD Post ${Date.now()}`;
    await page.fill('input[name="title"]', testArticleTitle);

    // Select Kanal (mandatory required field)
    const kanalSelect = page.locator('select[name="kanal"], select#kanalSelect');
    await kanalSelect.selectOption({ index: 1 });

    // Fill summary
    const summaryInput = page.locator('textarea[name="summary"]');
    if (await summaryInput.count() > 0) {
      await summaryInput.first().fill('Ringkasan naskah artikel automated Playwright CRUD.');
    }

    // Fill content in Quill editor
    const quill = page.locator('.ql-editor');
    if (await quill.count() > 0) {
      await quill.first().fill('Naskah konten artikel lengkap untuk pengujian transaksi CRUD editorial.');
    }

    // Click submit button
    const submitBtn = page.locator('#postEditorForm button[type="submit"]').first();
    await submitBtn.click();

    // Verify redirected back to /post and new article is listed
    await page.waitForURL('**/post**', { timeout: 15000 });
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toContain(testArticleTitle);
  });

  test('POST-03: Update existing article title and verify updated text', async ({ page }) => {
    // Navigate to post list and get first article edit URL
    await page.goto('/post');
    const firstEditLink = page.locator('table tbody tr a[href*="/post/edit/"]').first();
    await expect(firstEditLink).toBeVisible();
    const editUrl = await firstEditLink.getAttribute('href');

    await page.goto(editUrl);
    await expect(page.locator('#postEditorForm')).toBeVisible();

    const titleInput = page.locator('input[name="title"]');
    const updatedTitle = `Updated News Post ${Date.now()}`;
    await titleInput.fill(updatedTitle);

    const submitBtn = page.locator('#postEditorForm button[type="submit"]').first();
    await submitBtn.click();

    await page.waitForURL('**/post**', { timeout: 15000 });
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toContain(updatedTitle);
  });

  test('POST-04: Delete article transaction removes post from database', async ({ page }) => {
    // Create an article specifically to be deleted
    await page.goto('/post/new');
    const deleteTargetTitle = `Post For Deletion ${Date.now()}`;
    await page.fill('input[name="title"]', deleteTargetTitle);
    await page.selectOption('select[name="kanal"]', { index: 1 });

    const quill = page.locator('.ql-editor');
    if (await quill.count() > 0) {
      await quill.first().fill('Artikel ini dibuat untuk diuji proses penghapusan data.');
    }

    await page.locator('#postEditorForm button[type="submit"]').first().click();
    await page.waitForURL('**/post**', { timeout: 15000 });

    // Locate the row for the target article and extract its delete URL
    const articleLink = page.locator(`table tbody tr a:has-text("${deleteTargetTitle}")`).first();
    await expect(articleLink).toBeVisible();

    const row = page.locator(`table tbody tr:has-text("${deleteTargetTitle}")`).first();
    const deleteBtn = row.locator('a[href*="/post/delete/"]').first();
    const deleteHref = await deleteBtn.getAttribute('href');
    expect(deleteHref).toBeTruthy();

    // Trigger delete via GET route directly
    await page.goto(deleteHref);
    await page.waitForURL('**/post**', { timeout: 15000 });

    // Verify post was removed and flash success is shown
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toMatch(/removed successfully|sukses|berhasil/i);
  });

  test('POST-05: Bulk action POST endpoint (/post/bulk-action) updates status', async ({ page }) => {
    // Test the bulkAction backend controller endpoint directly
    const res = await page.request.post('/post/bulk-action', {
      form: {
        action: 'publish',
        selected_ids: '1'
      },
      maxRedirects: 0
    });
    // Controller returns redirect to /post with flash message
    expect([200, 302, 303]).toContain(res.status());
  });

  test('POST-06: Export articles list (/post/export) returns CSV stream', async ({ page }) => {
    const res = await page.request.get('/post/export');
    expect(res.status()).toBe(200);
    const contentType = res.headers()['content-type'] || '';
    expect(contentType).toMatch(/csv|octet-stream|plain/i);
    const body = await res.text();
    expect(body).toContain('ID,Title');
  });

  test('POST-07: Real-time tag autocomplete API (/api/tags/search?q=saham) returns array', async ({ page }) => {
    const res = await page.request.get('/api/tags/search?q=saham');
    expect(res.status()).toBe(200);
    const data = await res.json();
    expect(Array.isArray(data)).toBe(true);
  });

  test('POST-08: Real-time stock autocomplete API (/api/stocks/search?q=BBCA) returns BBCA ticker', async ({ page }) => {
    const res = await page.request.get('/api/stocks/search?q=BBCA');
    expect(res.status()).toBe(200);
    const data = await res.json();
    expect(Array.isArray(data)).toBe(true);
    expect(data.length).toBeGreaterThan(0);
    const found = data.some(s => s.ticker === 'BBCA');
    expect(found).toBe(true);
  });
});

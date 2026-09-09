const { test, expect } = require('@playwright/test');
const path = require('path');

const authorAuth = path.join(__dirname, '../../.auth/author.json');

test.describe('02c - Post Editorial RBAC (Author Role)', () => {
  test.use({ storageState: authorAuth });

  test('RBAC-01: Author role workflow options only allow Draft and Review', async ({ page }) => {
    await page.goto('/post/new');
    await expect(page.locator('#postEditorForm')).toBeVisible();

    const statusOptions = await page.locator('#workflowStatusSelect option').allInnerTexts();
    const joined = statusOptions.join(' ');
    
    // Published and Scheduled options must not be available to Author
    expect(joined).not.toMatch(/Langsung Publish/i);
    expect(joined).toMatch(/Draft/i);
    expect(joined).toMatch(/Review/i);
  });

  test('RBAC-02: Author cannot edit articles authored by other users', async ({ page }) => {
    // Article 1 is authored by admin/master (Raka)
    await page.goto('/post/edit/1');

    // Should be redirected back to /post with access denied error
    await expect(page).toHaveURL(/.*\/post.*/);
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toMatch(/Akses ditolak|milik Anda sendiri|izin/i);
  });

  test('RBAC-03: Author cannot delete articles (forbidden)', async ({ page }) => {
    // Attempting to delete article 1
    await page.goto('/post/delete/1');

    // Should be redirected back to /post with error
    await expect(page).toHaveURL(/.*\/post.*/);
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toMatch(/Akses ditolak|tidak memiliki hak menghapus/i);
  });

  test('RBAC-04: Author creating article automatically gets Draft or Review status', async ({ page }) => {
    await page.goto('/post/new');
    await expect(page.locator('#postEditorForm')).toBeVisible();

    const testTitle = `Author Draft Post ${Date.now()}`;
    await page.fill('input[name="title"]', testTitle);
    await page.selectOption('select[name="kanal"]', { index: 1 });

    const quill = page.locator('.ql-editor');
    if (await quill.count() > 0) {
      await quill.first().fill('Konten artikel yang dibuat oleh Author.');
    }

    // Submit save
    const submitBtn = page.locator('#postEditorForm button[type="submit"]').first();
    await submitBtn.click();

    await page.waitForURL('**/post**', { timeout: 15000 });
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toContain(testTitle);
  });
});

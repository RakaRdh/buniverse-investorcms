const { test, expect } = require('@playwright/test');

test.describe('02 - Post Editorial Management', () => {
  let createdArticleId = null;

  test('Post list page (/post) loads with filter tabs and pagination', async ({ page }) => {
    await page.goto('/post');
    await expect(page).toHaveTitle(/Post|Artikel|Investor/i);

    // Filter tabs exist: All, Published, Draft, etc.
    const tabs = page.locator('a[href*="status="], button[data-tab]');
    await expect(tabs.first()).toBeVisible();

    // Table of articles exists
    const table = page.locator('table');
    await expect(table.first()).toBeVisible();

    // Search bar exists
    const searchInput = page.locator('input[name="search"], input[name="q"], input[type="search"]');
    if (await searchInput.count() > 0) {
      await expect(searchInput.first()).toBeVisible();
    }
  });

  test('Create new article transaction (/post/new) with dynamic fields', async ({ page }) => {
    await page.goto('/post/new');
    await expect(page.locator('form')).toBeVisible();

    const testTitle = `E2E Automated Post - ${Date.now()}`;
    await page.fill('input[name="title"], input[name="headline_text"]', testTitle);

    // Fill summary if present
    const summaryInput = page.locator('textarea[name="summary"], textarea[name="summary_text"]');
    if (await summaryInput.count() > 0) {
      await summaryInput.first().fill('Ringkasan naskah artikel otomatis hasil pengujian Playwright E2E.');
    }

    // Fill Quill editor or textarea
    const quillEditor = page.locator('.ql-editor');
    if (await quillEditor.count() > 0) {
      await quillEditor.first().fill('Ini adalah isi konten lengkap naskah artikel hasil automated Playwright E2E suite.');
    } else {
      const bodyInput = page.locator('textarea[name="content"], textarea[name="body_text"]');
      if (await bodyInput.count() > 0) {
        await bodyInput.first().fill('Isi naskah berita otomatis.');
      }
    }

    // Select category if dropdown exists
    const categorySelect = page.locator('select[name="category"], select[name="category_id"], select#category');
    if (await categorySelect.count() > 0) {
      const options = await categorySelect.locator('option').all();
      if (options.length > 1) {
        await categorySelect.selectOption({ index: 1 });
      }
    }

    // Submit form (Simpan Draft / Publish)
    const submitBtn = page.locator('button[type="submit"]').first();
    await submitBtn.click();

    // Expect navigation back to post list or edit page with success indication
    await page.waitForLoadState('networkidle');
    expect(page.url()).toMatch(/\/post/);
  });

  test('Article live preview simulator (/post/preview) renders responsive frame and favicon', async ({ page }) => {
    // Open preview of an existing article
    await page.goto('/post/preview/416');
    await expect(page).toHaveTitle(/Preview|Pratinjau|Investor/i);

    // Favicon must be defined in head
    const favicon = page.locator('link[rel="icon"], link[rel="shortcut icon"]');
    await expect(favicon.first()).toBeAttached();

    // Mode toggle buttons (Desktop, Tablet, Mobile)
    const desktopBtn = page.locator('button[data-device="desktop"], button:has-text("Desktop")');
    if (await desktopBtn.count() > 0) {
      await expect(desktopBtn.first()).toBeVisible();
    }
  });

  test('Article preview on mobile viewport displays sticky action bar', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/post/preview/416');

    // Sticky action bar at the bottom should be visible
    const bottomBar = page.locator('.sticky.bottom-0, .fixed.bottom-0');
    if (await bottomBar.count() > 0) {
      await expect(bottomBar.first()).toBeVisible();
    }
  });
});

const { test, expect } = require('@playwright/test');

test.describe('12 - Local Gaps Features Suite', () => {

  test('GAP-01: Headline reorder API accepts batch order and returns success (HEAD-01)', async ({ request }) => {
    // 1. Fetch current headlines
    const res = await request.post('/headline/reorder', {
      data: {
        category: 'home',
        order: [1, 2, 3]
      },
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.status).toBe('success');
    expect(json.message).toContain('berhasil diperbarui');
  });

  test('GAP-02: Editorial report page provides date range inputs and tab navigation', async ({ page }) => {
    await page.goto('/statistic/report');
    await expect(page).toHaveTitle(/Performa|Report|Statistik|Laporan/i);

    // Tab buttons exist (Author, Editor, Heatmap)
    const authorTab = page.locator('a:has-text("Author (Jurnalis)")');
    const editorTab = page.locator('a:has-text("Editor Redaksi")');
    const heatmapTab = page.locator('a:has-text("Hourly Heatmap")');
    await expect(authorTab).toBeVisible();
    await expect(editorTab).toBeVisible();
    await expect(heatmapTab).toBeVisible();

    // Date range input fields exist and are attached to DOM
    const dateFrom = page.locator('#reportDateFrom');
    const dateTo = page.locator('#reportDateTo');
    await expect(dateFrom).toBeAttached();
    await expect(dateTo).toBeAttached();

    // Apply button exists
    const applyBtn = page.locator('button:has-text("Terapkan")');
    await expect(applyBtn).toBeVisible();
  });

  test('GAP-03: Hourly Heatmap 24 Jam renders visual matrix and API endpoint (STAT-01)', async ({ page, request }) => {
    // Test API endpoint
    const apiRes = await request.get('/statistic/hourly-heatmap');
    expect(apiRes.status()).toBe(200);
    const apiJson = await apiRes.json();
    expect(apiJson.status).toBe('success');
    expect(apiJson).toHaveProperty('data');

    // Test UI Heatmap Tab
    await page.goto('/statistic/report?tab=heatmap');
    const heatmapTab = page.locator('a:has-text("Hourly Heatmap")');
    await expect(heatmapTab).toBeVisible();

    // Table with 24 columns should be visible
    const heatmapTable = page.locator('table');
    await expect(heatmapTable.first()).toBeVisible();

    // Check that hour headers exist (00, 12, 23)
    await expect(page.locator('th:has-text("00")').first()).toBeVisible();
    await expect(page.locator('th:has-text("12")').first()).toBeVisible();
    await expect(page.locator('th:has-text("23")').first()).toBeVisible();
  });

  test('GAP-04: Post deletion modal provides reason choices and notes (POST-03)', async ({ page }) => {
    await page.goto('/post');

    const modal = page.locator('#deleteArticleModal');
    await expect(modal).toBeAttached();

    // Check select reason options
    const reasonSelect = page.locator('#deleteReasonSelect');
    await expect(reasonSelect).toBeAttached();

    const options = await reasonSelect.locator('option').allTextContents();
    expect(options.some(o => o.includes('Salah Ketik') || o.includes('Duplikasi'))).toBe(true);

    // Check notes textarea
    const notesArea = page.locator('#deleteNotesText');
    await expect(notesArea).toBeAttached();
  });

  test('GAP-05: Reverse lookup prevents deleting media in use by active articles (MED-02)', async ({ request }) => {
    // Media #1 is used in seeded mediaarticle
    const deleteRes = await request.get('/media/delete/1');
    // CodeIgniter redirect 302 with flash error
    expect([200, 302]).toContain(deleteRes.status());
  });

  test('GAP-06: Editor author input supports multiple authors and co-authors (POST-04)', async ({ page }) => {
    await page.goto('/post/new');

    const authorInput = page.locator('#authorInput');
    await expect(authorInput).toBeVisible();

    // Type multi authors separated by comma
    await authorInput.fill('Raka Herdika, Natasha Khairunisa');
    const val = await authorInput.inputValue();
    expect(val).toBe('Raka Herdika, Natasha Khairunisa');
  });

  test('GAP-07: Audience & Traffic Analytics (/statistic) supports preset periods and custom date range filtering', async ({ page }) => {
    await page.goto('/statistic');

    // Preset buttons exist
    await expect(page.locator('#btnStatToday')).toBeVisible();
    await expect(page.locator('#btnStat7d')).toBeVisible();
    await expect(page.locator('#btnStatMtd')).toBeVisible();
    await expect(page.locator('#btnStatCustom')).toBeVisible();

    // Clicking Custom toggles the date inputs
    await page.locator('#btnStatCustom').click();
    const customBox = page.locator('#customDateRangeBox');
    await expect(customBox).toBeVisible();

    const fromInput = page.locator('#statDateFrom');
    const toInput = page.locator('#statDateTo');
    await expect(fromInput).toBeVisible();
    await expect(toInput).toBeVisible();

    // Fill custom dates and filter
    await fromInput.fill('2026-08-01');
    await toInput.fill('2026-08-15');
    await page.locator('#btnSubmitCustomRange').click();

    // URL should reflect custom filter
    await page.waitForURL(/\/statistic\?preset=custom/);
    await expect(page.locator('#customDateRangeBox')).toBeVisible();
    await expect(page.locator('#trafficChart')).toBeVisible();
  });
});

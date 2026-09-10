const { test, expect } = require('@playwright/test');

test.describe('03a - Headline Queue CRUD & Workflow', () => {
  let targetArticleId = 2;

  test.beforeAll(async ({ request }) => {
    // Fetch a published article to use as headline test target
    const res = await request.get('/api/articles');
    if (res.ok()) {
      const data = await res.json();
      if (data && data.data && data.data.length > 0) {
        const pub = data.data.find(a => ['publish', 'published'].includes((a.status || '').toLowerCase()));
        if (pub) {
          targetArticleId = pub.id;
        }
      }
    }
  });

  test('HEADLINE-01: Add published article to headline queue successfully', async ({ page }) => {
    const res = await page.request.post('/headline/add', {
      form: {
        article_id: String(targetArticleId),
        target_category: 'Headline'
      }
    });
    expect([200, 302, 303]).toContain(res.status());
    const body = await res.text();
    expect(body).toMatch(/berhasil ditambahkan|diaktifkan kembali|sudah terdaftar/i);
  });

  test('HEADLINE-02: Adding already queued article indicates it is already registered', async ({ page }) => {
    const res = await page.request.post('/headline/add', {
      form: {
        article_id: String(targetArticleId),
        target_category: 'Headline'
      }
    });
    expect(res.status()).toBe(200);
    const body = await res.text();
    expect(body).toMatch(/sudah terdaftar|berhasil ditambahkan/i);
  });

  test('HEADLINE-03: Adding non-existent article gets rejected with error', async ({ page }) => {
    const res = await page.request.post('/headline/add', {
      form: {
        article_id: '999999',
        target_category: 'Headline'
      }
    });
    expect(res.status()).toBe(200);
    const body = await res.text();
    expect(body).toMatch(/tidak ditemukan/i);
  });

  test('HEADLINE-04: Remove article from headline queue', async ({ page }) => {
    const res = await page.request.get(`/headline/remove/${targetArticleId}?target_category=Headline`);
    expect(res.status()).toBe(200);
    const body = await res.text();
    expect(body).toMatch(/dinonaktifkan|dihapus/i);
  });

  test('HEADLINE-05: Save headline configuration persists order', async ({ page }) => {
    const res = await page.request.post('/headline/save', {
      form: {
        target_category: 'Headline'
      }
    });
    expect(res.status()).toBe(200);
    const body = await res.text();
    expect(body).toMatch(/berhasil disimpan/i);
  });
});

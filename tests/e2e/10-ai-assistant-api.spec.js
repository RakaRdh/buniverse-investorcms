const { test, expect } = require('@playwright/test');

test.describe('10 - AI Assistant Editorial API Suite', () => {
  test('AI-01: Generate title API rejects empty body payload with 400', async ({ request }) => {
    const res = await request.post('/api/ai/title', {
      data: { body: '' }
    });
    expect(res.status()).toBe(400);
    const json = await res.json();
    expect(json.status).toBe('error');
  });

  test('AI-02: Generate summary API rejects empty body payload with 400', async ({ request }) => {
    const res = await request.post('/api/ai/summary', {
      data: { title: 'Judul', body: '' }
    });
    expect(res.status()).toBe(400);
    const json = await res.json();
    expect(json.status).toBe('error');
  });

  test('AI-03: Generate tags API rejects empty content payload with 400', async ({ request }) => {
    const res = await request.post('/api/ai/tags', {
      data: { body: '' }
    });
    expect(res.status()).toBe(400);
    const json = await res.json();
    expect(json.status).toBe('error');
  });

  test('AI-04: Generate key takeaway points API rejects empty content with 400', async ({ request }) => {
    const res = await request.post('/api/ai/points', {
      data: { body: '' }
    });
    expect(res.status()).toBe(400);
    const json = await res.json();
    expect(json.status).toBe('error');
  });

  test('AI-05: Scrape article API rejects missing URL with 400', async ({ request }) => {
    const res = await request.post('/api/ai/scrape', {
      data: { url: '' }
    });
    expect(res.status()).toBe(400);
    const json = await res.json();
    expect(json.status).toBe('error');
  });

  test('AI-06: Scrape article API rejects malformed non-HTTP URL with 400', async ({ request }) => {
    const res = await request.post('/api/ai/scrape', {
      data: { url: 'ftp://not-a-valid-http-url' }
    });
    expect(res.status()).toBe(400);
    const json = await res.json();
    expect(json.status).toBe('error');
  });

  test('AI-07: Generate title with valid sample text returns structured JSON format', async ({ request }) => {
    const sampleBody = 'Indeks Harga Saham Gabungan (IHSG) menguat tipis 0,2% pada perdagangan hari ini didorong oleh sektor perbankan dan energi.';
    const res = await request.post('/api/ai/title', {
      data: { body: sampleBody }
    });
    // Can be 200 (if Gemini API key valid) or 500 (if quota/key unavailable in local dev)
    expect([200, 500]).toContain(res.status());
    const json = await res.json();
    expect(json).toHaveProperty('status');
    expect(json).toHaveProperty('data');
  });

  test('AI-08: Generate summary with valid sample text returns structured JSON format', async ({ request }) => {
    const sampleBody = 'Bank Indonesia memutuskan untuk menahan suku bunga acuan BI-Rate di level 6,00% dalam Rapat Dewan Gubernur (RDG) bulanan.';
    const res = await request.post('/api/ai/summary', {
      data: { title: 'Keputusan RDG BI', body: sampleBody }
    });
    expect([200, 500]).toContain(res.status());
    const json = await res.json();
    expect(json).toHaveProperty('status');
    expect(json).toHaveProperty('data');
  });
});

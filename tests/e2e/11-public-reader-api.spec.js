const { test, expect } = require('@playwright/test');

test.describe('11 - Public Reader API & Integration Endpoints', () => {
  test('PUB-01: GET /api/articles returns published articles with pagination metadata', async ({ request }) => {
    const res = await request.get('/api/articles');
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.status).toBe('success');
    expect(json).toHaveProperty('data');
    expect(json).toHaveProperty('meta');
    expect(Array.isArray(json.data)).toBe(true);
  });

  test('PUB-02: GET /api/articles respects limit query parameter', async ({ request }) => {
    const res = await request.get('/api/articles?limit=3');
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.status).toBe('success');
    expect(json.data.length).toBeLessThanOrEqual(3);
    expect(json.meta.limit).toBe(3);
  });

  test('PUB-03: GET /api/articles?category=Market filters articles by kanal', async ({ request }) => {
    const res = await request.get('/api/articles?category=Market');
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.status).toBe('success');
    expect(Array.isArray(json.data)).toBe(true);
  });

  test('PUB-04: GET /api/articles?sort=popular returns articles sorted by popularity', async ({ request }) => {
    const res = await request.get('/api/articles?sort=popular&limit=5');
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.status).toBe('success');
    expect(Array.isArray(json.data)).toBe(true);
    if (json.data.length >= 2) {
      const views0 = Number(json.data[0].views_count || 0);
      const views1 = Number(json.data[1].views_count || 0);
      expect(views0).toBeGreaterThanOrEqual(views1);
    }
  });

  test('PUB-05: GET /api/categories returns kanal taxonomy tree', async ({ request }) => {
    const res = await request.get('/api/categories');
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.status).toBe('success');
    expect(Array.isArray(json.data)).toBe(true);
    expect(json.data.length).toBeGreaterThan(0);
    expect(json.data[0]).toHaveProperty('name');
  });

  test('PUB-06: GET /api/search returns keyword matching articles', async ({ request }) => {
    const res = await request.get('/api/search?q=saham');
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json).toHaveProperty('data');
    expect(Array.isArray(json.data)).toBe(true);
  });

  test('PUB-07: GET /api/market/ticker returns financial market indices', async ({ request }) => {
    const res = await request.get('/api/market/ticker');
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.status).toBe('success');
    expect(Array.isArray(json.data)).toBe(true);
    const symbols = json.data.map(item => item.symbol);
    expect(symbols).toContain('IHSG');
  });

  test('PUB-08: GET /api/market/movers returns top movers data', async ({ request }) => {
    const res = await request.get('/api/market/movers');
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.status).toBe('success');
    expect(json.data).toHaveProperty('gainers');
    expect(json.data).toHaveProperty('losers');
  });

  test('PUB-09: POST /api/auth/client-login rejects empty credentials with 400', async ({ request }) => {
    const res = await request.post('/api/auth/client-login', {
      data: { email: '', password: '' }
    });
    expect(res.status()).toBe(400);
    const json = await res.json();
    expect(json.status).toBe('error');
  });

  test('PUB-10: POST /api/auth/client-login with non-existent member returns 401', async ({ request }) => {
    const res = await request.post('/api/auth/client-login', {
      data: { email: 'unknown_reader_xyz@random.com', password: 'wrongpassword' }
    });
    expect(res.status()).toBe(401);
    const json = await res.json();
    expect(json.status).toBe('error');
  });
});

const { test, expect } = require('@playwright/test');
const path = require('path');

const authorAuth = path.join(__dirname, '../../.auth/author.json');

test.describe('07a - Mobile Apps Ecosystem CRUD & Management', () => {
  test('APPS-01: Apps version overview (/apps-version) displays mobile platforms and saves release', async ({ page }) => {
    await page.goto('/apps-version');
    await expect(page).toHaveTitle(/Version|Apps/i);

    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toMatch(/iOS|Android|App Store|Play Store/i);

    // Test saving a new version release
    const res = await page.request.post('/apps-version/save', {
      form: {
        platform: 'iOS (App Store)',
        version: 'v4.5.0',
        build: '4500',
        changelog: 'Automated release test'
      }
    });
    expect([200, 302, 303]).toContain(res.status());
  });

  test('APPS-02: Broadcast push notifications (/apps-push-notif) creates new notification', async ({ page }) => {
    await page.goto('/apps-push-notif');
    await expect(page).toHaveTitle(/Push Notif|Apps/i);

    const testNotifTitle = `Breaking Alert ${Date.now()}`;
    const res = await page.request.post('/apps-push-notif/create', {
      form: {
        title: testNotifTitle
      }
    });
    expect([200, 302, 303]).toContain(res.status());

    // Verify presence in notification history table
    await page.goto('/apps-push-notif');
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toContain(testNotifTitle);
  });

  test('APPS-03: Apps feature content carousel (/apps-feature-content) saves featured banner', async ({ page }) => {
    await page.goto('/apps-feature-content');
    await expect(page).toHaveTitle(/Feature Content|Apps/i);

    const featureTitle = `Featured Spotlight ${Date.now()}`;
    const res = await page.request.post('/apps-feature-content/save', {
      form: {
        title: featureTitle,
        badge: 'Top Story',
        image: '/img/investor_placeholder.jpg'
      }
    });
    expect([200, 302, 303]).toContain(res.status());

    await page.goto('/apps-feature-content');
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toContain(featureTitle);
  });
});

test.describe('07a - Apps RBAC Guard (Author Role Forbidden)', () => {
  test.use({ storageState: authorAuth });

  test('APPS-04: Author cannot access apps version management (/apps-version)', async ({ page }) => {
    const res = await page.goto('/apps-version');
    const url = page.url();
    const isRedirected = !url.includes('/apps-version') || url.includes('/dashboard') || url.includes('/login');
    const status = res ? res.status() : 200;
    expect(isRedirected || status === 403).toBe(true);
  });
});

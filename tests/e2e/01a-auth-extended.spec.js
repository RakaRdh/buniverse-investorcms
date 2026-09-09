const { test, expect } = require('@playwright/test');

test.describe('01a - Auth Extended Verification', () => {
  test('AUTH-01: Valid username with incorrect password displays error and remains on login', async ({ browser }) => {
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const page = await context.newPage();

    await page.goto('/login');
    await page.fill('input[name="username"]', 'raka_h');
    await page.fill('input[name="password"]', 'wrong_password_999');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.click('button[type="submit"]'),
    ]);

    await expect(page).toHaveURL(/.*login.*/);
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toMatch(/Kredensial tidak valid|tidak valid|sandi/i);

    await context.close();
  });

  test('AUTH-02: Empty credentials submission displays validation error', async ({ browser }) => {
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const page = await context.newPage();

    await page.goto('/login');
    // Remove HTML5 required attribute to trigger backend validation
    await page.evaluate(() => {
      document.querySelectorAll('input').forEach(i => i.removeAttribute('required'));
    });
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.click('button[type="submit"]'),
    ]);

    await expect(page).toHaveURL(/.*login.*/);
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toMatch(/masukkan username|kredensial|valid/i);

    await context.close();
  });

  test('AUTH-03: Already authenticated user visiting /login is redirected to /dashboard', async ({ page }) => {
    // Uses default logged-in context
    await page.goto('/login');
    await expect(page).toHaveURL(/.*dashboard.*/);
    await expect(page.locator('body')).toContainText(/Dashboard|Ringkasan|Investor/i);
  });

  test('AUTH-04: Logout destroys session, redirects to /login, and prevents back navigation to /dashboard', async ({ browser }) => {
    // Create new context, login manually, then logout
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const page = await context.newPage();

    await page.goto('/login');
    await page.fill('input[name="username"]', 'raka_h');
    await page.fill('input[name="password"]', 'password123');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.click('button[type="submit"]'),
    ]);
    await expect(page).toHaveURL(/.*dashboard.*/);

    // Click logout link or navigate to /logout
    await page.goto('/logout');
    await expect(page).toHaveURL(/.*login.*/);

    // Trying to go back to /dashboard must redirect to /login
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/.*login.*/);

    await context.close();
  });
});

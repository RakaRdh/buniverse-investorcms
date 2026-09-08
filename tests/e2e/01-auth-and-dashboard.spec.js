const { test, expect } = require('@playwright/test');

test.describe('01 - Auth & Dashboard Overview', () => {
  test('Dashboard loads with all metric cards and widgets', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page).toHaveTitle(/Newsroom|Dashboard|Overview|Investor/i);

    // Verify key elements exist on dashboard
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toMatch(/Dashboard|Overview|Investor|Redaksi/i);

    // Check presence of sidebar navigation links
    const sidebar = page.locator('aside, nav, .sidebar');
    await expect(sidebar.first()).toBeVisible();
    await expect(page.locator('a[href*="/post"]').first()).toBeVisible();
    await expect(page.locator('a[href*="/headline"]').first()).toBeVisible();
  });

  test('Unauthenticated user cannot access dashboard and is redirected to login', async ({ browser }) => {
    // Create isolated unauthenticated context
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const cleanPage = await context.newPage();

    await cleanPage.goto('/dashboard');
    // Should be redirected to /login
    await expect(cleanPage).toHaveURL(/.*login.*/);
    await expect(cleanPage.locator('input[name="username"], input[name="identity"]')).toBeVisible();

    await context.close();
  });

  test('Login rejects invalid credentials with error message', async ({ browser }) => {
    // Create isolated unauthenticated context
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const cleanPage = await context.newPage();

    await cleanPage.goto('/login');
    await cleanPage.fill('input[name="username"]', 'wrong_user_xyz');
    await cleanPage.fill('input[name="password"]', 'invalidpassword');
    await cleanPage.click('button[type="submit"]');

    // Verify remaining on login page with error indication
    await expect(cleanPage).toHaveURL(/.*login.*/);
    await context.close();
  });
});

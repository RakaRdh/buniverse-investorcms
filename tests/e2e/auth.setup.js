const { test: setup, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const authFile = path.join(__dirname, '../../.auth/admin.json');

setup('authenticate as master admin', async ({ page }) => {
  // Ensure .auth directory exists
  const authDir = path.dirname(authFile);
  if (!fs.existsSync(authDir)) {
    fs.mkdirSync(authDir, { recursive: true });
  }

  // Go to login page
  await page.goto('/login');
  await expect(page).toHaveTitle(/Login|Investor/i);

  // Fill credentials (using new username schema)
  await page.fill('input[name="username"]', 'raka_h');
  await page.fill('input[name="password"]', 'password123');

  // Submit form
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.click('button[type="submit"]'),
  ]);

  // Verify redirected to dashboard or contains dashboard content
  await expect(page).toHaveURL(/.*dashboard.*/);
  await expect(page.locator('body')).toContainText(/Dashboard|Ringkasan|Investor/i);

  // Save authentication storage state
  await page.context().storageState({ path: authFile });
});

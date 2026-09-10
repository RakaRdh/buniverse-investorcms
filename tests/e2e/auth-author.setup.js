const { test: setup, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const authorAuthFile = path.join(__dirname, '../../.auth/author.json');

setup('authenticate as support role (support)', async ({ page }) => {
  const authDir = path.dirname(authorAuthFile);
  if (!fs.existsSync(authDir)) {
    fs.mkdirSync(authDir, { recursive: true });
  }

  await page.goto('/login');
  await page.fill('input[name="username"]', 'support');
  await page.fill('input[name="password"]', 'password123');

  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.click('button[type="submit"]'),
  ]);

  await expect(page).toHaveURL(/.*dashboard.*/);
  await page.context().storageState({ path: authorAuthFile });
});

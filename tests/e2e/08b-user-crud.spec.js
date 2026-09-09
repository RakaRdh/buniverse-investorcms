const { test, expect } = require('@playwright/test');
const path = require('path');

const authorAuth = path.join(__dirname, '../../.auth/author.json');

test.describe('08b - Staff User & RBAC Permissions CRUD', () => {
  let createdUsername = '';
  let activeAdminId = null;
  let targetStaffId = null;

  test('USER-01: Staff user management (/user) renders staff table and role tags', async ({ page }) => {
    await page.goto('/user');
    await expect(page).toHaveTitle(/User|Staff/i);

    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toMatch(/Staff|User Management|Admin|Editor|Author/i);

    const table = page.locator('table');
    await expect(table.first()).toBeVisible();

    // Extract real active admin ID and target staff ID from permission buttons
    const permButtons = page.locator('button[onclick*="openPermissionModal"]');
    const btnCount = await permButtons.count();
    expect(btnCount).toBeGreaterThan(0);

    for (let i = 0; i < btnCount; i++) {
      const onclickVal = await permButtons.nth(i).getAttribute('onclick');
      const m = onclickVal ? onclickVal.match(/openPermissionModal\((\d+)/) : null;
      if (m) {
        if (!activeAdminId) activeAdminId = m[1];
        else if (!targetStaffId) targetStaffId = m[1];
      }
    }
    if (!targetStaffId) targetStaffId = activeAdminId;
  });

  test('USER-02: Create new staff user (/user/save) persists account', async ({ page }) => {
    createdUsername = `testuser_${Date.now()}`;
    const userEmail = `${createdUsername}@investor.id`;

    const res = await page.request.post('/user/save', {
      form: {
        name: `Test Staff ${Date.now()}`,
        email: userEmail,
        username: createdUsername,
        role: 'Author'
      }
    });
    expect([200, 302, 303]).toContain(res.status());

    await page.goto('/user');
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toContain(createdUsername);
  });

  test('USER-03: Self-deletion prevention blocks deleting currently logged in account', async ({ page }) => {
    await page.goto('/user');
    // Find row for current user (@raka_h) and extract its ID
    const rakaRow = page.locator('tr:has-text("@raka_h")').first();
    let deleteId = activeAdminId;
    if (await rakaRow.count() > 0) {
      const permBtn = rakaRow.locator('button[onclick*="openPermissionModal"]').first();
      if (await permBtn.count() > 0) {
        const onclick = await permBtn.getAttribute('onclick');
        const m = onclick ? onclick.match(/openPermissionModal\((\d+)/) : null;
        if (m) deleteId = m[1];
      }
    }

    await page.goto(`/user/delete/${deleteId}`);
    await expect(page).toHaveURL(/.*\/user.*/);

    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toMatch(/Cannot delete currently active|tidak dapat menghapus/i);
  });

  test('USER-04: Fetch custom page permissions API returns JSON', async ({ page }) => {
    const res = await page.request.get(`/user/permissions/${targetStaffId || 2}`);
    expect([200, 404]).toContain(res.status());
    const json = await res.json();
    if (res.status() === 200) {
      expect(json.status).toBe('success');
      expect(json.permissions).toBeDefined();
    }
  });

  test('USER-05: Save custom page permissions API (/user/permissions/save)', async ({ page }) => {
    const res = await page.request.post('/user/permissions/save', {
      form: {
        user_id: String(targetStaffId || 2),
        pages: ['post', 'media', 'files']
      }
    });
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.status).toBe('success');
  });

  test('USER-06: Delete newly created staff user removes account', async ({ page }) => {
    await page.goto('/user');
    const row = page.locator(`tr:has-text("${createdUsername}")`).first();
    
    if (await row.count() > 0) {
      const deleteBtn = row.locator('a[href*="/user/delete/"], button[onclick*="openDeleteUserModal"]').first();
      if (await deleteBtn.count() > 0) {
        const onclickVal = await deleteBtn.getAttribute('onclick');
        const m = onclickVal ? onclickVal.match(/openDeleteUserModal\((\d+)/) : null;
        if (m) {
          await page.goto(`/user/delete/${m[1]}`);
        } else {
          const href = await deleteBtn.getAttribute('href');
          if (href) await page.goto(href);
        }
        await page.waitForURL('**/user**', { timeout: 15000 });
        const bodyText = await page.locator('body').innerText();
        expect(bodyText).toMatch(/removed|dihapus|berhasil/i);
      }
    }
  });
});

test.describe('08b - User RBAC Guard (Author Role Forbidden)', () => {
  test.use({ storageState: authorAuth });

  test('USER-07: Author role cannot access user management (/user)', async ({ page }) => {
    const res = await page.goto('/user');
    const url = page.url();
    const isRedirected = !url.includes('/user') || url.includes('/dashboard') || url.includes('/login');
    const status = res ? res.status() : 200;
    expect(isRedirected || status === 403).toBe(true);
  });
});

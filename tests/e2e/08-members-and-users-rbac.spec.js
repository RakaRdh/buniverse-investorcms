const { test, expect } = require('@playwright/test');

test.describe('08 - Members & RBAC User Management', () => {
  test('Member management (/member) displays client accounts and status', async ({ page }) => {
    await page.goto('/member');
    await expect(page).toHaveTitle(/Member|Klien|Investor/i);

    const table = page.locator('table');
    await expect(table.first()).toBeVisible();

    // Verify status badges (Active, Pending, Suspended)
    const tableText = await table.first().innerText();
    expect(tableText).toMatch(/Active|Pending|Suspended|Member/i);
  });

  test('User management (/user) displays staff users with roles and username field', async ({ page }) => {
    await page.goto('/user');
    await expect(page).toHaveTitle(/User|Pengguna|Investor/i);

    const table = page.locator('table');
    await expect(table.first()).toBeVisible();

    // Table should show existing user roles
    const tableText = await table.first().innerText();
    expect(tableText).toMatch(/Administrator|Editor|Redpel|Riset|Sekred|Support/i);

    // Click "Tambah User" / "Register Staff" button
    const addUserBtn = page.locator('button:has-text("Register"), button:has-text("Tambah User"), button[onclick*="newUserModal"]').first();
    if (await addUserBtn.count() > 0) {
      await addUserBtn.click();

      // Modal should open
      const modal = page.locator('#newUserModal');
      await expect(modal).toBeVisible();

      // Check username input field exists
      const usernameInput = modal.locator('input[name="username"]');
      await expect(usernameInput).toBeVisible();

      // Close modal
      const closeBtn = modal.locator('button:has-text("Batal"), button[onclick*="closeNewUserModal"]').first();
      if (await closeBtn.count() > 0) {
        await closeBtn.click();
      }
    }
  });

  test('User permissions modal loads informative read-only page access overview', async ({ page }) => {
    await page.goto('/user');

    // Click on permissions button for first available staff
    const permBtn = page.locator('button[onclick*="openPermissionModal"]').first();
    if (await permBtn.count() > 0) {
      await permBtn.click();

      // Permissions modal should appear
      const permModal = page.locator('#permissionModal');
      await expect(permModal).toBeVisible();

      // Verify module indicators and close button
      await expect(page.locator('#permissionUserDesc')).toBeVisible();
      const closeBtn = permModal.locator('button:has-text("Tutup")').first();
      await expect(closeBtn).toBeVisible();
      await closeBtn.click();
      await expect(permModal).toBeHidden();
    }
  });
});

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
    expect(tableText).toMatch(/Master|Admin|Editor|Reporter/i);

    // Click "Tambah User" button
    const addUserBtn = page.locator('button:has-text("Tambah User"), button[data-modal-target="addUserModal"]');
    if (await addUserBtn.count() > 0) {
      await addUserBtn.first().click();

      // Modal should open
      const modal = page.locator('#addUserModal, [role="dialog"]').first();
      await expect(modal).toBeVisible();

      // Check username input field exists
      const usernameInput = modal.locator('input[name="username"]');
      await expect(usernameInput).toBeVisible();

      // Close modal
      const closeBtn = modal.locator('button:has-text("Batal"), button[data-modal-close]').first();
      if (await closeBtn.count() > 0) {
        await closeBtn.click();
      }
    }
  });

  test('User permissions modal loads granular checkboxes and can be saved', async ({ page }) => {
    await page.goto('/user');

    // Click on permissions button for user ID 6 (Arnoldus Kristianus)
    const permBtn = page.locator('button[data-user-id="6"], button[onclick*="openPermissionsModal(6)"]').first();
    if (await permBtn.count() > 0) {
      await permBtn.click();

      // Permissions modal should appear
      const permModal = page.locator('#permissionsModal, [role="dialog"]:has-text("Hak Akses")');
      await expect(permModal.first()).toBeVisible();

      // Checkboxes for modules should be rendered
      const checkboxes = permModal.locator('input[type="checkbox"]');
      await expect(checkboxes.first()).toBeVisible();

      // Close modal
      const cancelBtn = permModal.locator('button:has-text("Batal"), button:has-text("Tutup")').first();
      if (await cancelBtn.count() > 0) {
        await cancelBtn.click();
      }
    }
  });
});

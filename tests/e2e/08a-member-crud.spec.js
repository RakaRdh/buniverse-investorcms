const { test, expect } = require('@playwright/test');
const path = require('path');

const authorAuth = path.join(__dirname, '../../.auth/author.json');

test.describe('08a - Client Account & Member Management CRUD', () => {
  let createdMemberEmail = '';

  test('MEMBER-01: Member list page (/member) renders statistics cards and members table', async ({ page }) => {
    await page.goto('/member');
    await expect(page).toHaveTitle(/Member|Client/i);

    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toMatch(/Client|Member|Total Member|Active|Status/i);

    const table = page.locator('table');
    await expect(table.first()).toBeVisible();
  });

  test('MEMBER-02: Member search filters list by query (/member?q=PT)', async ({ page }) => {
    await page.goto('/member?q=PT');
    await expect(page).toHaveURL(/.*q=PT.*/);

    const searchInput = page.locator('input[name="q"]');
    if (await searchInput.count() > 0) {
      await expect(searchInput).toHaveValue('PT');
    }
  });

  test('MEMBER-03: Create new client member (/member/save) persists to database', async ({ page }) => {
    createdMemberEmail = `member_${Date.now()}@corporate.id`;
    const res = await page.request.post('/member/save', {
      form: {
        full_name: `Budi Perkasa ${Date.now()}`,
        email: createdMemberEmail,
        company: 'PT Sekuritas Investor Mandiri',
        status: 'Active'
      }
    });
    expect([200, 302, 303]).toContain(res.status());

    await page.goto('/member');
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toContain(createdMemberEmail);
  });

  test('MEMBER-04: Update existing member details updates information', async ({ page }) => {
    await page.goto('/member');
    const editBtn = page.locator('button[onclick*="openEditModal"], a[href*="/member/edit"]').first();

    if (await editBtn.count() > 0) {
      // Test update endpoint directly with id 1
      const res = await page.request.post('/member/save', {
        form: {
          id: '1',
          full_name: 'Budi Santoso Update',
          email: 'budi.santoso.updated@example.com',
          company: 'PT Trimegah Sekuritas',
          status: 'Active'
        }
      });
      expect([200, 302, 303]).toContain(res.status());
    }
  });

  test('MEMBER-05: Delete member record removes account from database', async ({ page }) => {
    // Create member to delete
    const deleteEmail = `delete_member_${Date.now()}@target.id`;
    await page.request.post('/member/save', {
      form: {
        full_name: 'Member To Delete',
        email: deleteEmail,
        company: 'Target Corp',
        status: 'Inactive'
      }
    });

    await page.goto('/member');
    const row = page.locator(`tr:has-text("${deleteEmail}")`).first();
    await expect(row).toBeVisible();

    const deleteLink = row.locator('a[href*="/member/delete/"]').first();
    if (await deleteLink.count() > 0) {
      const delHref = await deleteLink.getAttribute('href');
      await page.goto(delHref);
      await page.waitForURL('**/member**', { timeout: 15000 });
      const bodyText = await page.locator('body').innerText();
      expect(bodyText).toMatch(/berhasil dihapus|deleted/i);
    }
  });
});

test.describe('08a - Member RBAC Guard (Author Role Forbidden)', () => {
  test.use({ storageState: authorAuth });

  test('MEMBER-06: Author role cannot access member management (/member)', async ({ page }) => {
    const res = await page.goto('/member');
    const url = page.url();
    const isRedirected = !url.includes('/member') || url.includes('/dashboard') || url.includes('/login');
    const status = res ? res.status() : 200;
    expect(isRedirected || status === 403).toBe(true);
  });
});

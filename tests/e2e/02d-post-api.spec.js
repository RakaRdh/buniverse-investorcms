const { test, expect } = require('@playwright/test');
const path = require('path');
const fs = require('fs');

test.describe('02d - Post Editorial Backend API (/api/autosave & /api/upload-image)', () => {
  let createdDraftId = null;
  const fixtureImgPath = path.join(__dirname, '../fixtures/test_image_1x1.png');
  const fixturePdfPath = path.join(__dirname, '../fixtures/sample_test_file.pdf');

  test('POST-API-01: Auto-save rejects completely empty draft', async ({ page }) => {
    const res = await page.request.post('/api/autosave', {
      data: {
        title: '',
        content: ''
      }
    });
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.success).toBe(false);
    expect(json.message).toMatch(/kosong/i);
  });

  test('POST-API-02: Auto-save creates new draft with title and content', async ({ page }) => {
    const res = await page.request.post('/api/autosave', {
      data: {
        title: `AutoSave Draft Article ${Date.now()}`,
        content: '<p>Konten auto-save otomatis disimpan di background.</p>',
        kanal: 'Market',
        summary: 'Ringkasan draft naskah autosave.'
      }
    });
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.success).toBe(true);
    expect(json.id).toBeTruthy();
    createdDraftId = json.id;
  });

  test('POST-API-03: Auto-save updates existing draft without creating duplicate', async ({ page }) => {
    expect(createdDraftId).toBeTruthy();

    const updatedTitle = `Updated AutoSave Draft ${Date.now()}`;
    const res = await page.request.post('/api/autosave', {
      data: {
        id: createdDraftId,
        title: updatedTitle,
        content: '<p>Konten artikel telah diupdate via autosave.</p>',
        kanal: 'Market'
      }
    });
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.success).toBe(true);
    expect(json.id).toBe(createdDraftId);
  });

  test('POST-API-04: Upload image rejects missing file with 400 error', async ({ page }) => {
    const res = await page.request.post('/api/upload-image', {
      multipart: {}
    });
    expect(res.status()).toBe(400);
    const json = await res.json();
    expect(json.success).toBe(false);
    expect(json.error).toMatch(/tidak ditemukan|korup/i);
  });

  test('POST-API-05: Upload image rejects disallowed file type (e.g. PDF) with 400 error', async ({ page }) => {
    const fileBuffer = fs.readFileSync(fixturePdfPath);
    const res = await page.request.post('/api/upload-image', {
      multipart: {
        image: {
          name: 'document.pdf',
          mimeType: 'application/pdf',
          buffer: fileBuffer
        }
      }
    });
    expect(res.status()).toBe(400);
    const json = await res.json();
    expect(json.success).toBe(false);
    expect(json.error).toMatch(/tidak didukung/i);
  });

  test('POST-API-06: Upload valid PNG image processes and returns web accessible URL', async ({ page }) => {
    const fileBuffer = fs.readFileSync(fixtureImgPath);
    const res = await page.request.post('/api/upload-image', {
      multipart: {
        image: {
          name: 'test_fixture.png',
          mimeType: 'image/png',
          buffer: fileBuffer
        }
      }
    });
    expect(res.status()).toBe(200);
    const json = await res.json();
    expect(json.success).toBe(true);
    expect(json.url).toBeTruthy();
    expect(json.url).toMatch(/\.(jpg|jpeg|png|webp)/i);
  });
});

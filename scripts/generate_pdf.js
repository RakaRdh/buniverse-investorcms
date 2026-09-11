const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const { marked } = require('marked');

async function generatePdf() {
  const mdPath = 'D:/Raka/B-Universe/Obsidian/B-Universe/Revamp Investor CMS/Documentation/Dokumentasi Migrasi Codebase Legacy ke CodeIgniter 4 - V2.md';
  const pdfPath = 'D:/Raka/B-Universe/Obsidian/B-Universe/Revamp Investor CMS/Documentation/Dokumentasi Migrasi Codebase Legacy ke CodeIgniter 4 - V2.pdf';

  if (!fs.existsSync(mdPath)) {
    console.error('File MD tidak ditemukan di: ' + mdPath);
    process.exit(1);
  }

  let mdContent = fs.readFileSync(mdPath, 'utf-8');

  // Convert GitHub-style alerts: > [!NOTE], > [!IMPORTANT], > [!WARNING], > [!TIP]
  mdContent = mdContent.replace(/^>\s*\[!NOTE\]\s*\n((?:>.*\n?)*)/gim, (match, p1) => {
    const text = p1.replace(/^>\s?/gm, '');
    return `<div class="alert alert-note"><div class="alert-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg><span>CATATAN</span></div><div class="alert-body">${text}</div></div>\n`;
  });

  mdContent = mdContent.replace(/^>\s*\[!IMPORTANT\]\s*\n((?:>.*\n?)*)/gim, (match, p1) => {
    const text = p1.replace(/^>\s?/gm, '');
    return `<div class="alert alert-important"><div class="alert-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg><span>PENTING</span></div><div class="alert-body">${text}</div></div>\n`;
  });

  mdContent = mdContent.replace(/^>\s*\[!WARNING\]\s*\n((?:>.*\n?)*)/gim, (match, p1) => {
    const text = p1.replace(/^>\s?/gm, '');
    return `<div class="alert alert-warning"><div class="alert-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg><span>PERINGATAN</span></div><div class="alert-body">${text}</div></div>\n`;
  });

  // Convert markdown to HTML
  const parsedHtml = marked.parse(mdContent);

  const fullHtml = `<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dokumentasi Migrasi Codebase Legacy ke CodeIgniter 4 — V2</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    @page {
      size: A4;
      margin: 22mm 16mm 22mm 16mm;
    }
    *, *::before, *::after {
      box-sizing: border-box;
    }
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      font-size: 10pt;
      line-height: 1.55;
      color: #1e293b;
      background-color: #ffffff;
      margin: 0;
      padding: 0;
      -webkit-font-smoothing: antialiased;
    }
    h1 {
      font-size: 20pt;
      font-weight: 800;
      color: #0f172a;
      line-height: 1.25;
      margin-top: 0;
      margin-bottom: 12pt;
      border-bottom: 2.5px solid #2563eb;
      padding-bottom: 8pt;
      page-break-after: avoid;
    }
    h2 {
      font-size: 14pt;
      font-weight: 700;
      color: #1e3a8a;
      margin-top: 20pt;
      margin-bottom: 8pt;
      border-bottom: 1px solid #e2e8f0;
      padding-bottom: 4pt;
      page-break-after: avoid;
    }
    h3 {
      font-size: 11.5pt;
      font-weight: 700;
      color: #1e293b;
      margin-top: 14pt;
      margin-bottom: 6pt;
      page-break-after: avoid;
    }
    h4 {
      font-size: 10pt;
      font-weight: 700;
      color: #334155;
      margin-top: 10pt;
      margin-bottom: 4pt;
      page-break-after: avoid;
    }
    p {
      margin-top: 0;
      margin-bottom: 8pt;
    }
    ul, ol {
      margin-top: 0;
      margin-bottom: 8pt;
      padding-left: 18pt;
    }
    li {
      margin-bottom: 3pt;
    }
    hr {
      border: 0;
      border-top: 1px solid #e2e8f0;
      margin: 16pt 0;
    }
    blockquote {
      margin: 8pt 0;
      padding: 6pt 12pt;
      background: #f8fafc;
      border-left: 3.5px solid #64748b;
      color: #475569;
      font-size: 9pt;
      border-radius: 0 4pt 4pt 0;
    }
    blockquote p:last-child {
      margin-bottom: 0;
    }
    /* Tables */
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 12pt 0;
      font-size: 8.5pt;
      line-height: 1.4;
      page-break-inside: auto;
    }
    tr {
      page-break-inside: avoid;
      page-break-after: auto;
    }
    thead {
      display: table-header-group;
    }
    th {
      background-color: #0f172a;
      color: #ffffff;
      font-weight: 700;
      text-align: left;
      padding: 6pt 8pt;
      border: 1px solid #0f172a;
      font-size: 8pt;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    td {
      padding: 5.5pt 8pt;
      border: 1px solid #e2e8f0;
      vertical-align: top;
    }
    tbody tr:nth-child(even) {
      background-color: #f8fafc;
    }
    tbody tr:hover {
      background-color: #f1f5f9;
    }
    /* Code & Pre */
    code {
      font-family: 'JetBrains Mono', monospace;
      font-size: 8.2pt;
      background-color: #f1f5f9;
      color: #0f172a;
      padding: 1.5pt 3.5pt;
      border-radius: 3pt;
      border: 1px solid #e2e8f0;
    }
    pre {
      background-color: #0f172a;
      color: #f8fafc;
      padding: 10pt;
      border-radius: 6pt;
      overflow-x: auto;
      font-size: 8.2pt;
      line-height: 1.45;
      margin: 10pt 0;
      page-break-inside: avoid;
    }
    pre code {
      background-color: transparent;
      color: inherit;
      padding: 0;
      border: 0;
      font-size: inherit;
    }
    /* GitHub style alerts */
    .alert {
      padding: 8pt 12pt;
      border-radius: 6pt;
      margin: 10pt 0;
      font-size: 9pt;
      page-break-inside: avoid;
    }
    .alert-title {
      font-weight: 700;
      font-size: 8.5pt;
      letter-spacing: 0.05em;
      display: flex;
      align-items: center;
      gap: 5pt;
      margin-bottom: 4pt;
    }
    .alert-body p:last-child {
      margin-bottom: 0;
    }
    .alert-note {
      background-color: #eff6ff;
      border: 1px solid #bfdbfe;
      border-left: 4px solid #2563eb;
      color: #1e40af;
    }
    .alert-note .alert-title {
      color: #1d4ed8;
    }
    .alert-important {
      background-color: #f5f3ff;
      border: 1px solid #ddd6fe;
      border-left: 4px solid #7c3aed;
      color: #5b21b6;
    }
    .alert-important .alert-title {
      color: #6d28d9;
    }
    .alert-warning {
      background-color: #fffbeb;
      border: 1px solid #fde68a;
      border-left: 4px solid #d97706;
      color: #92400e;
    }
    .alert-warning .alert-title {
      color: #b45309;
    }
    /* Status Badges */
    .badge {
      display: inline-block;
      padding: 1.5pt 5pt;
      font-size: 7.5pt;
      font-weight: 700;
      border-radius: 3pt;
      text-transform: uppercase;
    }
    .badge-success {
      background-color: #dcfce7;
      color: #166534;
    }
    .badge-warning {
      background-color: #fef3c7;
      color: #92400e;
    }
    .badge-danger {
      background-color: #fee2e2;
      color: #991b1b;
    }
    .badge-info {
      background-color: #e0f2fe;
      color: #075985;
    }
  </style>
</head>
<body>
  ${parsedHtml}
</body>
</html>`;

  console.log('Meluncurkan headless browser via Playwright...');
  const browser = await chromium.launch();
  const page = await browser.newPage();

  console.log('Merender konten HTML & styling ke DOM...');
  await page.setContent(fullHtml, { waitUntil: 'networkidle' });

  // Post-process table badges for extra elegance
  await page.evaluate(() => {
    document.querySelectorAll('td').forEach(td => {
      const text = td.innerText.trim();
      if (text.startsWith('✅') || text.includes('100% Cocok') || text.includes('Selesai')) {
        td.style.fontWeight = '600';
      }
    });
  });

  console.log('Mencetak PDF ke format A4 dengan running header/footer...');
  await page.pdf({
    path: pdfPath,
    format: 'A4',
    printBackground: true,
    margin: {
      top: '24mm',
      bottom: '24mm',
      left: '16mm',
      right: '16mm'
    },
    displayHeaderFooter: true,
    headerTemplate: `
      <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 8px; color: #94a3b8; width: 100%; display: flex; justify-content: space-between; padding-left: 16mm; padding-right: 16mm; box-sizing: border-box;">
        <span><strong>CMS Investor.ID</strong> &bull; Laporan Paritas Arsitektur Migrasi</span>
        <span>Versi 2.0 (September 2026)</span>
      </div>
    `,
    footerTemplate: `
      <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 8px; color: #94a3b8; width: 100%; display: flex; justify-content: space-between; padding-left: 16mm; padding-right: 16mm; box-sizing: border-box;">
        <span>B-Universe Newsroom Engineering &bull; Confidential Internal Report</span>
        <span>Halaman <span class="pageNumber"></span> dari <span class="totalPages"></span></span>
      </div>
    `
  });

  await browser.close();
  console.log('✅ PDF berhasil digenerate di: ' + pdfPath);
}

generatePdf().catch(err => {
  console.error('Gagal generate PDF:', err);
  process.exit(1);
});

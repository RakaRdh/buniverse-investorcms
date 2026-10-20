const fs = require('fs');
const path = require('path');
const { marked } = require('marked');
const { chromium } = require('@playwright/test');

function githubSlugify(text) {
    return text
        .toLowerCase()
        .trim()
        .replace(/<[^>]*>/g, '')
        .replace(/[^\w\s-]/g, '')
        .replace(/\s/g, '-');
}

async function generatePdf() {
    const mdPath = path.resolve(__dirname, '../docs/Revamp Investor CMS Documentation.md');
    const pdfPath = path.resolve(__dirname, '../docs/Revamp Investor CMS Documentation.pdf');
    const obsidianPdfPath = 'D:\\Raka\\B-Universe\\Obsidian\\B-Universe\\Revamp Investor CMS\\Documentation\\Revamp Investor CMS Documentation.pdf';

    if (!fs.existsSync(mdPath)) {
        console.error('File markdown tidak ditemukan:', mdPath);
        process.exit(1);
    }

    const mdContent = fs.readFileSync(mdPath, 'utf8');

    // Setup marked with custom heading renderer
    marked.use({
        renderer: {
            heading({ tokens, depth, text }) {
                const slug = githubSlugify(text);
                return `<h${depth} id="${slug}"><a id="${slug}" class="anchor"></a>${text}</h${depth}>\n`;
            }
        }
    });

    let htmlBody = marked.parse(mdContent);

    // Post-processing fallback to guarantee all headings have id and anchor
    htmlBody = htmlBody.replace(/<h([1-6])(.*?)>(.*?)<\/h\1>/gi, (match, level, attrs, innerText) => {
        if (attrs.includes('id=')) {
            return match;
        }
        const slug = githubSlugify(innerText);
        return `<h${level} id="${slug}"${attrs}><a id="${slug}" class="anchor"></a>${innerText}</h${level}>`;
    });

    const fullHtml = `<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Revamp Investor CMS Documentation</title>
    <style>
        @page {
            size: A4;
            margin: 20mm 15mm 20mm 15mm;
            @bottom-right {
                content: counter(page);
            }
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 12.5px;
            line-height: 1.6;
            color: #24292f;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }
        h1, h2, h3, h4, h5, h6 {
            color: #0f172a;
            font-weight: 600;
            margin-top: 24px;
            margin-bottom: 12px;
            page-break-after: avoid;
            position: relative;
        }
        h1 {
            font-size: 22px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
            margin-top: 0;
        }
        h2 {
            font-size: 17px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
        }
        h3 {
            font-size: 14.5px;
        }
        h4 {
            font-size: 13px;
        }
        p, ul, ol {
            margin-top: 0;
            margin-bottom: 12px;
        }
        ul, ol {
            padding-left: 24px;
        }
        li {
            margin-bottom: 4px;
        }
        hr {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 20px 0;
        }
        code {
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 11px;
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 2px 5px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }
        pre {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            overflow-x: auto;
            margin-top: 8px;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        pre code {
            background: none;
            border: none;
            padding: 0;
            font-size: 10.5px;
            line-height: 1.5;
            color: #1e293b;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 12px;
            margin-bottom: 16px;
            font-size: 11.5px;
            page-break-inside: avoid;
        }
        table th, table td {
            border: 1px solid #cbd5e1;
            padding: 7px 10px;
            text-align: left;
        }
        table th {
            background-color: #f1f5f9;
            font-weight: 600;
            color: #0f172a;
        }
        table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        blockquote {
            margin: 14px 0;
            padding: 10px 16px;
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            color: #92400e;
            border-radius: 0 4px 4px 0;
            page-break-inside: avoid;
        }
        blockquote strong {
            color: #78350f;
        }
        a {
            color: #0969da;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .anchor {
            position: absolute;
            top: -20px;
            left: 0;
        }
    </style>
</head>
<body>
    ${htmlBody}
</body>
</html>`;

    console.log('Meluncurkan headless browser Playwright Chromium...');
    const browser = await chromium.launch();
    const page = await browser.newPage();

    await page.setContent(fullHtml, { waitUntil: 'networkidle' });

    console.log('Mencetak PDF ke docs/Revamp Investor CMS Documentation.pdf...');
    await page.pdf({
        path: pdfPath,
        format: 'A4',
        margin: {
            top: '20mm',
            bottom: '20mm',
            left: '15mm',
            right: '15mm'
        },
        printBackground: true,
        displayHeaderFooter: true,
        headerTemplate: '<div style="font-size: 9px; color: #94a3b8; width: 100%; text-align: right; padding-right: 15mm;">Investor.ID Revamp CMS Technical Documentation</div>',
        footerTemplate: '<div style="font-size: 9px; color: #94a3b8; width: 100%; text-align: center;"><span class="pageNumber"></span> / <span class="totalPages"></span></div>'
    });

    console.log('Menyalin PDF ke Obsidian Vault...');
    fs.copyFileSync(pdfPath, obsidianPdfPath);

    await browser.close();
    console.log('PDF berhasil dibuat dengan seluruh internal links aktif di:');
    console.log('1. ' + pdfPath);
    console.log('2. ' + obsidianPdfPath);
}

generatePdf().catch(err => {
    console.error('Error saat menghasilkan PDF:', err);
    process.exit(1);
});

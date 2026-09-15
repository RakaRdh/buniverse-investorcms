<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\HtmlSanitizer;

class HtmlSanitizerTest extends CIUnitTestCase
{
    public function testStripsScriptTagsEntirely()
    {
        $dirty = '<p>Paragraf berita normal.</p><script>alert("XSS")</script><script src="https://evil.com/payload.js"></script>';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('alert("XSS")', $clean);
        $this->assertStringContainsString('<p>Paragraf berita normal.</p>', $clean);
    }

    public function testStripsInlineEventHandlersOnAnyTag()
    {
        $dirty = '<p onmouseover="stealCookie()">Hover me</p><img src="foto.jpg" onerror="alert(1)" alt="Test"><b onclick="evil()">Bold</b>';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringNotContainsString('onmouseover', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringContainsString('<p>Hover me</p>', $clean);
        $this->assertStringContainsString('<img src="foto.jpg" alt="Test">', $clean);
        $this->assertStringContainsString('<b>Bold</b>', $clean);
    }

    public function testNeutralizesDangerousJavascriptUrls()
    {
        $dirty = '<a href="javascript:alert(\'pwned\')">Klik Disini</a><a href="https://investor.id/market">Link Aman</a>';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('href="#"', $clean);
        $this->assertStringContainsString('href="https://investor.id/market"', $clean);
    }

    public function testAllowsWhitelistedIframesAndBlocksUntrustedIframes()
    {
        $dirty = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560"></iframe><iframe src="https://evil-hacker.com/malware"></iframe>';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringContainsString('https://www.youtube.com/embed/dQw4w9WgXcQ', $clean);
        $this->assertStringNotContainsString('evil-hacker.com', $clean);
    }

    public function testPreservesLegitimateJournalismFormatting()
    {
        $journalismHtml = '<h2>Judul Sub-Bab</h2><p>Pertumbuhan ekonomi <strong>stabil</strong> dan <em>berkualitas</em>.</p><blockquote>Kutipan narasumber penting.</blockquote><table><thead><tr><th>No</th><th>Emiten</th></tr></thead><tbody><tr><td>1</td><td>BBCA</td></tr></tbody></table><hr>';
        $clean = HtmlSanitizer::clean($journalismHtml);

        $this->assertEquals(trim($journalismHtml), $clean);
    }

    public function testCleanTitlePreservesOnlyItalicAndEmphasis()
    {
        $dirtyTitle = 'Eksklusif: <i>Kinerja Emiten</i> <strong>Kuartal III</strong><script>alert(1)</script>';
        $cleanTitle = HtmlSanitizer::cleanTitle($dirtyTitle);

        $this->assertStringContainsString('<i>Kinerja Emiten</i>', $cleanTitle);
        $this->assertStringNotContainsString('<script', $cleanTitle);
        $this->assertStringNotContainsString('<strong>', $cleanTitle);
        $this->assertStringContainsString('Kuartal III', $cleanTitle);
    }

    public function testCleanSummaryStripsAllTags()
    {
        $dirtySummary = '<p>Ringkasan berita <b>penting</b> hari ini.</p>';
        $cleanSummary = HtmlSanitizer::cleanSummary($dirtySummary);

        $this->assertEquals('Ringkasan berita penting hari ini.', $cleanSummary);
    }
}

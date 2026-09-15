<?php

namespace App\Libraries;

/**
 * HtmlSanitizer
 *
 * Layanan pembersih HTML redaksional untuk mencegah Stored XSS & Script Injection.
 * - Melucuti tag eksekusi (<script>, <style>, <object>, <embed>, <form>, <input>, dll)
 * - Menghapus seluruh event handler inline (onload, onerror, onclick, onmouseover, dll)
 * - Menetralkan skema URL berbahaya (javascript:, vbscript:, data:text/html)
 * - Memvalidasi domain iframe (hanya mengizinkan YouTube, Instagram, Google Docs, Twitter, dan internal embed)
 * - Mempertahankan tag jurnalisme (<p>, <br>, <strong>, <em>, heading, table, blockquote, figure, img, dll)
 */
class HtmlSanitizer
{
    /**
     * Whitelist tag HTML yang diizinkan untuk naskah berita
     */
    protected static array $allowedTags = [
        'p', 'br', 'hr',
        'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'sub', 'sup', 'small', 'mark',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li',
        'blockquote', 'cite', 'q', 'div', 'span',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'colgroup', 'col',
        'a',
        'img', 'figure', 'figcaption', 'picture', 'source',
        'iframe',
        'pre', 'code'
    ];

    /**
     * Whitelist domain yang diizinkan pada tag <iframe>
     */
    protected static array $allowedIframeDomains = [
        'youtube.com',
        'www.youtube.com',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
        'youtu.be',
        'instagram.com',
        'www.instagram.com',
        'twitter.com',
        'x.com',
        'platform.twitter.com',
        'docs.google.com',
        'drive.google.com',
        'investor.id',
        'cms.investor.id',
    ];

    /**
     * Bersihkan konten HTML naskah berita secara menyeluruh
     */
    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $clean = $html;

        // 1. Hapus komentar HTML bersarang atau conditional comments
        $clean = preg_replace('/<!--\[if\s.*?\]>.*?<!\[endif\]-->/is', '', $clean);

        // 2. Hapus secara mutlak tag eksekusi & non-konten beserta isinya
        $dangerousTags = [
            'script', 'style', 'object', 'embed', 'applet', 'form', 
            'input', 'button', 'textarea', 'select', 'option', 
            'meta', 'link', 'base', 'frame', 'frameset'
        ];
        foreach ($dangerousTags as $tag) {
            $clean = preg_replace('#<' . $tag . '\b[^>]*>.*?</' . $tag . '>#is', '', $clean);
            $clean = preg_replace('#<' . $tag . '\b[^>]*\/?>#is', '', $clean);
        }

        // 3. Hapus seluruh atribut event handler JavaScript inline (on*, onerror, onload, onclick, onmouseover, dll)
        // Menangani kutip ganda, kutip tunggal, maupun tanpa kutip
        $clean = preg_replace('/\s*on[a-zA-Z0-9_-]+\s*=\s*(["\'])(.*?)\1/is', '', $clean);
        $clean = preg_replace('/\s*on[a-zA-Z0-9_-]+\s*=\s*[^"\'\s>]+/is', '', $clean);

        // 4. Hapus protokol URL berbahaya pada href dan src (javascript:, vbscript:, data:text/html, dll)
        $clean = preg_replace_callback(
            '/\b(href|src)\s*=\s*(["\'])(.*?)\2/is',
            function ($matches) {
                $attr = $matches[1];
                $quote = $matches[2];
                $url = trim($matches[3]);

                // Deteksi skema berbahaya (termasuk enkripsi URL/spasi tersembunyi)
                $normalizedUrl = preg_replace('/[\x00-\x1F\x7F\s]+/u', '', $url);
                if (preg_match('/^(javascript|vbscript|data(?!\s*:\s*image)):/i', $normalizedUrl)) {
                    return $attr . '=' . $quote . '#' . $quote;
                }
                return $attr . '=' . $quote . $url . $quote;
            },
            $clean
        );

        // 5. Filter ketat atribut <iframe>: Hanya izinkan domain whitelist resmi
        $clean = preg_replace_callback(
            '/<iframe\b([^>]*)>(.*?)<\/iframe>/is',
            function ($matches) {
                $attributes = $matches[1];
                $inner = $matches[2];

                // Ekstrak URL src
                if (preg_match('/\bsrc\s*=\s*(["\'])(.*?)\1/is', $attributes, $srcMatch)) {
                    $src = trim($srcMatch[2]);
                    
                    // Izinkan relative URL internal (misal /embed-chart/...)
                    if (str_starts_with($src, '/')) {
                        return '<iframe' . $attributes . '>' . $inner . '</iframe>';
                    }

                    $parsed = parse_url($src);
                    $host = strtolower($parsed['host'] ?? '');

                    // Periksa apakah host cocok dengan domain whitelist
                    $isAllowed = false;
                    foreach (self::$allowedIframeDomains as $domain) {
                        if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                            $isAllowed = true;
                            break;
                        }
                    }

                    if ($isAllowed) {
                        return '<iframe' . $attributes . '>' . $inner . '</iframe>';
                    }
                }

                // Jika iframe mengarah ke domain asing/tak dikenal, lucuti
                return '';
            },
            $clean
        );

        // 6. Whitelist Tag Enforcement: Bangun string tag yang diizinkan untuk strip_tags
        $allowedTagString = '<' . implode('><', self::$allowedTags) . '>';
        $clean = strip_tags($clean, $allowedTagString);

        // 7. Sanitasi ekspresi CSS berbahaya dalam style="..." (misal expression(...) atau behavior)
        $clean = preg_replace_callback(
            '/\bstyle\s*=\s*(["\'])(.*?)\1/is',
            function ($matches) {
                $quote = $matches[1];
                $style = $matches[2];

                if (preg_match('/(expression|behavior|javascript|vbscript|-moz-binding)/i', $style)) {
                    return '';
                }
                return 'style=' . $quote . $style . $quote;
            },
            $clean
        );

        return trim($clean);
    }

    /**
     * Pembersih teks murni untuk judul berita (hanya izinkan tag <i> dan <em> sesuai standar legacy)
     */
    public static function cleanTitle(?string $title): string
    {
        if ($title === null || trim($title) === '') {
            return '';
        }
        $stripped = strip_tags($title, '<i><em>');
        return trim(preg_replace('/\s*on[a-zA-Z0-9_-]+\s*=\s*(["\']).*?\1/is', '', $stripped));
    }

    /**
     * Pembersih ringkasan / summary (bersihkan 100% tag HTML)
     */
    public static function cleanSummary(?string $summary): string
    {
        if ($summary === null || trim($summary) === '') {
            return '';
        }
        return trim(strip_tags($summary));
    }
}

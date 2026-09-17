<?php

namespace App\Libraries;

/**
 * GeminiProvider
 * Ported Google Gemini Generative AI Provider for CodeIgniter 4
 * Features: Auto Summarization, Headline/Title Generation, Keyword/Tag Extraction, Key Points Bulleting
 */
class GeminiProvider
{
    protected string $apiKey;
    protected string $primaryModel;
    protected string $fallbackModel;
    protected string $tertiaryModel;
    protected ?string $lastModelUsed = null;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '') ?: (isset($_ENV['GEMINI_API_KEY']) ? $_ENV['GEMINI_API_KEY'] : getenv('GEMINI_API_KEY'));
        $this->primaryModel = env('GEMINI_PRIMARY_MODEL', 'gemini-3.5-flash-lite') ?: 'gemini-3.5-flash-lite';
        $this->fallbackModel = env('GEMINI_FALLBACK_MODEL', 'gemini-2.5-flash-lite') ?: 'gemini-2.5-flash-lite';
        $this->tertiaryModel = env('GEMINI_TERTIARY_MODEL', 'gemini-3.1-flash-lite') ?: 'gemini-3.1-flash-lite';
    }

    public function getPrimaryModel(): string
    {
        return $this->primaryModel;
    }

    public function getFallbackModel(): string
    {
        return $this->fallbackModel;
    }

    public function getTertiaryModel(): string
    {
        return $this->tertiaryModel;
    }

    public function getLastModelUsed(): ?string
    {
        return $this->lastModelUsed;
    }

    /**
     * Generate concise SEO summary (110 - 160 characters)
     */
    public function generateSummary(string $title, string $body): string
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException("Gemini API Key is not configured in .env.");
        }

        $prompt = "Buatkan ringkasan berita singkat padat dalam Bahasa Indonesia dari judul dan isi artikel berikut ini.\n\n";
        $prompt .= "Judul: " . $title . "\n";
        $prompt .= "Isi Artikel: " . strip_tags($body) . "\n\n";
        $prompt .= "Ketentuan Keras (HARD CONSTRAINT):\n";
        $prompt .= "1. Panjang ringkasan HARUS berada di antara 110 hingga 160 karakter.\n";
        $prompt .= "2. DILARANG KERAS melebihi 160 karakter dan dilarang kurang dari 110 karakter.\n";
        $prompt .= "3. Ringkasan harus padat, informatif, dan langsung ke inti berita finansial/pasar modal.\n";
        $prompt .= "4. Tulis hasil HANYA berupa teks biasa tanpa tanda kutip pembungkus dan tanpa format markdown.\n\n";
        $prompt .= "Ringkasan (tepat 110 - 160 karakter):";

        $result = $this->callWithFallback($prompt, 500);
        $clean = trim(trim($result), "\"'\n\r \t“”«»");
        if (mb_strlen($clean) > 160) {
            $sub = mb_substr($clean, 0, 160);
            $lastSpace = mb_strrpos($sub, ' ');
            if ($lastSpace !== false && $lastSpace > 110) {
                $clean = rtrim(mb_substr($sub, 0, $lastSpace), ',;') . '.';
            } else {
                $clean = $sub;
            }
        }
        return $clean;
    }

    /**
     * Generate punchy, attractive news title (max 70 characters)
     */
    public function generateTitle(string $body): string
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException("Gemini API Key is not configured in .env.");
        }

        $prompt = "Buatkan judul berita yang menarik, kredibel dan relevan dalam Bahasa Indonesia berdasarkan isi artikel berikut.\n\n";
        $prompt .= "Isi Artikel:\n" . strip_tags($body) . "\n\n";
        $prompt .= "Ketentuan Keras (HARD CONSTRAINT):\n";
        $prompt .= "1. Panjang judul HARUS MAKSIMAL 70 KARAKTER (tidak boleh melebihi 70 karakter sedikitpun).\n";
        $prompt .= "2. Buat judul yang memicu rasa ingin tahu pembaca investor/bisnis namun tetap informatif & akurat.\n";
        $prompt .= "3. Tulis HANYA judul saja tanpa tanda kutip pembungkus.\n";
        $prompt .= "4. Tulis hasil HANYA berupa teks biasa tanpa format markdown seperti tebal (**) atau miring (*).\n\n";
        $prompt .= "Judul (maksimal 70 karakter):";

        $result = $this->callWithFallback($prompt, 200);
        $clean = trim(trim($result), "\"'\n\r \t“”«»");
        if (mb_strlen($clean) > 70) {
            $sub = mb_substr($clean, 0, 70);
            $lastSpace = mb_strrpos($sub, ' ');
            if ($lastSpace !== false && $lastSpace > 40) {
                $clean = mb_substr($sub, 0, $lastSpace);
            } else {
                $clean = $sub;
            }
        }
        return $clean;
    }

    /**
     * Generate 3 - 5 relevant tags/keywords (comma separated)
     * Prioritizes existing candidate keywords from the database if provided
     */
    public function generateTags(string $body, string $title = '', array $existingKeywords = []): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException("Gemini API Key is not configured in .env.");
        }

        $prompt = "Tentukan 3 sampai 5 tag kata kunci (keywords) pasar modal, emiten, atau ekonomi yang paling relevan dalam Bahasa Indonesia berdasarkan isi artikel berikut.\n\n";
        if (!empty(trim($title))) {
            $prompt .= "Judul Artikel:\n" . trim($title) . "\n\n";
        }
        $prompt .= "Isi Artikel:\n" . strip_tags($body) . "\n\n";

        if (!empty($existingKeywords)) {
            $candidateSample = array_slice($existingKeywords, 0, 70);
            $prompt .= "Daftar Kata Kunci yang SUDAH TERDAFTAR di Database Redaksi kami:\n";
            $prompt .= "[" . implode(', ', $candidateSample) . "]\n\n";
            $prompt .= "Ketentuan Penting (SANGAT KRUSIAL):\n";
            $prompt .= "1. PRIORITAS UTAMA: Periksa dan gunakan kata kunci dari 'Daftar Kata Kunci yang SUDAH TERDAFTAR di Database' di atas jika ada yang relevan dan cocok dengan isi artikel.\n";
            $prompt .= "2. HANYA JIKA tidak ada kata kunci di daftar database yang cocok, atau artikel ini membahas entitas/emiten/istilah baru yang spesifik (misal nama emiten IPO baru atau istilah baru), kamu DIPERBOLEHKAN membuat kata kunci baru.\n";
            $prompt .= "3. Tag harus singkat (1-2 kata per tag, misal: Saham BBCA, IHSG, Bank Mandiri, Dividen).\n";
            $prompt .= "4. Tulis tag dipisahkan dengan koma, contoh: Saham, Perbankan, Laba Bersih\n";
            $prompt .= "5. Tulis HANYA daftar tag saja tanpa pengantar, penutup, nomor, atau tanda kurung.\n";
            $prompt .= "6. Tulis hasil HANYA berupa teks biasa tanpa format markdown seperti tebal (**) atau miring (*).\n\n";
        } else {
            $prompt .= "Ketentuan Penting:\n";
            $prompt .= "1. Prioritaskan kata kunci standar industri finansial, bursa efek, atau perbankan yang umum dan konsisten.\n";
            $prompt .= "2. Tag harus singkat (1-2 kata per tag, misal: Saham BBCA, IHSG, Bank Mandiri, Dividen).\n";
            $prompt .= "3. Tulis tag dipisahkan dengan koma, contoh: Saham, Perbankan, Laba Bersih\n";
            $prompt .= "4. Tulis HANYA daftar tag saja tanpa teks tambahan apapun.\n";
            $prompt .= "5. Tulis hasil HANYA berupa teks biasa tanpa format markdown seperti tebal (**) atau miring (*).\n\n";
        }
        $prompt .= "Daftar Tag:";

        $raw = $this->callWithFallback($prompt, 300);
        $tags = array_map(function($t) {
            $clean = trim(trim($t), " \t\n\r\0\x0B\"'#[]•-*");
            return $clean;
        }, explode(',', $raw));
        return array_values(array_filter($tags, fn($t) => !empty($t)));
    }

    /**
     * Generate 3 bullet key points using bullet symbol (•)
     */
    public function generatePoints(string $body): string
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException("Gemini API Key is not configured in .env.");
        }

        $prompt = "Buatkan daftar poin-poin penting (maksimal 3 butir) dalam Bahasa Indonesia yang merupakan kesimpulan utama berdasarkan isi artikel berikut.\n\n";
        $prompt .= "Isi Artikel:\n" . strip_tags($body) . "\n\n";
        $prompt .= "Ketentuan Penting:\n";
        $prompt .= "1. Tulis poin-poin penting dalam bentuk bullet list sebanyak maksimal 3 butir menggunakan karakter bulat biasa (•).\n";
        $prompt .= "2. Setiap poin harus singkat, padat, informatif, dan berupa kesimpulan utama dari isi artikel.\n";
        $prompt .= "3. Tulis hasil HANYA berupa daftar poin saja tanpa judul, kata pengantar, atau penutup.\n";
        $prompt .= "4. Tulis hasil HANYA berupa teks biasa tanpa format markdown seperti tebal (**) atau miring (*).\n\n";
        $prompt .= "Poin Penting (menggunakan simbol •):";

        return $this->callWithFallback($prompt, 500);
    }

    /**
     * Call Primary model with automated fallback chain on Rate Limit (429), Timeout, 404, or Server Overload (503)
     */
    protected function callWithFallback(string $prompt, int $maxTokens = 500, array $options = []): string
    {
        $models = array_unique([$this->primaryModel, $this->fallbackModel, $this->tertiaryModel]);
        $lastException = null;

        foreach ($models as $idx => $model) {
            try {
                return $this->callGeminiApi($model, $prompt, $maxTokens, $options);
            } catch (\Throwable $e) {
                $lastException = $e;
                $msg = $e->getMessage();
                log_message('warning', "Gemini model '{$model}' failed: {$msg}");
                
                // If there is another fallback model in line, continue trying
                if ($idx < count($models) - 1) {
                    $nextModel = $models[$idx + 1];
                    log_message('notice', "Retrying with next fallback model: {$nextModel}");
                    continue;
                }
            }
        }

        throw $lastException ?? new \RuntimeException("All Gemini models in fallback chain failed.");
    }

    /**
     * cURL HTTPS Client to Google Gemini API
     */
    protected function callGeminiApi(string $model, string $prompt, int $maxTokens = 500, array $options = []): string
    {
        $this->lastModelUsed = $model;
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":generateContent?key=" . $this->apiKey;

        $generationConfig = [
            'maxOutputTokens' => $maxTokens,
            'temperature'     => $options['temperature'] ?? 0.7,
        ];

        if (!empty($options['responseMimeType'])) {
            $generationConfig['responseMimeType'] = $options['responseMimeType'];
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => $generationConfig
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => $options['timeout'] ?? 35,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \RuntimeException("CURL Error: " . $curlError);
        }

        $result = json_decode($response, true);

        if ($httpCode !== 200) {
            $errorMsg = $result['error']['message'] ?? 'Unknown API Error';
            throw new \RuntimeException("Gemini API Error (" . $httpCode . "): " . $errorMsg);
        }

        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $text = trim($result['candidates'][0]['content']['parts'][0]['text']);
            if (!empty($options['raw']) || !empty($options['responseMimeType'])) {
                return $text;
            }
            return str_replace(['**', '*'], '', $text);
        }

        throw new \RuntimeException("Unexpected response structure from Gemini API.");
    }

    /**
     * =========================================================================
     * ORIGINAL METHOD (COMMENTED OUT SEMENTARA SESUAI INSTRUKSI)
     * Batasan ketat didefinisikan langsung via backend (contoh: wordCount < 150 throw exception)
     * =========================================================================
     *
     * public function generateSeoQualityScoreOriginal(string $title, string $body, string $summary = '', array $keywords = []): array
     * {
     *     if (empty($this->apiKey)) {
     *         throw new \RuntimeException("Gemini API Key is not configured in .env.");
     *     }
     *
     *     $cleanBody = strip_tags($body);
     *     $wordCount = str_word_count($cleanBody);
     *
     *     if ($wordCount < 150) {
     *         throw new \InvalidArgumentException("Artikel terlalu pendek untuk dinilai oleh AI (minimal 150 kata, saat ini {$wordCount} kata).");
     *     }
     *
     *     $keywordStr = !empty($keywords) ? implode(', ', $keywords) : 'Tidak ada kata kunci terdaftar';
     *
     *     $prompt = <<<PROMPT
     * Kamu adalah Senior Editorial SEO Analyst untuk media berita finansial dan investasi (Investor Daily / Investor.id).
     * Tugasmu adalah menganalisis kualitas konten artikel berikut secara objektif dan mendalam dari sudut pandang editorial & SEO.
     *
     * DATA ARTIKEL:
     * - Judul: {$title}
     * - Ringkasan / Meta Description: {$summary}
     * - Kata Kunci Terdaftar: {$keywordStr}
     * - Isi Artikel:
     * {$cleanBody}
     *
     * KRITERIA PENILAIAN (Skor 0-100 untuk tiap kriteria):
     * 1. search_intent (bobot 30% dari skor AI)
     * 2. content_depth (bobot 30% dari skor AI)
     * 3. click_readiness (bobot 20% dari skor AI)
     * 4. keyword_naturalness (bobot 20% dari skor AI)
     * ...
     * PROMPT;
     *     ...
     * }
     */

    /**
     * Generate SEO Quality Score (AI Analysis - 4 Criteria)
     * Versi Fleksibel: Tidak menolak artikel pendek di backend, melainkan parameter panjang
     * judul (maks 70 char), summary (110-160 char), dan artikel (min 300 kata/char) diinstruksikan
     * ke AI untuk dinilai dan disesuaikan bobotnya secara dinamis & proporsional.
     */
    public function generateSeoQualityScore(string $title, string $body, string $summary = '', array $keywords = []): array
    {
        return $this->generateSeoQualityScoreFlexible($title, $body, $summary, $keywords);
    }

    /**
     * Versi Fleksibel (Tanpa Hard Block Backend, Bobot Disesuaikan Proporsional oleh AI)
     */
    public function generateSeoQualityScoreFlexible(string $title, string $body, string $summary = '', array $keywords = []): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException("Gemini API Key is not configured in .env.");
        }

        $cleanBody = strip_tags($body);
        $wordCount = str_word_count($cleanBody);
        $charCount = mb_strlen($cleanBody);
        $titleLen = mb_strlen(trim($title));
        $summaryLen = mb_strlen(trim($summary));

        $keywordStr = !empty($keywords) ? implode(', ', $keywords) : 'Tidak ada kata kunci terdaftar';

        $prompt = <<<PROMPT
Kamu adalah Senior Editorial SEO Analyst untuk media berita finansial dan investasi (Investor Daily / Investor.id).
Tugasmu adalah menganalisis kualitas konten artikel berikut secara objektif dan mendalam dari sudut pandang editorial & SEO.

DATA ARTIKEL:
- Judul ({$titleLen} karakter): {$title}
- Ringkasan / Meta Description ({$summaryLen} karakter): {$summary}
- Kata Kunci Terdaftar: {$keywordStr}
- Panjang Naskah Artikel: {$wordCount} kata ({$charCount} karakter)
- Isi Artikel:
{$cleanBody}

ATURAN DAN STANDAR KONTEN (Perhitungkan dalam penilaian skor):
- Judul: Ideal maksimal 70 karakter (agar tidak terpotong di Google SERP). Jika lebih dari 70 karakter atau terlalu pendek, sesuaikan nilai skor click_readiness.
- Ringkasan / Meta Description: Ideal 110 - 160 karakter. Jika kurang dari 110 atau melebihi 160 karakter, sesuaikan nilai skor click_readiness.
- Isi Artikel: Minimal 300 kata (atau > 300 karakter). Jika naskah terlalu pendek, kurangi skor content_depth secara proporsional. Jangan menolak artikel pendek, tapi turunkan skor kedalaman kontennya sesuai substansi.

KRITERIA PENILAIAN (Skor 0-100 untuk tiap kriteria):

1. search_intent (bobot 30% dari skor AI):
   - Apakah artikel ini menjawab apa yang dicari investor atau pembaca finansial terkait topik/kata kunci tersebut?
   - Apakah informasi relevan, fokus, dan tidak melenceng?

2. content_depth (bobot 30% dari skor AI):
   - Kedalaman analisis, kelengkapan data/angka (misal persentase laba, harga saham, proyeksi, kutipan narasumber kompeten).
   - Perhitungkan panjang artikel (standar berita minimal 300 kata). Jika artikel terlalu singkat/minim data, berikan nilai content_depth yang rendah secara objektif.

3. click_readiness (bobot 20% dari skor AI):
   - Kesesuaian panjang judul (maksimal 70 karakter) dan ringkasan (110-160 karakter).
   - Daya tarik judul dan ringkasan (Click-Through Rate potential) tanpa menjadi clickbait murahan.

4. keyword_naturalness (bobot 20% dari skor AI):
   - Alami tidaknya penempatan kata kunci terdaftar ({$keywordStr}) dalam artikel.
   - Tidak terjadi keyword stuffing (pemaksaan kata kunci berlebihan) atau sebaliknya topik sama sekali tidak menyinggung kata kunci tersebut.

INSTRUKSI OUTPUT:
Kembalikan HANYA format JSON valid tanpa teks pengantar atau markdown wrapper. Struktur JSON HARUS persis seperti ini:
{
  "search_intent": {
    "score": 85,
    "reasoning": "Penjelasan singkat 1-2 kalimat mengapa skor ini diberikan."
  },
  "content_depth": {
    "score": 75,
    "reasoning": "Penjelasan singkat 1-2 kalimat mengapa skor ini diberikan."
  },
  "click_readiness": {
    "score": 90,
    "reasoning": "Penjelasan singkat 1-2 kalimat mengapa skor ini diberikan."
  },
  "keyword_naturalness": {
    "score": 80,
    "reasoning": "Penjelasan singkat 1-2 kalimat mengapa skor ini diberikan."
  },
  "recommendations": [
    "Rekomendasi spesifik 1 untuk meningkatkan kualitas artikel ini.",
    "Rekomendasi spesifik 2 untuk meningkatkan kualitas artikel ini.",
    "Rekomendasi spesifik 3 untuk meningkatkan kualitas artikel ini."
  ]
}
PROMPT;

        $options = [
            'temperature'      => 0.1,
            'responseMimeType' => 'application/json',
            'raw'              => true,
            'timeout'          => 45,
        ];

        $rawJson = $this->callWithFallback($prompt, 1200, $options);
        $cleanJson = trim($rawJson);

        // Remove markdown backticks if any slipped through
        if (str_starts_with($cleanJson, '```json')) {
            $cleanJson = substr($cleanJson, 7);
        }
        if (str_starts_with($cleanJson, '```')) {
            $cleanJson = substr($cleanJson, 3);
        }
        if (str_ends_with($cleanJson, '```')) {
            $cleanJson = substr($cleanJson, 0, -3);
        }
        $cleanJson = trim($cleanJson);

        $parsed = json_decode($cleanJson, true);
        if (!is_array($parsed)) {
            throw new \RuntimeException("Gemini response is not valid JSON: " . substr($rawJson, 0, 200));
        }

        // Validate required keys
        $requiredKeys = ['search_intent', 'content_depth', 'click_readiness', 'keyword_naturalness'];
        foreach ($requiredKeys as $key) {
            if (!isset($parsed[$key]['score']) || !isset($parsed[$key]['reasoning'])) {
                throw new \RuntimeException("Gemini response missing criteria '{$key}' or invalid structure.");
            }
            // Clamp score between 0 and 100
            $parsed[$key]['score'] = max(0, min(100, (float)$parsed[$key]['score']));
        }

        if (!isset($parsed['recommendations']) || !is_array($parsed['recommendations'])) {
            $parsed['recommendations'] = [];
        }

        return $parsed;
    }

    /**
     * Allowed domains for scraping to prevent SSRF
     */
    protected array $allowedDomains = [
        'antaranews.com',
        'www.antaranews.com',
        'investor.id',
        'www.investor.id',
        'beritasatu.com',
        'www.beritasatu.com'
    ];

    /**
     * Scrape article body and og:image thumbnail from an external URL
     */
    public function scrapeArticle(string $url): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException("Gemini API Key is not configured in .env.");
        }

        // 1. Validate domain whitelist
        $this->validateScrapeUrl($url);

        // 2. Fetch HTML
        $html = $this->fetchUrlContent($url);

        // 2b. Extract Article Title
        $title = null;
        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\'](.*?)["\']/i', $html, $mTitle)) {
            $title = html_entity_decode(trim($mTitle[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } elseif (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $mTitle)) {
            $title = html_entity_decode(trim(strip_tags($mTitle[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if ($title) {
            $title = preg_replace('/\s*[-|]\s*(ANTARA News|Investor\.id|BeritaSatu).*$/i', '', $title);
            $title = trim($title);
        }

        // 3. Extract Open Graph image for thumbnail
        $thumbnailUrl = null;
        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\'](.*?)["\']/i', $html, $m)) {
            $thumbnailUrl = $m[1];
        } elseif (preg_match('/<meta[^>]+content=["\'](https?:\/\/[^"\']+)["\'][^>]+property=["\']og:image["\']/i', $html, $m)) {
            $thumbnailUrl = $m[1];
        }

        // 3b. Extract Image Caption & Description
        $thumbnailCaption = '';
        $thumbnailDescription = '';

        // Prioritize <figcaption>
        if (preg_match('/<figcaption[^>]*>(.*?)<\/figcaption>/is', $html, $mFigCap)) {
            $rawCap = trim(strip_tags($mFigCap[1]));
            if (!empty($rawCap)) {
                $thumbnailDescription = html_entity_decode($rawCap, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        // Secondary fallback: caption class in div, p, span
        if (empty($thumbnailDescription)) {
            if (preg_match('/<(?:div|p|span)[^>]+class=["\'][^"\']*(?:caption|text-muted|img-desc|image-desc|credit|foto-caption)[^"\']*["\'][^>]*>(.*?)<\/(?:div|p|span)>/is', $html, $mDivCap)) {
                $rawCap = trim(strip_tags($mDivCap[1]));
                if (!empty($rawCap)) {
                    $thumbnailDescription = html_entity_decode($rawCap, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
            }
        }

        // Tertiary fallback: og:image:alt or twitter:image:alt
        if (empty($thumbnailDescription)) {
            if (preg_match('/<meta[^>]+(?:property|name)=["\'](?:og|twitter):image:alt["\'][^>]+content=["\'](.*?)["\']/i', $html, $mAlt)) {
                $thumbnailDescription = html_entity_decode(trim($mAlt[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        // Quaternary fallback: article title
        if (empty($thumbnailDescription) && !empty($title)) {
            $thumbnailDescription = 'Dokumentasi visual artikel: ' . $title;
        }

        // Clean up whitespace
        $thumbnailDescription = preg_replace('/\s+/', ' ', trim($thumbnailDescription));

        // Generate concise thumbnail caption from description
        $thumbnailCaption = $thumbnailDescription;
        if (mb_strlen($thumbnailCaption) > 140) {
            if (preg_match('/^(.*?[.!?])(?:\s|$)/u', $thumbnailDescription, $mSentence)) {
                $thumbnailCaption = trim($mSentence[1]);
            } else {
                $thumbnailCaption = mb_substr($thumbnailDescription, 0, 137) . '...';
            }
        }

        // 4. Download and cache image locally if available
        $savedImage = null;
        if ($thumbnailUrl) {
            try {
                $savedImage = $this->saveLocalImage($thumbnailUrl);
            } catch (\Throwable $e) {
                log_message('error', 'Thumbnail download failed: ' . $e->getMessage());
                $savedImage = null;
            }
        }

        // 5. Optimize HTML context window
        $stripped = strip_tags($html, '<p><h1><h2><h3><h4><h5><h6><blockquote><ul><ol><li><a><strong><em><br><img>');
        $truncatedHtml = mb_substr($stripped, 0, 30000);

        // 6. Semantic extraction via Gemini AI
        $prompt = "Kamu adalah alat ekstraksi konten artikel berita yang sangat presisi.\n\n";
        $prompt .= "Tugas: Dari potongan HTML halaman berita berikut, ekstrak HANYA isi artikel utama (body text).\n\n";
        $prompt .= "PENTING - JANGAN SERTAKAN ELEMEN BERIKUT DALAM OUTPUT:\n";
        $prompt .= "- Judul / headline artikel (biasanya tag <h1> atau <h2> paling atas)\n";
        $prompt .= "- Navigasi, menu, sidebar, iklan, footer, komentar pembaca, script, metadata, breadcrumb\n";
        $prompt .= "- Artikel terkait, nama penulis / byline, tanggal publikasi\n\n";
        $prompt .= "Format output:\n";
        $prompt .= "1. Mulai langsung dari paragraf PERTAMA isi berita.\n";
        $prompt .= "2. Gunakan tag HTML sederhana: <p>, <h2>, <h3>, <blockquote>, <ul>, <ol>, <li>, <strong>, <em>\n";
        $prompt .= "3. JANGAN tambahkan class, style, atau atribut apapun pada tag HTML.\n";
        $prompt .= "4. Pertahankan urutan paragraf asli.\n";
        $prompt .= "5. Jangan menambahkan komentar, catatan, atau teks tambahan apapun.\n\n";
        $prompt .= "HTML Halaman:\n" . $truncatedHtml;

        $body = $this->callWithFallback($prompt, 4096);

        return [
            'title'                 => $title,
            'body'                  => $body,
            'thumbnail_url'         => $savedImage ?: '/img/investor_placeholder.jpg',
            'thumbnail_caption'     => $thumbnailCaption,
            'thumbnail_description' => $thumbnailDescription,
            'source_url'            => $url,
            'model'                 => $this->getLastModelUsed(),
        ];
    }

    protected function validateScrapeUrl(string $url): void
    {
        $parsed = parse_url($url);
        if (!isset($parsed['host']) || empty($parsed['host'])) {
            throw new \InvalidArgumentException("URL tidak valid.");
        }

        $host = strtolower($parsed['host']);

        // Strict SSRF Defense: Block private & internal IP addresses (including legacy IPs 10.0.4.16, 172.*, 192.168.*)
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException("Akses IP address secara langsung diblokir demi keamanan.");
        }

        $allowed = false;
        foreach ($this->allowedDomains as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            throw new \InvalidArgumentException("Domain '{$host}' tidak diizinkan untuk di-scrape. Sumber yang diizinkan: Antaranews, Investor.id, Beritasatu.");
        }
    }

    protected function fetchUrlContent(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: id-ID,id;q=0.9,en;q=0.8'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("Gagal mengambil halaman: " . $error);
        }
        if ($httpCode !== 200) {
            throw new \RuntimeException("Halaman mengembalikan status HTTP " . $httpCode);
        }
        if (empty($response)) {
            throw new \RuntimeException("Halaman tidak memiliki konten.");
        }

        return $response;
    }

    protected function saveLocalImage(string $imageUrl): ?string
    {
        // 1. Validasi skema URL (hanya http dan https)
        $parsed = parse_url($imageUrl);
        if (!$parsed || empty($parsed['host']) || !in_array(strtolower($parsed['scheme'] ?? ''), ['http', 'https'])) {
            return null;
        }

        $host = strtolower($parsed['host']);

        // 2. Proteksi SSRF (SEC-07): Tolak loopback, link-local metadata cloud, dan private IP range (RFC 1918)
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0', '169.254.169.254'])) {
            return null;
        }

        $resolvedIp = gethostbyname($host);
        if ($resolvedIp !== $host) {
            if (filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return null;
            }
        }

        $ch = curl_init($imageUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        ]);

        $imgData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && !empty($imgData)) {
            $processor = new ImageProcessor();
            $savedPath = $processor->processFromString($imgData, 'scrape');
            if ($savedPath) {
                return $savedPath;
            }
        }

        return null;
    }
}

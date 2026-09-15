<?php

namespace App\Libraries;

use App\Libraries\GeminiProvider;

/**
 * SeoScorer
 * Hybrid SEO On-Page Readiness Scoring Engine for Investor CMS
 * 
 * Composition:
 * - 60% Rule-Based Score (12 Deterministic Technical SEO Parameters)
 * - 40% AI Quality Score (4 Editorial & Content Depth Criteria via Gemini)
 * 
 * If AI fails or content < 150 words: Rule-Based only (scaled to 100).
 */
class SeoScorer
{
    protected GeminiProvider $gemini;

    public function __construct(?GeminiProvider $gemini = null)
    {
        $this->gemini = $gemini ?? new GeminiProvider();
    }

    /**
     * Run full hybrid score on an article
     *
     * @param array $article Article data: headline, body, summary, slug, etc.
     * @param array $keywords Array of keyword strings associated with this article
     * @param array $meta Array of metadata key-values (e.g. thumbnail)
     * @param bool $skipAi Whether to skip AI scoring (manual toggle or fallback)
     * @return array Complete scoring results
     */
    public function scoreArticle(array $article, array $keywords = [], array $meta = [], bool $skipAi = false): array
    {
        $headline  = $article['headline'] ?? '';
        $body      = $article['body'] ?? '';
        $summary   = $article['summary'] ?? '';
        $slug      = $article['slug'] ?? '';
        $thumbnail = $meta['thumbnail'] ?? '';

        // Step 1: Execute Rule-Based Scoring (12 Parameters)
        $ruleResult = $this->calculateRuleScore($headline, $body, $summary, $slug, $thumbnail, $keywords);

        $aiResult = null;
        $aiError = null;
        $modelUsed = null;

        // Step 2: Execute AI Scoring (if not skipped)
        if (!$skipAi) {
            $cleanBody = strip_tags($body);
            $wordCount = str_word_count($cleanBody);

            if ($wordCount < 150) {
                $aiError = "Artikel terlalu pendek ({$wordCount} kata, minimal 150 kata). Penilaian AI dilewati.";
            } else {
                try {
                    $aiResult = $this->gemini->generateSeoQualityScore($headline, $body, $summary, $keywords);
                    $modelUsed = $this->gemini->getLastModelUsed();
                } catch (\Throwable $e) {
                    $aiError = $e->getMessage();
                    log_message('warning', 'SeoScorer AI scoring failed: ' . $aiError);
                }
            }
        }

        // Step 3: Calculate Final Weighted Score
        $ruleScore = $ruleResult['total_score']; // 0 - 100

        if ($aiResult !== null) {
            // AI sub-score: search_intent(30%) + content_depth(30%) + click_readiness(20%) + keyword_naturalness(20%)
            $aiScore = ($aiResult['search_intent']['score'] * 0.30)
                     + ($aiResult['content_depth']['score'] * 0.30)
                     + ($aiResult['click_readiness']['score'] * 0.20)
                     + ($aiResult['keyword_naturalness']['score'] * 0.20);
            $aiScore = round($aiScore, 2);

            // Final: 60% Rule + 40% AI
            $finalScore = round(($ruleScore * 0.60) + ($aiScore * 0.40), 2);
        } else {
            // Fallback: 100% Rule Score
            $aiScore = null;
            $finalScore = round($ruleScore, 2);
        }

        // Step 4: Determine Category
        $category = $this->getCategory($finalScore);

        // Step 5: Consolidate Recommendations
        $allRecommendations = [];
        // Add rule-based recommendations
        foreach ($ruleResult['parameters'] as $param) {
            if ($param['status'] === 'fail' || $param['status'] === 'warning') {
                $allRecommendations[] = [
                    'type'    => 'technical',
                    'title'   => $param['title'],
                    'message' => $param['message']
                ];
            }
        }
        // Add AI recommendations
        if ($aiResult !== null && !empty($aiResult['recommendations'])) {
            foreach ($aiResult['recommendations'] as $rec) {
                $allRecommendations[] = [
                    'type'    => 'editorial',
                    'title'   => 'Saran AI',
                    'message' => $rec
                ];
            }
        }

        return [
            'final_score'         => $finalScore,
            'rule_score'          => $ruleScore,
            'ai_score'            => $aiScore,
            'score_category'      => $category['name'],
            'score_label'         => $category['label'],
            'score_color'         => $category['color'],
            'rule_details'        => $ruleResult['parameters'],
            'ai_feedback'         => $aiResult,
            'ai_error'            => $aiError,
            'recommendations'     => $allRecommendations,
            'model_used'          => $modelUsed,
            'is_ai_included'      => ($aiResult !== null),
        ];
    }

    /**
     * Calculate 12 Rule-Based SEO Parameters (Max score 100)
     */
    public function calculateRuleScore(
        string $headline,
        string $body,
        string $summary,
        string $slug,
        string $thumbnail,
        array $keywords
    ): array {
        $params = [];
        $totalScore = 0;

        // Clean plain texts
        $cleanHeadline = trim($headline);
        $cleanSummary  = trim($summary);
        $cleanBody     = strip_tags($body);
        $headlineLen   = mb_strlen($cleanHeadline);
        $summaryLen    = mb_strlen($cleanSummary);
        $wordCount     = str_word_count($cleanBody);

        // Normalize keywords
        $normalizedKeywords = array_map(fn($k) => mb_strtolower(trim($k)), $keywords);
        $normalizedKeywords = array_values(array_filter($normalizedKeywords, fn($k) => !empty($k)));

        // -------------------------------------------------------------
        // Parameter 1: Title Length (Bobot: 10)
        // -------------------------------------------------------------
        $p1 = [
            'key'         => 'title_length',
            'title'       => 'Panjang Judul',
            'weight'      => 10,
            'score'       => 0,
            'status'      => 'fail',
            'message'     => '',
            'current_val' => "{$headlineLen} karakter",
            'ideal_val'   => '50 - 70 karakter'
        ];
        if ($headlineLen >= 50 && $headlineLen <= 70) {
            $p1['score']   = 10;
            $p1['status']  = 'pass';
            $p1['message'] = "Panjang judul ideal ({$headlineLen} karakter).";
        } elseif (($headlineLen >= 40 && $headlineLen < 50) || ($headlineLen > 70 && $headlineLen <= 80)) {
            $p1['score']   = 6;
            $p1['status']  = 'warning';
            $p1['message'] = "Panjang judul cukup baik ({$headlineLen} karakter), optimalnya 50-70 karakter.";
        } else {
            $p1['score']   = 2;
            $p1['status']  = 'fail';
            $p1['message'] = $headlineLen < 40
                ? "Judul terlalu pendek ({$headlineLen} karakter, minimal 40-50 karakter)."
                : "Judul terlalu panjang ({$headlineLen} karakter, berisiko terpotong di Google SERP).";
        }
        $params[] = $p1;
        $totalScore += $p1['score'];

        // -------------------------------------------------------------
        // Parameter 2: Keyword in Title (Bobot: 10)
        // -------------------------------------------------------------
        $p2 = [
            'key'         => 'keyword_in_title',
            'title'       => 'Kata Kunci di Judul',
            'weight'      => 10,
            'score'       => 0,
            'status'      => 'fail',
            'message'     => '',
            'current_val' => count($normalizedKeywords) > 0 ? 'Dicek terhadap tag terdaftar' : 'Belum ada tag',
            'ideal_val'   => 'Minimal 1 kata kunci muncul di judul'
        ];
        if (empty($normalizedKeywords)) {
            $p2['score']   = 0;
            $p2['status']  = 'fail';
            $p2['message'] = 'Artikel belum memiliki tag/kata kunci terdaftar.';
        } else {
            $lowerHeadline = mb_strtolower($cleanHeadline);
            $foundInTitle = false;
            $matchedKeyword = '';
            foreach ($normalizedKeywords as $kw) {
                if (mb_strpos($lowerHeadline, $kw) !== false) {
                    $foundInTitle = true;
                    $matchedKeyword = $kw;
                    break;
                }
            }

            if ($foundInTitle) {
                $p2['score']   = 10;
                $p2['status']  = 'pass';
                $p2['message'] = "Kata kunci '{$matchedKeyword}' ditemukan di dalam judul.";
                $p2['current_val'] = "Ditemukan: {$matchedKeyword}";
            } else {
                $p2['score']   = 0;
                $p2['status']  = 'fail';
                $p2['message'] = 'Tidak ada kata kunci terdaftar yang ditemukan di judul.';
            }
        }
        $params[] = $p2;
        $totalScore += $p2['score'];

        // -------------------------------------------------------------
        // Parameter 3: Meta Description Length (Summary) (Bobot: 10)
        // -------------------------------------------------------------
        $p3 = [
            'key'         => 'meta_description_length',
            'title'       => 'Panjang Ringkasan (Meta Description)',
            'weight'      => 10,
            'score'       => 0,
            'status'      => 'fail',
            'message'     => '',
            'current_val' => "{$summaryLen} karakter",
            'ideal_val'   => '120 - 160 karakter'
        ];
        if ($summaryLen >= 120 && $summaryLen <= 160) {
            $p3['score']   = 10;
            $p3['status']  = 'pass';
            $p3['message'] = "Panjang ringkasan ideal ({$summaryLen} karakter).";
        } elseif (($summaryLen >= 90 && $summaryLen < 120) || ($summaryLen > 160 && $summaryLen <= 180)) {
            $p3['score']   = 6;
            $p3['status']  = 'warning';
            $p3['message'] = "Panjang ringkasan ({$summaryLen} karakter) cukup, optimalnya 120-160 karakter.";
        } else {
            $p3['score']   = 2;
            $p3['status']  = 'fail';
            $p3['message'] = $summaryLen === 0 
                ? 'Ringkasan masih kosong.' 
                : ($summaryLen < 90 ? "Ringkasan terlalu singkat ({$summaryLen} karakter)." : "Ringkasan terlalu panjang ({$summaryLen} karakter).");
        }
        $params[] = $p3;
        $totalScore += $p3['score'];

        // -------------------------------------------------------------
        // Parameter 4: Keyword in Meta Description (Bobot: 5)
        // -------------------------------------------------------------
        $p4 = [
            'key'         => 'keyword_in_summary',
            'title'       => 'Kata Kunci di Ringkasan',
            'weight'      => 5,
            'score'       => 0,
            'status'      => 'fail',
            'message'     => '',
            'current_val' => count($normalizedKeywords) > 0 ? 'Dicek terhadap tag terdaftar' : 'Belum ada tag',
            'ideal_val'   => 'Minimal 1 kata kunci muncul di ringkasan'
        ];
        if (empty($normalizedKeywords) || $summaryLen === 0) {
            $p4['score']   = 0;
            $p4['status']  = 'fail';
            $p4['message'] = $summaryLen === 0 ? 'Ringkasan kosong.' : 'Belum ada kata kunci terdaftar.';
        } else {
            $lowerSummary = mb_strtolower($cleanSummary);
            $foundInSummary = false;
            $matchedKw = '';
            foreach ($normalizedKeywords as $kw) {
                if (mb_strpos($lowerSummary, $kw) !== false) {
                    $foundInSummary = true;
                    $matchedKw = $kw;
                    break;
                }
            }
            if ($foundInSummary) {
                $p4['score']   = 5;
                $p4['status']  = 'pass';
                $p4['message'] = "Kata kunci '{$matchedKw}' ditemukan di ringkasan.";
                $p4['current_val'] = "Ditemukan: {$matchedKw}";
            } else {
                $p4['score']   = 0;
                $p4['status']  = 'fail';
                $p4['message'] = 'Tidak ada kata kunci terdaftar di ringkasan.';
            }
        }
        $params[] = $p4;
        $totalScore += $p4['score'];

        // -------------------------------------------------------------
        // Parameter 5: Content Word Count (Bobot: 15)
        // -------------------------------------------------------------
        $p5 = [
            'key'         => 'content_word_count',
            'title'       => 'Panjang Konten (Jumlah Kata)',
            'weight'      => 15,
            'score'       => 0,
            'status'      => 'fail',
            'message'     => '',
            'current_val' => "{$wordCount} kata",
            'ideal_val'   => 'Minimal 300 kata (Hard news) / 600+ kata (In-depth)'
        ];
        if ($wordCount >= 600) {
            $p5['score']   = 15;
            $p5['status']  = 'pass';
            $p5['message'] = "Panjang konten sangat mendalam ({$wordCount} kata).";
        } elseif ($wordCount >= 300) {
            $p5['score']   = 12;
            $p5['status']  = 'pass';
            $p5['message'] = "Panjang konten memenuhi standar berita ({$wordCount} kata).";
        } elseif ($wordCount >= 150) {
            $p5['score']   = 7;
            $p5['status']  = 'warning';
            $p5['message'] = "Konten agak pendek ({$wordCount} kata). Disarankan minimal 300 kata.";
        } else {
            $p5['score']   = 2;
            $p5['status']  = 'fail';
            $p5['message'] = "Konten terlalu pendek ({$wordCount} kata, minimal 150 kata).";
        }
        $params[] = $p5;
        $totalScore += $p5['score'];

        // -------------------------------------------------------------
        // Parameter 6: Keyword in First Paragraph (Bobot: 10)
        // -------------------------------------------------------------
        $p6 = [
            'key'         => 'keyword_in_first_p',
            'title'       => 'Kata Kunci di Paragraf Pertama',
            'weight'      => 10,
            'score'       => 0,
            'status'      => 'fail',
            'message'     => '',
            'current_val' => '-',
            'ideal_val'   => 'Minimal 1 kata kunci muncul di 100 kata pertama'
        ];
        // Extract first ~100 words or first paragraph
        $first100Words = implode(' ', array_slice(explode(' ', $cleanBody), 0, 100));
        $lowerFirst100 = mb_strtolower($first100Words);

        if (empty($normalizedKeywords)) {
            $p6['score']   = 0;
            $p6['status']  = 'fail';
            $p6['message'] = 'Belum ada kata kunci terdaftar untuk diperiksa.';
        } else {
            $foundInFirstP = false;
            $firstPMatch = '';
            foreach ($normalizedKeywords as $kw) {
                if (mb_strpos($lowerFirst100, $kw) !== false) {
                    $foundInFirstP = true;
                    $firstPMatch = $kw;
                    break;
                }
            }
            if ($foundInFirstP) {
                $p6['score']   = 10;
                $p6['status']  = 'pass';
                $p6['message'] = "Kata kunci '{$firstPMatch}' ditemukan di lead/paragraf pembuka.";
                $p6['current_val'] = "Ditemukan: {$firstPMatch}";
            } else {
                $p6['score']   = 0;
                $p6['status']  = 'fail';
                $p6['message'] = 'Kata kunci terdaftar tidak ditemukan di 100 kata pertama artikel.';
            }
        }
        $params[] = $p6;
        $totalScore += $p6['score'];

        // -------------------------------------------------------------
        // Parameter 7: Slug Formatting (Bobot: 5)
        // -------------------------------------------------------------
        $p7 = [
            'key'         => 'slug_format',
            'title'       => 'Format URL Slug',
            'weight'      => 5,
            'score'       => 0,
            'status'      => 'fail',
            'message'     => '',
            'current_val' => $slug ?: '-',
            'ideal_val'   => 'Huruf kecil, tanda hubung (-), tanpa karakter khusus'
        ];
        if (empty($slug)) {
            $p7['score']   = 0;
            $p7['status']  = 'fail';
            $p7['message'] = 'Slug belum terbentuk.';
        } else {
            $cleanSlug = trim($slug);
            $slugLen = mb_strlen($cleanSlug);
            $isFormatted = preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $cleanSlug);

            if ($isFormatted && $slugLen <= 100) {
                $p7['score']   = 5;
                $p7['status']  = 'pass';
                $p7['message'] = 'URL slug bersih, SEO friendly, dan menggunakan tanda hubung.';
            } elseif ($isFormatted && $slugLen > 100) {
                $p7['score']   = 3;
                $p7['status']  = 'warning';
                $p7['message'] = "Format slug valid tetapi agak panjang ({$slugLen} karakter).";
            } else {
                $p7['score']   = 2;
                $p7['status']  = 'fail';
                $p7['message'] = 'Slug mengandung karakter non-standar atau format tidak konsisten.';
            }
        }
        $params[] = $p7;
        $totalScore += $p7['score'];

        // -------------------------------------------------------------
        // Parameter 8: Heading Structure (H2/H3) (Bobot: 10)
        // -------------------------------------------------------------
        $p8 = [
            'key'         => 'heading_structure',
            'title'       => 'Struktur Subheading (H2 / H3)',
            'weight'      => 10,
            'score'       => 0,
            'status'      => 'fail',
            'message'     => '',
            'current_val' => '0 subheadings',
            'ideal_val'   => 'Memiliki minimal 1 tag H2/H3 untuk artikel > 300 kata'
        ];
        $h2Count = preg_match_all('/<h2\b[^>]*>/i', $body);
        $h3Count = preg_match_all('/<h3\b[^>]*>/i', $body);
        $h1InBody = preg_match_all('/<h1\b[^>]*>/i', $body);
        $totalHeadings = $h2Count + $h3Count;
        $p8['current_val'] = "{$h2Count} H2, {$h3Count} H3";

        if ($h1InBody > 0) {
            $p8['score']   = 3;
            $p8['status']  = 'fail';
            $p8['message'] = 'Ditemukan tag H1 di dalam body artikel. H1 hanya boleh ada satu (Judul Utama).';
        } elseif ($totalHeadings >= 1) {
            $p8['score']   = 10;
            $p8['status']  = 'pass';
            $p8['message'] = "Struktur heading baik ({$h2Count} H2, {$h3Count} H3 ditemukan).";
        } else {
            if ($wordCount < 250) {
                // Short breaking news might not strictly require subheadings
                $p8['score']   = 7;
                $p8['status']  = 'warning';
                $p8['message'] = 'Artikel singkat tanpa subheading. Tambahkan H2 jika artikel dikembangkan.';
            } else {
                $p8['score']   = 0;
                $p8['status']  = 'fail';
                $p8['message'] = 'Artikel panjang tanpa subheading (H2/H3). Pembaca & Google kesulitan memindai struktur.';
            }
        }
        $params[] = $p8;
        $totalScore += $p8['score'];

        // -------------------------------------------------------------
        // Parameter 9: Paragraph Length (Bobot: 5)
        // -------------------------------------------------------------
        $p9 = [
            'key'         => 'paragraph_length',
            'title'       => 'Panjang Paragraf & Keterbacaan',
            'weight'      => 5,
            'score'       => 0,
            'status'      => 'fail',
            'message'     => '',
            'current_val' => '',
            'ideal_val'   => 'Paragraf ringkas (rata-rata < 80 kata per paragraf)'
        ];
        // Split by <p> tags
        preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', $body, $pMatches);
        $paragraphs = array_filter(array_map('strip_tags', $pMatches[1] ?? []));

        if (empty($paragraphs)) {
            // Check newline breaks if no <p> tags
            $paragraphs = array_filter(explode("\n", $cleanBody));
        }

        $pCount = count($paragraphs);
        if ($pCount === 0) {
            $p9['score']   = 2;
            $p9['status']  = 'fail';
            $p9['message'] = 'Tidak dapat mendeteksi pemisahan paragraf.';
            $p9['current_val'] = '0 paragraf';
        } else {
            $maxWordsInP = 0;
            $totalPWords = 0;
            foreach ($paragraphs as $pText) {
                $w = str_word_count($pText);
                $totalPWords += $w;
                if ($w > $maxWordsInP) {
                    $maxWordsInP = $w;
                }
            }
            $avgWordsPerP = round($totalPWords / max(1, $pCount), 1);
            $p9['current_val'] = "{$pCount} paragraf (rata-rata {$avgWordsPerP} kata, terpanjang {$maxWordsInP} kata)";

            if ($maxWordsInP <= 90 && $avgWordsPerP <= 60) {
                $p9['score']   = 5;
                $p9['status']  = 'pass';
                $p9['message'] = 'Keterbacaan sangat baik, paragraf padat dan mudah dibaca di mobile.';
            } elseif ($maxWordsInP <= 130) {
                $p9['score']   = 3;
                $p9['status']  = 'warning';
                $p9['message'] = "Ada paragraf yang cukup panjang ({$maxWordsInP} kata). Disarankan pecah menjadi 2-3 kalimat per paragraf.";
            } else {
                $p9['score']   = 1;
                $p9['status']  = 'fail';
                $p9['message'] = "Ditemukan 'wall of text' (paragraf dengan {$maxWordsInP} kata). Sulit dibaca di layar smartphone.";
            }
        }
        $params[] = $p9;
        $totalScore += $p9['score'];

        // -------------------------------------------------------------
        // Parameter 10: Featured Image & Alt Text (Bobot: 10)
        // -------------------------------------------------------------
        $p10 = [
            'key'         => 'featured_image',
            'title'       => 'Foto Utama & Kelengkapan Gambar',
            'weight'      => 10,
            'score'       => 0,
            'status'      => 'fail',
            'message'     => '',
            'current_val' => !empty($thumbnail) ? 'Ada foto utama' : 'Tidak ada foto',
            'ideal_val'   => 'Foto utama tersedia & gambar artikel memiliki alt text'
        ];
        $hasThumbnail = !empty(trim($thumbnail));

        // Check image tags in body
        preg_match_all('/<img\b([^>]*)>/i', $body, $imgMatches);
        $bodyImgCount = count($imgMatches[0] ?? []);
        $imgsWithAlt = 0;
        if ($bodyImgCount > 0) {
            foreach ($imgMatches[1] as $imgAttrs) {
                if (preg_match('/alt\s*=\s*["\']([^"\']+)["\']/i', $imgAttrs, $altMatch)) {
                    if (!empty(trim($altMatch[1]))) {
                        $imgsWithAlt++;
                    }
                }
            }
        }

        if ($hasThumbnail) {
            if ($bodyImgCount === 0 || $imgsWithAlt === $bodyImgCount) {
                $p10['score']   = 10;
                $p10['status']  = 'pass';
                $p10['message'] = 'Foto utama terpasang dan kelengkapan gambar valid.';
            } else {
                $p10['score']   = 7;
                $p10['status']  = 'warning';
                $p10['message'] = "Foto utama ada, tetapi {$imgsWithAlt}/{$bodyImgCount} gambar di body belum memiliki alt text.";
            }
        } else {
            $p10['score']   = 0;
            $p10['status']  = 'fail';
            $p10['message'] = 'Foto utama (featured image/thumbnail) belum diunggah.';
        }
        $params[] = $p10;
        $totalScore += $p10['score'];

        // -------------------------------------------------------------
        // Parameter 11: Internal & External Links (Bobot: 5)
        // -------------------------------------------------------------
        $p11 = [
            'key'         => 'links_check',
            'title'       => 'Tautan Internal / Eksternal',
            'weight'      => 5,
            'score'       => 0,
            'status'      => 'fail',
            'message'     => '',
            'current_val' => '0 tautan',
            'ideal_val'   => 'Minimal 1 internal link (baca juga/berita terkait)'
        ];
        preg_match_all('/<a\b[^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $body, $linkMatches);
        $links = $linkMatches[1] ?? [];
        $linkCount = count($links);
        $p11['current_val'] = "{$linkCount} tautan";

        if ($linkCount >= 2) {
            $p11['score']   = 5;
            $p11['status']  = 'pass';
            $p11['message'] = "Tautan memadai ({$linkCount} link ditemukan). Memperkuat navigasi SEO.";
        } elseif ($linkCount === 1) {
            $p11['score']   = 4;
            $p11['status']  = 'pass';
            $p11['message'] = "Ditemukan 1 tautan di dalam body.";
        } else {
            $p11['score']   = 0;
            $p11['status']  = 'fail';
            $p11['message'] = 'Belum ada tautan (link) ke artikel terkait atau sumber di dalam body.';
        }
        $params[] = $p11;
        $totalScore += $p11['score'];

        // -------------------------------------------------------------
        // Parameter 12: Keyword Count & Density (Bobot: 10)
        // -------------------------------------------------------------
        $p12 = [
            'key'         => 'keyword_density',
            'title'       => 'Kerapatan & Jumlah Tag Terdaftar',
            'weight'      => 10,
            'score'       => 0,
            'status'      => 'fail',
            'message'     => '',
            'current_val' => count($normalizedKeywords) . ' tag',
            'ideal_val'   => '3 - 8 tag terdaftar dengan distribusi alami (0.8% - 2.5%)'
        ];
        $tagCount = count($normalizedKeywords);

        if ($tagCount === 0) {
            $p12['score']   = 0;
            $p12['status']  = 'fail';
            $p12['message'] = 'Tidak ada tag/kata kunci yang dipilih untuk artikel ini.';
        } elseif ($tagCount < 2) {
            $p12['score']   = 4;
            $p12['status']  = 'warning';
            $p12['message'] = "Tag terlalu sedikit ({$tagCount} tag). Disarankan 3-6 tag spesifik.";
        } elseif ($tagCount > 10) {
            $p12['score']   = 5;
            $p12['status']  = 'warning';
            $p12['message'] = "Terlalu banyak tag ({$tagCount} tag). Berisiko tag-dilution, batasi maksimal 8-10 tag.";
        } else {
            // Check density of primary tag
            $primaryTag = $normalizedKeywords[0];
            $lowerBody = mb_strtolower($cleanBody);
            $occurrences = mb_substr_count($lowerBody, $primaryTag);
            $tagWords = str_word_count($primaryTag);
            $density = $wordCount > 0 ? round(($occurrences * $tagWords / $wordCount) * 100, 2) : 0;

            if ($density > 3.5) {
                $p12['score']   = 5;
                $p12['status']  = 'warning';
                $p12['message'] = "Tag {$tagCount} memadai, namun tag utama '{$primaryTag}' terdeteksi terlalu sering ({$density}% density). Hindari keyword stuffing.";
                $p12['current_val'] = "{$tagCount} tag, densitas '{$primaryTag}': {$density}%";
            } elseif ($density >= 0.5 && $density <= 3.0) {
                $p12['score']   = 10;
                $p12['status']  = 'pass';
                $p12['message'] = "Jumlah tag ideal ({$tagCount} tag) dengan frekuensi alami di teks (densitas {$density}%).";
                $p12['current_val'] = "{$tagCount} tag, densitas '{$primaryTag}': {$density}%";
            } else {
                $p12['score']   = 8;
                $p12['status']  = 'pass';
                $p12['message'] = "Jumlah tag ideal ({$tagCount} tag).";
                $p12['current_val'] = "{$tagCount} tag";
            }
        }
        $params[] = $p12;
        $totalScore += $p12['score'];

        return [
            'total_score' => $totalScore,
            'parameters'  => $params
        ];
    }

    /**
     * Map numerical score (0 - 100) to category classification
     */
    public function getCategory(float $score): array
    {
        if ($score >= 85) {
            return [
                'name'  => 'Excellent',
                'label' => 'Sangat Siap (Siap Rilis)',
                'color' => '#10b981' // emerald-500
            ];
        } elseif ($score >= 70) {
            return [
                'name'  => 'Good',
                'label' => 'Cukup Baik (Siap Rilis)',
                'color' => '#3b82f6' // blue-500
            ];
        } elseif ($score >= 50) {
            return [
                'name'  => 'Needs Improvement',
                'label' => 'Perlu Perbaikan',
                'color' => '#f59e0b' // amber-500
            ];
        } else {
            return [
                'name'  => 'Poor',
                'label' => 'Belum Siap Publikasi',
                'color' => '#ef4444' // red-500
            ];
        }
    }
}

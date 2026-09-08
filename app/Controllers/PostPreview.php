<?php

namespace App\Controllers;

use App\Models\ArticleModel;

class PostPreview extends BaseController
{
    protected ArticleModel $articleModel;

    public function __construct()
    {
        $this->articleModel = new ArticleModel();
    }

    public function show($id)
    {
        $article = $this->articleModel->getArticleDetail((int)$id);

        if (!$article) {
            return redirect()->to('/post')->with('error', 'Artikel tidak ditemukan.');
        }

        $rawParsedContent = $this->parseEmbeds($article['content'] ?? '');

        // Page break pattern support (Multi-page article splitting)
        $pageBreakPattern = '/(?:<div[^>]*class=["\'][^"\']*article-page-break[^"\']*["\'][^>]*>.*?<\/div>|<div[^>]*data-page-break=["\']true["\'][^>]*>.*?<\/div>|<!--\s*pagebreak\s*-->|<hr[^>]*>\s*<p[^>]*>--- HALAMAN BERIKUTNYA.*?<\/p>\s*<hr[^>]*>|<p[^>]*>--- HALAMAN BERIKUTNYA.*?<\/p>|--- HALAMAN BERIKUTNYA [^-\n]+ ---)/is';
        $contentPages = preg_split($pageBreakPattern, $rawParsedContent);
        $contentPages = array_values(array_filter(array_map('trim', $contentPages), fn($p) => $p !== ''));

        if (empty($contentPages)) {
            $contentPages = [$rawParsedContent];
        }

        $totalPages = count($contentPages);
        $reqPage = $this->request->getGet('halaman') ?: ($this->request->getGet('page') ?: 1);

        if ($reqPage === 'all' || $reqPage === 'semua') {
            $displayedContent = implode('', $contentPages);
            $currentPage = 'all';
        } else {
            $pageIndex = max(1, min((int)$reqPage, $totalPages));
            $displayedContent = $contentPages[$pageIndex - 1];
            $currentPage = $pageIndex;
        }

        $pagination = [
            'is_multipage' => ($totalPages > 1),
            'total_pages'  => $totalPages,
            'current_page' => $currentPage,
            'has_next'     => ($currentPage !== 'all' && (int)$currentPage < $totalPages),
            'next_page'    => ((int)$currentPage + 1),
            'has_prev'     => ($currentPage !== 'all' && (int)$currentPage > 1),
            'prev_page'    => ((int)$currentPage - 1),
        ];

        $article['content'] = $displayedContent;

        // Extract stock symbols from title & content
        $stockSymbols = [];
        preg_match_all('/\$([A-Z]{4})\b/', $article['title'] . ' ' . $article['content'], $matches);
        if (!empty($matches[1])) {
            $stockSymbols = array_unique($matches[1]);
        }
        $article['stock_symbols'] = array_values($stockSymbols);

        return view('post/preview', [
            'title'      => 'Live Preview: ' . $article['title'],
            'article'    => $article,
            'pagination' => $pagination,
            'isFromForm' => false
        ]);
    }

    public function fromForm()
    {
        $pointsInput = $this->request->getPost('points');
        if (is_array($pointsInput)) {
            $points = $pointsInput;
        } else {
            $points = [
                $this->request->getPost('point_1') ?: '',
                $this->request->getPost('point_2') ?: '',
                $this->request->getPost('point_3') ?: ''
            ];
        }
        $points = array_values(array_filter($points, fn($pt) => trim($pt) !== ''));

        $content = $this->request->getPost('content') ?: '<p>Konten naskah berita masih kosong.</p>';
        $rawParsedContent = $this->parseEmbeds($content);

        // Page break pattern support (Multi-page article splitting)
        $pageBreakPattern = '/(?:<div[^>]*class=["\'][^"\']*article-page-break[^"\']*["\'][^>]*>.*?<\/div>|<div[^>]*data-page-break=["\']true["\'][^>]*>.*?<\/div>|<!--\s*pagebreak\s*-->|<hr[^>]*>\s*<p[^>]*>--- HALAMAN BERIKUTNYA.*?<\/p>\s*<hr[^>]*>|<p[^>]*>--- HALAMAN BERIKUTNYA.*?<\/p>|--- HALAMAN BERIKUTNYA [^-\n]+ ---)/is';
        $contentPages = preg_split($pageBreakPattern, $rawParsedContent);
        $contentPages = array_values(array_filter(array_map('trim', $contentPages), fn($p) => $p !== ''));

        if (empty($contentPages)) {
            $contentPages = [$rawParsedContent];
        }

        $totalPages = count($contentPages);
        $reqPage = $this->request->getGet('halaman') ?: ($this->request->getGet('page') ?: 1);

        if ($reqPage === 'all' || $reqPage === 'semua') {
            $displayedContent = implode('', $contentPages);
            $currentPage = 'all';
        } else {
            $pageIndex = max(1, min((int)$reqPage, $totalPages));
            $displayedContent = $contentPages[$pageIndex - 1];
            $currentPage = $pageIndex;
        }

        $pagination = [
            'is_multipage' => ($totalPages > 1),
            'total_pages'  => $totalPages,
            'current_page' => $currentPage,
            'has_next'     => ($currentPage !== 'all' && (int)$currentPage < $totalPages),
            'next_page'    => ((int)$currentPage + 1),
            'has_prev'     => ($currentPage !== 'all' && (int)$currentPage > 1),
            'prev_page'    => ((int)$currentPage - 1),
        ];

        // Extract stock symbols from title & content
        $title = $this->request->getPost('title') ?: 'Judul Artikel Masih Kosong';
        $stockSymbols = [];
        preg_match_all('/\$([A-Z]{4})\b/', $title . ' ' . $content, $matches);
        if (!empty($matches[1])) {
            $stockSymbols = array_unique($matches[1]);
        }

        $article = [
            'id'            => 0,
            'title'         => $title,
            'upper_deck'    => $this->request->getPost('upper_deck') ?: '',
            'author'        => $this->request->getPost('author') ?: 'Redaksi Investor.id',
            'category'      => $this->request->getPost('sub_kanal') ? ($this->request->getPost('kanal') . ' ' . $this->request->getPost('sub_kanal')) : ($this->request->getPost('kanal') ?: 'Market'),
            'kanal'         => $this->request->getPost('kanal') ?: 'Market',
            'sub_kanal'     => $this->request->getPost('sub_kanal') ?: '',
            'sumber_berita' => $this->request->getPost('source') ?: ($this->request->getPost('sumber_berita') ?: 'Investor.id / Riset'),
            'publish_date'  => $this->request->getPost('publish_date') ?: date('d M Y H:i'),
            'status'        => $this->request->getPost('status') ?: 'Draft',
            'is_premium'    => (int)($this->request->getPost('is_premium') ?: 0),
            'thumbnail'     => $this->request->getPost('thumbnail') ?: '/img/investor_placeholder.jpg',
            'summary'       => $this->request->getPost('summary') ?: 'Ringkasan artikel pengantar belum diisi.',
            'content'       => $displayedContent,
            'tags'          => $this->request->getPost('tags') ?: '',
            'points'        => $points,
            'stock_symbols' => array_values($stockSymbols),
        ];

        return view('post/preview', [
            'title'      => 'Live Preview: ' . $article['title'],
            'article'    => $article,
            'pagination' => $pagination,
            'isFromForm' => true
        ]);
    }

    /**
     * Parse Shortcodes & Embed Tokens into ultra-lightweight, responsive widgets
     * Identical to Frontend/app/Controllers/Portal.php
     */
    protected function parseEmbeds(string $content): string
    {
        if (empty($content)) {
            return '';
        }

        // 1. Stock / Financial Charts (Tokens and Placeholders)
        $content = preg_replace_callback(
            '/(?:<div[^>]*data-embed-type=["\'](?:stock_chart|financial_chart)["\'][^>]*data-(?:ticker|symbol)=["\']([^"\']+)["\'][^>]*>.*?<\/div>|::(?:Stock|Embed) Chart:\s*([A-Z0-9:\.\-]+)::)/i',
            function ($m) {
                $rawTicker = !empty($m[1]) ? $m[1] : $m[2];
                $cleanTicker = strtoupper(trim(str_replace(['IDX:', '$'], '', $rawTicker)));
                if (empty($cleanTicker)) $cleanTicker = 'BBCA';

                return '
                <div class="my-6 rounded-xl border border-slate-200 overflow-hidden bg-white shadow-xs">
                    <div class="w-full relative" style="height: 480px;">
                        <iframe src="https://s.tradingview.com/widgetembed/?symbol=IDX%3A' . urlencode($cleanTicker) . '&interval=D&symboledit=1&saveimage=1&toolbarbg=ffffff&studies=%5B%22STD%3BVolume%22%5D&theme=light&style=1&timezone=Asia%2FJakarta&locale=id&utm_source=investor.id"
                            class="w-full h-full border-0"
                            loading="lazy"
                            title="' . htmlspecialchars($cleanTicker) . ' Chart oleh TradingView">
                        </iframe>
                    </div>
                    <div class="text-center text-xs text-slate-500 py-2 border-t border-slate-100 font-sans">
                        <a href="https://id.tradingview.com/symbols/IDX-' . htmlspecialchars($cleanTicker) . '/" target="_blank" rel="noopener nofollow" class="text-blue-600 hover:underline font-semibold">' . htmlspecialchars($cleanTicker) . ' Chart</a> oleh TradingView
                    </div>
                </div>';
            },
            $content
        );

        // 2. Social Media & Document Embeds (Tokens and Placeholders)
        $content = preg_replace_callback(
            '/<div[^>]*data-embed-type=["\']socmed["\'][^>]*data-platform=["\']([^"\']+)["\'][^>]*data-url=["\']([^"\']+)["\'][^>]*>.*?<\/div>/i',
            function ($m) {
                return $this->renderSocialEmbedBadge(strtolower(trim($m[1])), $m[2]);
            },
            $content
        );

        $content = preg_replace_callback(
            '/::Embed Socmed:\s*([A-Za-z0-9_\-]+)::/i',
            function ($m) {
                return $this->renderSocialEmbedBadge(strtolower(trim($m[1])), '#');
            },
            $content
        );

        // 3. Baca Juga blockquote polish
        $content = preg_replace_callback(
            '/<blockquote>\s*<p>\s*<strong>Baca Juga:\s*<\/strong>\s*<a href="([^"]+)"[^>]*>(.*?)<\/a>\s*<\/p>\s*<\/blockquote>/i',
            function ($m) {
                return '<div class="my-5 p-4 rounded-lg bg-[#EBF8FF] border-l-4 border-[#2B6CB0] flex items-start gap-3 shadow-2xs font-sans">
                    <span class="text-[#1F4E79] font-bold text-xs uppercase tracking-wider shrink-0 mt-0.5">Baca Juga:</span>
                    <a href="' . htmlspecialchars($m[1]) . '" class="text-xs font-bold text-slate-900 hover:text-[#2B6CB0] transition leading-snug underline decoration-[#2B6CB0]/40 hover:decoration-[#2B6CB0]">' . $m[2] . '</a>
                </div>';
            },
            $content
        );

        return $content;
    }

    /**
     * Render lightweight social/doc embed badge with native SVG branding
     */
    protected function renderSocialEmbedBadge(string $platform, string $url): string
    {
        $safeUrl = htmlspecialchars($url);

        // YouTube video embed
        if ($platform === 'youtube') {
            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $ytMatch);
            if (!empty($ytMatch[1])) {
                $videoId = htmlspecialchars($ytMatch[1]);
                return '<div class="my-6 aspect-video w-full rounded-xl overflow-hidden shadow-xs border border-slate-200 bg-black">
                    <iframe src="https://www.youtube-nocookie.com/embed/' . $videoId . '" class="w-full h-full border-0" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen title="YouTube Video"></iframe>
                </div>';
            }
        }

        // Platform specific logos & badges
        $badges = [
            'instagram' => [
                'name'  => 'Instagram Post',
                'color' => 'bg-pink-50 border-pink-200 text-pink-700',
                'btn'   => 'bg-gradient-to-r from-purple-600 to-pink-500 text-white',
                'svg'   => '<rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>'
            ],
            'x' => [
                'name'  => 'X (Twitter) Thread',
                'color' => 'bg-slate-50 border-slate-200 text-slate-800',
                'btn'   => 'bg-black text-white',
                'svg'   => '<path d="M4 4l11.733 16h4.267l-11.733 -16z"/><path d="M4 20l6.768 -6.768m2.46 -2.46l6.772 -6.772"/>'
            ],
            'youtube' => [
                'name'  => 'YouTube Video',
                'color' => 'bg-red-50 border-red-200 text-red-700',
                'btn'   => 'bg-red-600 text-white',
                'svg'   => '<path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/>'
            ],
            'tiktok' => [
                'name'  => 'TikTok Video',
                'color' => 'bg-slate-50 border-slate-200 text-slate-900',
                'btn'   => 'bg-black text-white',
                'svg'   => '<path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/>'
            ],
            'google_drive' => [
                'name'  => 'Google Drive / PDF Dokumen Riset',
                'color' => 'bg-blue-50 border-blue-200 text-blue-700',
                'btn'   => 'bg-blue-600 text-white',
                'svg'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/>'
            ],
            'scribd' => [
                'name'  => 'Scribd Financial Report',
                'color' => 'bg-amber-50 border-amber-200 text-amber-800',
                'btn'   => 'bg-amber-700 text-white',
                'svg'   => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10M6 10h10"/>'
            ]
        ];

        $p = $badges[$platform] ?? [
            'name'  => ucfirst($platform) . ' Embed',
            'color' => 'bg-slate-50 border-slate-200 text-slate-700',
            'btn'   => 'bg-slate-800 text-white',
            'svg'   => '<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>'
        ];

        return '
        <div class="my-5 p-4 rounded-xl border ' . $p['color'] . ' flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs font-sans">
            <div class="flex items-center gap-3">
                <div class="size-9 rounded-lg bg-white/80 border border-current/20 flex items-center justify-center shrink-0">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">' . $p['svg'] . '</svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold leading-tight">' . htmlspecialchars($p['name']) . '</h4>
                    <p class="text-[11px] opacity-75 truncate max-w-xs sm:max-w-md mt-0.5">' . ($safeUrl !== '#' ? $safeUrl : 'Dokumen pendukung publikasi') . '</p>
                </div>
            </div>
            ' . ($safeUrl !== '#' ? '<a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer" class="px-3 py-1.5 rounded-lg text-xs font-bold ' . $p['btn'] . ' hover:opacity-90 transition shrink-0 flex items-center gap-1.5 shadow-2xs">Buka Tautan <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a>' : '') . '
        </div>';
    }
}

<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use App\Models\ChartModel;
use App\Libraries\HtmlSanitizer;

class PostPreview extends BaseController
{
    protected ArticleModel $articleModel;
    protected ChartModel $chartModel;

    public function __construct()
    {
        $this->articleModel = new ArticleModel();
        $this->chartModel = new ChartModel();
    }

    public function show($id)
    {
        $article = $this->articleModel->getArticleDetail((int)$id);

        if (!$article) {
            return redirect()->to('/post')->with('error', 'Artikel tidak ditemukan.');
        }

        $rawParsedContent = $this->parseEmbeds($article['content'] ?? '');

        // Page break pattern support (Multi-page article splitting)
        $pageBreakPattern = '/(?:<blockquote[^>]*class=["\'][^"\']*article-page-break[^"\']*["\'][^>]*>.*?<\/blockquote>|<blockquote[^>]*data-page-break=["\']true["\'][^>]*>.*?<\/blockquote>|<div[^>]*class=["\'][^"\']*article-page-break[^"\']*["\'][^>]*>.*?<\/div>|<div[^>]*data-page-break=["\']true["\'][^>]*>.*?<\/div>|<!--\s*pagebreak\s*-->|<hr[^>]*>\s*<p[^>]*>--- HALAMAN BERIKUTNYA.*?<\/p>\s*<hr[^>]*>|<p[^>]*>--- HALAMAN BERIKUTNYA.*?<\/p>|--- HALAMAN BERIKUTNYA [^-\n]+ ---)/is';
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

        $rawContent = $this->request->getPost('content') ?: '<p>Konten naskah berita masih kosong.</p>';
        $content = HtmlSanitizer::clean($rawContent);
        $rawParsedContent = $this->parseEmbeds($content);

        // Page break pattern support (Multi-page article splitting)
        $pageBreakPattern = '/(?:<blockquote[^>]*class=["\'][^"\']*article-page-break[^"\']*["\'][^>]*>.*?<\/blockquote>|<blockquote[^>]*data-page-break=["\']true["\'][^>]*>.*?<\/blockquote>|<div[^>]*class=["\'][^"\']*article-page-break[^"\']*["\'][^>]*>.*?<\/div>|<div[^>]*data-page-break=["\']true["\'][^>]*>.*?<\/div>|<!--\s*pagebreak\s*-->|<hr[^>]*>\s*<p[^>]*>--- HALAMAN BERIKUTNYA.*?<\/p>\s*<hr[^>]*>|<p[^>]*>--- HALAMAN BERIKUTNYA.*?<\/p>|--- HALAMAN BERIKUTNYA [^-\n]+ ---)/is';
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

        $rawSubKanal = $this->request->getPost('sub_kanal');
        $subKanalStr = is_array($rawSubKanal) ? implode(', ', array_filter($rawSubKanal)) : (string)($rawSubKanal ?? '');
        $kanalStr = (string)($this->request->getPost('kanal') ?: 'Market');
        $categoryStr = !empty($subKanalStr) ? ($kanalStr . ' ' . $subKanalStr) : $kanalStr;

        $article = [
            'id'            => 0,
            'title'         => $title,
            'upper_deck'    => $this->request->getPost('upper_deck') ?: '',
            'author'        => $this->request->getPost('author') ?: 'Redaksi Investor.id',
            'category'      => $categoryStr,
            'kanal'         => $kanalStr,
            'sub_kanal'     => $subKanalStr,
            'sumber_berita' => $this->request->getPost('source') ?: ($this->request->getPost('sumber_berita') ?: 'Investor.id / Riset'),
            'publish_date'  => $this->request->getPost('publish_date') ?: date('d M Y H:i'),
            'status'        => $this->request->getPost('status') ?: 'Draft',
            'is_premium'    => (int)($this->request->getPost('is_premium') ?: 0),
            'thumbnail'             => $this->request->getPost('thumbnail') ?: '/img/investor_placeholder.jpg',
            'thumbnail_caption'     => $this->request->getPost('thumbnail_caption') ?: '',
            'thumbnail_description' => $this->request->getPost('thumbnail_description') ?: '',
            'summary'               => $this->request->getPost('summary') ?: 'Ringkasan artikel pengantar belum diisi.',
            'raw_content'   => $content,
            'content'       => $displayedContent,
            'tags'          => $this->request->getPost('tags') ?: '',
            'points'        => $points,
            'stock_symbols' => array_values($stockSymbols),
        ];

        // Store in session for multipage pagination navigation
        session()->set('preview_article_form', $article);

        return view('post/preview', [
            'title'      => 'Live Preview: ' . $article['title'],
            'article'    => $article,
            'pagination' => $pagination,
            'isFromForm' => true
        ]);
    }

    public function fromSessionOrForm()
    {
        $article = session()->get('preview_article_form');
        if (!$article) {
            return redirect()->to('/post')->with('error', 'Sesi pratinjau telah berakhir. Silakan klik Preview Artikel kembali dari editor.');
        }

        $rawContent = $article['raw_content'] ?? ($article['content'] ?? '');
        $rawParsedContent = $this->parseEmbeds($rawContent);

        // Page break pattern support (Multi-page article splitting)
        $pageBreakPattern = '/(?:<blockquote[^>]*class=["\'][^"\']*article-page-break[^"\']*["\'][^>]*>.*?<\/blockquote>|<blockquote[^>]*data-page-break=["\']true["\'][^>]*>.*?<\/blockquote>|<div[^>]*class=["\'][^"\']*article-page-break[^"\']*["\'][^>]*>.*?<\/div>|<div[^>]*data-page-break=["\']true["\'][^>]*>.*?<\/div>|<!--\s*pagebreak\s*-->|<hr[^>]*>\s*<p[^>]*>--- HALAMAN BERIKUTNYA.*?<\/p>\s*<hr[^>]*>|<p[^>]*>--- HALAMAN BERIKUTNYA.*?<\/p>|--- HALAMAN BERIKUTNYA [^-\n]+ ---)/is';
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

        // 1. Stock / Financial Ticker Embed via <blockquote>[embed type=stock]TICKER[/embed]</blockquote>
        $content = preg_replace_callback(
            '/(?:<blockquote>\s*\[embed\s+type=["\']?stock["\']?\]\s*([A-Z0-9:\.\-]+)\s*\[\/embed\]\s*<\/blockquote>|<div[^>]*data-embed-type=["\'](?:stock_chart|financial_chart)["\'][^>]*data-(?:ticker|symbol)=["\']([^"\']+)["\'][^>]*>.*?<\/div>|::(?:Stock|Embed) Chart:\s*([A-Z0-9:\.\-]+)::)/i',
            function ($m) {
                $rawTicker = !empty($m[1]) ? $m[1] : (!empty($m[2]) ? $m[2] : ($m[3] ?? 'BBCA'));
                return $this->renderTradingViewWidget($rawTicker);
            },
            $content
        );

        // 2. Chart Embed via <blockquote>[embed type=chart ... ]...[/embed]</blockquote> or ::(label)::
        $content = preg_replace_callback(
            '/(?:<blockquote>\s*\[embed\s+type=["\']?chart["\']?([^\]]*)\](.*?)\[\/embed\]\s*<\/blockquote>|::\((.*?)\)::)/i',
            function ($m) {
                $attrs = $m[1] ?? '';
                $bodyText = trim($m[2] ?? '');
                $legacyLabel = trim($m[3] ?? '');

                $chartId = null;
                $symbol = '';
                $title = '';

                if (preg_match('/\bid=["\']?(\d+)["\']?/i', $attrs, $im)) {
                    $chartId = (int)$im[1];
                }
                if (preg_match('/\bsymbol=["\']([^"\']*)["\']/i', $attrs, $sm)) {
                    $symbol = trim($sm[1]);
                }
                if (preg_match('/\btitle=["\']([^"\']*)["\']/i', $attrs, $tm)) {
                    $title = trim($tm[1]);
                }

                $chartTitle = $title ?: ($bodyText ?: ($legacyLabel ?: 'Financial Chart Analysis'));

                // Query database chartdata table first
                $chartRow = null;
                if ($chartId) {
                    $chartRow = $this->chartModel->find($chartId);
                }
                if (!$chartRow && !empty($chartTitle)) {
                    $chartRow = $this->chartModel->where('LOWER(chartTitle)', strtolower($chartTitle))->first();
                }

                if ($chartRow) {
                    return $this->renderCustomDataChart($chartRow, $chartTitle);
                }

                // If not in chartdata, check if it is a stock ticker
                $stockCandidate = $symbol ?: $chartTitle;
                if (preg_match('/\b(?:IDX:)?([A-Z]{4})\b/', strtoupper($stockCandidate), $tm)) {
                    return $this->renderTradingViewWidget($tm[1], $chartTitle);
                }

                // General interactive financial chart fallback card
                return '
                <div class="my-6 rounded-xl border border-blue-200/80 bg-blue-50/40 p-4 shadow-xs">
                    <div class="flex items-center justify-between border-b border-blue-200/60 pb-2 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-blue-600 animate-pulse"></span>
                            <h4 class="text-xs font-bold text-slate-900">' . htmlspecialchars($chartTitle) . '</h4>
                        </div>
                        <span class="rounded bg-blue-600 text-white px-2 py-0.5 text-[10px] font-bold">EMBED CHART</span>
                    </div>
                    <div class="h-48 w-full rounded-lg bg-white border border-blue-100 flex flex-col items-center justify-center text-slate-400 p-4 text-center">
                        <svg class="size-10 text-blue-500/70 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                        <span class="text-xs font-semibold text-slate-700">' . htmlspecialchars($chartTitle) . '</span>
                        <span class="text-[11px] text-slate-400 mt-0.5">Konfigurasi chart belum ditemukan di database</span>
                    </div>
                </div>';
            },
            $content
        );

        // 3. Google Docs / Sheets via <blockquote>[embed type=googledoc|googlesheet url=URL]Title[/embed]</blockquote> or ::Google Doc: URL::
        $content = preg_replace_callback(
            '/(?:<blockquote[^>]*>\s*(?:<p>)?\s*\[embed\s+type=["\']?(googledoc|googlesheet)["\']?(?:\s+url=["\']([^"\']+)["\'])?[^\]]*\](.*?)\[\/embed\](?:\s*<\/p>)?\s*<\/blockquote>|::Google (Doc|Sheet|Spreadsheet):\s*(https?:\/\/[^\s:]+)(?:\s*\|\s*([^:]+))?::)/is',
            function ($m) {
                if (!empty($m[1])) {
                    $type = strtolower($m[1]) === 'googlesheet' ? 'sheet' : 'doc';
                    $url = html_entity_decode(trim($m[2] ?? ''));
                    $title = trim(strip_tags($m[3] ?? ''));
                    if (empty($url) && filter_var($title, FILTER_VALIDATE_URL)) {
                        $url = $title;
                        $title = ($type === 'sheet' ? 'Google Spreadsheet' : 'Google Document');
                    }
                } else {
                    $type = strtolower($m[4] ?? 'doc');
                    $url = html_entity_decode(trim($m[5] ?? ''));
                    $title = !empty($m[6]) ? trim(strip_tags($m[6])) : ($type === 'doc' ? 'Google Document' : 'Google Spreadsheet');
                }
                return $this->renderGoogleEmbed($type, $url, $title ?: 'Google Document');
            },
            $content
        );

        // 4. Social Media Embeds via <blockquote>[embed type=youtube|instagram|twitter|x|tiktok|googledrive|scribd|socmed]URL[/embed]</blockquote> or ::Embed Socmed::
        $content = preg_replace_callback(
            '/(?:<blockquote>\s*\[embed\s+type=["\']?(youtube|instagram|twitter|x|tiktok|googledrive|scribd|socmed)["\']?[^\]]*\](.*?)\[\/embed\]\s*<\/blockquote>|::Embed Socmed:\s*([^\|:]+)(?:\s*\|\s*([^\|:]+))?(?:\s*\|\s*([^:]+))?::|<div[^>]*data-embed-type=["\']socmed["\'][^>]*data-platform=["\']([^"\']+)["\'][^>]*data-url=["\']([^"\']+)["\'][^>]*>.*?<\/div>)/i',
            function ($m) {
                if (!empty($m[1])) {
                    $platform = strtolower($m[1]);
                    $body = trim($m[2] ?? '');
                    $parts = explode('|', $body, 2);
                    $url = trim($parts[0]);
                    $caption = isset($parts[1]) ? trim($parts[1]) : '';
                    return $this->renderSocialEmbedBadge($platform, $url, $caption);
                } elseif (!empty($m[3])) {
                    $platform = strtolower(trim($m[3]));
                    $url = !empty($m[4]) ? trim($m[4]) : '#';
                    $caption = !empty($m[5]) ? trim($m[5]) : '';
                    return $this->renderSocialEmbedBadge($platform, $url, $caption);
                } else {
                    return $this->renderSocialEmbedBadge(strtolower(trim($m[6] ?? 'socmed')), $m[7] ?? '#');
                }
            },
            $content
        );

        // 5. Baca Juga blockquote polish (Investor.id parity .id-group)
        $content = preg_replace_callback(
            '/<blockquote[^>]*>\s*(?:<p>)?\s*<strong>Baca Juga:\s*<\/strong>\s*<a href="([^"]+)"[^>]*>(.*?)<\/a>\s*(?:<\/p>)?\s*<\/blockquote>/i',
            function ($m) {
                return '<div class="id-group mb-3 position-relative">
                    <h2 class="h6 text-muted mb-2">Baca Juga:</h2>
                    <b><a class="text-theme stretched-link" href="' . htmlspecialchars($m[1]) . '">' . $m[2] . '</a></b>
                </div>';
            },
            $content
        );

        return $content;
    }

    /**
     * Render lightweight social/doc embed badge with native SVG branding
     */
    protected function renderSocialEmbedBadge(string $platform, string $url, string $caption = ''): string
    {
        $safeUrl = htmlspecialchars($url);
        $safeCaption = htmlspecialchars($caption);

        // YouTube video embed
        if ($platform === 'youtube') {
            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $ytMatch);
            if (!empty($ytMatch[1])) {
                $videoId = htmlspecialchars($ytMatch[1]);
                return '<div class="my-6 aspect-video w-full rounded-xl overflow-hidden shadow-xs border border-slate-200 bg-black">
                    <iframe src="https://www.youtube-nocookie.com/embed/' . $videoId . '" class="w-full h-full border-0" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen title="' . ($safeCaption ?: 'YouTube Video') . '"></iframe>
                </div>' . ($safeCaption ? '<p class="text-center text-[11px] text-slate-500 italic mt-1 font-sans">' . $safeCaption . '</p>' : '');
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
            'google_doc' => [
                'name'  => 'Google Document',
                'color' => 'bg-blue-50 border-blue-200 text-blue-800',
                'btn'   => 'bg-blue-600 text-white',
                'svg'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/>'
            ],
            'google_sheet' => [
                'name'  => 'Google Spreadsheet',
                'color' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
                'btn'   => 'bg-emerald-600 text-white',
                'svg'   => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M3 15h18"/><path d="M9 3v18"/><path d="M15 3v18"/>'
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

        $subLabel = $safeCaption ? $safeCaption : ($safeUrl !== '#' ? $safeUrl : 'Dokumen pendukung publikasi');

        return '
        <div class="my-5 p-4 rounded-xl border ' . $p['color'] . ' flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs font-sans">
            <div class="flex items-center gap-3">
                <div class="size-9 rounded-lg bg-white/80 border border-current/20 flex items-center justify-center shrink-0">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">' . $p['svg'] . '</svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold leading-tight">' . htmlspecialchars($p['name']) . '</h4>
                    <p class="text-[11px] opacity-75 truncate max-w-xs sm:max-w-md mt-0.5">' . $subLabel . '</p>
                </div>
            </div>
            ' . ($safeUrl !== '#' ? '<a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer" class="px-3 py-1.5 rounded-lg text-xs font-bold ' . $p['btn'] . ' hover:opacity-90 transition shrink-0 flex items-center gap-1.5 shadow-2xs">Buka Tautan <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a>' : '') . '
        </div>';
    }

    /**
     * Render responsive Google Docs / Sheets embedded viewer
     */
    protected function renderGoogleEmbed(string $type, string $url, string $title): string
    {
        $url = html_entity_decode(trim($url));
        $safeUrl = htmlspecialchars($url);
        $safeTitle = htmlspecialchars($title);

        // Auto-detect type if URL contains distinct Google Docs/Sheets identifiers
        if (str_contains($url, 'spreadsheets')) {
            $type = 'sheet';
        } elseif (str_contains($url, 'document')) {
            $type = 'doc';
        }

        $isSheet = in_array($type, ['sheet', 'spreadsheet']);
        
        $embedUrl = $url;
        // Transform standard edit URLs to preview / pub embed URLs
        if (str_contains($url, 'docs.google.com/document/d/')) {
            if (str_contains($url, '/pub')) {
                $embedUrl = str_contains($url, '?') ? $url . '&embedded=true' : $url . '?embedded=true';
            } else {
                $embedUrl = preg_replace('/\/edit.*$/', '/preview', $url);
            }
        } elseif (str_contains($url, 'docs.google.com/spreadsheets/d/')) {
            if (!str_contains($url, '/pubchart') && !str_contains($url, '/pubhtml')) {
                $embedUrl = preg_replace('/\/edit.*$/', '/pubhtml?widget=true&headers=false', $url);
            }
        }

        $badgeColor = $isSheet ? 'bg-emerald-600 text-white' : 'bg-blue-600 text-white';
        $badgeLabel = $isSheet ? 'GOOGLE SPREADSHEET' : 'GOOGLE DOCS';

        return '
        <div class="my-6 rounded-xl border border-slate-200 overflow-hidden bg-white shadow-xs">
            <div class="bg-slate-50 px-4 py-2.5 border-b border-slate-200 flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <span class="rounded px-2 py-0.5 text-[10px] font-bold ' . $badgeColor . '">' . $badgeLabel . '</span>
                    <span class="font-bold text-slate-800 truncate max-w-sm sm:max-w-md">' . $safeTitle . '</span>
                </div>
                <a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline font-semibold text-[11px] flex items-center gap-1 shrink-0">
                    <span>Buka Penuh</span>
                    <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>
            <div class="w-full relative" style="height: 480px;">
                <iframe src="' . htmlspecialchars($embedUrl) . '" class="w-full h-full border-0" loading="lazy" title="' . $safeTitle . '" allow="fullscreen" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>';
    }

    /**
     * Render production-identical TradingView widget (Gambar 1 parity)
     * Clean, border-rounded, no outer headers/footers, saveimage=0 for the pop-out link icon
     */
    protected function renderTradingViewWidget(string $ticker, string $desc = ''): string
    {
        $cleanTicker = strtoupper(trim(str_replace(['IDX:', '$'], '', $ticker)));
        if (empty($cleanTicker)) {
            $cleanTicker = 'BBCA';
        }
        $title = $desc ?: "{$cleanTicker} Chart oleh TradingView";

        return '
        <div class="my-6 w-full rounded-xl border border-slate-200 overflow-hidden bg-white shadow-xs" style="height: 480px;">
            <iframe src="https://s.tradingview.com/widgetembed/?symbol=IDX%3A' . urlencode($cleanTicker) . '&interval=D&symboledit=1&saveimage=0&toolbarbg=ffffff&studies=%5B%22STD%3BVolume%22%5D&theme=light&style=1&timezone=Asia%2FJakarta&locale=id&utm_source=investor.id"
                class="w-full h-full border-0"
                loading="lazy"
                title="' . htmlspecialchars($title) . '">
            </iframe>
        </div>';
    }

    /**
     * Render real interactive Custom Data Chart from database (chartdata)
     * Displays real Chart.js interactive canvas with categories and numeric series
     */
    protected function renderCustomDataChart(array $chartRow, string $titleOverride = ''): string
    {
        $parsed = $this->chartModel->parseChartAxisData($chartRow);
        $chartId = (int)($chartRow['chartID'] ?? ($chartRow['id'] ?? 1));
        $chartTitle = $titleOverride ?: ($chartRow['chartTitle'] ?? 'Grafik Finansial');
        $style = $chartRow['chartStyle'] ?? ($chartRow['type'] ?? 'line');
        $symbol = $chartRow['xAxisTitle'] ?? ($chartRow['symbol'] ?? 'Periode');
        $source = $chartRow['source'] ?? 'Investor.id Riset';
        $yTitle = $parsed['yAxisTitle'] ?? 'Nilai';
        $labels = $parsed['labels'] ?? [];
        $values = $parsed['values'] ?? [];

        $uniqueCanvasId = 'custom_chart_' . $chartId . '_' . substr(md5($chartTitle . microtime()), 0, 6);

        return '
        <div class="my-6 rounded-xl border border-slate-200 bg-white p-5 shadow-xs overflow-hidden article-custom-chart-wrapper">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 mb-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center rounded-sm bg-blue-50 text-blue-700 px-2 py-0.5 text-[10px] font-bold capitalize border border-blue-100">
                            ' . htmlspecialchars($style) . '
                        </span>
                        <span class="text-[11px] font-semibold text-slate-400 font-mono">
                            #' . $chartId . '
                        </span>
                    </div>
                    <h4 class="font-bold text-slate-900 text-base leading-snug">' . htmlspecialchars($chartTitle) . '</h4>
                    <p class="text-xs text-slate-500 mt-0.5">' . htmlspecialchars($symbol) . ' &bull; Sumber: <span class="font-medium text-slate-700">' . htmlspecialchars($source) . '</span></p>
                </div>
                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200">
                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                    Data Riset
                </span>
            </div>
            <div class="w-full relative bg-slate-50/50 rounded-lg p-3" style="height: 320px;">
                <canvas id="' . $uniqueCanvasId . '"
                        data-type="' . htmlspecialchars($style) . '"
                        data-title="' . htmlspecialchars($chartTitle) . '"
                        data-ytitle="' . htmlspecialchars($yTitle) . '"
                        data-labels="' . htmlspecialchars(json_encode($labels)) . '"
                        data-values="' . htmlspecialchars(json_encode($values)) . '">
                </canvas>
            </div>
            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-400 pt-2 border-t border-slate-100">
                <span>Grafik Interaktif Investor.id</span>
                <span>Sumber Data: <strong class="text-slate-600">' . htmlspecialchars($source) . '</strong></span>
            </div>
        </div>';
    }
}

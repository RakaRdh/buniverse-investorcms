<?php

namespace App\Controllers;

use App\Models\DataRepository;

class Post extends BaseController
{
    public function index()
    {
        $posts = DataRepository::get('posts');
        $kanals = DataRepository::get('kanals') ?? [];
        $users = DataRepository::get('users') ?? [];

        // Filtering parameters
        $keyword = $this->request->getGet('q');
        $kanalFilter = $this->request->getGet('kanal') ?: $this->request->getGet('category');
        $statusFilter = $this->request->getGet('status');
        $premiumFilter = $this->request->getGet('premium');
        $authorFilter = $this->request->getGet('author');
        $editorFilter = $this->request->getGet('editor');
        $dateFromFilter = $this->request->getGet('date_from');
        $dateToFilter = $this->request->getGet('date_to');

        if ($keyword) {
            $posts = array_filter($posts, fn($p) => 
                stripos($p['title'], $keyword) !== false || 
                stripos($p['author'], $keyword) !== false
            );
        }

        if ($kanalFilter && $kanalFilter !== 'all') {
            $posts = array_filter($posts, fn($p) => 
                ($p['kanal'] ?? '') === $kanalFilter || 
                ($p['category'] ?? '') === $kanalFilter ||
                str_starts_with($p['category'] ?? '', $kanalFilter)
            );
        }

        if ($statusFilter && $statusFilter !== 'all') {
            $posts = array_filter($posts, fn($p) => strtolower($p['status']) === strtolower($statusFilter));
        }

        if ($premiumFilter !== null && $premiumFilter !== '' && $premiumFilter !== 'all') {
            $posts = array_filter($posts, fn($p) => (string)$p['is_premium'] === (string)$premiumFilter);
        }

        if ($authorFilter && $authorFilter !== 'all') {
            $posts = array_filter($posts, fn($p) => stripos($p['author'] ?? '', $authorFilter) !== false);
        }

        if ($editorFilter && $editorFilter !== 'all') {
            $posts = array_filter($posts, fn($p) => ($p['post_by'] ?? '') === $editorFilter);
        }

        if ($dateFromFilter) {
            $tsFrom = strtotime($dateFromFilter);
            if ($tsFrom) {
                $posts = array_filter($posts, function($p) use ($tsFrom) {
                    $ts = strtotime($p['publish_date'] ?? $p['create_date'] ?? '');
                    return $ts && strtotime(date('Y-m-d', $ts)) >= strtotime(date('Y-m-d', $tsFrom));
                });
            }
        }

        if ($dateToFilter) {
            $tsTo = strtotime($dateToFilter);
            if ($tsTo) {
                $posts = array_filter($posts, function($p) use ($tsTo) {
                    $ts = strtotime($p['publish_date'] ?? $p['create_date'] ?? '');
                    return $ts && strtotime(date('Y-m-d', $ts)) <= strtotime(date('Y-m-d', $tsTo));
                });
            }
        }

        $filteredPosts = array_values($posts);
        $totalItems = count($filteredPosts);
        $perPage = 25;
        $page = max(1, (int)($this->request->getGet('page') ?? 1));
        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        if ($page > $totalPages) $page = $totalPages;

        $offset = ($page - 1) * $perPage;
        $paginatedPosts = array_slice($filteredPosts, $offset, $perPage);

        return view('post/index', [
            'title' => 'Articles & Posts',
            'breadcrumb' => 'Post / All Articles',
            'posts' => $paginatedPosts,
            'kanals' => $kanals,
            'users' => $users,
            'filters' => [
                'q' => $keyword,
                'kanal' => $kanalFilter ?? 'all',
                'status' => $statusFilter ?? 'all',
                'premium' => $premiumFilter ?? 'all',
                'author' => $authorFilter ?? 'all',
                'editor' => $editorFilter ?? 'all',
                'date_from' => $dateFromFilter ?? '',
                'date_to' => $dateToFilter ?? '',
            ],
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
                'from' => $totalItems > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $totalItems),
            ]
        ]);
    }

    public function new()
    {
        $kanals = DataRepository::get('kanals') ?? [];
        $users = DataRepository::get('users') ?? [];
        $media = DataRepository::get('media_extended') ?? [];
        $posts = DataRepository::get('posts') ?? [];

        return view('post/editor', [
            'title' => 'Create New Post',
            'breadcrumb' => 'Post / New Article',
            'kanals' => $kanals,
            'categories' => $kanals, // fallback
            'users' => $users,
            'media' => $media,
            'allPosts' => $posts,
            'article' => null,
            'isEdit' => false
        ]);
    }

    public function edit($id)
    {
        $posts = DataRepository::get('posts');
        $article = null;
        foreach ($posts as $p) {
            if ($p['id'] == $id) {
                $article = $p;
                break;
            }
        }

        if (!$article) {
            return redirect()->to('/post')->with('error', 'Article not found');
        }

        $kanals = DataRepository::get('kanals') ?? [];
        $users = DataRepository::get('users') ?? [];
        $media = DataRepository::get('media_extended') ?? [];

        return view('post/editor', [
            'title' => 'Edit Post: ' . $article['title'],
            'breadcrumb' => 'Post / Edit Article',
            'kanals' => $kanals,
            'categories' => $kanals,
            'users' => $users,
            'media' => $media,
            'allPosts' => $posts,
            'article' => $article,
            'isEdit' => true
        ]);
    }

    public function save()
    {
        $posts = DataRepository::get('posts');
        
        $newId = count($posts) > 0 ? max(array_column($posts, 'id')) + 1 : 1;
        $title = $this->request->getPost('title');
        $kanal = $this->request->getPost('kanal') ?? 'Market';
        $subKanal = $this->request->getPost('sub_kanal') ?? '';
        $sumberBerita = $this->request->getPost('source') ?: ($this->request->getPost('sumber_berita') ?: 'Investor.id / Riset');
        
        $points = [
            $this->request->getPost('point_1') ?: '',
            $this->request->getPost('point_2') ?: '',
            $this->request->getPost('point_3') ?: ''
        ];
        $points = array_values(array_filter($points, fn($pt) => trim($pt) !== ''));

        $newArticle = [
            'id' => $newId,
            'title' => $title,
            'slug' => url_title($title, '-', true),
            'upper_deck' => $this->request->getPost('upper_deck') ?? '',
            'author' => $this->request->getPost('author') ?? 'Raka Herdika',
            'category' => $subKanal ? "{$kanal} {$subKanal}" : $kanal,
            'category_slug' => url_title($kanal, '-', true),
            'kanal' => $kanal,
            'sub_kanal' => $subKanal,
            'sumber_berita' => $sumberBerita,
            'source' => $sumberBerita,
            'post_by' => 'raka_h',
            'create_date' => date('d M Y H:i'),
            'publish_date' => $this->request->getPost('publish_date') ?: date('d M Y H:i'),
            'status' => $this->request->getPost('status') ?? 'Published',
            'is_premium' => (int)($this->request->getPost('is_premium') ?? 0),
            'views' => 0,
            'thumbnail' => $this->request->getPost('thumbnail') ?: '/img/article1.jpg',
            'summary' => $this->request->getPost('summary') ?? '',
            'content' => $this->request->getPost('content') ?? '',
            'tags' => $this->request->getPost('tags') ?? '',
            'points' => $points,
            'headline_type' => $this->request->getPost('headline_type') ?? 'none',
            'is_breaking' => 0,
        ];

        array_unshift($posts, $newArticle);
        DataRepository::save('posts', $posts);

        return redirect()->to('/post')->with('success', 'Article successfully created and published!');
    }

    public function update($id)
    {
        $posts = DataRepository::get('posts');
        $updated = false;

        $kanal = $this->request->getPost('kanal') ?? 'Market';
        $subKanal = $this->request->getPost('sub_kanal') ?? '';
        $sumberBerita = $this->request->getPost('source') ?: ($this->request->getPost('sumber_berita') ?: 'Investor.id / Riset');
        
        $points = [
            $this->request->getPost('point_1') ?: '',
            $this->request->getPost('point_2') ?: '',
            $this->request->getPost('point_3') ?: ''
        ];
        $points = array_values(array_filter($points, fn($pt) => trim($pt) !== ''));

        foreach ($posts as &$p) {
            if ($p['id'] == $id) {
                $p['title'] = $this->request->getPost('title');
                $p['upper_deck'] = $this->request->getPost('upper_deck') ?? '';
                $p['author'] = $this->request->getPost('author');
                $p['kanal'] = $kanal;
                $p['sub_kanal'] = $subKanal;
                $p['category'] = $subKanal ? "{$kanal} {$subKanal}" : $kanal;
                $p['category_slug'] = url_title($kanal, '-', true);
                $p['status'] = $this->request->getPost('status');
                $p['is_premium'] = (int)($this->request->getPost('is_premium') ?? 0);
                $p['is_breaking'] = 0;
                $p['publish_date'] = $this->request->getPost('publish_date');
                $p['summary'] = $this->request->getPost('summary');
                $p['content'] = $this->request->getPost('content');
                $p['tags'] = $this->request->getPost('tags') ?? '';
                $p['points'] = $points;
                $p['source'] = $sumberBerita;
                $p['sumber_berita'] = $sumberBerita;
                if ($this->request->getPost('thumbnail')) {
                    $p['thumbnail'] = $this->request->getPost('thumbnail');
                }
                $updated = true;
                break;
            }
        }

        if ($updated) {
            DataRepository::save('posts', $posts);
            return redirect()->to('/post')->with('success', 'Article updated successfully!');
        }

        return redirect()->to('/post')->with('error', 'Article not found');
    }

    public function delete($id)
    {
        $posts = DataRepository::get('posts');
        $posts = array_filter($posts, fn($p) => $p['id'] != $id);
        DataRepository::save('posts', array_values($posts));

        return redirect()->to('/post')->with('success', 'Article removed successfully.');
    }

    public function bulkAction()
    {
        $action = $this->request->getPost('action');
        $selectedIds = $this->request->getPost('selected_ids');

        if (!empty($selectedIds)) {
            $ids = is_array($selectedIds) ? $selectedIds : explode(',', $selectedIds);
            $posts = DataRepository::get('posts');

            if ($action === 'publish') {
                foreach ($posts as &$p) {
                    if (in_array($p['id'], $ids)) $p['status'] = 'Published';
                }
                DataRepository::save('posts', $posts);
                return redirect()->to('/post')->with('success', count($ids) . ' articles marked as Published.');
            } elseif ($action === 'draft') {
                foreach ($posts as &$p) {
                    if (in_array($p['id'], $ids)) $p['status'] = 'Draft';
                }
                DataRepository::save('posts', $posts);
                return redirect()->to('/post')->with('success', count($ids) . ' articles moved to Draft.');
            } elseif ($action === 'delete') {
                $posts = array_filter($posts, fn($p) => !in_array($p['id'], $ids));
                DataRepository::save('posts', array_values($posts));
                return redirect()->to('/post')->with('success', count($ids) . ' articles deleted.');
            }
        }

        return redirect()->to('/post');
    }

    public function export()
    {
        $posts = DataRepository::get('posts');
        $filename = 'investor_articles_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Title', 'Author', 'Category', 'Created Date', 'Publish Date', 'Status', 'Views', 'Is Premium']);

        foreach ($posts as $row) {
            fputcsv($output, [
                $row['id'],
                $row['title'],
                $row['author'],
                $row['category'],
                $row['create_date'],
                $row['publish_date'],
                $row['status'],
                $row['views'],
                $row['is_premium'] ? 'Yes' : 'No'
            ]);
        }
        fclose($output);
        exit;
    }
}

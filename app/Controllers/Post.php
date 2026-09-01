<?php

namespace App\Controllers;

use App\Models\DataRepository;

class Post extends BaseController
{
    public function index()
    {
        $posts = DataRepository::get('posts');
        $categories = DataRepository::get('categories');

        // Filtering parameters
        $keyword = $this->request->getGet('q');
        $categoryFilter = $this->request->getGet('category');
        $statusFilter = $this->request->getGet('status');
        $premiumFilter = $this->request->getGet('premium');

        if ($keyword) {
            $posts = array_filter($posts, fn($p) => 
                stripos($p['title'], $keyword) !== false || 
                stripos($p['author'], $keyword) !== false
            );
        }

        if ($categoryFilter && $categoryFilter !== 'all') {
            $posts = array_filter($posts, fn($p) => $p['category'] === $categoryFilter);
        }

        if ($statusFilter && $statusFilter !== 'all') {
            $posts = array_filter($posts, fn($p) => strtolower($p['status']) === strtolower($statusFilter));
        }

        if ($premiumFilter !== null && $premiumFilter !== '' && $premiumFilter !== 'all') {
            $posts = array_filter($posts, fn($p) => (string)$p['is_premium'] === (string)$premiumFilter);
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
            'categories' => $categories,
            'filters' => [
                'q' => $keyword,
                'category' => $categoryFilter ?? 'all',
                'status' => $statusFilter ?? 'all',
                'premium' => $premiumFilter ?? 'all',
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
        $categories = DataRepository::get('categories');
        $users = DataRepository::get('users');
        $media = DataRepository::get('media_extended') ?? [];

        return view('post/editor', [
            'title' => 'Create New Post',
            'breadcrumb' => 'Post / New Article',
            'categories' => $categories,
            'users' => $users,
            'media' => $media,
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

        $categories = DataRepository::get('categories');
        $users = DataRepository::get('users');
        $media = DataRepository::get('media_extended') ?? [];

        return view('post/editor', [
            'title' => 'Edit Post: ' . $article['title'],
            'breadcrumb' => 'Post / Edit Article',
            'categories' => $categories,
            'users' => $users,
            'media' => $media,
            'article' => $article,
            'isEdit' => true
        ]);
    }

    public function save()
    {
        $posts = DataRepository::get('posts');
        
        $newId = count($posts) > 0 ? max(array_column($posts, 'id')) + 1 : 1;
        $title = $this->request->getPost('title');
        
        $newArticle = [
            'id' => $newId,
            'title' => $title,
            'slug' => url_title($title, '-', true),
            'upper_deck' => $this->request->getPost('upper_deck') ?? '',
            'author' => $this->request->getPost('author') ?? 'Raka Herdika',
            'category' => $this->request->getPost('category') ?? 'Market Stock',
            'category_slug' => url_title($this->request->getPost('category') ?? 'market-stock', '-', true),
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
            'source' => $this->request->getPost('source') ?? 'Investor.id Research',
            'headline_type' => $this->request->getPost('headline_type') ?? 'none',
            'is_breaking' => (int)($this->request->getPost('is_breaking') ?? 0),
        ];

        array_unshift($posts, $newArticle);
        DataRepository::save('posts', $posts);

        return redirect()->to('/post')->with('success', 'Article successfully created and published!');
    }

    public function update($id)
    {
        $posts = DataRepository::get('posts');
        $updated = false;

        foreach ($posts as &$p) {
            if ($p['id'] == $id) {
                $p['title'] = $this->request->getPost('title');
                $p['upper_deck'] = $this->request->getPost('upper_deck') ?? '';
                $p['author'] = $this->request->getPost('author');
                $p['category'] = $this->request->getPost('category');
                $p['category_slug'] = url_title($this->request->getPost('category') ?? 'market-stock', '-', true);
                $p['status'] = $this->request->getPost('status');
                $p['is_premium'] = (int)($this->request->getPost('is_premium') ?? 0);
                $p['is_breaking'] = (int)($this->request->getPost('is_breaking') ?? 0);
                $p['publish_date'] = $this->request->getPost('publish_date');
                $p['summary'] = $this->request->getPost('summary');
                $p['content'] = $this->request->getPost('content');
                $p['tags'] = $this->request->getPost('tags') ?? '';
                $p['source'] = $this->request->getPost('source') ?? '';
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

<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ArticleModel;
use App\Models\CategoryModel;

class ArticleApi extends BaseController
{
    protected ArticleModel $articleModel;
    protected CategoryModel $categoryModel;

    public function __construct()
    {
        $this->articleModel = new ArticleModel();
        $this->categoryModel = new CategoryModel();
    }

    /**
     * GET /api/articles
     * Query parameters: limit, offset, category/kanal, tag, sort (latest, popular), q
     */
    public function index()
    {
        $limit = (int)($this->request->getGet('limit') ?? 10);
        if ($limit < 1) $limit = 10;
        if ($limit > 100) $limit = 100;

        $offset = (int)($this->request->getGet('offset') ?? 0);
        if ($offset < 0) $offset = 0;

        $page = (int)floor($offset / $limit) + 1;

        $category = $this->request->getGet('category') ?: $this->request->getGet('kanal');
        $tag = $this->request->getGet('tag');
        $q = $this->request->getGet('q');
        $sort = $this->request->getGet('sort') ?: 'latest';

        $db = \Config\Database::connect();
        $builder = $db->table('article n')
            ->select('
                n.articleID AS id,
                n.headline AS title,
                n.slug AS slug,
                n.summary AS summary,
                n.viewed AS views_count,
                n.articleDate AS published_at,
                n.status AS status,
                ANY_VALUE(c.categoryName) AS category_name,
                ANY_VALUE(c.label) AS category_slug,
                ANY_VALUE(c.categoryID) AS category_id,
                GROUP_CONCAT(DISTINCT a.authorName SEPARATOR ", ") AS author_name,
                COALESCE(ANY_VALUE(nm_thumb.metaval), "/img/investor_placeholder.jpg") AS featured_image,
                COALESCE(ANY_VALUE(nm_deck.metaval), "") AS upper_deck,
                COALESCE(ANY_VALUE(nm_prem.metaval), "0") AS is_premium
            ')
            ->join('articlecategory ncl', 'ncl.articleID = n.articleID', 'left')
            ->join('category c', 'c.categoryID = ncl.categoryID', 'left')
            ->join('articleauthor nal', 'nal.articleID = n.articleID', 'left')
            ->join('authors a', 'a.authorID = nal.authorID', 'left')
            ->join('articlemeta nm_thumb', 'nm_thumb.articleID = n.articleID AND nm_thumb.metakey = "thumbnail"', 'left')
            ->join('articlemeta nm_deck', 'nm_deck.articleID = n.articleID AND nm_deck.metakey = "upper_deck"', 'left')
            ->join('articlemeta nm_prem', 'nm_prem.articleID = n.articleID AND nm_prem.metakey = "is_premium"', 'left')
            ->whereIn('LOWER(n.status)', ['publish', 'published']);

        if (!empty($category) && $category !== 'all') {
            $builder->groupStart()
                ->where('c.categoryName', $category)
                ->orWhere('c.label', $category)
                ->orLike('c.categoryName', $category, 'after')
                ->groupEnd();
        }

        if (!empty($tag)) {
            $builder->join('articletag ntl', 'ntl.articleId = n.articleID', 'inner')
                    ->join('tag t', 't.tagID = ntl.tagID', 'inner')
                    ->groupStart()
                    ->where('t.slug', $tag)
                    ->orWhere('t.label', $tag)
                    ->groupEnd();
        }

        if (!empty($q)) {
            $builder->groupStart()
                ->like('n.headline', $q)
                ->orLike('n.summary', $q)
                ->orLike('a.authorName', $q)
                ->groupEnd();
        }

        $builder->groupBy('n.articleID');

        $countBuilder = clone $builder;
        $total = count($countBuilder->get()->getResultArray());

        if ($sort === 'popular') {
            $builder->orderBy('n.viewed', 'DESC')->orderBy('n.articleDate', 'DESC');
        } else {
            $builder->orderBy('n.articleDate', 'DESC')->orderBy('n.articleID', 'DESC');
        }

        $articles = $builder->limit($limit, $offset)->get()->getResultArray();

        foreach ($articles as &$item) {
            $item['id'] = (int)$item['id'];
            $item['is_premium'] = (int)$item['is_premium'];
            $item['views_count'] = (int)$item['views_count'];
            $item['author_name'] = $item['author_name'] ?: 'Redaksi Investor.id';
            $item['category_name'] = $item['category_name'] ?: 'Market';
            $item['category_slug'] = $item['category_slug'] ?: 'market';
            $item['featured_image'] = $item['featured_image'] ?: '/img/investor_placeholder.jpg';
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $articles,
            'meta'   => [
                'limit'  => $limit,
                'offset' => $offset,
                'page'   => $page,
                'total'  => $total
            ]
        ]);
    }

    /**
     * GET /api/articles/(:any)
     * Retrieve single article detail by slug or ID
     */
    public function show($identifier = null)
    {
        if (empty($identifier)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Article identifier (slug or ID) is required.'
            ]);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('article n')
            ->select('
                n.articleID AS id,
                n.headline AS title,
                n.slug AS slug,
                n.summary AS summary,
                n.body AS body,
                n.body AS content,
                n.viewed AS views_count,
                n.articleDate AS published_at,
                n.status AS status,
                ANY_VALUE(c.categoryName) AS category_name,
                ANY_VALUE(c.label) AS category_slug,
                ANY_VALUE(c.categoryID) AS category_id,
                GROUP_CONCAT(DISTINCT a.authorName SEPARATOR ", ") AS author_name,
                COALESCE(ANY_VALUE(nm_thumb.metaval), "/img/investor_placeholder.jpg") AS featured_image,
                COALESCE(ANY_VALUE(nm_deck.metaval), "") AS upper_deck,
                COALESCE(ANY_VALUE(nm_prem.metaval), "0") AS is_premium,
                COALESCE(ANY_VALUE(nm_source.metaval), "Investor.id / Riset") AS source
            ')
            ->join('articlecategory ncl', 'ncl.articleID = n.articleID', 'left')
            ->join('category c', 'c.categoryID = ncl.categoryID', 'left')
            ->join('articleauthor nal', 'nal.articleID = n.articleID', 'left')
            ->join('authors a', 'a.authorID = nal.authorID', 'left')
            ->join('articlemeta nm_thumb', 'nm_thumb.articleID = n.articleID AND nm_thumb.metakey = "thumbnail"', 'left')
            ->join('articlemeta nm_deck', 'nm_deck.articleID = n.articleID AND nm_deck.metakey = "upper_deck"', 'left')
            ->join('articlemeta nm_prem', 'nm_prem.articleID = n.articleID AND nm_prem.metakey = "is_premium"', 'left')
            ->join('articlemeta nm_source', 'nm_source.articleID = n.articleID AND nm_source.metakey = "source"', 'left')
            ->groupBy('n.articleID');

        if (is_numeric($identifier)) {
            $builder->where('n.articleID', (int)$identifier);
        } else {
            $builder->where('n.slug', $identifier);
        }

        $article = $builder->get()->getRowArray();

        if (!$article) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Article not found.'
            ]);
        }

        // Increment views count
        $db->table('article')
            ->where('articleID', $article['id'])
            ->increment('viewed', 1);

        $article['id'] = (int)$article['id'];
        $article['is_premium'] = (int)$article['is_premium'];
        $article['views_count'] = (int)$article['views_count'] + 1;
        $article['author_name'] = $article['author_name'] ?: 'Redaksi Investor.id';
        $article['category_name'] = $article['category_name'] ?: 'Market';
        $article['category_slug'] = $article['category_slug'] ?: 'market';
        $article['featured_image'] = $article['featured_image'] ?: '/img/investor_placeholder.jpg';

        // Fetch Points (Singkatnya)
        $pointsList = $db->table('article_points')
            ->where('article_id', $article['id'])
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();
        if (!empty($pointsList)) {
            $article['points'] = array_column($pointsList, 'point_text');
        } else {
            $pointsMeta = $db->table('articlemeta')
                ->where('articleID', $article['id'])
                ->where('metakey', 'point')
                ->orderBy('metaID', 'ASC')
                ->get()->getResultArray();
            $article['points'] = array_column($pointsMeta, 'metaval');
        }

        // Fetch Tags
        $tags = $db->table('articletag ntl')
            ->select('t.tagID AS id, t.label AS name, t.slug AS slug')
            ->join('tag t', 't.tagID = ntl.tagID')
            ->where('ntl.articleId', $article['id'])
            ->get()->getResultArray();
        $article['tags'] = $tags;

        // Detect stock codes from content/tags (e.g. $BBCA, BBCA, TLKM)
        $stockCodes = [];
        preg_match_all('/\$([A-Z]{4})\b/', $article['title'] . ' ' . $article['body'], $matches);
        if (!empty($matches[1])) {
            $stockCodes = array_unique($matches[1]);
        }
        $article['stock_symbols'] = array_values($stockCodes);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $article
        ]);
    }

    /**
     * GET /api/articles/(:any)/related
     */
    public function related($identifier = null)
    {
        $db = \Config\Database::connect();
        
        $article = $db->table('article n')
            ->select('n.articleID AS id, ANY_VALUE(ncl.categoryID) AS category_id')
            ->join('articlecategory ncl', 'ncl.articleID = n.articleID', 'left')
            ->where(is_numeric($identifier) ? 'n.articleID' : 'n.slug', $identifier)
            ->groupBy('n.articleID')
            ->get()->getRowArray();

        if (!$article) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Article not found.'
            ]);
        }

        $categoryId = (int)($article['category_id'] ?? 0);
        $builder = $db->table('article n')
            ->select('
                n.articleID AS id,
                n.headline AS title,
                n.slug AS slug,
                n.summary AS summary,
                n.articleDate AS published_at,
                n.viewed AS views_count,
                COALESCE(ANY_VALUE(nm_thumb.metaval), "/img/investor_placeholder.jpg") AS featured_image,
                ANY_VALUE(c.categoryName) AS category_name,
                ANY_VALUE(c.label) AS category_slug
            ')
            ->join('articlecategory ncl', 'ncl.articleID = n.articleID', 'left')
            ->join('category c', 'c.categoryID = ncl.categoryID', 'left')
            ->join('articlemeta nm_thumb', 'nm_thumb.articleID = n.articleID AND nm_thumb.metakey = "thumbnail"', 'left')
            ->where('n.articleID !=', $article['id'])
            ->where('LOWER(n.status)', 'published');

        if ($categoryId > 0) {
            $builder->where('ncl.categoryID', $categoryId);
        }

        $related = $builder->groupBy('n.articleID')
            ->orderBy('n.articleDate', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $related
        ]);
    }

    /**
     * GET /api/categories
     */
    public function categories()
    {
        $tree = $this->categoryModel->getKanalTree();
        
        $flatten = [];
        foreach ($tree as $k) {
            $flatten[] = [
                'id'            => (int)$k['id'],
                'name'          => $k['name'],
                'slug'          => $k['slug'] ?: url_title($k['name'], '-', true),
                'article_count' => (int)$k['article_count'],
                'sub_kanals'    => array_map(function ($sub) {
                    return [
                        'id'            => (int)$sub['id'],
                        'name'          => $sub['name'],
                        'slug'          => $sub['slug'] ?: url_title($sub['name'], '-', true),
                        'article_count' => (int)$sub['article_count'],
                    ];
                }, $k['sub_kanals'] ?? [])
            ];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $flatten
        ]);
    }

    /**
     * GET /api/search?q=keyword
     */
    public function search()
    {
        $q = trim($this->request->getGet('q') ?? '');
        if (empty($q)) {
            return $this->response->setJSON([
                'status' => 'success',
                'data'   => [],
                'meta'   => ['total' => 0]
            ]);
        }

        $db = \Config\Database::connect();
        $articles = $db->table('article n')
            ->select('
                n.articleID AS id,
                n.headline AS title,
                n.slug AS slug,
                n.summary AS summary,
                n.viewed AS views_count,
                n.articleDate AS published_at,
                ANY_VALUE(c.categoryName) AS category_name,
                ANY_VALUE(c.label) AS category_slug,
                COALESCE(ANY_VALUE(nm_thumb.metaval), "/img/investor_placeholder.jpg") AS featured_image
            ')
            ->join('articlecategory ncl', 'ncl.articleID = n.articleID', 'left')
            ->join('category c', 'c.categoryID = ncl.categoryID', 'left')
            ->join('articlemeta nm_thumb', 'nm_thumb.articleID = n.articleID AND nm_thumb.metakey = "thumbnail"', 'left')
            ->whereIn('LOWER(n.status)', ['publish', 'published'])
            ->groupStart()
                ->like('n.headline', $q)
                ->orLike('n.summary', $q)
                ->orLike('n.body', $q)
            ->groupEnd()
            ->groupBy('n.articleID')
            ->orderBy('n.articleDate', 'DESC')
            ->limit(20)
            ->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $articles,
            'meta'   => ['total' => count($articles)]
        ]);
    }
}

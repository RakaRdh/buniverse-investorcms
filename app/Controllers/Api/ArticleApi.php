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
        $builder = $db->table('entity_nano n')
            ->select('
                n.ref_entity_nano_id AS id,
                n.headline_text AS title,
                n.slug_value AS slug,
                n.summary_text AS summary,
                n.view_count AS views_count,
                n.entity_date AS published_at,
                n.status_code AS status,
                ANY_VALUE(c.charlie_name_a) AS category_name,
                ANY_VALUE(c.field_label_a) AS category_slug,
                ANY_VALUE(c.ref_entity_charlie_id) AS category_id,
                GROUP_CONCAT(DISTINCT a.aubert_name_a SEPARATOR ", ") AS author_name,
                COALESCE(ANY_VALUE(nm_thumb.meta_value), "/img/investor_placeholder.jpg") AS featured_image,
                COALESCE(ANY_VALUE(nm_deck.meta_value), "") AS upper_deck,
                COALESCE(ANY_VALUE(nm_prem.meta_value), "0") AS is_premium
            ')
            ->join('entity_nano_charlie_link ncl', 'ncl.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
            ->join('entity_charlie c', 'c.ref_entity_charlie_id = ncl.ref_entity_charlie_id', 'left')
            ->join('entity_nano_aubert_link nal', 'nal.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
            ->join('entity_aubert a', 'a.ref_entity_aubert_id = nal.ref_entity_aubert_id', 'left')
            ->join('entity_nano_meta nm_thumb', 'nm_thumb.ref_entity_nano_id = n.ref_entity_nano_id AND nm_thumb.meta_key = "thumbnail"', 'left')
            ->join('entity_nano_meta nm_deck', 'nm_deck.ref_entity_nano_id = n.ref_entity_nano_id AND nm_deck.meta_key = "upper_deck"', 'left')
            ->join('entity_nano_meta nm_prem', 'nm_prem.ref_entity_nano_id = n.ref_entity_nano_id AND nm_prem.meta_key = "is_premium"', 'left')
            ->where('LOWER(n.status_code)', 'published');

        if (!empty($category) && $category !== 'all') {
            $builder->groupStart()
                ->where('c.charlie_name_a', $category)
                ->orWhere('c.field_label_a', $category)
                ->orLike('c.charlie_name_a', $category, 'after')
                ->groupEnd();
        }

        if (!empty($tag)) {
            $builder->join('entity_nano_teta_link ntl', 'ntl.ref_entity_nano_id = n.ref_entity_nano_id', 'inner')
                    ->join('entity_teta t', 't.ref_entity_teta_id = ntl.ref_entity_teta_id', 'inner')
                    ->groupStart()
                    ->where('t.slug_value', $tag)
                    ->orWhere('t.field_label_a', $tag)
                    ->groupEnd();
        }

        if (!empty($q)) {
            $builder->groupStart()
                ->like('n.headline_text', $q)
                ->orLike('n.summary_text', $q)
                ->orLike('a.aubert_name_a', $q)
                ->groupEnd();
        }

        $builder->groupBy('n.ref_entity_nano_id');

        $countBuilder = clone $builder;
        $total = count($countBuilder->get()->getResultArray());

        if ($sort === 'popular') {
            $builder->orderBy('n.view_count', 'DESC')->orderBy('n.entity_date', 'DESC');
        } else {
            $builder->orderBy('n.entity_date', 'DESC')->orderBy('n.ref_entity_nano_id', 'DESC');
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
        $builder = $db->table('entity_nano n')
            ->select('
                n.ref_entity_nano_id AS id,
                n.headline_text AS title,
                n.slug_value AS slug,
                n.summary_text AS summary,
                n.body_text AS body,
                n.body_text AS content,
                n.view_count AS views_count,
                n.entity_date AS published_at,
                n.status_code AS status,
                ANY_VALUE(c.charlie_name_a) AS category_name,
                ANY_VALUE(c.field_label_a) AS category_slug,
                ANY_VALUE(c.ref_entity_charlie_id) AS category_id,
                GROUP_CONCAT(DISTINCT a.aubert_name_a SEPARATOR ", ") AS author_name,
                COALESCE(ANY_VALUE(nm_thumb.meta_value), "/img/investor_placeholder.jpg") AS featured_image,
                COALESCE(ANY_VALUE(nm_deck.meta_value), "") AS upper_deck,
                COALESCE(ANY_VALUE(nm_prem.meta_value), "0") AS is_premium,
                COALESCE(ANY_VALUE(nm_source.meta_value), "Investor.id / Riset") AS source
            ')
            ->join('entity_nano_charlie_link ncl', 'ncl.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
            ->join('entity_charlie c', 'c.ref_entity_charlie_id = ncl.ref_entity_charlie_id', 'left')
            ->join('entity_nano_aubert_link nal', 'nal.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
            ->join('entity_aubert a', 'a.ref_entity_aubert_id = nal.ref_entity_aubert_id', 'left')
            ->join('entity_nano_meta nm_thumb', 'nm_thumb.ref_entity_nano_id = n.ref_entity_nano_id AND nm_thumb.meta_key = "thumbnail"', 'left')
            ->join('entity_nano_meta nm_deck', 'nm_deck.ref_entity_nano_id = n.ref_entity_nano_id AND nm_deck.meta_key = "upper_deck"', 'left')
            ->join('entity_nano_meta nm_prem', 'nm_prem.ref_entity_nano_id = n.ref_entity_nano_id AND nm_prem.meta_key = "is_premium"', 'left')
            ->join('entity_nano_meta nm_source', 'nm_source.ref_entity_nano_id = n.ref_entity_nano_id AND nm_source.meta_key = "source"', 'left')
            ->groupBy('n.ref_entity_nano_id');

        if (is_numeric($identifier)) {
            $builder->where('n.ref_entity_nano_id', (int)$identifier);
        } else {
            $builder->where('n.slug_value', $identifier);
        }

        $article = $builder->get()->getRowArray();

        if (!$article) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Article not found.'
            ]);
        }

        // Increment views count
        $db->table('entity_nano')
            ->where('ref_entity_nano_id', $article['id'])
            ->increment('view_count', 1);

        $article['id'] = (int)$article['id'];
        $article['is_premium'] = (int)$article['is_premium'];
        $article['views_count'] = (int)$article['views_count'] + 1;
        $article['author_name'] = $article['author_name'] ?: 'Redaksi Investor.id';
        $article['category_name'] = $article['category_name'] ?: 'Market';
        $article['category_slug'] = $article['category_slug'] ?: 'market';
        $article['featured_image'] = $article['featured_image'] ?: '/img/investor_placeholder.jpg';

        // Fetch Points (Singkatnya)
        $pointsList = $db->table('article_points')
            ->where('ref_entity_nano_id', $article['id'])
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();
        if (!empty($pointsList)) {
            $article['points'] = array_column($pointsList, 'point_text');
        } else {
            $pointsMeta = $db->table('entity_nano_meta')
                ->where('ref_entity_nano_id', $article['id'])
                ->where('meta_key', 'point')
                ->orderBy('record_id', 'ASC')
                ->get()->getResultArray();
            $article['points'] = array_column($pointsMeta, 'meta_value');
        }

        // Fetch Tags
        $tags = $db->table('entity_nano_teta_link ntl')
            ->select('t.ref_entity_teta_id AS id, t.field_label_a AS name, t.slug_value AS slug')
            ->join('entity_teta t', 't.ref_entity_teta_id = ntl.ref_entity_teta_id')
            ->where('ntl.ref_entity_nano_id', $article['id'])
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
        
        $article = $db->table('entity_nano n')
            ->select('n.ref_entity_nano_id AS id, ANY_VALUE(ncl.ref_entity_charlie_id) AS category_id')
            ->join('entity_nano_charlie_link ncl', 'ncl.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
            ->where(is_numeric($identifier) ? 'n.ref_entity_nano_id' : 'n.slug_value', $identifier)
            ->groupBy('n.ref_entity_nano_id')
            ->get()->getRowArray();

        if (!$article) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Article not found.'
            ]);
        }

        $categoryId = (int)($article['category_id'] ?? 0);
        $builder = $db->table('entity_nano n')
            ->select('
                n.ref_entity_nano_id AS id,
                n.headline_text AS title,
                n.slug_value AS slug,
                n.summary_text AS summary,
                n.entity_date AS published_at,
                n.view_count AS views_count,
                COALESCE(ANY_VALUE(nm_thumb.meta_value), "/img/investor_placeholder.jpg") AS featured_image,
                ANY_VALUE(c.charlie_name_a) AS category_name,
                ANY_VALUE(c.field_label_a) AS category_slug
            ')
            ->join('entity_nano_charlie_link ncl', 'ncl.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
            ->join('entity_charlie c', 'c.ref_entity_charlie_id = ncl.ref_entity_charlie_id', 'left')
            ->join('entity_nano_meta nm_thumb', 'nm_thumb.ref_entity_nano_id = n.ref_entity_nano_id AND nm_thumb.meta_key = "thumbnail"', 'left')
            ->where('n.ref_entity_nano_id !=', $article['id'])
            ->where('LOWER(n.status_code)', 'published');

        if ($categoryId > 0) {
            $builder->where('ncl.ref_entity_charlie_id', $categoryId);
        }

        $related = $builder->groupBy('n.ref_entity_nano_id')
            ->orderBy('n.entity_date', 'DESC')
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
        $articles = $db->table('entity_nano n')
            ->select('
                n.ref_entity_nano_id AS id,
                n.headline_text AS title,
                n.slug_value AS slug,
                n.summary_text AS summary,
                n.view_count AS views_count,
                n.entity_date AS published_at,
                ANY_VALUE(c.charlie_name_a) AS category_name,
                ANY_VALUE(c.field_label_a) AS category_slug,
                COALESCE(ANY_VALUE(nm_thumb.meta_value), "/img/investor_placeholder.jpg") AS featured_image
            ')
            ->join('entity_nano_charlie_link ncl', 'ncl.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
            ->join('entity_charlie c', 'c.ref_entity_charlie_id = ncl.ref_entity_charlie_id', 'left')
            ->join('entity_nano_meta nm_thumb', 'nm_thumb.ref_entity_nano_id = n.ref_entity_nano_id AND nm_thumb.meta_key = "thumbnail"', 'left')
            ->where('LOWER(n.status_code)', 'published')
            ->groupStart()
                ->like('n.headline_text', $q)
                ->orLike('n.summary_text', $q)
                ->orLike('n.body_text', $q)
            ->groupEnd()
            ->groupBy('n.ref_entity_nano_id')
            ->orderBy('n.entity_date', 'DESC')
            ->limit(20)
            ->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $articles,
            'meta'   => ['total' => count($articles)]
        ]);
    }
}

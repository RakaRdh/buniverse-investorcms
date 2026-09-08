<?php

namespace App\Models;

use CodeIgniter\Model;

class ArticleModel extends Model
{
    protected $table = 'entity_nano';
    protected $primaryKey = 'ref_entity_nano_id';
    protected $allowedFields = [
        'ref_entity_acc_id',
        'source_channel',
        'headline_text',
        'slug_value',
        'entity_date',
        'post_type',
        'view_count',
        'status_code',
        'summary_text',
        'body_text',
        'created_at',
        'edit_lock'
    ];
    protected $useTimestamps = false;

    /**
     * JOIN Query: Get Articles with Categories, Authors, Tags, and Metadata
     */
    public function getArticlesWithRelations(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $builder = $this->db->table('entity_nano n')
            ->select('
                n.ref_entity_nano_id AS id,
                n.ref_entity_acc_id AS author_user_id,
                n.headline_text AS title,
                n.slug_value AS slug,
                n.summary_text AS summary,
                n.body_text AS content,
                n.status_code AS status,
                n.view_count AS views,
                n.entity_date AS publish_date,
                n.created_at AS create_date,
                p.attr_identifier AS post_by,
                p.attr_display_name AS post_by_name,
                ANY_VALUE(c.charlie_name_a) AS category,
                ANY_VALUE(c.field_label_a) AS category_slug,
                ANY_VALUE(c.parent_id) AS category_parent_id,
                GROUP_CONCAT(DISTINCT a.aubert_name_a SEPARATOR ", ") AS author,
                COALESCE(ANY_VALUE(nm_prem.meta_value), "0") AS is_premium,
                COALESCE(ANY_VALUE(nm_thumb.meta_value), "/img/investor_placeholder.jpg") AS thumbnail,
                COALESCE(ANY_VALUE(nm_head.meta_value), "none") AS headline_type,
                COALESCE(ANY_VALUE(nm_deck.meta_value), "") AS upper_deck
            ')
            ->join('sys_principal_entity p', 'p.ref_entity_principal_id = n.ref_entity_acc_id', 'left')
            ->join('entity_nano_charlie_link ncl', 'ncl.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
            ->join('entity_charlie c', 'c.ref_entity_charlie_id = ncl.ref_entity_charlie_id', 'left')
            ->join('entity_nano_aubert_link nal', 'nal.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
            ->join('entity_aubert a', 'a.ref_entity_aubert_id = nal.ref_entity_aubert_id', 'left')
            ->join('entity_nano_meta nm_prem', 'nm_prem.ref_entity_nano_id = n.ref_entity_nano_id AND nm_prem.meta_key = "is_premium"', 'left')
            ->join('entity_nano_meta nm_thumb', 'nm_thumb.ref_entity_nano_id = n.ref_entity_nano_id AND nm_thumb.meta_key = "thumbnail"', 'left')
            ->join('entity_nano_meta nm_head', 'nm_head.ref_entity_nano_id = n.ref_entity_nano_id AND nm_head.meta_key = "headline_type"', 'left')
            ->join('entity_nano_meta nm_deck', 'nm_deck.ref_entity_nano_id = n.ref_entity_nano_id AND nm_deck.meta_key = "upper_deck"', 'left')
            ->groupBy('n.ref_entity_nano_id');

        // Apply filters
        if (!empty($filters['user_id'])) {
            $builder->where('n.ref_entity_acc_id', (int)$filters['user_id']);
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $builder->where('LOWER(n.status_code)', strtolower($filters['status']));
        }

        if (!empty($filters['q'])) {
            $builder->groupStart()
                ->like('n.headline_text', $filters['q'])
                ->orLike('n.summary_text', $filters['q'])
                ->orLike('a.aubert_name_a', $filters['q'])
            ->groupEnd();
        }

        if (!empty($filters['kanal']) && $filters['kanal'] !== 'all') {
            $builder->groupStart()
                ->where('c.charlie_name_a', $filters['kanal'])
                ->orWhere('c.field_label_a', $filters['kanal'])
                ->orLike('c.charlie_name_a', $filters['kanal'], 'after')
            ->groupEnd();
        }

        if (isset($filters['premium']) && $filters['premium'] !== '' && $filters['premium'] !== 'all') {
            $builder->having('is_premium', (string)$filters['premium']);
        }

        if (!empty($filters['author']) && $filters['author'] !== 'all') {
            $builder->like('a.aubert_name_a', $filters['author']);
        }

        if (!empty($filters['editor']) && $filters['editor'] !== 'all') {
            $builder->where('p.attr_identifier', $filters['editor']);
        }

        if (!empty($filters['date_from'])) {
            $builder->where('n.entity_date >=', date('Y-m-d 00:00:00', strtotime($filters['date_from'])));
        }

        if (!empty($filters['date_to'])) {
            $builder->where('n.entity_date <=', date('Y-m-d 23:59:59', strtotime($filters['date_to'])));
        }

        $compiledSql = $builder->getCompiledSelect(false);
        $countRow = $this->db->query("SELECT COUNT(*) AS total FROM ({$compiledSql}) AS count_tbl")->getRowArray();
        $totalItems = (int)($countRow['total'] ?? 0);

        $offset = ($page - 1) * $perPage;
        $data = $builder->orderBy('n.entity_date', 'DESC')
            ->orderBy('n.ref_entity_nano_id', 'DESC')
            ->limit($perPage, $offset)
            ->get()->getResultArray();

        // Normalise fields for view compatibility
        foreach ($data as &$row) {
            $row['is_premium'] = (int)($row['is_premium'] ?? 0);
            $row['is_breaking'] = 0;
            $row['author'] = $row['author'] ?: 'Redaksi Investor';
            $row['category'] = $row['category'] ?: 'Market';
            $row['category_slug'] = $row['category_slug'] ?: 'market';
            $row['thumbnail'] = $row['thumbnail'] ?: '/img/investor_placeholder.jpg';
        }

        return [
            'data'  => $data,
            'total' => $totalItems,
        ];
    }

    /**
     * Fast, lightweight article query for dropdowns and inserter modals.
     * Selects only required columns with minimal JOINs instead of full relations.
     */
    public function getLightweightArticles(int $limit = 50): array
    {
        $builder = $this->db->table('tb_entity_nano n')
            ->select('
                n.ref_entity_nano_id AS id,
                n.attr_title AS title,
                n.attr_intro_text AS summary,
                DATE_FORMAT(n.entity_date, "%d %b %Y") AS publish_date,
                COALESCE(cat.attr_title, "Market") AS kanal,
                COALESCE(thumb.meta_value, "/img/investor_placeholder.jpg") AS thumbnail
            ')
            ->join('tb_nano_category nc', 'nc.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
            ->join('tb_category_nano cat', 'cat.ref_category_nano_id = nc.ref_category_nano_id', 'left')
            ->join('tb_nano_meta thumb', 'thumb.ref_entity_nano_id = n.ref_entity_nano_id AND thumb.meta_key = "thumbnail"', 'left')
            ->where('n.entity_status', 1)
            ->groupBy('n.ref_entity_nano_id')
            ->orderBy('n.entity_date', 'DESC')
            ->limit($limit);

        return $builder->get()->getResultArray();
    }

    /**
     * Get detail of a single article with taxonomy & metadata
     */
    public function getArticleDetail(int $id): ?array
    {
        $article = $this->db->table('entity_nano n')
            ->select('
                n.ref_entity_nano_id AS id,
                n.ref_entity_acc_id AS author_user_id,
                n.headline_text AS title,
                n.slug_value AS slug,
                n.summary_text AS summary,
                n.body_text AS content,
                n.status_code AS status,
                n.view_count AS views,
                n.entity_date AS publish_date,
                n.created_at AS create_date,
                p.attr_identifier AS post_by,
                p.attr_display_name AS post_by_name,
                ANY_VALUE(c.charlie_name_a) AS category,
                ANY_VALUE(c.field_label_a) AS category_slug,
                ANY_VALUE(c.ref_entity_charlie_id) AS category_id,
                GROUP_CONCAT(DISTINCT a.aubert_name_a SEPARATOR ", ") AS author,
                COALESCE(ANY_VALUE(nm_prem.meta_value), "0") AS is_premium,
                COALESCE(ANY_VALUE(nm_thumb.meta_value), "/img/investor_placeholder.jpg") AS thumbnail,
                COALESCE(ANY_VALUE(nm_head.meta_value), "none") AS headline_type,
                COALESCE(ANY_VALUE(nm_deck.meta_value), "") AS upper_deck,
                COALESCE(ANY_VALUE(nm_source.meta_value), "Investor.id / Riset") AS source,
                COALESCE(ANY_VALUE(nm_sub.meta_value), "") AS sub_kanal,
                COALESCE(ANY_VALUE(nm_kanal.meta_value), "") AS kanal
            ')
            ->join('sys_principal_entity p', 'p.ref_entity_principal_id = n.ref_entity_acc_id', 'left')
            ->join('entity_nano_charlie_link ncl', 'ncl.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
            ->join('entity_charlie c', 'c.ref_entity_charlie_id = ncl.ref_entity_charlie_id', 'left')
            ->join('entity_nano_aubert_link nal', 'nal.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
            ->join('entity_aubert a', 'a.ref_entity_aubert_id = nal.ref_entity_aubert_id', 'left')
            ->join('entity_nano_meta nm_prem', 'nm_prem.ref_entity_nano_id = n.ref_entity_nano_id AND nm_prem.meta_key = "is_premium"', 'left')
            ->join('entity_nano_meta nm_thumb', 'nm_thumb.ref_entity_nano_id = n.ref_entity_nano_id AND nm_thumb.meta_key = "thumbnail"', 'left')
            ->join('entity_nano_meta nm_head', 'nm_head.ref_entity_nano_id = n.ref_entity_nano_id AND nm_head.meta_key = "headline_type"', 'left')
            ->join('entity_nano_meta nm_deck', 'nm_deck.ref_entity_nano_id = n.ref_entity_nano_id AND nm_deck.meta_key = "upper_deck"', 'left')
            ->join('entity_nano_meta nm_source', 'nm_source.ref_entity_nano_id = n.ref_entity_nano_id AND nm_source.meta_key = "source"', 'left')
            ->join('entity_nano_meta nm_sub', 'nm_sub.ref_entity_nano_id = n.ref_entity_nano_id AND nm_sub.meta_key = "sub_kanal"', 'left')
            ->join('entity_nano_meta nm_kanal', 'nm_kanal.ref_entity_nano_id = n.ref_entity_nano_id AND nm_kanal.meta_key = "kanal"', 'left')
            ->where('n.ref_entity_nano_id', $id)
            ->groupBy('n.ref_entity_nano_id')
            ->get()->getRowArray();

        if (!$article) return null;

        $article['is_premium'] = (int)($article['is_premium'] ?? 0);
        $article['is_breaking'] = 0;
        $article['sumber_berita'] = $article['source'];

        // Get points (from standalone article_points table or fallback to entity_nano_meta)
        $nanoPoints = $this->db->table('article_points')
            ->where('ref_entity_nano_id', $id)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();
        if (!empty($nanoPoints)) {
            $article['points'] = array_column($nanoPoints, 'point_text');
        } else {
            $points = $this->db->table('entity_nano_meta')
                ->where('ref_entity_nano_id', $id)
                ->where('meta_key', 'point')
                ->orderBy('record_id', 'ASC')
                ->get()->getResultArray();
            $article['points'] = array_column($points, 'meta_value');
        }

        // Get tags
        $tags = $this->db->table('entity_nano_teta_link ntl')
            ->select('t.field_label_a')
            ->join('entity_teta t', 't.ref_entity_teta_id = ntl.ref_entity_teta_id')
            ->where('ntl.ref_entity_nano_id', $id)
            ->get()->getResultArray();
        $article['tags'] = implode(', ', array_column($tags, 'field_label_a'));

        return $article;
    }

    /**
     * Create Article with taxonomy linkages & metadata
     */
    public function createArticle(array $data, int $authorId, int $categoryId, array $tags = [], array $points = []): int
    {
        $this->db->transStart();

        $entityDate = date('Y-m-d H:i:s');
        if (!empty($data['publish_date'])) {
            $parsed = strtotime($data['publish_date']);
            if ($parsed !== false && $parsed > 0) {
                $entityDate = date('Y-m-d H:i:s', $parsed);
            }
        }

        $this->insert([
            'ref_entity_acc_id' => $data['ref_entity_acc_id'] ?? 1,
            'source_channel'    => 'cms',
            'headline_text'     => $data['title'],
            'slug_value'        => $data['slug'] ?? url_title($data['title'], '-', true),
            'entity_date'       => $entityDate,
            'post_type'         => 'Nano',
            'view_count'        => 0,
            'status_code'       => $data['status'] ?? 'Published',
            'summary_text'      => $data['summary'] ?? '',
            'body_text'         => $data['content'] ?? '',
            'created_at'        => date('Y-m-d H:i:s'),
            'edit_lock'         => 0,
        ]);
        $nanoId = (int)$this->insertID();

        // Link Kategori
        $this->db->table('entity_nano_charlie_link')->insert([
            'ref_entity_nano_id'    => $nanoId,
            'ref_entity_charlie_id' => $categoryId,
        ]);

        // Link Penulis
        $this->db->table('entity_nano_aubert_link')->insert([
            'ref_entity_nano_id'   => $nanoId,
            'ref_entity_aubert_id' => $authorId,
        ]);

        // Meta entries
        $metaEntries = [
            'thumbnail'     => $data['thumbnail'] ?? '/img/investor_placeholder.jpg',
            'is_premium'    => (string)($data['is_premium'] ?? 0),
            'headline_type' => $data['headline_type'] ?? 'none',
            'upper_deck'    => $data['upper_deck'] ?? '',
            'source'        => $data['source'] ?? 'Investor.id / Riset',
            'kanal'         => $data['kanal'] ?? '',
            'sub_kanal'     => $data['sub_kanal'] ?? '',
        ];

        foreach ($metaEntries as $k => $v) {
            $this->db->table('entity_nano_meta')->insert([
                'ref_entity_nano_id' => $nanoId,
                'meta_key'           => $k,
                'meta_value'         => $v,
            ]);
        }

        // Points (Save to both entity_nano_meta and standalone article_points table)
        foreach ($points as $idx => $pt) {
            $trimmed = trim($pt);
            if (!empty($trimmed)) {
                $this->db->table('entity_nano_meta')->insert([
                    'ref_entity_nano_id' => $nanoId,
                    'meta_key'           => 'point',
                    'meta_value'         => $trimmed,
                ]);

                $this->db->table('article_points')->insert([
                    'ref_entity_nano_id' => $nanoId,
                    'point_text'         => $trimmed,
                    'sort_order'         => $idx + 1,
                    'created_at'         => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Tags
        if (!empty($tags)) {
            $tagModel = new TagModel();
            $tagModel->syncArticleTags($nanoId, $tags);
        }

        $this->db->transComplete();

        return $nanoId;
    }

    /**
     * Update Article & audit trail history
     */
    public function updateArticle(int $id, array $data, int $authorId, int $categoryId, array $tags = [], array $points = [], int $editorId = 1): bool
    {
        $oldArticle = $this->find($id);
        if (!$oldArticle) return false;

        $this->db->transStart();

        // 1. Log revision history in entity_nano_history
        $this->db->table('entity_nano_history')->insert([
            'event_timestamp'       => date('Y-m-d H:i:s'),
            'ref_entity_nano_id'    => $id,
            'ref_entity_epsilon_id' => $editorId,
            'title_prev_val'        => $oldArticle['headline_text'],
            'title_after_val'       => $data['title'],
            'content_prev_val'      => $oldArticle['body_text'],
            'content_after_val'     => $data['content'],
            'status_prev_val'       => $oldArticle['status_code'],
            'status_after_val'      => $data['status'],
        ]);

        $entityDate = $oldArticle['entity_date'];
        if (!empty($data['publish_date'])) {
            $parsed = strtotime($data['publish_date']);
            if ($parsed !== false && $parsed > 0) {
                $entityDate = date('Y-m-d H:i:s', $parsed);
            }
        }

        // 2. Update entity_nano
        $this->update($id, [
            'headline_text' => $data['title'],
            'status_code'   => $data['status'],
            'summary_text'  => $data['summary'] ?? '',
            'body_text'     => $data['content'] ?? '',
            'entity_date'   => $entityDate,
        ]);

        // 3. Update Category Link
        $this->db->table('entity_nano_charlie_link')->where('ref_entity_nano_id', $id)->delete();
        $this->db->table('entity_nano_charlie_link')->insert([
            'ref_entity_nano_id'    => $id,
            'ref_entity_charlie_id' => $categoryId,
        ]);

        // 4. Update Author Link
        $this->db->table('entity_nano_aubert_link')->where('ref_entity_nano_id', $id)->delete();
        $this->db->table('entity_nano_aubert_link')->insert([
            'ref_entity_nano_id'   => $id,
            'ref_entity_aubert_id' => $authorId,
        ]);

        // 5. Update Metadata
        $metaKeys = ['thumbnail', 'is_premium', 'headline_type', 'upper_deck', 'source', 'kanal', 'sub_kanal'];
        $this->db->table('entity_nano_meta')
            ->where('ref_entity_nano_id', $id)
            ->whereIn('meta_key', array_merge($metaKeys, ['point']))
            ->delete();

        $metaEntries = [
            'thumbnail'     => $data['thumbnail'] ?? '/img/investor_placeholder.jpg',
            'is_premium'    => (string)($data['is_premium'] ?? 0),
            'headline_type' => $data['headline_type'] ?? 'none',
            'upper_deck'    => $data['upper_deck'] ?? '',
            'source'        => $data['source'] ?? 'Investor.id / Riset',
            'kanal'         => $data['kanal'] ?? '',
            'sub_kanal'     => $data['sub_kanal'] ?? '',
        ];

        foreach ($metaEntries as $k => $v) {
            $this->db->table('entity_nano_meta')->insert([
                'ref_entity_nano_id' => $id,
                'meta_key'           => $k,
                'meta_value'         => $v,
            ]);
        }

        // Points (Sync to both entity_nano_meta and standalone article_points table)
        $this->db->table('article_points')->where('ref_entity_nano_id', $id)->delete();
        foreach ($points as $idx => $pt) {
            $trimmed = trim($pt);
            if (!empty($trimmed)) {
                $this->db->table('entity_nano_meta')->insert([
                    'ref_entity_nano_id' => $id,
                    'meta_key'           => 'point',
                    'meta_value'         => $trimmed,
                ]);

                $this->db->table('article_points')->insert([
                    'ref_entity_nano_id' => $id,
                    'point_text'         => $trimmed,
                    'sort_order'         => $idx + 1,
                    'created_at'         => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Tags
        $tagModel = new TagModel();
        $tagModel->syncArticleTags($id, $tags);

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Delete article & cascaded links
     */
    public function deleteArticle(int $id): bool
    {
        $this->db->transStart();
        $this->db->table('entity_nano_charlie_link')->where('ref_entity_nano_id', $id)->delete();
        $this->db->table('entity_nano_aubert_link')->where('ref_entity_nano_id', $id)->delete();
        $this->db->table('entity_nano_teta_link')->where('ref_entity_nano_id', $id)->delete();
        $this->db->table('entity_nano_meta')->where('ref_entity_nano_id', $id)->delete();
        $this->db->table('entity_nano_headline')->where('ref_entity_nano_id', $id)->delete();
        $this->delete($id);
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Dashboard Metrics calculation via SQL aggregates
     */
    public function getDashboardMetrics(): array
    {
        $res = $this->db->table('entity_nano')
            ->select('
                COUNT(*) AS total,
                SUM(CASE WHEN LOWER(status_code) = "published" THEN 1 ELSE 0 END) AS published,
                SUM(CASE WHEN LOWER(status_code) = "draft" THEN 1 ELSE 0 END) AS drafts,
                SUM(CASE WHEN LOWER(status_code) = "scheduled" THEN 1 ELSE 0 END) AS scheduled,
                SUM(CASE WHEN LOWER(status_code) = "review" THEN 1 ELSE 0 END) AS review,
                SUM(COALESCE(view_count, 0)) AS total_views
            ')
            ->get()->getRowArray();

        return [
            'todayArticles' => (int)($res['total'] ?? 0),
            'published'     => (int)($res['published'] ?? 0),
            'drafts'        => (int)($res['drafts'] ?? 0),
            'scheduled'     => (int)($res['scheduled'] ?? 0),
            'review'        => (int)($res['review'] ?? 0),
            'totalViews'    => number_format((int)($res['total_views'] ?? 0) + 1200000),
            'rawViews'      => (int)($res['total_views'] ?? 0),
        ];
    }

    /**
     * Top Articles by View Count
     */
    public function getTopArticles(int $limit = 5): array
    {
        $rows = $this->db->table('entity_nano n')
            ->select('
                n.ref_entity_nano_id AS id,
                n.headline_text AS title,
                n.view_count AS views,
                n.status_code AS status,
                n.entity_date AS publish_date,
                GROUP_CONCAT(DISTINCT a.aubert_name_a SEPARATOR ", ") AS author,
                ANY_VALUE(c.charlie_name_a) AS category,
                COALESCE(ANY_VALUE(nm_prem.meta_value), "0") AS is_premium,
                COALESCE(ANY_VALUE(nm_thumb.meta_value), "/img/investor_placeholder.jpg") AS thumbnail
            ')
            ->join('entity_nano_aubert_link nal', 'nal.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
            ->join('entity_aubert a', 'a.ref_entity_aubert_id = nal.ref_entity_aubert_id', 'left')
            ->join('entity_nano_charlie_link ncl', 'ncl.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
            ->join('entity_charlie c', 'c.ref_entity_charlie_id = ncl.ref_entity_charlie_id', 'left')
            ->join('entity_nano_meta nm_prem', 'nm_prem.ref_entity_nano_id = n.ref_entity_nano_id AND nm_prem.meta_key = "is_premium"', 'left')
            ->join('entity_nano_meta nm_thumb', 'nm_thumb.ref_entity_nano_id = n.ref_entity_nano_id AND nm_thumb.meta_key = "thumbnail"', 'left')
            ->groupBy('n.ref_entity_nano_id')
            ->orderBy('n.view_count', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();

        foreach ($rows as &$r) {
            $r['is_premium'] = (int)($r['is_premium'] ?? 0);
            $r['author'] = $r['author'] ?: 'Redaksi Investor';
            $r['category'] = $r['category'] ?: 'Market';
        }

        return $rows;
    }

    /**
     * Articles in Pending Review / Draft status
     */
    public function getPendingArticles(int $limit = 10): array
    {
        return $this->db->table('entity_nano n')
            ->select('
                n.ref_entity_nano_id AS id,
                n.headline_text AS title,
                n.view_count AS views,
                n.status_code AS status,
                n.created_at AS create_date,
                GROUP_CONCAT(DISTINCT a.aubert_name_a SEPARATOR ", ") AS author
            ')
            ->join('entity_nano_aubert_link nal', 'nal.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
            ->join('entity_aubert a', 'a.ref_entity_aubert_id = nal.ref_entity_aubert_id', 'left')
            ->whereIn('LOWER(n.status_code)', ['review', 'draft'])
            ->groupBy('n.ref_entity_nano_id')
            ->orderBy('n.created_at', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }
}

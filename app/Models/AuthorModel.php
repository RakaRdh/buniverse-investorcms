<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthorModel extends Model
{
    protected $table = 'authors';
    protected $primaryKey = 'authorID';
    protected $allowedFields = [
        'authorName',
        'email',
        'slug',
        'valid'
    ];
    protected $useTimestamps = false;

    /**
     * JOIN Query: Get Authors with published article count
     */
    public function getAuthorsWithCount(): array
    {
        return $this->db->table('authors a')
            ->select('
                a.authorID AS id,
                a.authorName AS name,
                a.slug AS slug,
                a.email AS contact,
                a.valid AS status,
                COUNT(DISTINCT nal.articleID) AS article_count
            ')
            ->join('articleauthor nal', 'nal.authorID = a.authorID', 'left')
            ->groupBy('a.authorID')
            ->orderBy('article_count', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Find author or create new record if non-existent
     */
    public function findOrCreate(string $name): int
    {
        $name = trim($name);
        if (empty($name)) {
            $name = 'Redaksi Investor';
        }

        // Clean name for slug, and format name with *)
        $cleanName = trim(rtrim(trim($name), '*)'));
        $formattedName = !empty($cleanName) ? $cleanName . '*)' : 'Redaksi Investor*)';

        $row = $this->where('authorName', $formattedName)
            ->orWhere('authorName', $name)
            ->first();
        if ($row) {
            return (int)$row['authorID'];
        }

        $slug = url_title($cleanName, '-', true) ?: 'author-' . uniqid();

        $this->insert([
            'authorName' => $formattedName,
            'email'      => null,
            'slug'       => $slug,
            'valid'      => '1',
        ]);
        $newId = (int)$this->insertID();

        // Keep authors_current in sync if table exists
        if ($this->db->tableExists('authors_current')) {
            $this->db->table('authors_current')->insert([
                'authorID'   => $newId,
                'authorName' => $formattedName,
                'slug'       => null,
            ]);
        }

        return $newId;
    }
}

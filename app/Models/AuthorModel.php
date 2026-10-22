<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthorModel extends Model
{
    protected $table = 'Authors';
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
        return $this->db->table('Authors a')
            ->select('
                a.authorID AS id,
                a.authorName AS name,
                a.slug AS slug,
                a.email AS contact,
                a.valid AS status,
                COUNT(DISTINCT nal.articleID) AS article_count
            ')
            ->join('ArticleAuthor nal', 'nal.authorID = a.authorID', 'left')
            ->where('a.valid !=', '3')
            ->groupBy('a.authorID')
            ->orderBy('article_count', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Find author or create new record if non-existent
     * @param string $name Nama penulis
     * @param bool $isInternal Jika true, format internal newsroom diberi akhiran '*)' sesuai konvensi legacy
     */
    public function findOrCreate(string $name, bool $isInternal = true): int
    {
        $name = trim($name);
        if (empty($name)) {
            $name = 'Redaksi Investor';
        }

        // Clean name for slug, and format name with *) if internal
        $cleanName = trim(rtrim(trim($name), '*)'));
        $formattedName = $isInternal ? (!empty($cleanName) ? $cleanName . '*)' : 'Redaksi Investor*)') : $cleanName;

        $row = $this->where('authorName', $formattedName)
            ->orWhere('authorName', $name)
            ->orWhere('authorName', $cleanName)
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
        if ($this->db->tableExists('AuthorsCurrent')) {
            $this->db->table('AuthorsCurrent')->insert([
                'authorID'   => $newId,
                'authorName' => $formattedName,
                'slug'       => null,
            ]);
        }

        return $newId;
    }
}

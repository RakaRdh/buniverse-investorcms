<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthorModel extends Model
{
    protected $table = 'entity_aubert';
    protected $primaryKey = 'ref_entity_aubert_id';
    protected $allowedFields = [
        'aubert_name_a',
        'contact_val',
        'slug_value',
        'status_flag'
    ];
    protected $useTimestamps = false;

    /**
     * JOIN Query: Get Authors with published article count
     */
    public function getAuthorsWithCount(): array
    {
        return $this->db->table('entity_aubert a')
            ->select('
                a.ref_entity_aubert_id AS id,
                a.aubert_name_a AS name,
                a.slug_value AS slug,
                a.contact_val AS contact,
                a.status_flag AS status,
                COUNT(DISTINCT nal.ref_entity_nano_id) AS article_count
            ')
            ->join('entity_nano_aubert_link nal', 'nal.ref_entity_aubert_id = a.ref_entity_aubert_id', 'left')
            ->groupBy('a.ref_entity_aubert_id')
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

        $row = $this->where('aubert_name_a', $name)->first();
        if ($row) {
            return (int)$row['ref_entity_aubert_id'];
        }

        $this->insert([
            'aubert_name_a' => $name,
            'contact_val'   => '',
            'slug_value'    => url_title($name, '-', true) ?: 'author-' . uniqid(),
            'status_flag'   => 'STATE_1',
        ]);

        return (int)$this->insertID();
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class GoogleDocsModel extends Model
{
    protected $table = 'GoogleDocs';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'title',
        'url',
        'userID',
        'timestamp',
        'source'
    ];
    protected $useTimestamps = false;

    /**
     * Get list of embeddable Google Docs / Datawrapper charts
     */
    public function getDocsList(): array
    {
        return $this->db->table('GoogleDocs g')
            ->select('
                g.id,
                g.title,
                g.url,
                g.source,
                g.timestamp,
                p.name AS author
            ')
            ->join('UserAdministrator p', 'p.userID = g.userID', 'left')
            ->orderBy('g.id', 'DESC')
            ->get()->getResultArray();
    }
}

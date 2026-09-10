<?php

namespace App\Models;

use CodeIgniter\Model;

class FileModel extends Model
{
    protected $table = 'files';
    protected $primaryKey = 'fileID';
    protected $allowedFields = [
        'title',
        'filename',
        'timestamp',
        'userID'
    ];
    protected $useTimestamps = false;

    /**
     * JOIN Query: Files with uploader identifier and pagination
     */
    public function getFilesWithUploader(?string $keyword = null, int $page = 1, int $perPage = 25): array
    {
        $builder = $this->db->table('files f')
            ->select('
                f.fileID AS id,
                f.title AS name,
                f.filename AS url,
                f.timestamp AS date,
                p.username AS upload_by
            ')
            ->join('useradministrator p', 'p.userID = f.userID', 'left')
            ->orderBy('f.timestamp', 'DESC');

        if (!empty($keyword)) {
            $builder->groupStart()
                ->like('f.title', $keyword)
                ->orLike('p.username', $keyword)
            ->groupEnd();
        }

        $countBuilder = clone $builder;
        $totalItems = $countBuilder->countAllResults(false);

        $offset = ($page - 1) * $perPage;
        $data = $builder->limit($perPage, $offset)->get()->getResultArray();

        return [
            'data'       => $data,
            'totalItems' => $totalItems,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => max(1, (int)ceil($totalItems / $perPage)),
            'from'       => $totalItems > 0 ? $offset + 1 : 0,
            'to'         => min($offset + $perPage, $totalItems),
        ];
    }
}

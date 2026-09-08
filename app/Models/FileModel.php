<?php

namespace App\Models;

use CodeIgniter\Model;

class FileModel extends Model
{
    protected $table = 'storage_item_record';
    protected $primaryKey = 'ref_entity_file_id';
    protected $allowedFields = [
        'attr_header_title',
        'attr_resource_name',
        'attr_recorded_at',
        'ref_entity_principal_id'
    ];
    protected $useTimestamps = false;

    /**
     * JOIN Query: Files with uploader identifier and pagination
     */
    public function getFilesWithUploader(?string $keyword = null, int $page = 1, int $perPage = 25): array
    {
        $builder = $this->db->table('storage_item_record f')
            ->select('
                f.ref_entity_file_id AS id,
                f.attr_header_title AS name,
                f.attr_resource_name AS url,
                f.attr_recorded_at AS date,
                p.attr_identifier AS upload_by
            ')
            ->join('sys_principal_entity p', 'p.ref_entity_principal_id = f.ref_entity_principal_id', 'left')
            ->orderBy('f.attr_recorded_at', 'DESC');

        if (!empty($keyword)) {
            $builder->groupStart()
                ->like('f.attr_header_title', $keyword)
                ->orLike('p.attr_identifier', $keyword)
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

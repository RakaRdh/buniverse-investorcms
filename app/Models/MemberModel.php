<?php

namespace App\Models;

use CodeIgniter\Model;

class MemberModel extends Model
{
    protected $table = 'client_account_entity';
    protected $primaryKey = 'ref_entity_client_id';
    protected $allowedFields = [
        'attr_credential_hash',
        'uniq_id',
        'attr_contact_val',
        'attr_full_name',
        'attr_slug_token',
        'attr_registration_at',
        'attr_last_access_at',
        'attr_status_code',
        'attr_profile_image',
        'attr_profile_summary',
        'attr_modified_at',
    ];
    protected $useTimestamps = false;

    /**
     * Map status internal enum to human-friendly labels
     */
    public static function getStatusLabel(string $code): string
    {
        return match ($code) {
            'STATUS_1' => 'Active',
            'STATUS_2' => 'Pending',
            'STATUS_3' => 'Suspended',
            default    => 'Active',
        };
    }

    public static function getStatusCode(string $label): string
    {
        $label = strtolower(trim($label));
        return match ($label) {
            'pending'   => 'STATUS_2',
            'suspended' => 'STATUS_3',
            default     => 'STATUS_1',
        };
    }

    /**
     * Get paginated member list from client_account_entity
     */
    public function getMembers(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $builder = $this->db->table('client_account_entity');

        if (!empty($filters['q'])) {
            $q = trim($filters['q']);
            $builder->groupStart()
                ->like('attr_full_name', $q)
                ->orLike('attr_contact_val', $q)
                ->orLike('attr_profile_summary', $q)
                ->orLike('attr_slug_token', $q)
                ->groupEnd();
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $code = self::getStatusCode($filters['status']);
            $builder->where('attr_status_code', $code);
        }

        $total = $builder->countAllResults(false);
        $offset = ($page - 1) * $perPage;

        $builder->orderBy('ref_entity_client_id', 'DESC');
        $rows = $builder->limit($perPage, $offset)->get()->getResultArray();

        // Transform into clean presentation array
        $data = array_map(function ($row) {
            $clientId = (int)$row['ref_entity_client_id'];
            
            // Count favorites / read articles if linked
            $favCount = $this->db->table('client_favorite_entity_nano')
                ->where('ref_entity_client_id', $clientId)
                ->countAllResults();

            return [
                'id'              => $clientId,
                'full_name'       => $row['attr_full_name'] ?? 'Member',
                'email'           => $row['attr_contact_val'] ?? '-',
                'slug'            => $row['attr_slug_token'] ?? '',
                'company'         => $row['attr_profile_summary'] ?? 'Investor.id Member',
                'status_code'     => $row['attr_status_code'] ?? 'STATUS_1',
                'status'          => self::getStatusLabel($row['attr_status_code'] ?? 'STATUS_1'),
                'avatar'          => $row['attr_profile_image'] ?: null,
                'favorites_count' => $favCount,
                'last_login_at'   => $row['attr_last_access_at'],
                'created_at'      => $row['attr_registration_at'] ?? $row['attr_modified_at'],
                'modified_at'     => $row['attr_modified_at'],
            ];
        }, $rows);

        return [
            'data'       => $data,
            'totalItems' => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => max(1, (int)ceil($total / $perPage)),
            'offset'     => $offset
        ];
    }

    /**
     * Get member stats from client_account_entity
     */
    public function getMemberStats(): array
    {
        $total = $this->db->table('client_account_entity')->countAllResults();
        $active = $this->db->table('client_account_entity')->where('attr_status_code', 'STATUS_1')->countAllResults();
        $pending = $this->db->table('client_account_entity')->where('attr_status_code', 'STATUS_2')->countAllResults();
        $suspended = $this->db->table('client_account_entity')->where('attr_status_code', 'STATUS_3')->countAllResults();

        return [
            'total'     => $total,
            'active'    => $active,
            'pending'   => $pending,
            'suspended' => $suspended,
        ];
    }

    /**
     * Save member to client_account_entity AND sync to client_account_backup
     */
    public function saveMember(array $input, ?int $id = null): int
    {
        $fullName = trim($input['full_name'] ?? '');
        $email = trim($input['email'] ?? '');
        $company = trim($input['company'] ?? '');
        $status = self::getStatusCode($input['status'] ?? 'Active');
        $slug = url_title($fullName, '-', true) . '-' . substr(md5($email), 0, 6);
        $now = date('Y-m-d H:i:s');

        $record = [
            'attr_full_name'       => $fullName,
            'attr_contact_val'     => $email,
            'attr_profile_summary' => $company ?: 'Investor.id Member',
            'attr_status_code'     => $status,
            'attr_modified_at'     => $now,
        ];

        if (!empty($id)) {
            // Update client_account_entity
            $this->db->table('client_account_entity')
                ->where('ref_entity_client_id', $id)
                ->update($record);

            // Sync to client_account_backup
            $backupExists = $this->db->table('client_account_backup')
                ->where('ref_entity_client_id', $id)
                ->countAllResults();

            if ($backupExists > 0) {
                $this->db->table('client_account_backup')
                    ->where('ref_entity_client_id', $id)
                    ->update($record);
            } else {
                $record['ref_entity_client_id'] = $id;
                $record['attr_slug_token'] = $slug;
                $record['uniq_id'] = md5($email . time());
                $record['attr_registration_at'] = $now;
                $this->db->table('client_account_backup')->insert($record);
            }

            return $id;
        } else {
            // Insert client_account_entity
            $password = !empty($input['password']) ? trim($input['password']) : 'investor123';
            $record['uniq_id'] = md5($email . time());
            $record['attr_slug_token'] = $slug;
            $record['attr_credential_hash'] = md5($password);
            $record['attr_registration_at'] = $now;
            $record['attr_last_access_at'] = $now;

            $this->db->table('client_account_entity')->insert($record);
            $newId = $this->db->insertID();

            // Sync to client_account_backup with same PK
            $record['ref_entity_client_id'] = $newId;
            $this->db->table('client_account_backup')->insert($record);

            return $newId;
        }
    }

    /**
     * Delete member from client_account_entity and client_account_backup
     */
    public function deleteMember(int $id): bool
    {
        $this->db->table('client_favorite_entity_nano')->where('ref_entity_client_id', $id)->delete();
        $this->db->table('client_account_backup')->where('ref_entity_client_id', $id)->delete();
        return $this->db->table('client_account_entity')->where('ref_entity_client_id', $id)->delete();
    }
}

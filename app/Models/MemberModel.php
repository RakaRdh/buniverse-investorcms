<?php

namespace App\Models;

use CodeIgniter\Model;

class MemberModel extends Model
{
    protected $table = 'Member';
    protected $primaryKey = 'memberID';
    protected $allowedFields = [
        'password',
        'uniq',
        'email',
        'fullname',
        'slug',
        'signupdate',
        'lastlogin',
        'status',
        'avatar',
        'bio',
        'lastupdate',
    ];
    protected $useTimestamps = false;

    /**
     * Map status internal enum to human-friendly labels
     */
    public static function getStatusLabel(string $code): string
    {
        $code = strtolower(trim($code));
        return match ($code) {
            'active', 'status_1'               => 'Active',
            'inactive', 'pending', 'status_2'   => 'Pending',
            'banned', 'suspended', 'status_3'   => 'Suspended',
            default                             => 'Active',
        };
    }

    public static function getStatusCode(string $label): string
    {
        $label = strtolower(trim($label));
        return match ($label) {
            'pending', 'inactive' => 'inactive',
            'suspended', 'banned' => 'banned',
            default               => 'active',
        };
    }

    /**
     * Get paginated member list from member table
     */
    public function getMembers(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $builder = $this->db->table('Member');

        if (!empty($filters['q'])) {
            $q = trim($filters['q']);
            $builder->groupStart()
                ->like('fullname', $q)
                ->orLike('email', $q)
                ->orLike('bio', $q)
                ->orLike('slug', $q)
                ->groupEnd();
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $code = self::getStatusCode($filters['status']);
            $builder->where('status', $code);
        }

        $total = $builder->countAllResults(false);
        $offset = ($page - 1) * $perPage;

        $builder->orderBy('memberID', 'DESC');
        $rows = $builder->limit($perPage, $offset)->get()->getResultArray();

        // Transform into clean presentation array
        $data = array_map(function ($row) {
            $clientId = (int)$row['memberID'];
            
            // Count favorites / read articles if linked
            $favCount = $this->db->table('MemberFavArticle')
                ->where('memberID', $clientId)
                ->countAllResults();

            return [
                'id'              => $clientId,
                'full_name'       => $row['fullname'] ?? 'Member',
                'email'           => $row['email'] ?? '-',
                'slug'            => $row['slug'] ?? '',
                'company'         => $row['bio'] ?? 'Investor.id Member',
                'status_code'     => $row['status'] ?? 'active',
                'status'          => self::getStatusLabel($row['status'] ?? 'active'),
                'avatar'          => $row['avatar'] ?: null,
                'favorites_count' => $favCount,
                'last_login_at'   => $row['lastlogin'],
                'created_at'      => $row['signupdate'] ?? $row['lastupdate'],
                'modified_at'     => $row['lastupdate'],
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
     * Get member stats from member table
     */
    public function getMemberStats(): array
    {
        $total = $this->db->table('Member')->countAllResults();
        $active = $this->db->table('Member')->where('status', 'active')->countAllResults();
        $pending = $this->db->table('Member')->where('status', 'inactive')->countAllResults();
        $suspended = $this->db->table('Member')->where('status', 'banned')->countAllResults();

        return [
            'total'     => $total,
            'active'    => $active,
            'pending'   => $pending,
            'suspended' => $suspended,
        ];
    }

    /**
     * Save member to member AND sync to memberbackup
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
            'fullname'   => $fullName,
            'email'      => $email,
            'bio'        => $company ?: 'Investor.id Member',
            'status'     => $status,
            'lastupdate' => $now,
        ];

        if (!empty($id)) {
            // Update member
            $this->db->table('Member')
                ->where('memberID', $id)
                ->update($record);

            // Sync to memberbackup
            $backupExists = $this->db->table('MemberBackup')
                ->where('memberID', $id)
                ->countAllResults();

            if ($backupExists > 0) {
                $this->db->table('MemberBackup')
                    ->where('memberID', $id)
                    ->update($record);
            } else {
                $record['memberID'] = $id;
                $record['slug'] = $slug;
                $record['uniq'] = md5($email . time());
                $record['signupdate'] = $now;
                $this->db->table('MemberBackup')->insert($record);
            }

            return $id;
        } else {
            // Insert member
            $password = !empty($input['password']) ? trim($input['password']) : 'investor123';
            $record['uniq'] = md5($email . time());
            $record['slug'] = $slug;
            $record['password'] = md5($password);
            $record['signupdate'] = $now;
            $record['lastlogin'] = $now;

            $this->db->table('Member')->insert($record);
            $newId = $this->db->insertID();

            // Sync to memberbackup with same PK
            $record['memberID'] = $newId;
            $this->db->table('MemberBackup')->insert($record);

            return $newId;
        }
    }

    /**
     * Delete member from member and memberbackup
     */
    public function deleteMember(int $id): bool
    {
        $this->db->table('MemberFavArticle')->where('memberID', $id)->delete();
        $this->db->table('MemberBackup')->where('memberID', $id)->delete();
        return $this->db->table('Member')->where('memberID', $id)->delete();
    }
}

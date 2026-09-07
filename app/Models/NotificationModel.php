<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table = 'notif_message';
    protected $primaryKey = 'ref_push_id';
    protected $allowedFields = [
        'posted_by',
        'push_date',
        'push_type',
        'push_url',
        'target_direction',
        'push_slug',
        'push_title',
        'push_body',
        'push_image',
        'push_message_ref',
        'status_code',
        'dump_start_at',
        'dump_end_at',
        'complete_sent_at',
        'ref_entity_nano_id',
        'category_name'
    ];
    protected $useTimestamps = false;

    /**
     * JOIN Query: Push notification history with author name
     */
    public function getNotifHistory(): array
    {
        return $this->db->table('notif_message nm')
            ->select('
                nm.ref_push_id AS id,
                nm.push_title AS content,
                nm.push_date AS push_date,
                p.attr_identifier AS post_by,
                p.attr_display_name AS author_name,
                nm.status_code AS status
            ')
            ->join('sys_principal_entity p', 'p.ref_entity_principal_id = nm.posted_by', 'left')
            ->orderBy('nm.push_date', 'DESC')
            ->get()->getResultArray();
    }
}

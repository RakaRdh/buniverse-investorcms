<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table = 'pushnotif';
    protected $primaryKey = 'idPush';
    protected $allowedFields = [
        'postBy',
        'pushDate',
        'pushType',
        'pushUrl',
        'urlTarget',
        'pushSlug',
        'pushTitle',
        'pushBody',
        'pushImage',
        'fcm_message_id',
        'status',
        'dumpStart',
        'dumpEnd',
        'completeSent',
        'articleID',
        'categoryName'
    ];
    protected $useTimestamps = false;

    /**
     * JOIN Query: Push notification history with author name
     */
    public function getNotifHistory(): array
    {
        return $this->db->table('pushnotif nm')
            ->select('
                nm.idPush AS id,
                nm.pushTitle AS content,
                nm.pushDate AS push_date,
                p.username AS post_by,
                p.name AS author_name,
                nm.status AS status
            ')
            ->join('useradministrator p', 'p.userID = nm.postBy', 'left')
            ->orderBy('nm.pushDate', 'DESC')
            ->get()->getResultArray();
    }
}

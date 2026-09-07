<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table = 'entity_epsilon_log';
    protected $primaryKey = 'record_id';
    protected $allowedFields = [
        'ref_entity_epsilon_id',
        'created_at',
        'action_type_a',
        'action_type_b',
        'log_data'
    ];
    protected $useTimestamps = false;

    public function logAction(int $userId, string $actionTypeA, string $actionTypeB, string $details): void
    {
        $this->insert([
            'ref_entity_epsilon_id' => $userId,
            'action_type_a'        => $actionTypeA,
            'action_type_b'        => $actionTypeB,
            'log_data'             => $details,
        ]);
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table = 'editorlog';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'editorId',
        'timestamp',
        'action',
        'doaction',
        'log'
    ];
    protected $useTimestamps = false;

    /**
     * Log user/editor activity
     * Produksi convention: action ('login', 'insert', 'edit'), doaction ('login', 'foto'), log (JSON or empty)
     */
    public function logAction(int $userId, string $actionTypeA, string $actionTypeB, $details = ''): void
    {
        $logVal = is_array($details) || is_object($details) ? json_encode($details) : (string)$details;

        $this->insert([
            'editorId'  => $userId,
            'timestamp' => date('Y-m-d H:i:s'),
            'action'    => strtolower($actionTypeA),
            'doaction'  => strtolower($actionTypeB),
            'log'       => $logVal,
        ]);
    }
}

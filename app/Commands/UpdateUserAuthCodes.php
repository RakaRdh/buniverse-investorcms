<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class UpdateUserAuthCodes extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'user:update-auth-codes';
    protected $description = 'Populate authCode and authExpired into useradministrator table';

    public function run(array $params)
    {
        $db = Database::connect();
        $users = $db->table('UserAdministrator')->get()->getResultArray();
        $updated = 0;

        foreach ($users as $u) {
            if (empty($u['authCode'])) {
                $db->table('UserAdministrator')->where('userID', $u['userID'])->update([
                    'authCode'    => (string)random_int(100000, 999999),
                    'authExpired' => date('Y-m-d H:i:s', strtotime('+30 days')),
                ]);
                $updated++;
            }
        }

        CLI::write("Updated {$updated} users with authCode and authExpired!", 'green');
    }
}

<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleGuard implements FilterInterface
{
    /**
     * Verify that current logged-in user has at least one of the required roles
     * Example route filter: ['filter' => 'role:master,admin']
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->has('user_id')) {
            return redirect()->to('/login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses ruang kerja.');
        }

        $userRole = strtolower($session->get('user_role') ?? 'staff');
        
        // Allow 'master' full bypass access to all modules
        if ($userRole === 'master') {
            return;
        }

        $userId = (string)($session->get('user_id') ?? '');
        $permFile = WRITEPATH . 'data/json/permissions.json';

        // Check if there are customized page permissions for this user
        if (file_exists($permFile)) {
            $perms = json_decode(file_get_contents($permFile), true) ?: [];
            if (!empty($perms['users'][$userId])) {
                $userPerms = $perms['users'][$userId];
                // Resolve requested route/URI
                $uri = trim(service('request')->getUri()->getPath(), '/');
                $matchedKey = null;

                // Match against known module keys
                $knownKeys = [
                    'dashboard', 'post', 'headline', 'media', 'files',
                    'embed-chart', 'statistic/report', 'statistic',
                    'kanal/master', 'kanal/sub', 'kanal/stats',
                    'apps-version', 'apps-push-notif', 'apps-feature-content',
                    'member', 'user'
                ];

                foreach ($knownKeys as $k) {
                    if ($uri === $k || str_starts_with($uri, $k . '/')) {
                        $matchedKey = $k;
                        break;
                    }
                }

                if ($matchedKey && empty($userPerms[$matchedKey])) {
                    return redirect()->to('/dashboard')->with('error', 'Akses Ditolak: Akun Anda tidak memiliki izin untuk mengakses halaman ' . strtoupper($matchedKey) . '. Silakan hubungi Administrator.');
                }
            }
        }

        if (!empty($arguments)) {
            $allowedRoles = array_map('trim', array_map('strtolower', $arguments));

            if (!in_array($userRole, $allowedRoles, true)) {
                return redirect()->to('/dashboard')->with('error', 'Akses ditolak: Peran (' . esc($session->get('user_role')) . ') tidak memiliki wewenang untuk membuka halaman tersebut.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No after action needed
    }
}

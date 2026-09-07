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

        if (!empty($arguments)) {
            $userRole = strtolower($session->get('user_role') ?? 'staff');
            
            // Allow 'master' full bypass access to all modules
            if ($userRole === 'master') {
                return;
            }

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

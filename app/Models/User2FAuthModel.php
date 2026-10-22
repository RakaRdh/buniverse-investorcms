<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Class User2FAuthModel
 *
 * @category Discontinued / Archival Track Record
 * @description Model representasi tabel terisolasi `user2FAuth` (tanpa prefix `tbl`).
 *
 * PENTING:
 * 1. Modul 2FA AKTIF di Smarty CMS maupun Revamp CMS TIDAK MENGGUNAKAN tabel ini!
 *    Sistem 2FA aktif murni terintegrasi langsung pada tabel `tblUserAdministrator`
 *    dengan kolom (`authCode`, `authExpired`, `nextVerify`, `lastLogin`, `lastLoginIP`).
 * 2. Tabel `user2FAuth` ini adalah tabel yatim (orphan table) dari percobaan historis
 *    (hanya memiliki 1 dummy record di basis data produksi).
 * 3. Model ini dibuat semata-mata sebagai TRACK RECORD dan dokumentasi skema database
 *    agar integritas inventarisasi tabel tetap 100% terlacak.
 */
class User2FAuthModel extends Model
{
    protected $table = 'user2FAuth';
    protected $primaryKey = 'iduser';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $allowedFields = [
        'iduser',
        'code',
        'expired',
        'isUsed',
    ];
    protected $useTimestamps = false;

    /**
     * Helper untuk mengambil builder tabel raw tanpa terdistorsi prefix tbl jika diperlukan
     */
    protected function getRawBuilder()
    {
        // Periksa apakah tabel eksis dengan prefix atau tanpa prefix
        return $this->db->table('user2FAuth');
    }

    /**
     * Mengambil record OTP berdasarkan user ID
     */
    public function getOtpByUserId(int $userId): ?array
    {
        return $this->where('iduser', $userId)->first();
    }

    /**
     * Menyimpan OTP pada tabel orphan ini (replika track record)
     */
    public function storeOtp(int $userId, int $code, int $durationMinutes = 5): bool
    {
        $expired = date('Y-m-d H:i:s', strtotime("+{$durationMinutes} minutes"));

        $existing = $this->where('iduser', $userId)->first();
        if ($existing) {
            return (bool)$this->update($userId, [
                'code'    => $code,
                'expired' => $expired,
                'isUsed'  => '0',
            ]);
        }

        return (bool)$this->insert([
            'iduser'  => $userId,
            'code'    => $code,
            'expired' => $expired,
            'isUsed'  => '0',
        ]);
    }

    /**
     * Menandai OTP telah digunakan
     */
    public function markAsUsed(int $userId): bool
    {
        return (bool)$this->update($userId, ['isUsed' => '1']);
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminModel extends Model
{
    protected $table = 'sys_principal_entity';
    protected $primaryKey = 'ref_entity_principal_id';
    protected $allowedFields = [
        'attr_display_name',
        'attr_identifier',
        'attr_alt_label',
        'attr_slug_token',
        'attr_credential_hash',
        'attr_credential_salt',
        'attr_contact_val',
        'attr_is_active',
        'attr_role_code',
        'attr_last_access_at',
        'attr_last_access_network',
        'attr_credential_expiry_date',
        'attr_auth_numeric_code',
        'attr_auth_expiry_at',
        'attr_next_verification_date'
    ];
    protected $useTimestamps = false;

    /**
     * Generate a cryptographic random salt string
     */
    public static function generateSalt(int $length = 16): string
    {
        return substr(bin2hex(random_bytes((int)ceil($length / 2))), 0, $length);
    }

    /**
     * Reversible cipher: character -> hash
     * Encrypts plain character password using the user's credential salt
     */
    public static function encodePassword(string $plainPassword, string $salt): string
    {
        $key = hash('sha256', $salt, true);
        $iv  = substr(hash('md5', $salt, true), 0, 16);
        $encrypted = openssl_encrypt($plainPassword, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($encrypted);
    }

    /**
     * Reversible decipher: hash -> decode(salt) -> character
     * Decrypts the stored hash using the credential salt back to original character
     */
    public static function decodePassword(string $hash, string $salt): ?string
    {
        if (empty($hash) || empty($salt)) {
            return null;
        }

        $key = hash('sha256', $salt, true);
        $iv  = substr(hash('md5', $salt, true), 0, 16);
        $decoded = base64_decode($hash, true);
        if ($decoded === false) {
            return null;
        }

        $decrypted = openssl_decrypt($decoded, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return ($decrypted !== false) ? $decrypted : null;
    }

    /**
     * Authenticate via username (attr_identifier) or email (attr_contact_val)
     * Supports:
     * 1. Reversible hash with salt: decode(salt) === password
     * 2. Bcrypt hash with auto-upgrade to salt/hash
     * 3. Legacy MD5 with auto-upgrade to salt/hash
     */
    public function authenticate(string $identifier, string $password): ?array
    {
        $user = $this->where('attr_is_active', 1)
            ->groupStart()
                ->where('attr_identifier', $identifier)
                ->orWhere('attr_contact_val', $identifier)
            ->groupEnd()
            ->first();

        if (!$user) {
            return null;
        }

        $authenticated = false;
        $salt = $user['attr_credential_salt'] ?? '';
        $storedHash = $user['attr_credential_hash'] ?? '';

        // Strategy 1: Reversible cipher decode(salt)
        if (!empty($salt)) {
            $decoded = self::decodePassword($storedHash, $salt);
            if ($decoded !== null && hash_equals($decoded, $password)) {
                $authenticated = true;
            }
        }

        // Strategy 2: Bcrypt verify (and upgrade to reversible salt/hash)
        if (!$authenticated && password_verify($password, $storedHash)) {
            $authenticated = true;
            $newSalt = self::generateSalt(16);
            $newHash = self::encodePassword($password, $newSalt);
            $this->update($user['ref_entity_principal_id'], [
                'attr_credential_hash' => $newHash,
                'attr_credential_salt' => $newSalt,
            ]);
            $user['attr_credential_hash'] = $newHash;
            $user['attr_credential_salt'] = $newSalt;
        }

        // Strategy 3: Legacy MD5 (and upgrade to reversible salt/hash)
        if (!$authenticated && strcasecmp(md5($password), $storedHash) === 0) {
            $authenticated = true;
            $newSalt = self::generateSalt(16);
            $newHash = self::encodePassword($password, $newSalt);
            $this->update($user['ref_entity_principal_id'], [
                'attr_credential_hash' => $newHash,
                'attr_credential_salt' => $newSalt,
            ]);
            $user['attr_credential_hash'] = $newHash;
            $user['attr_credential_salt'] = $newSalt;
        }

        if ($authenticated) {
            // Update last access
            $this->update($user['ref_entity_principal_id'], [
                'attr_last_access_at' => date('Y-m-d H:i:s'),
                'attr_last_access_network' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ]);
            return $user;
        }

        return null;
    }

    /**
     * JOIN Query: Admin staff with activity count & last activity log
     */
    public function getAdminsWithActivity(): array
    {
        $admins = $this->db->table('sys_principal_entity p')
            ->select('
                p.ref_entity_principal_id AS id,
                p.attr_display_name AS name,
                p.attr_identifier AS username,
                p.attr_contact_val AS email,
                p.attr_role_code AS role,
                p.attr_is_active AS is_active,
                p.attr_last_access_at AS last_active_at,
                COUNT(DISTINCT el.record_id) AS activity_count,
                MAX(el.created_at) AS last_activity_time
            ')
            ->join('entity_epsilon_log el', 'el.ref_entity_epsilon_id = p.ref_entity_principal_id', 'left')
            ->groupBy('p.ref_entity_principal_id')
            ->orderBy('p.ref_entity_principal_id', 'ASC')
            ->get()->getResultArray();

        foreach ($admins as &$a) {
            $a['status'] = $a['is_active'] ? 'Active' : 'Inactive';
            $a['last_active'] = !empty($a['last_active_at']) ? date('d M Y H:i', strtotime($a['last_active_at'])) : 'Recently';
        }

        return $admins;
    }
}

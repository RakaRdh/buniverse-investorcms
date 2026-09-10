<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminModel extends Model
{
    protected $table = 'useradministrator';
    protected $primaryKey = 'userID';
    protected $allowedFields = [
        'name',
        'username',
        'alias',
        'slug',
        'password',
        'salt',
        'email',
        'active',
        'roleName',
        'lastLogin',
        'lastLoginIP',
        'passwordExpired',
        'authCode',
        'authExpired',
        'nextVerify'
    ];
    protected $useTimestamps = false;

    /**
     * Generate a random salt string using SHA-512 (production convention: 128 characters lowercase hex [0-9a-f])
     */
    public static function generateSalt(int $length = 128): string
    {
        $hash = hash('sha512', random_bytes(64) . microtime(true));
        return ($length === 128) ? $hash : substr($hash, 0, $length);
    }

    /**
     * SHA-512 Hash with salt (production convention: 128 characters lowercase [0-9a-f])
     */
    public static function encodePassword(string $plainPassword, string $salt): string
    {
        return hash('sha512', $salt . $plainPassword);
    }

    /**
     * Verify plain password against SHA-512 salted hash
     */
    public static function verifyPassword(string $plainPassword, string $salt, string $hash): bool
    {
        if (empty($salt) || empty($hash)) {
            return false;
        }
        $check1 = hash('sha512', $salt . $plainPassword);
        $check2 = hash('sha512', $plainPassword . $salt);
        return hash_equals($hash, $check1) || hash_equals($hash, $check2);
    }

    /**
     * Reversible decipher fallback for legacy AES passwords.
     * Note: SHA-512 is a one-way hash (128 hex chars) and mathematically irreversible.
     */
    public static function decodePassword(string $hash, string $salt): ?string
    {
        if (empty($hash) || empty($salt)) {
            return null;
        }

        // Only attempt AES decode for legacy base64 strings (< 64 chars)
        if (strlen($hash) < 64 && (strpos($hash, '=') !== false || strpos($hash, '/') !== false || strpos($hash, '+') !== false)) {
            $decoded = base64_decode($hash, true);
            if ($decoded !== false) {
                // Try SHA-512 derived key
                $hash512 = hash('sha512', $salt, true);
                $key512  = substr($hash512, 0, 32);
                $iv512   = substr($hash512, 32, 16);
                $decrypted = openssl_decrypt($decoded, 'AES-256-CBC', $key512, OPENSSL_RAW_DATA, $iv512);
                if ($decrypted !== false) {
                    return $decrypted;
                }

                // Try SHA-256 derived key
                $key256 = hash('sha256', $salt, true);
                $ivMd5  = substr(hash('md5', $salt, true), 0, 16);
                $decrypted2 = openssl_decrypt($decoded, 'AES-256-CBC', $key256, OPENSSL_RAW_DATA, $ivMd5);
                if ($decrypted2 !== false) {
                    return $decrypted2;
                }
            }
        }

        return null;
    }

    /**
     * Authenticate via username or email
     * Supports:
     * 1. SHA-512 salted hash (128 hex chars, production standard)
     * 2. Legacy AES cipher decode (auto-upgrade to SHA-512)
     * 3. Bcrypt hash with auto-upgrade to SHA-512
     * 4. Legacy MD5 with auto-upgrade to SHA-512
     */
    public function authenticate(string $identifier, string $password): ?array
    {
        $user = $this->where('active', 1)
            ->groupStart()
                ->where('username', $identifier)
                ->orWhere('email', $identifier)
            ->groupEnd()
            ->first();

        if (!$user) {
            return null;
        }

        $authenticated = false;
        $salt = $user['salt'] ?? '';
        $storedHash = $user['password'] ?? '';

        // Strategy 1: SHA-512 salted hash (128 lowercase hex chars)
        if (!empty($salt) && self::verifyPassword($password, $salt, $storedHash)) {
            $authenticated = true;
        }

        // Strategy 2: Legacy AES cipher decode (and auto-upgrade to SHA-512)
        if (!$authenticated && !empty($salt)) {
            $legacyDecoded = self::decodePassword($storedHash, $salt);
            if ($legacyDecoded !== null && hash_equals($legacyDecoded, $password)) {
                $authenticated = true;
                $newHash = self::encodePassword($password, $salt);
                $this->update($user['userID'], ['password' => $newHash]);
                $user['password'] = $newHash;
            }
        }

        // Strategy 3: Bcrypt verify (and upgrade to SHA-512 hash)
        if (!$authenticated && password_verify($password, $storedHash)) {
            $authenticated = true;
            $newSalt = self::generateSalt(128);
            $newHash = self::encodePassword($password, $newSalt);
            $this->update($user['userID'], [
                'password' => $newHash,
                'salt'     => $newSalt,
            ]);
            $user['password'] = $newHash;
            $user['salt']     = $newSalt;
        }

        // Strategy 4: Legacy MD5 (and upgrade to SHA-512 hash)
        if (!$authenticated && strcasecmp(md5($password), $storedHash) === 0) {
            $authenticated = true;
            $newSalt = self::generateSalt(128);
            $newHash = self::encodePassword($password, $newSalt);
            $this->update($user['userID'], [
                'password' => $newHash,
                'salt'     => $newSalt,
            ]);
            $user['password'] = $newHash;
            $user['salt']     = $newSalt;
        }

        if ($authenticated) {
            // Update last access
            $this->update($user['userID'], [
                'lastLogin'   => date('Y-m-d H:i:s'),
                'lastLoginIP' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ]);
            // Provide backward compatibility aliases
            $user['id'] = $user['userID'];
            $user['ref_entity_principal_id'] = $user['userID'];
            $user['name'] = $user['name'] ?? '';
            $user['role'] = $user['roleName'] ?? 'admin';
            return $user;
        }

        return null;
    }

    /**
     * Count total admin staff matching optional filters
     */
    public function countAdminsWithActivity(array $filters = []): int
    {
        $builder = $this->db->table('useradministrator p');

        if (!empty($filters['q'])) {
            $q = trim($filters['q']);
            $builder->groupStart()
                ->like('p.name', $q)
                ->orLike('p.username', $q)
                ->orLike('p.email', $q)
            ->groupEnd();
        }

        if (isset($filters['status']) && $filters['status'] !== 'all' && $filters['status'] !== '') {
            $statusVal = ($filters['status'] === 'Active' || $filters['status'] === '1') ? 1 : 0;
            $builder->where('p.active', $statusVal);
        }

        if (!empty($filters['role']) && $filters['role'] !== 'all') {
            $builder->where('LOWER(p.roleName)', strtolower($filters['role']));
        }

        return $builder->countAllResults();
    }

    /**
     * JOIN Query: Admin staff with activity count & last activity log (with optional filters & pagination)
     */
    public function getAdminsWithActivity(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        $builder = $this->db->table('useradministrator p')
            ->select('
                p.userID AS id,
                p.name AS name,
                p.username AS username,
                p.email AS email,
                p.roleName AS role,
                p.active AS is_active,
                p.lastLogin AS last_active_at,
                COUNT(DISTINCT el.id) AS activity_count,
                MAX(el.timestamp) AS last_activity_time
            ')
            ->join('editorlog el', 'el.editorId = p.userID', 'left');

        if (!empty($filters['q'])) {
            $q = trim($filters['q']);
            $builder->groupStart()
                ->like('p.name', $q)
                ->orLike('p.username', $q)
                ->orLike('p.email', $q)
            ->groupEnd();
        }

        if (isset($filters['status']) && $filters['status'] !== 'all' && $filters['status'] !== '') {
            $statusVal = ($filters['status'] === 'Active' || $filters['status'] === '1') ? 1 : 0;
            $builder->where('p.active', $statusVal);
        }

        if (!empty($filters['role']) && $filters['role'] !== 'all') {
            $builder->where('LOWER(p.roleName)', strtolower($filters['role']));
        }

        $builder->groupBy('p.userID')
            ->orderBy('p.userID', 'ASC');

        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }

        $admins = $builder->get()->getResultArray();

        foreach ($admins as &$a) {
            $a['status'] = $a['is_active'] ? 'Active' : 'Inactive';
            $a['last_active'] = !empty($a['last_active_at']) ? date('d M Y H:i', strtotime($a['last_active_at'])) : 'Recently';
        }

        return $admins;
    }
}

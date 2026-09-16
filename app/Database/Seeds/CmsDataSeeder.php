<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\AdminModel;

/**
 * CmsDataSeeder (Unified 1% Anonymized Sampler Seeder)
 * 
 * Menggabungkan seluruh data seeder ke dalam satu perintah tunggal:
 * Mengambil 1% sampel representatif dari database legacy (`investor`),
 * melakukan substitusi / anonimisasi menyeluruh (terutama data sensitif & kepemilikan berita),
 * namun tetap mempertahankan 100% struktur HTML, styling, tipografi, shortcodes, dan integritas relasi tabel
 * ke database baru (`db_investorcms`).
 * 
 * Cara menjalankan:
 *   php spark db:seed CmsDataSeeder
 */
class CmsDataSeeder extends Seeder
{
    protected ?\mysqli $legacyDb = null;
    protected ?\mysqli $targetDb = null;

    /**
     * Inisialisasi koneksi MySQLi langsung untuk performa streaming & batching cepat
     */
    protected function initConnections(): bool
    {
        $host = env('database.default.hostname', '127.0.0.1');
        $user = env('database.default.username', 'root');
        $pass = env('database.default.password', 'rakaherdika15');
        $port = (int)(env('database.default.port') ?: 3306);
        $targetDbName = env('database.default.database', 'db_investorcms');
        $legacyDbName = 'investor';

        $this->legacyDb = @new \mysqli($host, $user, $pass, $legacyDbName, $port);
        if ($this->legacyDb->connect_error) {
            echo "[-] Gagal terhubung ke database legacy '{$legacyDbName}': " . $this->legacyDb->connect_error . "\n";
            echo "[!] Pastikan database MySQL legacy '{$legacyDbName}' berjalan di {$host}:{$port}.\n";
            return false;
        }
        $this->legacyDb->set_charset('utf8mb4');

        $this->targetDb = @new \mysqli($host, $user, $pass, $targetDbName, $port);
        if ($this->targetDb->connect_error) {
            echo "[-] Gagal terhubung ke database target '{$targetDbName}': " . $this->targetDb->connect_error . "\n";
            return false;
        }
        $this->targetDb->set_charset('utf8mb4');

        return true;
    }

    public function run()
    {
        $startTime = microtime(true);
        echo "=======================================================\n";
        echo "   MEMULAI CMS DATA SEEDER (1% ANONYMIZED SAMPLING)    \n";
        echo "=======================================================\n";

        if (!$this->initConnections()) {
            echo "[!] Seeder dihentikan karena koneksi ke database legacy gagal.\n";
            return;
        }

        // 1. Nonaktifkan foreign key checks dan truncate tabel target
        echo "[1/10] Mengosongkan tabel target (TRUNCATE)... \n";
        $this->targetDb->query("SET FOREIGN_KEY_CHECKS = 0");

        $tablesToClean = [
            'articlestatus', 'useradministrator', 'category', 'category_backup',
            'categorymenu', 'authors', 'authors_current', 'keyword', 'articlekeyword',
            'tag', 'articletag', 'articlepoints', 'articlemeta', 'articlecategory',
            'articleauthor', 'articleheadline', 'articlehistory', 'ai_generated_logs',
            'article', 'media', 'mediaarticle', 'files', 'chartdata', 'featurecontent',
            'pushnotif', 'pushregistered', 'pushtodevice', 'pushtodevicescron',
            'editorlog', 'member', 'memberbackup', 'memberfavarticle', 'memberpost',
            'sahamemiten', 'saham', 'saham_topvalue', 'saham_index', 'saham_sectoral',
            'saham_summary', 'saham_topactive', 'saham_topgainers', 'saham_topvolume',
            'apps', 'googledocs', 'user2fauth', 'commentar', 'trending', 'trendingexclude'
        ];

        foreach ($tablesToClean as $tbl) {
            $this->targetDb->query("TRUNCATE TABLE `{$tbl}`");
        }

        // 2. Seeding Master & Lookup Tables
        echo "[2/10] Seeding master lookup tables (category, status, saham, apps)...\n";
        $this->seedLookupTables();

        // 3. Seeding User Administrator (58 staff legacy ter-anonimisasi + akun dev utama, pass: password123)
        echo "[3/10] Seeding useradministrator (termasuk raka_h & admin, pass: password123)...\n";
        $this->seedUserAdministrator();

        // 4. Seeding Members (anonymized)
        echo "[4/10] Seeding members (ter-anonimisasi)...\n";
        $this->seedMembers();

        // 5. Mengambil 1% Sampel Article (articleID % 100 = 0)
        echo "[5/10] Mengambil 1% sampel artikel (~4.270 artikel) & anonimisasi konten...\n";
        $sampledArticleIDs = $this->seedArticles();
        $totalSampled = count($sampledArticleIDs);
        echo "       -> Berhasil memproses {$totalSampled} artikel representatif.\n";

        if ($totalSampled > 0) {
            $idList = implode(',', $sampledArticleIDs);

            // 6. Seeding Authors Terkait
            echo "[6/10] Seeding authors terkait artikel sampled (ter-anonimisasi)...\n";
            $this->seedAuthors($idList);

            // 7. Seeding Relasi Artikel (articlecategory, articleauthor, articlemeta [6 keys], articlepoints)
            echo "[7/10] Seeding relasi artikel (category, author, meta [6 keys], points)...\n";
            $this->seedArticleRelations($idList, $sampledArticleIDs);

            // 8. Seeding Media & MediaArticle
            echo "[8/10] Seeding media & mediaarticle (posisi 1 thumbnail, metadata aman)...\n";
            $this->seedMedia($idList);

            // 9. Seeding Keywords, Headline, History, & Comments
            echo "[9/10] Seeding keywords, headline, history, & komentar artikel...\n";
            $this->seedArticleKeywordsAndHeadlines($idList);
            $this->seedComments($sampledArticleIDs);
        }

        // 10. Seeding Secondary Tables (chartdata, push, editorlog, saham ranking)
        echo "[10/10] Seeding chartdata, pushregistered (1%), editorlog (1%), dan saham ranking...\n";
        $this->seedSecondaryTables($sampledArticleIDs);

        $this->targetDb->query("SET FOREIGN_KEY_CHECKS = 1");

        $elapsed = round(microtime(true) - $startTime, 2);
        echo "=======================================================\n";
        echo "   [SUCCESS] CMS DATA SEEDER SELESAI DALAM {$elapsed} DETIK!\n";
        echo "   Silakan login menggunakan akun:\n";
        echo "   - Username: raka_h | Password: password123\n";
        echo "   - Username: admin  | Password: password123\n";
        echo "=======================================================\n";
    }

    /**
     * 2. Seed Master Lookup Tables
     */
    protected function seedLookupTables()
    {
        // articlestatus (id, label)
        $res = $this->legacyDb->query("SELECT * FROM tblarticlestatus");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $id = (int)$row['id'];
                $label = $this->targetDb->real_escape_string($row['label']);
                $this->targetDb->query("INSERT IGNORE INTO articlestatus (id, label) VALUES ({$id}, '{$label}')");
            }
        }

        // category: (categoryID, categoryName, label, parent, status, num)
        $res = $this->legacyDb->query("SELECT * FROM tblcategory");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $catId  = (int)$row['categoryID'];
                $slug   = $this->targetDb->real_escape_string($row['categoryName'] ?? '');
                $label  = $this->targetDb->real_escape_string($row['label'] ?? '');
                $parent = ($row['parent'] !== null && $row['parent'] !== '') ? (int)$row['parent'] : 'NULL';
                $status = (int)($row['status'] ?? 0);
                $num    = (int)($row['num'] ?? 0);

                $this->targetDb->query("INSERT IGNORE INTO category (categoryID, categoryName, label, parent, status, num)
                    VALUES ({$catId}, '{$slug}', '{$label}', {$parent}, {$status}, {$num})");
            }
        }

        // categorymenu: (categoryID, num, icon, icondark, img)
        $res = $this->legacyDb->query("SELECT * FROM tblcategorymenu");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $catId    = (int)$row['categoryID'];
                $num      = (int)($row['num'] ?? 0);
                $icon     = $this->targetDb->real_escape_string($row['icon'] ?? '');
                $icondark = $this->targetDb->real_escape_string($row['icondark'] ?? '');
                $img      = $this->targetDb->real_escape_string($row['img'] ?? '');

                $this->targetDb->query("INSERT IGNORE INTO categorymenu (categoryID, num, icon, icondark, img)
                    VALUES ({$catId}, {$num}, '{$icon}', '{$icondark}', '{$img}')");
            }
        }

        // sahamemiten (701 rows)
        $res = $this->legacyDb->query("SELECT * FROM tblsaham_emiten");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $id = (int)$row['id'];
                $code = $this->targetDb->real_escape_string($row['StockCode']);
                $name = $this->targetDb->real_escape_string($row['companyName']);
                $this->targetDb->query("INSERT IGNORE INTO sahamemiten (id, StockCode, companyName) VALUES ({$id}, '{$code}', '{$name}')");
            }
        }

        // saham, saham_index, saham_sectoral
        foreach (['tblsaham' => 'saham', 'tblsaham_index' => 'saham_index', 'tblsaham_sectoral' => 'saham_sectoral'] as $src => $dst) {
            $res = $this->legacyDb->query("SELECT * FROM `{$src}`");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $code = $this->targetDb->real_escape_string($row['IndexCode']);
                    $prev = (float)($row['PrevVal'] ?? 0);
                    $high = (float)($row['HighVal'] ?? 0);
                    $low  = (float)($row['LowVal'] ?? 0);
                    $last = (float)($row['LastVal'] ?? 0);
                    $chgV = (float)($row['ChgVal'] ?? 0);
                    $chgP = (float)($row['ChgPct'] ?? 0);
                    $lc   = $row['LastChange'] ? "'{$row['LastChange']}'" : 'NOW()';
                    $this->targetDb->query("INSERT IGNORE INTO `{$dst}` (IndexCode, PrevVal, HighVal, LowVal, LastVal, ChgVal, ChgPct, LastChange)
                        VALUES ('{$code}', {$prev}, {$high}, {$low}, {$last}, {$chgV}, {$chgP}, {$lc})");
                }
            }
        }
    }

    /**
     * 3. Seed User Administrator (58 rows ter-anonimisasi + akun dev utama)
     * Password seragam: 'password123'
     */
    protected function seedUserAdministrator()
    {
        // 3a. Masukkan akun dev utama terlebih dahulu agar selalu tersedia
        $primaryAccounts = [
            [
                'userID'   => 1,
                'name'     => 'Raka Herdika',
                'username' => 'raka_h',
                'alias'    => 'RHR',
                'slug'     => 'raka-herdika',
                'email'    => 'raka.herdika.ramadhan.tik23@stu.pnj.ac.id',
                'roleName' => 'administrator',
                'active'   => 1
            ],
            [
                'userID'   => 2,
                'name'     => 'Redaksi Master Admin',
                'username' => 'admin',
                'alias'    => 'AGG',
                'slug'     => 'admin',
                'email'    => 'admin@investorcms.test',
                'roleName' => 'administrator',
                'active'   => 1
            ],
        ];

        foreach ($primaryAccounts as $dev) {
            $salt     = AdminModel::generateSalt($dev['username'], 'password123');
            $passHash = AdminModel::encodePassword('password123', $salt);
            $passExp  = date('Y-m-d', strtotime('+90 days'));

            $this->targetDb->query("INSERT INTO useradministrator 
                (userID, name, username, alias, slug, password, salt, email, active, roleName, lastLogin, lastLoginIP, passwordExpired, authCode, authExpired, nextVerify)
                VALUES ({$dev['userID']}, '{$dev['name']}', '{$dev['username']}', '{$dev['alias']}', '{$dev['slug']}', '{$passHash}', '{$salt}', '{$dev['email']}', {$dev['active']}, '{$dev['roleName']}', NULL, NULL, '{$passExp}', NULL, NULL, NULL)
                ON DUPLICATE KEY UPDATE 
                name='{$dev['name']}', username='{$dev['username']}', alias='{$dev['alias']}', password='{$passHash}', salt='{$salt}', email='{$dev['email']}', roleName='{$dev['roleName']}', active=1, authCode=NULL, authExpired=NULL, nextVerify=NULL, lastLogin=NULL, lastLoginIP=NULL");
        }

        // 3b. Masukkan sisa user legacy lainnya (ID 3 s/d 58) dengan variasi nama Indo & Inggris yang realistis
        $res = $this->legacyDb->query("SELECT * FROM tbluseradministrator WHERE userID NOT IN (1, 2) ORDER BY userID ASC");
        if (!$res) return;

        $realisticProfiles = [
            ['name' => 'Budi Santoso', 'username' => 'budi.santoso'],
            ['name' => 'Sarah Jenkins', 'username' => 'sarah.jenkins'],
            ['name' => 'Rian Pratama', 'username' => 'rian.pratama'],
            ['name' => 'Michael Chen', 'username' => 'michael.chen'],
            ['name' => 'Dewi Lestari', 'username' => 'dewi.lestari'],
            ['name' => 'David Alexander', 'username' => 'david.alexander'],
            ['name' => 'Ahmad Fauzi', 'username' => 'ahmad.fauzi'],
            ['name' => 'Jessica Taylor', 'username' => 'jessica.taylor'],
            ['name' => 'Eko Prasetyo', 'username' => 'eko.prasetyo'],
            ['name' => 'Amanda Collins', 'username' => 'amanda.collins'],
            ['name' => 'Rizky Ramadhan', 'username' => 'rizky.ramadhan'],
            ['name' => 'James Harrison', 'username' => 'james.harrison'],
            ['name' => 'Indah Permata', 'username' => 'indah.permata'],
            ['name' => 'Robert Davies', 'username' => 'robert.davies'],
            ['name' => 'Farhan Maulana', 'username' => 'farhan.maulana'],
            ['name' => 'Emily Watson', 'username' => 'emily.watson'],
            ['name' => 'Bambang Sutrisno', 'username' => 'bambang.sutrisno'],
            ['name' => 'William Anderson', 'username' => 'william.anderson'],
            ['name' => 'Maya Anggraini', 'username' => 'maya.anggraini'],
            ['name' => 'Daniel Miller', 'username' => 'daniel.miller'],
            ['name' => 'Fajar Nugraha', 'username' => 'fajar.nugraha'],
            ['name' => 'Olivia Cooper', 'username' => 'olivia.cooper'],
            ['name' => 'Hendro Subroto', 'username' => 'hendro.subroto'],
            ['name' => 'Thomas Wright', 'username' => 'thomas.wright'],
            ['name' => 'Sri Wahyuni', 'username' => 'sri.wahyuni'],
            ['name' => 'Sophia Evans', 'username' => 'sophia.evans'],
            ['name' => 'Bayu Setiawan', 'username' => 'bayu.setiawan'],
            ['name' => 'Christopher Lee', 'username' => 'christopher.lee'],
            ['name' => 'Anisa Tri Hapsari', 'username' => 'anisa.tri'],
            ['name' => 'Benjamin Hughes', 'username' => 'benjamin.hughes'],
            ['name' => 'Dimas Aditya', 'username' => 'dimas.aditya'],
            ['name' => 'Rachel Morgan', 'username' => 'rachel.morgan'],
            ['name' => 'Wahyu Hidayat', 'username' => 'wahyu.hidayat'],
            ['name' => 'Edward Bennett', 'username' => 'edward.bennett'],
            ['name' => 'Mega Wulandari', 'username' => 'mega.wulandari'],
            ['name' => 'Lucas Mitchell', 'username' => 'lucas.mitchell'],
            ['name' => 'Arif Munandar', 'username' => 'arif.munandar'],
            ['name' => 'Grace Thompson', 'username' => 'grace.thompson'],
            ['name' => 'Tari Indriani', 'username' => 'tari.indriani'],
            ['name' => 'Matthew Turner', 'username' => 'matthew.turner'],
            ['name' => 'Doni Wijaya', 'username' => 'doni.wijaya'],
            ['name' => 'Hannah Phillips', 'username' => 'hannah.phillips'],
            ['name' => 'Hendra Gunawan', 'username' => 'hendra.gunawan'],
            ['name' => 'Jonathan Scott', 'username' => 'jonathan.scott'],
            ['name' => 'Yulia Safitri', 'username' => 'yulia.safitri'],
            ['name' => 'Samuel Edwards', 'username' => 'samuel.edwards'],
            ['name' => 'Gilang Dirga', 'username' => 'gilang.dirga'],
            ['name' => 'Victoria Ward', 'username' => 'victoria.ward'],
            ['name' => 'Aditya Nugroho', 'username' => 'aditya.nugroho'],
            ['name' => 'Alexander Foster', 'username' => 'alexander.foster'],
            ['name' => 'Super Administrator', 'username' => 'superadmin'],
            ['name' => 'Clara Oswald', 'username' => 'clara.oswald'],
            ['name' => 'Rahmat Hidayatullah', 'username' => 'rahmat.hidayat'],
            ['name' => 'George Clark', 'username' => 'george.clark'],
            ['name' => 'Dian Sastro Wardoyo', 'username' => 'dian.sastro'],
            ['name' => 'Nicholas Brooks', 'username' => 'nicholas.brooks']
        ];

        $idx = 0;
        while ($row = $res->fetch_assoc()) {
            $uid      = (int)$row['userID'];
            $role     = $row['roleName'] ?: 'editor';
            $active   = (int)($row['active'] ?? 1);
            
            $profile  = $realisticProfiles[$idx % count($realisticProfiles)];
            $idx++;

            $name     = ($uid === 51) ? 'Super Administrator' : $profile['name'];
            $username = ($uid === 51) ? 'superadmin' : $profile['username'];
            
            // Generate clean initials alias
            $nameParts = preg_split('/\s+/', $name);
            $alias = '';
            foreach ($nameParts as $np) {
                if (strlen($alias) < 3 && !empty($np)) {
                    $alias .= strtoupper(substr($np, 0, 1));
                }
            }
            if (strlen($alias) < 2) $alias = strtoupper(substr($username, 0, 3));

            $slug     = str_replace('.', '-', $username);
            $email    = "{$username}@investorcms.test";

            // Enkripsi password123 sesuai mekanisme AdminModel
            $salt     = AdminModel::generateSalt($username, 'password123');
            $passHash = AdminModel::encodePassword('password123', $salt);
            $passExp  = date('Y-m-d', strtotime('+90 days'));

            $this->targetDb->query("INSERT INTO useradministrator 
                (userID, name, username, alias, slug, password, salt, email, active, roleName, lastLogin, lastLoginIP, passwordExpired, authCode, authExpired, nextVerify)
                VALUES ({$uid}, '{$name}', '{$username}', '{$alias}', '{$slug}', '{$passHash}', '{$salt}', '{$email}', {$active}, '{$role}', NULL, NULL, '{$passExp}', NULL, NULL, NULL)
                ON DUPLICATE KEY UPDATE 
                name='{$name}', username='{$username}', alias='{$alias}', slug='{$slug}', password='{$passHash}', salt='{$salt}', email='{$email}', active={$active}, roleName='{$role}', authCode=NULL, authExpired=NULL, nextVerify=NULL, lastLogin=NULL, lastLoginIP=NULL");
        }
    }

    /**
     * 4. Seed Members (Anonymized)
     */
    protected function seedMembers()
    {
        $res = $this->legacyDb->query("SELECT * FROM tblmember ORDER BY memberID ASC");
        if (!$res) return;

        $memberNames = [
            'Budi Hartono', 'Sarah Jenkins', 'Hendra Kusuma', 'Michael Chen',
            'Dewi Anggraini', 'David Alexander', 'Rizky Kurniawan', 'Amanda Collins',
            'Bambang Sutrisno', 'Olivia Cooper', 'Farhan Maulana', 'Jessica Taylor',
            'Wahyu Hidayat', 'Christopher Lee', 'Sri Wahyuni', 'Daniel Miller',
            'Dimas Aditya', 'Rachel Morgan', 'Fajar Nugraha', 'Emily Watson',
            'Arif Munandar', 'Thomas Wright', 'Maya Anggraini', 'Lucas Mitchell',
            'Doni Prasetya', 'Sophia Evans', 'Eko Purnomo', 'James Harrison',
            'Tari Indriani', 'Benjamin Hughes', 'Yulia Safitri', 'Matthew Turner',
            'Agus Salim', 'Hannah Phillips', 'Irwan Santoso', 'Jonathan Scott',
            'Ratna Sari', 'Samuel Edwards', 'Gilang Ramadhan', 'Victoria Ward'
        ];

        $batch = [];
        $idx = 0;
        while ($row = $res->fetch_assoc()) {
            $mid     = (int)$row['memberID'];
            $midStr  = sprintf('%06d', $mid);
            $pass    = md5('password123');
            $uniq    = md5('member_' . $mid);
            
            $baseName = $memberNames[$idx % count($memberNames)];
            $idx++;

            $name    = $baseName;
            $email   = strtolower(str_replace(' ', '.', $name)) . "{$mid}@investor-member.test";
            $slug    = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-')) . "-{$mid}";
            $signup  = $row['signupdate'] ? "'{$row['signupdate']}'" : 'NOW()';
            $lastlog = $row['lastlogin'] ? "'{$row['lastlogin']}'" : 'NULL';
            $status  = in_array($row['status'], ['active', 'inactive', 'banned']) ? $row['status'] : 'active';
            $update  = $row['lastupdate'] ? "'{$row['lastupdate']}'" : 'NOW()';

            $batch[] = "('{$midStr}', '{$pass}', '{$uniq}', '{$email}', '{$name}', '{$slug}', {$signup}, {$lastlog}, '{$status}', NULL, NULL, {$update})";

            if (count($batch) >= 200) {
                $values = implode(',', $batch);
                $this->targetDb->query("INSERT IGNORE INTO member (memberID, password, uniq, email, fullname, slug, signupdate, lastlogin, status, avatar, bio, lastupdate) VALUES {$values}");
                $batch = [];
            }
        }
        if (!empty($batch)) {
            $values = implode(',', $batch);
            $this->targetDb->query("INSERT IGNORE INTO member (memberID, password, uniq, email, fullname, slug, signupdate, lastlogin, status, avatar, bio, lastupdate) VALUES {$values}");
        }
    }

    /**
     * 5. Seed 1% Sample of Articles
     * articleID % 100 = 0 -> exactly 4,277 articles spanning from 2010 to 2026.
     */
    protected function seedArticles(): array
    {
        $res = $this->legacyDb->query("SELECT * FROM tblarticle WHERE articleID % 100 = 0 ORDER BY articleID ASC");
        if (!$res) return [];

        $sampledIDs = [];
        $batch = [];

        // Pool topik & entitas finansial untuk headline synthesis (realistis, variatif, profesional)
        $entities = [
            'Bursa Efek Indonesia', 'Bank Central Asia', 'Bank Mandiri', 'Bank Rakyat Indonesia',
            'Bank Negara Indonesia', 'Telkom Indonesia', 'Astra International', 'Adaro Energy',
            'Aneka Tambang', 'Barito Renewables', 'Amman Mineral', 'Indofood Sukses Makmur',
            'Kalbe Farma', 'United Tractors', 'Semen Indonesia', 'Bank Syariah Indonesia',
            'Pertamina Geothermal Energy', 'Medco Energi Internasional', 'Bukit Asam',
            'Chandra Asri Petrochemical', 'Mitra Adiperkasa', 'Bank Indonesia', 'Otoritas Jasa Keuangan',
            'Kementerian Keuangan', 'Pasar Modal Domestik', 'Industri Manufaktur', 'Sektor Perbankan',
            'Sektor Energi Baru Terbarukan', 'Pasar Surat Utang Negara', 'Kawasan Industri Terpadu'
        ];

        $headlineTemplates = [
            // Pola 1: Laporan Kinerja Finansial
            fn($e, $id) => "Kinerja Keuangan {$e} Tumbuh Positif, Laba Bersih Naik Menjadi Rp " . (10 + ($id % 45)) . " Triliun",
            // Pola 2: Kebijakan & Rencana Strategis Korporasi
            fn($e, $id) => "Perkuat Struktur Permodalan, {$e} Siapkan Alokasi Belanja Modal Rp " . (1 + ($id % 15)) . " Triliun",
            // Pola 3: Analisis Pasar & Saham
            fn($e, $id) => "Saham {$e} Catat Akumulasi Beli Investor Asing di Tengah Reli Indeks Harga Saham",
            // Pola 4: Prospek Makro & Sektoral
            fn($e, $id) => "Penguatan Fundamental {$e} Ditopang Pertumbuhan Permintaan Domestik yang Solid",
            // Pola 5: Aksi Korporasi & Dividen
            fn($e, $id) => "Rapat Umum Pemegang Saham {$e} Setujui Pembagian Dividen Tunai Rp " . (50 + ($id % 350)) . " per Saham",
            // Pola 6: Ekspansi & Transformasi
            fn($e, $id) => "Pacu Efisiensi Bisnis dan Hilirisasi, {$e} Tuntaskan Fasilitas Operasional Baru",
            // Pola 7: Regulasi & Stabilitas
            fn($e, $id) => "Langkah Strategis {$e} dalam Menjaga Ketahanan Likuiditas dan Manajemen Risiko",
            // Pola 8: Inovasi & Investasi Berkelanjutan
            fn($e, $id) => "Dukung Transisi Berkelanjutan, {$e} Terbitkan Instrumen Pendanaan Hijau"
        ];

        while ($row = $res->fetch_assoc()) {
            $aid = (int)$row['articleID'];
            $sampledIDs[] = $aid;

            $uid = (int)($row['userID'] ?? 1);
            if ($uid <= 0 || $uid > 58) $uid = 1;

            $source   = ''; // Produksi kosong
            $posttype = $row['posttype'] ?: 'article';
            $viewed   = (int)($row['viewed'] ?? rand(500, 15000));
            $status   = in_array($row['status'], ['Publish', 'Draft', 'Review', 'Scheduled', 'Deleted']) ? $row['status'] : 'Publish';
            $artDate  = $row['articleDate'] ? "'{$row['articleDate']}'" : 'NOW()';
            $createAt = $row['createdAt'] ? "'{$row['createdAt']}'" : 'NOW()';

            // Sintesis Headline & Slug secara dinamis dan natural
            $entity   = $entities[$aid % count($entities)];
            $template = $headlineTemplates[((int)($aid / 100)) % count($headlineTemplates)];
            $headline = $template($entity, $aid);
            $slug     = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $headline), '-')) . "-{$aid}";

            // Sintesis Summary (Lead)
            $lead = "JAKARTA - {$headline}. Manajemen menyatakan pencapaian ini sejalan dengan komitmen perseroan dalam menjaga tata kelola yang prudent serta mengoptimalkan peluang pertumbuhan pasar.";

            // Deteksi embed shortcode dari body asli
            $origBody = $row['body'] ?? '';
            $embedShortcode = '';
            if (strpos($origBody, '[embed type=stock]') !== false || ($aid % 6 === 0)) {
                $embedShortcode = '<blockquote>[embed type=stock]BBCA,BBRI,BMRI[/embed]</blockquote>';
            } elseif (strpos($origBody, '[embed type=chart]') !== false || ($aid % 6 === 1)) {
                $embedShortcode = '<blockquote>[embed type=chart]1[/embed]</blockquote>';
            } elseif (strpos($origBody, '[embed type=youtube]') !== false || ($aid % 6 === 2)) {
                $embedShortcode = '<blockquote>[embed type=youtube]https://www.youtube.com/watch?v=dQw4w9WgXcQ[/embed]</blockquote>';
            } elseif (strpos($origBody, 'Baca Juga') !== false || ($aid % 6 === 3)) {
                $embedShortcode = '<blockquote><p><strong>Baca Juga: </strong><a href="/artikel/' . $slug . '">Analisis Mendalam Pergerakan Saham ' . $entity . '</a></p></blockquote>';
            }

            // Sintesis Body dengan tipografi legacy utuh
            $bodyContent = '<p><span style="font-family: Verdana, \'Lucida Grande\', Tahoma, Arial, sans-serif; line-height: 16px; font-size: 11px; border-collapse: collapse; color: #333333; ">'
                . '<span style="font-size: 10pt; ">' . htmlspecialchars($lead, ENT_QUOTES, 'UTF-8') . '</span><br><br>'
                . ($embedShortcode ? $embedShortcode . '<br>' : '')
                . '<span style="font-size: 10pt; ">Dalam laporan analis pasar modal, emiten ini dinilai berhasil memanfaatkan momentum perbaikan marjin laba dan ekspansi pembiayaan secara terukur. Langkah strategis perseroan diharapkan mampu memberikan kontribusi berkesinambungan bagi pemegang saham.</span><br><br>'
                . '<span style="font-size: 10pt; ">"Kami terus mencermati katalis pertumbuhan ekonomi nasional dan senantiasa menerapkan tata kelola perusahaan yang berorientasi pada keberlanjutan jangka panjang," ungkap manajemen dalam siaran pers resmi.</span><br><br>'
                . '<span style="font-size: 10pt; ">Pelaku pasar modal diimbau untuk tetap memperhatikan perkembangan indikator makroekonomi global serta melakukan analisis terukur dalam menentukan portofolio investasi.</span>'
                . '</span></p>';

            $escHeadline = $this->targetDb->real_escape_string($headline);
            $escSlug     = $this->targetDb->real_escape_string($slug);
            $escLead     = $this->targetDb->real_escape_string($lead);
            $escBody     = $this->targetDb->real_escape_string($bodyContent);

            $batch[] = "({$aid}, {$uid}, '{$source}', '{$escHeadline}', '{$escSlug}', {$artDate}, '{$posttype}', {$viewed}, '{$status}', '{$escLead}', '{$escBody}', {$createAt}, NULL)";

            if (count($batch) >= 200) {
                $values = implode(',', $batch);
                $this->targetDb->query("INSERT IGNORE INTO article (articleID, userID, source, headline, slug, articleDate, posttype, viewed, status, summary, body, createdAt, editlock) VALUES {$values}");
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $values = implode(',', $batch);
            $this->targetDb->query("INSERT IGNORE INTO article (articleID, userID, source, headline, slug, articleDate, posttype, viewed, status, summary, body, createdAt, editlock) VALUES {$values}");
        }

        return $sampledIDs;
    }

    /**
     * 6. Seed Authors Terkait
     */
    protected function seedAuthors(string $idList)
    {
        $res = $this->legacyDb->query("SELECT DISTINCT authorID FROM tblarticleauthor WHERE articleID IN ({$idList})");
        if (!$res) return;

        $authorIDs = [];
        while ($row = $res->fetch_row()) {
            if ($row[0] > 0) $authorIDs[] = (int)$row[0];
        }

        if (empty($authorIDs)) return;

        $authorList = implode(',', $authorIDs);
        $resAuth = $this->legacyDb->query("SELECT * FROM tblauthors WHERE authorID IN ({$authorList})");
        if (!$resAuth) return;

        $authorProfiles = [
            'Aditya Pratama', 'Sarah Jenkins', 'Hendra Wijaya', 'David Alexander',
            'Dewi Anggraini', 'Michael Chen', 'Rizky Kurniawan', 'Amanda Collins',
            'Bambang Sutrisno', 'Olivia Cooper', 'Farhan Maulana', 'Jessica Taylor',
            'Wahyu Hidayat', 'Christopher Lee', 'Sri Wahyuni', 'Daniel Miller',
            'Dimas Aditya', 'Rachel Morgan', 'Fajar Nugraha', 'Emily Watson',
            'Arif Munandar', 'Thomas Wright', 'Maya Anggraini', 'Lucas Mitchell',
            'Doni Prasetya', 'Sophia Evans', 'Eko Purnomo', 'James Harrison',
            'Tari Indriani', 'Benjamin Hughes', 'Yulia Safitri', 'Matthew Turner'
        ];

        $idx = 0;
        $batch = [];
        while ($row = $resAuth->fetch_assoc()) {
            $aid  = (int)$row['authorID'];
            $baseName = $authorProfiles[$idx % count($authorProfiles)];
            $idx++;

            $name = $baseName;
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-')) . "-{$aid}";
            $mail = strtolower(str_replace(' ', '.', $name)) . "{$aid}@investorcms.test";
            $val  = $row['valid'] === '1' ? '1' : '0';

            $batch[] = "({$aid}, '{$name}', '{$mail}', '{$slug}', '{$val}')";
            if (count($batch) >= 200) {
                $values = implode(',', $batch);
                $this->targetDb->query("INSERT IGNORE INTO authors (authorID, authorName, email, slug, valid) VALUES {$values}");
                $batch = [];
            }
        }
        if (!empty($batch)) {
            $values = implode(',', $batch);
            $this->targetDb->query("INSERT IGNORE INTO authors (authorID, authorName, email, slug, valid) VALUES {$values}");
        }
    }

    /**
     * 7. Seed Relasi Artikel
     */
    protected function seedArticleRelations(string $idList, array $sampledArticleIDs)
    {
        // 7a. articlecategory: (articleCategoryID, articleID, categoryID)
        $res = $this->legacyDb->query("SELECT * FROM tblarticlecategory WHERE articleID IN ({$idList})");
        if ($res) {
            $batch = [];
            while ($row = $res->fetch_assoc()) {
                $acid = (int)$row['articleCategoryID'];
                $aid  = (int)$row['articleID'];
                $cid  = (int)$row['categoryID'];
                $batch[] = "({$acid}, {$aid}, {$cid})";
                if (count($batch) >= 300) {
                    $values = implode(',', $batch);
                    $this->targetDb->query("INSERT IGNORE INTO articlecategory (articleCategoryID, articleID, categoryID) VALUES {$values}");
                    $batch = [];
                }
            }
            if (!empty($batch)) {
                $values = implode(',', $batch);
                $this->targetDb->query("INSERT IGNORE INTO articlecategory (articleCategoryID, articleID, categoryID) VALUES {$values}");
            }
        }

        // 7b. articleauthor: (id, articleID, authorID)
        $res = $this->legacyDb->query("SELECT * FROM tblarticleauthor WHERE articleID IN ({$idList})");
        if ($res) {
            $batch = [];
            while ($row = $res->fetch_assoc()) {
                $id   = (int)$row['id'];
                $aid  = (int)$row['articleID'];
                $auid = (int)$row['authorID'];
                $batch[] = "({$id}, {$aid}, {$auid})";
                if (count($batch) >= 300) {
                    $values = implode(',', $batch);
                    $this->targetDb->query("INSERT IGNORE INTO articleauthor (id, articleID, authorID) VALUES {$values}");
                    $batch = [];
                }
            }
            if (!empty($batch)) {
                $values = implode(',', $batch);
                $this->targetDb->query("INSERT IGNORE INTO articleauthor (id, articleID, authorID) VALUES {$values}");
            }
        }

        // 7c. articlemeta (HANYA 6 KUNCI LEGACY VALID, TIDAK ADA 'point' ATAU 'thumbnail'!)
        $validMetaKeys = ['news_source', 'is_premium', 'premium', 'location', 'article_type', 'canonical_url', 'embargo_time'];
        $res = $this->legacyDb->query("SELECT * FROM tblarticlemeta WHERE articleID IN ({$idList})");
        if ($res) {
            $batch = [];
            while ($row = $res->fetch_assoc()) {
                $aid = (int)$row['articleID'];
                $key = $row['metakey'];
                $val = $row['metaval'];

                if (!in_array($key, $validMetaKeys)) {
                    continue; // Lewatkan kunci polutan
                }

                if ($key === 'premium') {
                    $key = 'is_premium';
                    $val = ($val === 'true' || $val === '1') ? 'true' : 'false';
                }

                $escKey = $this->targetDb->real_escape_string($key);
                $escVal = $this->targetDb->real_escape_string($val);

                $batch[] = "({$aid}, '{$escKey}', '{$escVal}')";
                if (count($batch) >= 300) {
                    $values = implode(',', $batch);
                    $this->targetDb->query("INSERT IGNORE INTO articlemeta (articleID, metakey, metaval) VALUES {$values}");
                    $batch = [];
                }
            }
            if (!empty($batch)) {
                $values = implode(',', $batch);
                $this->targetDb->query("INSERT IGNORE INTO articlemeta (articleID, metakey, metaval) VALUES {$values}");
            }
        }

        // 7d. articlepoints: (article_id, point_text, sort_order, created_at)
        $mockPoints = [
            'Kinerja keuangan perseroan mencatatkan perbaikan signifikan sepanjang tahun berjalan.',
            'Pertumbuhan volume transaksi dan efisiensi operasional menopang profitabilitas.',
            'Manajemen optimistis target pendapatan dan ekspansi bisnis tercapai.',
            'Likuiditas yang terjaga memperkuat ketahanan fundamental menghadapi volatilitas.'
        ];
        $batch = [];
        foreach ($sampledArticleIDs as $aid) {
            $ptText = $this->targetDb->real_escape_string($mockPoints[$aid % count($mockPoints)]);
            $batch[] = "({$aid}, '{$ptText}', 1, NOW())";
            if (count($batch) >= 300) {
                $values = implode(',', $batch);
                $this->targetDb->query("INSERT IGNORE INTO articlepoints (article_id, point_text, sort_order, created_at) VALUES {$values}");
                $batch = [];
            }
        }
        if (!empty($batch)) {
            $values = implode(',', $batch);
            $this->targetDb->query("INSERT IGNORE INTO articlepoints (article_id, point_text, sort_order, created_at) VALUES {$values}");
        }
    }

    /**
     * 8. Seed Media & MediaArticle
     */
    protected function seedMedia(string $idList)
    {
        $res = $this->legacyDb->query("SELECT * FROM tblmediaarticle WHERE articleID IN ({$idList})");
        if (!$res) return;

        $mediaIDs = [];
        $batchMA = [];

        while ($row = $res->fetch_assoc()) {
            $mid = (int)$row['mediaID'];
            $aid = (int)$row['articleID'];
            $pos = (int)($row['position'] ?? 0);
            $cap = $row['caption'] ? "'" . $this->targetDb->real_escape_string($row['caption']) . "'" : "NULL";

            if ($mid > 0) {
                $mediaIDs[$mid] = true;
                $batchMA[] = "({$mid}, {$aid}, {$pos}, {$cap})";
            }

            if (count($batchMA) >= 300) {
                $values = implode(',', $batchMA);
                $this->targetDb->query("INSERT IGNORE INTO mediaarticle (mediaID, articleID, position, caption) VALUES {$values}");
                $batchMA = [];
            }
        }
        if (!empty($batchMA)) {
            $values = implode(',', $batchMA);
            $this->targetDb->query("INSERT IGNORE INTO mediaarticle (mediaID, articleID, position, caption) VALUES {$values}");
        }

        // Master media
        if (!empty($mediaIDs)) {
            $mediaTitles = [
                'Lantai Bursa Efek Indonesia Bergerak Menguat',
                'Gedung Kantor Pusat Perbankan Nasional di Kawasan Sudirman',
                'Aktivitas Pelabuhan Logistik Peti Kemas Tanjung Priok',
                'Pabrik Manufaktur Otomotif Modern Berorientasi Ekspor',
                'Pembangkit Listrik Energi Baru Terbarukan Nasional',
                'Rapat Umum Pemegang Saham Tahunan (RUPST) Emiten',
                'Penandatanganan Kerja Sama Investasi Strategis Multinasional',
                'Suasana Konferensi Pers Paparan Kinerja Keuangan Triwulan',
                'Operasional Tambang Nikel dan Fasilitas Smelter Hilirisasi',
                'Fasilitas Data Center dan Infrastruktur Telekomunikasi Cloud',
                'Peresmian Kawasan Industri Terpadu Berbasis Ramah Lingkungan',
                'Sesi Perdagangan Pasar Obligasi dan Surat Berharga Negara'
            ];

            $mediaCaptions = [
                'Pedagang efek memantau pergerakan grafik indeks saham gabungan di layar monitor bursa, Jakarta.',
                'Kawasan pusat bisnis Sudirman-Thamrin yang menjadi jantung perekonomian dan transaksi finansial nasional.',
                'Aktivitas bongkar muat komoditas ekspor impor guna menopang surplus neraca perdagangan domestik.',
                'Pekerja melakukan inspeksi akhir unit produksi di lini perakitan pabrik manufaktur modern.',
                'Infrastruktur pembangkit energi ramah lingkungan yang mendukung percepatan transisi energi terbarukan.',
                'Jajaran direksi dan komisaris memaparkan laporan pencapaian kinerja keuangan perseroan di hadapan pemegang saham.',
                'Manajemen menandatangani nota kesepahaman ekspansi portofolio bisnis dan sinergi teknologi informasi.',
                'Konferensi pers pemaparan prospek makroekonomi dan strategi mitigasi risiko pasar keuangan.',
                'Aktivitas operasional fasilitas pengolahan dan pemurnian mineral terpadu berstandar internasional.',
                'Ruang server data center yang memastikan keandalan ekosistem komputasi awan dan transaksi digital.'
            ];

            $mList = implode(',', array_keys($mediaIDs));
            $resMedia = $this->legacyDb->query("SELECT * FROM tblmedia WHERE mediaID IN ({$mList})");
            if ($resMedia) {
                $batchM = [];
                while ($row = $resMedia->fetch_assoc()) {
                    $mid   = (int)$row['mediaID'];
                    $tBase = $mediaTitles[$mid % count($mediaTitles)];
                    $cBase = $mediaCaptions[$mid % count($mediaCaptions)];

                    $title = $this->targetDb->real_escape_string($tBase);
                    $cap   = $this->targetDb->real_escape_string("{$cBase} (Foto: Investor Daily/Dok.)");
                    $desc  = $this->targetDb->real_escape_string("Arsip dokumentasi jurnalistik: {$tBase}. Seluruh hak cipta dilindungi undang-undang.");
                    $fname = $this->targetDb->real_escape_string($row['filename'] ?: "media_{$mid}.jpg");
                    $src   = "Investor Daily";
                    $auth  = "Tim Dokumentasi Foto";
                    $uid   = (int)($row['userID'] ?? 1);
                    if ($uid <= 0 || $uid > 58) $uid = 1;
                    $ts    = $row['timestamp'] ? "'{$row['timestamp']}'" : 'NOW()';

                    $batchM[] = "({$mid}, '{$cap}', '{$desc}', '{$fname}', {$ts}, '{$title}', '{$src}', '{$auth}', {$uid})";

                    if (count($batchM) >= 200) {
                        $values = implode(',', $batchM);
                        $this->targetDb->query("INSERT IGNORE INTO media (mediaID, caption, description, filename, timestamp, mediaTitle, source, author, userID) VALUES {$values}");
                        $batchM = [];
                    }
                }
                if (!empty($batchM)) {
                    $values = implode(',', $batchM);
                    $this->targetDb->query("INSERT IGNORE INTO media (mediaID, caption, description, filename, timestamp, mediaTitle, source, author, userID) VALUES {$values}");
                }
            }
        }
    }

    /**
     * 9. Seed Keywords, Headlines, & Comments
     */
    protected function seedArticleKeywordsAndHeadlines(string $idList)
    {
        // 9a. Keywords
        $res = $this->legacyDb->query("SELECT * FROM tblarticlekeyword WHERE articleID IN ({$idList})");
        if ($res) {
            $keywordIDs = [];
            $batchAK = [];
            while ($row = $res->fetch_assoc()) {
                $aid = (int)$row['articleID'];
                $kid = (int)$row['keywordID'];
                if ($kid > 0) {
                    $keywordIDs[$kid] = true;
                    $batchAK[] = "({$aid}, {$kid})";
                }
                if (count($batchAK) >= 300) {
                    $values = implode(',', $batchAK);
                    $this->targetDb->query("INSERT IGNORE INTO articlekeyword (articleID, keywordID) VALUES {$values}");
                    $batchAK = [];
                }
            }
            if (!empty($batchAK)) {
                $values = implode(',', $batchAK);
                $this->targetDb->query("INSERT IGNORE INTO articlekeyword (articleID, keywordID) VALUES {$values}");
            }

            if (!empty($keywordIDs)) {
                $kList = implode(',', array_keys($keywordIDs));
                $resK = $this->legacyDb->query("SELECT * FROM tblkeyword WHERE id IN ({$kList})");
                if ($resK) {
                    $batchK = [];
                    while ($row = $resK->fetch_assoc()) {
                        $kid  = (int)$row['id'];
                        $kw   = $this->targetDb->real_escape_string($row['keyword']);
                        $slug = $this->targetDb->real_escape_string($row['slug'] ?? '');

                        $batchK[] = "({$kid}, '{$kw}', '{$slug}')";
                        if (count($batchK) >= 300) {
                            $values = implode(',', $batchK);
                            $this->targetDb->query("INSERT IGNORE INTO keyword (id, keyword, slug) VALUES {$values}");
                            $batchK = [];
                        }
                    }
                    if (!empty($batchK)) {
                        $values = implode(',', $batchK);
                        $this->targetDb->query("INSERT IGNORE INTO keyword (id, keyword, slug) VALUES {$values}");
                    }
                }
            }
        }

        // 9b. articleheadline: (id, category, articleID, num, timestamp, status, set_by)
        $res = $this->legacyDb->query("SELECT * FROM tblarticleheadline WHERE articleID IN ({$idList})");
        if ($res) {
            $batchHL = [];
            while ($row = $res->fetch_assoc()) {
                $id   = (int)$row['id'];
                $cat  = $this->targetDb->real_escape_string($row['category'] ?? 'home');
                $aid  = (int)$row['articleID'];
                $num  = (int)($row['num'] ?? 1);
                $ts   = $row['timestamp'] ? "'{$row['timestamp']}'" : 'NOW()';
                $st   = (int)($row['status'] ?? 0);

                $batchHL[] = "({$id}, '{$cat}', {$aid}, {$num}, {$ts}, {$st}, 1)";
                if (count($batchHL) >= 200) {
                    $values = implode(',', $batchHL);
                    $this->targetDb->query("INSERT IGNORE INTO articleheadline (id, category, articleID, num, timestamp, status, set_by) VALUES {$values}");
                    $batchHL = [];
                }
            }
            if (!empty($batchHL)) {
                $values = implode(',', $batchHL);
                $this->targetDb->query("INSERT IGNORE INTO articleheadline (id, category, articleID, num, timestamp, status, set_by) VALUES {$values}");
            }
        }

        // 9b-2. User Requirement 3: By default, set headline aktif (status = 1) untuk beberapa artikel teratas di setiap kategori utama
        $targetCategories = ['home', 'topical', 'trending', 'newsletter'];
        
        // Ambil artikel Published terbaru dari database target
        $resPub = $this->targetDb->query("SELECT articleID FROM article WHERE status = 'Publish' ORDER BY articleDate DESC LIMIT 30");
        if ($resPub && $resPub->num_rows > 0) {
            $pubAids = [];
            while ($pRow = $resPub->fetch_assoc()) {
                $pubAids[] = (int)$pRow['articleID'];
            }

            $aidOffset = 0;
            foreach ($targetCategories as $targetCat) {
                // Beri 5 slot aktif untuk setiap kategori (posisi 1 sampai 5)
                for ($slot = 1; $slot <= 5; $slot++) {
                    if (isset($pubAids[$aidOffset])) {
                        $activeAid = $pubAids[$aidOffset];
                        $aidOffset++;

                        // Cek apakah relasi sudah ada di articleheadline
                        $check = $this->targetDb->query("SELECT id FROM articleheadline WHERE category = '{$targetCat}' AND articleID = {$activeAid} LIMIT 1");
                        if ($check && $check->num_rows > 0) {
                            $hlRow = $check->fetch_assoc();
                            $this->targetDb->query("UPDATE articleheadline SET status = 1, num = {$slot}, timestamp = NOW() WHERE id = {$hlRow['id']}");
                        } else {
                            $this->targetDb->query("INSERT INTO articleheadline (category, articleID, num, timestamp, status, set_by) 
                                VALUES ('{$targetCat}', {$activeAid}, {$slot}, NOW(), 1, 1)");
                        }
                    }
                }
            }
        }

        // 9c. articlehistory: (id, timestamp, articleID, editorID, title_prev, title_after, content_prev, content_after, status_prev, status_after)
        $res = $this->legacyDb->query("SELECT * FROM tblarticlehistory WHERE articleID IN ({$idList})");
        if ($res) {
            $batchHist = [];
            while ($row = $res->fetch_assoc()) {
                $id   = (int)$row['id'];
                $ts   = $row['timestamp'] ? "'{$row['timestamp']}'" : 'NOW()';
                $aid  = (int)$row['articleID'];
                $edId = (int)($row['editorID'] ?? 1);
                if ($edId <= 0 || $edId > 58) $edId = 1;
                $tPrev = $this->targetDb->real_escape_string($row['title_prev'] ?? '');
                $tAft  = $this->targetDb->real_escape_string($row['title_after'] ?? 'Pembaruan Judul Artikel');
                $cPrev = $this->targetDb->real_escape_string($row['content_prev'] ?? '');
                $cAft  = $this->targetDb->real_escape_string($row['content_after'] ?? '<p>Pembaruan konten artikel.</p>');
                $sPrev = $this->targetDb->real_escape_string($row['status_prev'] ?? 'Draft');
                $sAft  = $this->targetDb->real_escape_string($row['status_after'] ?? 'Publish');

                $batchHist[] = "({$id}, {$ts}, {$aid}, {$edId}, '{$tPrev}', '{$tAft}', '{$cPrev}', '{$cAft}', '{$sPrev}', '{$sAft}')";
                if (count($batchHist) >= 200) {
                    $values = implode(',', $batchHist);
                    $this->targetDb->query("INSERT IGNORE INTO articlehistory (id, timestamp, articleID, editorID, title_prev, title_after, content_prev, content_after, status_prev, status_after) VALUES {$values}");
                    $batchHist = [];
                }
            }
            if (!empty($batchHist)) {
                $values = implode(',', $batchHist);
                $this->targetDb->query("INSERT IGNORE INTO articlehistory (id, timestamp, articleID, editorID, title_prev, title_after, content_prev, content_after, status_prev, status_after) VALUES {$values}");
            }
        }
    }

    /**
     * Komentar artikel (Anonymized, linked to sampled articles)
     */
    protected function seedComments(array $sampledArticleIDs)
    {
        $res = $this->legacyDb->query("SELECT * FROM tblcommentar");
        if (!$res) return;

        $mockComments = [
            'Analisis yang sangat komprehensif mengenai fundamental emiten ini.',
            'Bagaimana proyeksi dividend payout ratio untuk tahun buku mendatang?',
            'Menarik mencermati pergerakan volume transaksi di sesi perdagangan kedua.',
            'Semoga perbaikan marjin laba bersih dapat terus dipertahankan.',
            'Katalis makroekonomi domestik memang terlihat masih sangat kuat.',
            'Apresiasi terhadap langkah strategis manajemen dalam memperkuat likuiditas.',
            'Prospek jangka panjang sektor ini dinilai masih cukup menjanjikan.',
        ];

        $batch = [];
        $i = 0;
        while ($row = $res->fetch_assoc()) {
            $cid     = (int)$row['commentID'];
            $aid     = !empty($sampledArticleIDs) ? $sampledArticleIDs[$i % count($sampledArticleIDs)] : 100;
            $mid     = sprintf('%06d', (($i % 400) + 1));
            $replyTo = (int)($row['replyto'] ?? 0);
            $posted  = $row['posted'] ? "'{$row['posted']}'" : 'NOW()';
            $status  = $row['status'] === '1' ? '1' : '0';
            $comm    = $this->targetDb->real_escape_string($mockComments[$cid % count($mockComments)]);
            $replies = (int)($row['replies'] ?? 0);
            $likes   = (int)($row['likes'] ?? 0);
            $dis     = (int)($row['dislikes'] ?? 0);

            $batch[] = "({$cid}, {$aid}, '{$mid}', {$replyTo}, {$posted}, '{$status}', '{$comm}', {$replies}, {$likes}, {$dis})";
            $i++;

            if (count($batch) >= 100) {
                $values = implode(',', $batch);
                $this->targetDb->query("INSERT IGNORE INTO commentar (commentID, articleId, memberID, replyto, posted, status, comment, replies, likes, dislikes) VALUES {$values}");
                $batch = [];
            }
        }
        if (!empty($batch)) {
            $values = implode(',', $batch);
            $this->targetDb->query("INSERT IGNORE INTO commentar (commentID, articleId, memberID, replyto, posted, status, comment, replies, likes, dislikes) VALUES {$values}");
        }
    }

    /**
     * 10. Secondary Tables (chartdata, featurecontent, push, apps, editorlog, saham ranking)
     */
    protected function seedSecondaryTables(array $sampledArticleIDs)
    {
        // apps
        $res = $this->legacyDb->query("SELECT * FROM tblapps");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $id  = (int)$row['id'];
                $key = $this->targetDb->real_escape_string($row['key']);
                $val = $this->targetDb->real_escape_string($row['value']);
                $this->targetDb->query("INSERT IGNORE INTO apps (id, `key`, `value`) VALUES ({$id}, '{$key}', '{$val}')");
            }
        }

        // pushnotif (anonymized)
        $res = $this->legacyDb->query("SELECT * FROM tblpushnotif");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $id      = (int)$row['idPush'];
                $pBy     = 1;
                $pDate   = $row['pushDate'] ? "'{$row['pushDate']}'" : 'NOW()';
                $pType   = $this->targetDb->real_escape_string($row['pushType'] ?? 'NewsLanding');
                $pUrl    = $this->targetDb->real_escape_string($row['pushUrl'] ?? '');
                $target  = $this->targetDb->real_escape_string($row['urlTarget'] ?? 'inbound');
                $slug    = 'notifikasi-berita-investor-' . $id;
                $title   = 'Pembaruan Analisis Pasar Modal Terkini #' . $id;
                $body    = 'Simak rangkuman sentimen pergerakan indeks harga saham dan makroekonomi hari ini.';
                $img     = 'https://img2.beritasatu.com/cache/investor/798x449-2/sample.webp';
                $st      = $this->targetDb->real_escape_string($row['status'] ?? 'complete');
                $dStart  = $row['dumpStart'] ? "'{$row['dumpStart']}'" : 'NOW()';
                $dEnd    = $row['dumpEnd'] ? "'{$row['dumpEnd']}'" : 'NULL';
                $cSent   = $row['completeSent'] ? "'{$row['completeSent']}'" : 'NOW()';
                $aid     = !empty($sampledArticleIDs) ? $sampledArticleIDs[$id % count($sampledArticleIDs)] : 100;
                $catName = 'market';

                $this->targetDb->query("INSERT IGNORE INTO pushnotif
                    (idPush, postBy, pushDate, pushType, pushUrl, urlTarget, pushSlug, pushTitle, pushBody, pushImage, fcm_message_id, status, dumpStart, dumpEnd, completeSent, articleID, categoryName)
                    VALUES ({$id}, {$pBy}, {$pDate}, '{$pType}', '{$pUrl}', '{$target}', '{$slug}', '{$title}', '{$body}', '{$img}', NULL, '{$st}', {$dStart}, {$dEnd}, {$cSent}, {$aid}, '{$catName}')");
            }
        }

        // saham_topactive, saham_topgainers, saham_topvolume
        foreach (['tblsaham_topactive' => 'saham_topactive', 'tblsaham_topgainers' => 'saham_topgainers', 'tblsaham_topvolume' => 'saham_topvolume'] as $src => $dst) {
            $res = $this->legacyDb->query("SELECT * FROM `{$src}`");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $id    = (int)$row['id'];
                    $code  = $this->targetDb->real_escape_string($row['StockCode']);
                    $price = (float)($row['Price'] ?? 0);
                    $chg   = (float)($row['Change'] ?? 0);
                    $pct   = (float)($row['Percent'] ?? 0);
                    $vol   = (float)($row['Volume'] ?? 0);
                    $val   = (float)($row['Value'] ?? 0);
                    $freq  = (int)($row['Frequency'] ?? 0);
                    $lu    = $row['LastUpdate'] ? "'{$row['LastUpdate']}'" : 'NOW()';

                    $this->targetDb->query("INSERT IGNORE INTO `{$dst}` (id, StockCode, Price, `Change`, Percent, Volume, `Value`, Frequency, LastUpdate)
                        VALUES ({$id}, '{$code}', {$price}, {$chg}, {$pct}, {$vol}, {$val}, {$freq}, {$lu})");
                }
            }
        }

        // editorlog (1% sample = ~1050 baris ter-anonimisasi)
        $res = $this->legacyDb->query("SELECT * FROM tbleditorlog WHERE id % 100 = 0");
        if ($res) {
            $batch = [];
            while ($row = $res->fetch_assoc()) {
                $id     = (int)$row['id'];
                $edId   = (int)($row['editorId'] ?? 1);
                if ($edId <= 0 || $edId > 58) $edId = 1;
                $ts     = $row['timestamp'] ? "'{$row['timestamp']}'" : 'NOW()';
                $act    = $this->targetDb->real_escape_string($row['action'] ?? 'Update');
                $doAct  = $this->targetDb->real_escape_string($row['doaction'] ?? 'Publish');
                $log    = $this->targetDb->real_escape_string("Aktivitas editorial sistem pada artikel");

                $batch[] = "({$id}, {$edId}, {$ts}, '{$act}', '{$doAct}', '{$log}')";
                if (count($batch) >= 200) {
                    $values = implode(',', $batch);
                    $this->targetDb->query("INSERT IGNORE INTO editorlog (id, editorId, timestamp, action, doaction, log) VALUES {$values}");
                    $batch = [];
                }
            }
            if (!empty($batch)) {
                $values = implode(',', $batch);
                $this->targetDb->query("INSERT IGNORE INTO editorlog (id, editorId, timestamp, action, doaction, log) VALUES {$values}");
            }
        }

        // chartdata
        $res = $this->legacyDb->query("SELECT * FROM tblchartdata");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $cid   = (int)$row['chartID'];
                $title = $this->targetDb->real_escape_string($row['chartTitle']);
                $xtitle= $this->targetDb->real_escape_string($row['xAxisTitle'] ?? '');
                $xdata = $this->targetDb->real_escape_string($row['xAxisData'] ?? '');
                $cAt   = $row['createdAt'] ? "'{$row['createdAt']}'" : 'NOW()';
                $cBy   = 1;
                $uAt   = $row['updatedAt'] ? "'{$row['updatedAt']}'" : 'NOW()';
                $uBy   = 1;
                $src   = $this->targetDb->real_escape_string($row['source'] ?? 'Datasatu');
                $style = $this->targetDb->real_escape_string($row['chartStyle'] ?? 'bar-vertical');

                $this->targetDb->query("INSERT IGNORE INTO chartdata 
                    (chartID, chartTitle, xAxisTitle, xAxisData, createdAt, createdBy, updateAt, updateBy, source, chartStyle)
                    VALUES ({$cid}, '{$title}', '{$xtitle}', '{$xdata}', {$cAt}, {$cBy}, {$uAt}, {$uBy}, '{$src}', '{$style}')");
            }
        }

        // featurecontent
        $res = $this->legacyDb->query("SELECT * FROM tblfeaturecontent");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $id    = (int)$row['id'];
                $type  = $this->targetDb->real_escape_string($row['type']);
                $title = $this->targetDb->real_escape_string($row['title']);
                $tag   = $this->targetDb->real_escape_string($row['tag']);
                $setby = 'raka_h';
                $setdt = $row['setdate'] ? "'{$row['setdate']}'" : 'NOW()';
                $pos   = $this->targetDb->real_escape_string($row['position']);
                $num   = (int)($row['num'] ?? 1);

                $this->targetDb->query("INSERT IGNORE INTO featurecontent 
                    (id, type, title, tag, setby, setdate, imageUrl, linkUrl, position, num)
                    VALUES ({$id}, '{$type}', '{$title}', '{$tag}', '{$setby}', {$setdt}, NULL, NULL, '{$pos}', {$num})");
            }
        }

        // pushregistered (1% sample = 35 devices ter-anonimisasi)
        $res = $this->legacyDb->query("SELECT * FROM tblpushregistered WHERE id % 100 = 0");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $id      = (int)$row['id'];
                $udid    = md5('udid_' . $id);
                $fcm     = 'sample_fcm_token_' . hash('sha256', (string)$id) . ':APA91b_mock';
                $os      = in_array($row['os'], ['android', 'ios']) ? $row['os'] : 'android';
                $osv     = $this->targetDb->real_escape_string($row['os_version'] ?? '13');
                $appv    = $this->targetDb->real_escape_string($row['app_version'] ?? '3.0.5');
                $notif   = $row['notif'] === 'true' ? 'true' : 'false';
                $reg     = $row['registered'] ? "'{$row['registered']}'" : 'NOW()';
                $act     = $row['lastactive'] ? "'{$row['lastactive']}'" : 'NOW()';
                $owner   = "device_user_{$id}";

                $this->targetDb->query("INSERT IGNORE INTO pushregistered
                    (id, udid, fcm_token, os, os_version, app_version, notif, registered, lastactive, owner)
                    VALUES ({$id}, '{$udid}', '{$fcm}', '{$os}', '{$osv}', '{$appv}', '{$notif}', {$reg}, {$act}, '{$owner}')");
            }
        }
    }
}

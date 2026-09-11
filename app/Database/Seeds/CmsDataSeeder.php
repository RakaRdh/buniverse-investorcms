<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\DataRepository;
use App\Models\AdminModel;

class CmsDataSeeder extends Seeder
{
    public function run()
    {
        DataRepository::initialize();

        // 0. TRUNCATE FRESH SEMUA TABEL TERKAIT
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $tablesToTruncate = [
            'articlestatus',
            'useradministrator',
            'category',
            'category_backup',
            'categorymenu',
            'authors',
            'authors_current',
            'keyword',
            'articlekeyword',
            'tag',
            'articletag',
            'articlepoints',
            'articlemeta',
            'articlecategory',
            'articleauthor',
            'articleheadline',
            'articlehistory',
            'ai_generated_logs',
            'article',
            'media',
            'mediaarticle',
            'files',
            'chartdata',
            'featurecontent',
            'pushnotif',
            'pushregistered',
            'pushtodevice',
            'pushtodevicescron',
            'editorlog',
            'member',
            'memberbackup',
            'memberfavarticle',
            'memberpost',
            'sahamemiten',
            'saham',
            'saham_topvalue',
            'saham_index',
            'saham_sectoral',
            'apps',
            'googledocs',
            'user2fauth',
            'commentar',
        ];

        foreach ($tablesToTruncate as $tbl) {
            if ($this->db->tableExists($tbl)) {
                $this->db->query("TRUNCATE TABLE `{$tbl}`");
            }
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        // 1. articlestatus (4 Master Status Produksi: Draft, Publish, Hold, Deleted)
        $statuses = [
            ['id' => 1, 'label' => 'Draft'],
            ['id' => 2, 'label' => 'Publish'],
            ['id' => 3, 'label' => 'Hold'],
            ['id' => 4, 'label' => 'Deleted'],
        ];
        $this->db->table('articlestatus')->insertBatch($statuses);

        // 2. useradministrator (15 Editorial Staff Accounts - SHA-512 Encrypted Salt)
        $targetEmail = 'raka.herdika.ramadhan.tik23@stu.pnj.ac.id';
        $defaultStaff = [
            // 1. Administrator (Master - Akses Penuh)
            ['username' => 'raka_h',        'name' => 'Raka Herdika',           'alias' => 'RHR', 'email' => $targetEmail, 'role' => 'administrator', 'password' => 'password123'],
            ['username' => 'admin',         'name' => 'Redaksi Master Admin',   'alias' => 'AGG', 'email' => $targetEmail, 'role' => 'administrator', 'password' => 'password123'],
            // 2. Redpel (Redaktur Pelaksana - Otoritas setara Editor)
            ['username' => 'budi_redpel',   'name' => 'Budi Prakoso',           'alias' => 'BDP', 'email' => $targetEmail, 'role' => 'redpel',        'password' => 'password123'],
            ['username' => 'hendra_redpel', 'name' => 'Hendra Kusuma',          'alias' => 'HNK', 'email' => $targetEmail, 'role' => 'redpel',        'password' => 'password123'],
            // 3. Editor (Editor & Jurnalis Penulis Artikel)
            ['username' => 'dewi_editor',   'name' => 'Dewi Sartika',           'alias' => 'DWS', 'email' => $targetEmail, 'role' => 'editor',        'password' => 'password123'],
            ['username' => 'natasha_k',     'name' => 'Natasha Khairunisa',     'alias' => 'NKH', 'email' => $targetEmail, 'role' => 'editor',        'password' => 'password123'],
            ['username' => 'dimas_a',       'name' => 'Dimas Anggoro',          'alias' => 'DMA', 'email' => $targetEmail, 'role' => 'editor',        'password' => 'password123'],
            ['username' => 'siti_r',        'name' => 'Siti Rahmawati',         'alias' => 'STR', 'email' => $targetEmail, 'role' => 'editor',        'password' => 'password123'],
            ['username' => 'bambang_w',     'name' => 'Bambang Wijaya',         'alias' => 'BMW', 'email' => $targetEmail, 'role' => 'editor',        'password' => 'password123'],
            ['username' => 'fajar_p',       'name' => 'Fajar Pratama',          'alias' => 'FJP', 'email' => $targetEmail, 'role' => 'editor',        'password' => 'password123'],
            ['username' => 'anisa_m',       'name' => 'Anisa Maharani',         'alias' => 'ANM', 'email' => $targetEmail, 'role' => 'editor',        'password' => 'password123'],
            // 4. Riset (Tim Riset & Analis - Akses Terbuka)
            ['username' => 'rizky_k',       'name' => 'Rizky Kurniawan',        'alias' => 'RZK', 'email' => $targetEmail, 'role' => 'riset',         'password' => 'password123'],
            ['username' => 'maya_s',        'name' => 'Maya Safitri',           'alias' => 'MYS', 'email' => $targetEmail, 'role' => 'riset',         'password' => 'password123'],
            // 5. Sekred (Sekretariat Redaksi - Akses Terbuka)
            ['username' => 'sekred',        'name' => 'Agus Triyono',           'alias' => 'AGT', 'email' => $targetEmail, 'role' => 'sekred',        'password' => 'password123'],
            // 6. Support (IT Dev & Support - Akses Terbatas)
            ['username' => 'support',       'name' => 'IT Dev & Support',       'alias' => 'DEV', 'email' => $targetEmail, 'role' => 'support',       'password' => 'password123', 'active' => 1],
            // 7. Suspended / Inactive Users (Menjaga paritas variasi kasus ekstrem active = 0 sesuai DB Sumber)
            ['username' => 'webadmin_old',  'name' => 'Former Web Administrator', 'alias' => 'WAD', 'email' => $targetEmail, 'role' => 'support',       'password' => 'password123', 'active' => 0],
            ['username' => 'alumni_editor', 'name' => 'Alumni Editor Nonaktif',    'alias' => 'ALM', 'email' => $targetEmail, 'role' => 'editor',        'password' => 'password123', 'active' => 0],
        ];

        $userMap = []; // username => userID
        foreach ($defaultStaff as $u) {
            $salt = AdminModel::generateSalt(128);
            $hashedPass = AdminModel::encodePassword($u['password'], $salt);
            $this->db->table('useradministrator')->insert([
                'name'      => $u['name'],
                'username'  => $u['username'],
                'alias'     => $u['alias'],
                'slug'      => url_title($u['name'], '-', true),
                'password'  => $hashedPass,
                'salt'      => $salt,
                'email'     => $u['email'],
                'active'    => $u['active'] ?? 1,
                'roleName'  => $u['role'],
                'nextVerify'=> date('Y-m-d', strtotime('+30 days')),
                'lastLogin' => date('Y-m-d H:i:s'),
            ]);
            $userMap[$u['username']] = $this->db->insertID();
        }

        // 3. category & category_backup (68 Production Categories)
        $prodCategories = [
            ['categoryID' => 1, 'categoryName' => 'market-and-corporate', 'label' => 'Market and Corporate', 'parent' => null, 'status' => 0, 'num' => 1],
            ['categoryID' => 2, 'categoryName' => 'finance', 'label' => 'Finance', 'parent' => null, 'status' => 1, 'num' => 2],
            ['categoryID' => 3, 'categoryName' => 'business', 'label' => 'Business', 'parent' => null, 'status' => 1, 'num' => 3],
            ['categoryID' => 4, 'categoryName' => 'it-and-telecommunication', 'label' => 'IT and Telco', 'parent' => null, 'status' => 0, 'num' => 4],
            ['categoryID' => 5, 'categoryName' => 'international', 'label' => 'International', 'parent' => null, 'status' => 1, 'num' => 6],
            ['categoryID' => 6, 'categoryName' => 'national', 'label' => 'National', 'parent' => null, 'status' => 1, 'num' => 5],
            ['categoryID' => 7, 'categoryName' => 'lifestyle', 'label' => 'Lifestyle', 'parent' => null, 'status' => 1, 'num' => 7],
            ['categoryID' => 8, 'categoryName' => 'corporate-action', 'label' => 'Corporate Action', 'parent' => 12, 'status' => 1, 'num' => 1],
            ['categoryID' => 9, 'categoryName' => 'bond', 'label' => 'Bond', 'parent' => 12, 'status' => 1, 'num' => 4],
            ['categoryID' => 10, 'categoryName' => 'commodities', 'label' => 'Commodities', 'parent' => 12, 'status' => 1, 'num' => 6],
            ['categoryID' => 11, 'categoryName' => 'mutual-fund', 'label' => 'Mutual Fund', 'parent' => 12, 'status' => 1, 'num' => 5],
            ['categoryID' => 12, 'categoryName' => 'market', 'label' => 'Market', 'parent' => null, 'status' => 1, 'num' => 1],
            ['categoryID' => 13, 'categoryName' => 'tips', 'label' => 'Tips', 'parent' => 51, 'status' => 1, 'num' => 3],
            ['categoryID' => 14, 'categoryName' => 'banking', 'label' => 'Banking', 'parent' => 2, 'status' => 1, 'num' => 0],
            ['categoryID' => 15, 'categoryName' => 'insurance', 'label' => 'Insurance', 'parent' => 2, 'status' => 1, 'num' => 0],
            ['categoryID' => 16, 'categoryName' => 'multifinance', 'label' => 'Multifinance', 'parent' => 2, 'status' => 1, 'num' => 0],
            ['categoryID' => 17, 'categoryName' => 'personal-finance', 'label' => 'Personal Finance', 'parent' => 2, 'status' => 0, 'num' => 0],
            ['categoryID' => 18, 'categoryName' => 'fintech', 'label' => 'Fintech', 'parent' => 2, 'status' => 1, 'num' => 0],
            ['categoryID' => 19, 'categoryName' => 'others', 'label' => 'Others', 'parent' => 2, 'status' => 1, 'num' => 0],
            ['categoryID' => 20, 'categoryName' => 'industry-trade', 'label' => 'Industry & Trade', 'parent' => 3, 'status' => 0, 'num' => 0],
            ['categoryID' => 21, 'categoryName' => 'energy', 'label' => 'Energy', 'parent' => 3, 'status' => 1, 'num' => 2],
            ['categoryID' => 22, 'categoryName' => 'infrastructure', 'label' => 'Infrastructure', 'parent' => 3, 'status' => 1, 'num' => 3],
            ['categoryID' => 23, 'categoryName' => 'property', 'label' => 'Property', 'parent' => 3, 'status' => 1, 'num' => 4],
            ['categoryID' => 24, 'categoryName' => 'agribusiness', 'label' => 'Agribusiness', 'parent' => 3, 'status' => 1, 'num' => 6],
            ['categoryID' => 25, 'categoryName' => 'ecommerce', 'label' => 'E-commerce', 'parent' => 3, 'status' => 1, 'num' => 7],
            ['categoryID' => 26, 'categoryName' => 'macroeconomics', 'label' => 'Macroeconomics', 'parent' => 3, 'status' => 0, 'num' => 0],
            ['categoryID' => 27, 'categoryName' => 'information-technology', 'label' => 'IT', 'parent' => 4, 'status' => 0, 'num' => 0],
            ['categoryID' => 28, 'categoryName' => 'telecommunication', 'label' => 'Telecommunication', 'parent' => 4, 'status' => 0, 'num' => 0],
            ['categoryID' => 29, 'categoryName' => 'new-product', 'label' => 'New Product', 'parent' => 4, 'status' => 0, 'num' => 0],
            ['categoryID' => 30, 'categoryName' => 'united-states', 'label' => 'US', 'parent' => 5, 'status' => 0, 'num' => 0],
            ['categoryID' => 31, 'categoryName' => 'european-union', 'label' => 'EU', 'parent' => 5, 'status' => 0, 'num' => 0],
            ['categoryID' => 32, 'categoryName' => 'asia', 'label' => 'Asia', 'parent' => 5, 'status' => 0, 'num' => 0],
            ['categoryID' => 33, 'categoryName' => 'world', 'label' => 'World', 'parent' => 5, 'status' => 0, 'num' => 0],
            ['categoryID' => 34, 'categoryName' => 'politic', 'label' => 'Politic', 'parent' => 6, 'status' => 0, 'num' => 0],
            ['categoryID' => 35, 'categoryName' => 'general', 'label' => 'General', 'parent' => 6, 'status' => 0, 'num' => 0],
            ['categoryID' => 36, 'categoryName' => 'sport', 'label' => 'Sport', 'parent' => 7, 'status' => 1, 'num' => 0],
            ['categoryID' => 37, 'categoryName' => 'art', 'label' => 'Art & Entertainment', 'parent' => 7, 'status' => 1, 'num' => 0],
            ['categoryID' => 38, 'categoryName' => 'opini', 'label' => 'Opini', 'parent' => 51, 'status' => 1, 'num' => 2],
            ['categoryID' => 39, 'categoryName' => 'uncategorized', 'label' => 'Uncategorized', 'parent' => null, 'status' => 0, 'num' => 0],
            ['categoryID' => 40, 'categoryName' => 'editorial', 'label' => 'Editorial', 'parent' => null, 'status' => 1, 'num' => 8],
            ['categoryID' => 42, 'categoryName' => 'health', 'label' => 'Health', 'parent' => 7, 'status' => 1, 'num' => 0],
            ['categoryID' => 43, 'categoryName' => 'bedah-emiten', 'label' => 'Bedah Emiten', 'parent' => 1, 'status' => 0, 'num' => 8],
            ['categoryID' => 44, 'categoryName' => 'rumours', 'label' => 'Rumours', 'parent' => 1, 'status' => 0, 'num' => 1],
            ['categoryID' => 46, 'categoryName' => 'from-the-readers', 'label' => 'From The Readers', 'parent' => 38, 'status' => 0, 'num' => 0],
            ['categoryID' => 47, 'categoryName' => 'stock-analysis', 'label' => 'Stock Analysis', 'parent' => 1, 'status' => 0, 'num' => 3],
            ['categoryID' => 48, 'categoryName' => 'ipo', 'label' => 'IPO', 'parent' => 1, 'status' => 0, 'num' => 9],
            ['categoryID' => 49, 'categoryName' => 'figure', 'label' => 'Figure', 'parent' => null, 'status' => 0, 'num' => 10],
            ['categoryID' => 50, 'categoryName' => 'interview', 'label' => 'Interview', 'parent' => 49, 'status' => 0, 'num' => 0],
            ['categoryID' => 51, 'categoryName' => 'investory', 'label' => 'InveStory', 'parent' => null, 'status' => 1, 'num' => 8],
            ['categoryID' => 52, 'categoryName' => 'community', 'label' => 'Community', 'parent' => 51, 'status' => 0, 'num' => 0],
            ['categoryID' => 53, 'categoryName' => 'literation', 'label' => 'Literation', 'parent' => 51, 'status' => 0, 'num' => 0],
            ['categoryID' => 54, 'categoryName' => 'public-sphere', 'label' => 'Public Sphere', 'parent' => 51, 'status' => 0, 'num' => 0],
            ['categoryID' => 55, 'categoryName' => 'commercial', 'label' => 'Commercial', 'parent' => null, 'status' => 1, 'num' => 12],
            ['categoryID' => 56, 'categoryName' => 'infografis', 'label' => 'Infografis', 'parent' => null, 'status' => 1, 'num' => 0],
            ['categoryID' => 57, 'categoryName' => 'otomotif', 'label' => 'Otomotif', 'parent' => null, 'status' => 0, 'num' => 7],
            ['categoryID' => 58, 'categoryName' => 'bumee', 'label' => 'Bumee', 'parent' => null, 'status' => 0, 'num' => 0],
            ['categoryID' => 59, 'categoryName' => 'gallery', 'label' => 'Gallery', 'parent' => null, 'status' => 1, 'num' => 0],
            ['categoryID' => 60, 'categoryName' => 'stock', 'label' => 'Stock', 'parent' => 12, 'status' => 1, 'num' => 2],
            ['categoryID' => 61, 'categoryName' => 'crypto', 'label' => 'Crypto', 'parent' => 12, 'status' => 1, 'num' => 3],
            ['categoryID' => 62, 'categoryName' => 'trade-industry', 'label' => 'Trade & Industry', 'parent' => 3, 'status' => 1, 'num' => 1],
            ['categoryID' => 63, 'categoryName' => 'telco', 'label' => 'Telco', 'parent' => 3, 'status' => 1, 'num' => 5],
            ['categoryID' => 64, 'categoryName' => 'automotive', 'label' => 'Automotive', 'parent' => 3, 'status' => 1, 'num' => 6],
            ['categoryID' => 65, 'categoryName' => 'umkm', 'label' => 'UMKM', 'parent' => 3, 'status' => 1, 'num' => 7],
            ['categoryID' => 66, 'categoryName' => 'macroeconomy', 'label' => 'Macroeconomy', 'parent' => null, 'status' => 1, 'num' => 4],
            ['categoryID' => 67, 'categoryName' => 'profil', 'label' => 'Profil', 'parent' => 51, 'status' => 1, 'num' => 1],
            ['categoryID' => 68, 'categoryName' => 'multimedia', 'label' => 'Multimedia', 'parent' => null, 'status' => 1, 'num' => 0],
        ];

        $this->db->table('category')->insertBatch($prodCategories);
        $this->db->table('category_backup')->insertBatch($prodCategories);

        // 4. categorymenu (9 Baris Menu Navigasi Ikon Produksi)
        $categoryMenuRows = [
            ['categoryID' => 2,  'num' => 2,  'icon' => 'https://investor.id/img/app-icon/finance.png',       'icondark' => 'https://investor.id/img/app-icon/finance_dark.png',       'img' => 'https://investor.id/img/app-icon/finance.png'],
            ['categoryID' => 3,  'num' => 3,  'icon' => 'https://investor.id/img/app-icon/business.png',      'icondark' => 'https://investor.id/img/app-icon/business_dark.png',      'img' => 'https://investor.id/img/app-icon/business.png'],
            ['categoryID' => 5,  'num' => 6,  'icon' => 'https://investor.id/img/app-icon/international.png', 'icondark' => 'https://investor.id/img/app-icon/international_dark.png', 'img' => 'https://investor.id/img/app-icon/international.png'],
            ['categoryID' => 6,  'num' => 5,  'icon' => 'https://investor.id/img/app-icon/national.png',      'icondark' => 'https://investor.id/img/app-icon/national_dark.png',      'img' => 'https://investor.id/img/app-icon/national.png'],
            ['categoryID' => 7,  'num' => 7,  'icon' => 'https://investor.id/img/app-icon/lifestyle.png',     'icondark' => 'https://investor.id/img/app-icon/lifestyle_dark.png',     'img' => 'https://investor.id/img/app-icon/lifestyle.png'],
            ['categoryID' => 12, 'num' => 1,  'icon' => 'https://investor.id/img/app-icon/market.png',        'icondark' => 'https://investor.id/img/app-icon/market_dark.png',        'img' => 'https://investor.id/img/app-icon/market.png'],
            ['categoryID' => 40, 'num' => 90, 'icon' => 'https://investor.id/img/app-icon/finance.png',       'icondark' => 'https://investor.id/img/app-icon/finance_dark.png',       'img' => 'https://investor.id/img/app-icon/finance.png'],
            ['categoryID' => 51, 'num' => 8,  'icon' => 'https://investor.id/img/app-icon/investory.png',     'icondark' => 'https://investor.id/img/app-icon/investory_dark.png',     'img' => 'https://investor.id/img/app-icon/investory.png'],
            ['categoryID' => 66, 'num' => 4,  'icon' => 'https://investor.id/img/app-icon/macroeconomic.png', 'icondark' => 'https://investor.id/img/app-icon/macroeconomic_dark.png', 'img' => 'https://investor.id/img/app-icon/macroeconomic.png'],
        ];
        $this->db->table('categorymenu')->insertBatch($categoryMenuRows);

        // 5. authors & authors_current (Authors berakhiran *))
        $authorNames = [
            'Raka Herdika*)',
            'Natasha Khairunisa*)',
            'Dimas Anggoro*)',
            'Siti Rahmawati*)',
            'Bambang Wijaya*)',
            'Fajar Pratama*)',
            'Anisa Maharani*)',
            'Dewi Sartika*)',
            'Budi Prakoso*)',
            'Hendra Kusuma*)',
            'Rizky Kurniawan*)',
            'Maya Safitri*)',
            'Agus Triyono*)',
            'Redaksi Investor*)',
            'Riset Mandiri Sekuritas*)',
            'Analis Sucor Asset Management*)',
            'Tim Datawrapper Redaksi*)',
        ];

        $authorMap = [];
        foreach ($authorNames as $authName) {
            $cleanName = trim(rtrim($authName, '*)'));
            $slug = url_title($cleanName, '-', true);
            $this->db->table('authors')->insert([
                'authorName' => $authName,
                'email'      => null,
                'slug'       => $slug,
                'valid'      => '1',
            ]);
            $authId = $this->db->insertID();
            $authorMap[$authName] = $authId;
            $authorMap[$cleanName] = $authId;

            // Mirror into authors_current (slug is NULL per production spec)
            $this->db->table('authors_current')->insert([
                'authorID'   => $authId,
                'authorName' => $authName,
                'slug'       => null,
            ]);
        }

        // 6. 100 RANDOM ARTIKEL BERITA LENGKAP & RELASINYA
        $articlesRaw = ArticleSeedData::getArticles();
        $articleMap = []; // index => articleID
        $keywordMap = []; // keywordName => keywordID

        foreach ($articlesRaw as $idx => $art) {
            $postByUsername = $art['post_by'] ?? 'raka_h';
            $accId = $userMap[$postByUsername] ?? 1;

            $daysAgo = $art['days'] ?? ($idx % 60);
            $hoursAgo = ($idx * 3) % 24;
            $pubDate = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -{$hoursAgo} hours"));
            $createDate = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -{$hoursAgo} hours -30 minutes"));

            // Full production body style with inline CSS and HTML formatting
            $bodyContent = '<p><span style="font-family: Verdana, \'Lucida Grande\', Tahoma, Arial, sans-serif; line-height: 16px; font-size: 11px; border-collapse: collapse; color: #333333; ">'
                . '<span style="font-size: 10pt; ">' . esc($art['lead']) . '</span><br><br>'
                . '<span style="font-size: 10pt; ">' . esc($art['summary']) . ' Dalam laporannya, para analis menyoroti ketahanan makroekonomi domestik serta perbaikan marjin laba bersih korporasi yang menjadi katalis fundamental utama.</span><br><br>'
                . '<span style="font-size: 10pt; ">"Kami melihat prospek akumulasi jangka panjang masih sangat terbuka lebar, didukung arus masuk modal asing dan kebijakan suku bunga acuan yang kondusif," ungkap perwakilan riset pasar modal di Jakarta.</span><br><br>'
                . '<span style="font-size: 10pt; ">Lebih lanjut, pelaku pasar diimbau untuk tetap mencermati faktor risiko geopolitik global serta menerapkan manajemen diversifikasi portofolio secara terukur guna meraih imbal hasil optimal.</span>'
                . '</span></p>';

            $this->db->table('article')->insert([
                'userID'      => $accId,
                'source'      => '', // Empty string in production
                'headline'    => $art['title'],
                'slug'        => url_title($art['title'], '-', true),
                'articleDate' => $pubDate,
                'posttype'    => 'article',
                'viewed'      => (int)($art['views'] ?? rand(500, 15000)),
                'status'      => $art['status'] ?? 'Publish',
                'summary'     => $art['summary'],
                'body'        => $bodyContent,
                'createdAt'   => $createDate,
                'editlock'    => null, // NULL in production
            ]);

            $articleId = $this->db->insertID();
            $articleMap[$idx] = $articleId;

            // Link to category
            $catId = $art['cat_id'] ?? 12;
            $this->db->table('articlecategory')->insert([
                'articleID'  => $articleId,
                'categoryID' => $catId,
            ]);

            // Link to author
            $authorNameKey = $art['author'] ?? 'Raka Herdika*)';
            $authId = $authorMap[$authorNameKey] ?? 1;
            $this->db->table('articleauthor')->insert([
                'articleID' => $articleId,
                'authorID'  => $authId,
            ]);

            // Article Meta
            $metaEntries = [
                'news_source'   => $art['source'] ?? 'Investor Daily',
                'thumbnail'     => $art['thumb'] ?? '/img/investor_placeholder.jpg',
                'is_premium'    => (string)($art['is_premium'] ?? 0),
                'headline_type' => $art['headline_type'] ?? 'none',
                'upper_deck'    => $art['upper_deck'] ?? 'FOKUS PASAR',
                'kanal'         => $art['kanal'] ?? 'Market',
                'sub_kanal'     => $art['sub_kanal'] ?? '',
            ];

            foreach ($metaEntries as $mKey => $mVal) {
                $this->db->table('articlemeta')->insert([
                    'articleID' => $articleId,
                    'metakey'   => $mKey,
                    'metaval'   => $mVal,
                ]);
            }

            // Article Points (articlepoints table)
            $points = $art['points'] ?? [
                'Pertumbuhan kinerja keuangan ditopang efisiensi operasional dan ekspansi pasar.',
                'Target valuasi dan dividen konsisten mencerminkan fundamental yang solid.',
                'Prospek jangka panjang industri didukung kebijakan regulasi yang kondusif.'
            ];
            foreach ($points as $pIdx => $pText) {
                $this->db->table('articlepoints')->insert([
                    'article_id' => $articleId,
                    'point_text' => $pText,
                    'sort_order' => $pIdx + 1,
                    'created_at' => $createDate,
                ]);
                $this->db->table('articlemeta')->insert([
                    'articleID' => $articleId,
                    'metakey'   => 'point',
                    'metaval'   => $pText,
                ]);
            }

            // Keywords (keyword & articlekeyword pivot)
            $kwList = $art['keywords'] ?? ['IHSG', 'Saham', 'Pasar Modal'];
            foreach ($kwList as $kw) {
                $kw = trim($kw);
                if (empty($kw)) continue;

                if (!isset($keywordMap[$kw])) {
                    $slug = url_title($kw, '-', true);
                    $this->db->table('keyword')->insert([
                        'keyword' => $kw,
                        'slug'    => $slug,
                    ]);
                    $keywordMap[$kw] = $this->db->insertID();
                }

                $kwId = $keywordMap[$kw];
                $this->db->table('articlekeyword')->insert([
                    'articleID' => $articleId,
                    'keywordID' => $kwId,
                ]);
            }
        }

        // 7. articleheadline (Active & Inactive Headlines: 'home', 'topical', 'trending', 'newsletter')
        $headlineCategories = ['home', 'topical', 'trending', 'newsletter'];
        for ($i = 0; $i < 20; $i++) {
            if (isset($articleMap[$i])) {
                $catName = $headlineCategories[$i % count($headlineCategories)];
                // Variasi status = 0 untuk 3 headline terakhir guna memastikan parity kasus status = 0 dari DB Sumber
                $hStatus = ($i >= 17) ? 0 : 1;
                $this->db->table('articleheadline')->insert([
                    'category'  => $catName,
                    'articleID' => $articleMap[$i],
                    'num'       => ($i % 5) + 1,
                    'timestamp' => date('Y-m-d H:i:s', strtotime("-{$i} hours")),
                    'status'    => $hStatus,
                ]);
            }
        }

        // 7b. articlehistory (Audit trail revisi naskah berita simulasi 3 minggu operasional)
        $historySamples = [];
        $historyStatuses = [
            ['prev' => 'Draft', 'after' => 'Hold'],
            ['prev' => 'Hold',  'after' => 'Publish'],
            ['prev' => 'Draft', 'after' => 'Publish'],
            ['prev' => 'Publish','after' => 'Publish'], // Edit judul / isi setelah tayang
        ];

        for ($h = 0; $h < 22; $h++) {
            $artIndex = ($h * 3) % count($articleMap);
            $targetArtId = $articleMap[$artIndex] ?? ($h + 1);
            $daysAgo = ($h % 21) + 1;
            $hoursAgo = ($h * 2) % 24;
            $editorId = ($h % 5) + 1; // Raka, admin, Budi, Hendra, Dewi

            $stPair = $historyStatuses[$h % count($historyStatuses)];
            $historySamples[] = [
                'timestamp'    => date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -{$hoursAgo} hours")),
                'articleID'    => $targetArtId,
                'editorID'     => $editorId,
                'title_prev'   => 'Draf Awal Naskah Berita #' . ($artIndex + 1),
                'title_after'  => 'Pembaruan Redaksi Naskah Berita #' . ($artIndex + 1),
                'content_prev' => '<p>Draf naskah awal yang diajukan reporter untuk peninjauan redaksi.</p>',
                'content_after'=> '<p>Naskah telah disunting, diverifikasi kelengkapan data fundamental, dan disetujui untuk tayang.</p>',
                'status_prev'  => $stPair['prev'],
                'status_after' => $stPair['after'],
            ];
        }
        $this->db->table('articlehistory')->insertBatch($historySamples);

        // 8. media & mediaarticle (Media Library: 24 Assets)
        $imgPool = [
            '/img/investor_placeholder.jpg',
            '/img/article2.jpg',
            '/img/article3.jpg',
            '/img/article4.jpg',
            '/img/article5.jpg',
            '/img/article6.jpg',
        ];
        $mediaTitles = [
            'Gedung Bursa Efek Indonesia Jakarta',
            'Aktivitas Kantor Cabang Bank Central Asia',
            'Fasilitas Smelter Tembaga dan Emas Sumbawa',
            'Pembangkit Listrik Tenaga Panas Bumi Lumut Balai',
            'Pelabuhan Petikemas dan Logistik Tanjung Priok',
            'Jaringan Jalan Tol Trans Jawa Ruang Semarang-Solo',
            'Pabrik Perakitan Mobil Elektrifikasi Karawang',
            'Ruang Data Center Hyper-Scale NeutraDC Batam',
            'Distribusi Bahan Pangan dan Unggas Peternakan',
            'Menara Telekomunikasi 5G Fiber Optik',
            'Kawasan Perkantoran SCBD Sudirman Senayan',
            'Fasilitas Rumah Sakit Spesialis Modern',
        ];

        for ($mIdx = 0; $mIdx < 24; $mIdx++) {
            $userKey = array_keys($userMap)[$mIdx % count($userMap)];
            $principalId = $userMap[$userKey] ?? 1;
            $imgFile = $imgPool[$mIdx % count($imgPool)];
            $mTitle = $mediaTitles[$mIdx % count($mediaTitles)] . ' #' . ($mIdx + 1);

            $this->db->table('media')->insert([
                'caption'     => 'Dokumentasi riset redaksi Investor.id terkait ' . $mTitle,
                'description' => 'Foto dokumentasi resolusi tinggi untuk kebutuhan liputan editorial pasar modal.',
                'filename'    => $imgFile,
                'mediaTitle'  => $mTitle,
                'source'      => 'Investor.id / Riset',
                'author'      => $userKey,
                'userID'      => $principalId,
                'timestamp'   => date('Y-m-d H:i:s', strtotime('-' . ($mIdx * 12) . ' hours')),
            ]);
            $mediaId = $this->db->insertID();

            // Link into mediaarticle (0-indexed position, caption NULL per production spec)
            if (isset($articleMap[$mIdx])) {
                $this->db->table('mediaarticle')->insert([
                    'mediaID'   => $mediaId,
                    'articleID' => $articleMap[$mIdx],
                    'position'  => $mIdx % 3, // 0, 1, 2
                    'caption'   => null,
                ]);
            }
        }

        // 9. files (Dokumen & Riset Publikasi)
        $docFiles = [
            ['title' => 'Laporan Tahunan Emisi Obligasi Korporasi 2026', 'filename' => '/files/annual_report_bond_2026.pdf'],
            ['title' => 'Panduan Edukasi Perpajakan Pasar Modal', 'filename' => '/files/guide_tax_capital_gain.pdf'],
            ['title' => 'Prospektus Ringkas Penawaran Umum Perdana Saham', 'filename' => '/files/prospektus_ipo_emiten.pdf'],
            ['title' => 'Kajian Makroekonomi & Suku Bunga Bank Sentral', 'filename' => '/files/macroeconomic_outlook_q3.pdf'],
            ['title' => 'Statistik Nilai Aktiva Bersih Reksadana Nasional', 'filename' => '/files/nav_reksadana_stat.pdf'],
            ['title' => 'Peta Jalan Transisi Energi dan Hilirisasi Mineral', 'filename' => '/files/roadmap_green_energy.pdf'],
        ];
        foreach ($docFiles as $fIdx => $f) {
            $this->db->table('files')->insert([
                'title'     => $f['title'],
                'filename'  => $f['filename'],
                'userID'    => 1,
                'timestamp' => date('Y-m-d H:i:s', strtotime("-{$fIdx} days")),
            ]);
        }

        // 10. chartdata (4 Template Bawaan di Atas + Custom Charts)
        $defaultXAxis = json_encode(["D.I Yogyakarta","DKI Jakarta","Jambi","Jawa Barat","Bali","Kep. Bangka Belitung","Banten","Bengkulu","Riau","Kalimantan Tengah"]);
        $templateCharts = [
            [
                'chartTitle' => 'Template Chart Bar Vertikal',
                'xAxisTitle' => 'Wilayah / Indikator',
                'xAxisData'  => $defaultXAxis,
                'createdAt'  => date('Y-m-d H:i:s'),
                'createdBy'  => 1,
                'updateAt'   => date('Y-m-d H:i:s'),
                'updateBy'   => 1,
                'source'     => 'template',
                'chartStyle' => 'bar-vertical',
            ],
            [
                'chartTitle' => 'Template Chart Bar Horizontal',
                'xAxisTitle' => 'Wilayah / Indikator',
                'xAxisData'  => $defaultXAxis,
                'createdAt'  => date('Y-m-d H:i:s'),
                'createdBy'  => 1,
                'updateAt'   => date('Y-m-d H:i:s'),
                'updateBy'   => 1,
                'source'     => 'template',
                'chartStyle' => 'bar-horizontal',
            ],
            [
                'chartTitle' => 'Template Chart Line',
                'xAxisTitle' => 'Periode / Kuartal',
                'xAxisData'  => $defaultXAxis,
                'createdAt'  => date('Y-m-d H:i:s'),
                'createdBy'  => 1,
                'updateAt'   => date('Y-m-d H:i:s'),
                'updateBy'   => 1,
                'source'     => 'template',
                'chartStyle' => 'line',
            ],
            [
                'chartTitle' => 'Template Chart Pie',
                'xAxisTitle' => 'Kategori Pasar',
                'xAxisData'  => $defaultXAxis,
                'createdAt'  => date('Y-m-d H:i:s'),
                'createdBy'  => 1,
                'updateAt'   => date('Y-m-d H:i:s'),
                'updateBy'   => 1,
                'source'     => 'template',
                'chartStyle' => 'pie',
            ],
        ];
        $this->db->table('chartdata')->insertBatch($templateCharts);

        // Custom User Charts
        $customCharts = [
            [
                'chartTitle' => 'Tren Pergerakan Laba Bersih Perbankan Big 4 (2024-2026)',
                'xAxisTitle' => 'Bank Mandiri, BCA, BRI, BNI',
                'xAxisData'  => json_encode(["Kuartal I 2025", "Kuartal II 2025", "Kuartal III 2025", "Kuartal IV 2025", "Kuartal I 2026", "Kuartal II 2026"]),
                'createdAt'  => date('Y-m-d H:i:s', strtotime('-10 days')),
                'createdBy'  => 1,
                'updateAt'   => date('Y-m-d H:i:s'),
                'updateBy'   => 1,
                'source'     => 'Riset Mandiri Sekuritas',
                'chartStyle' => 'bar-vertical',
            ],
            [
                'chartTitle' => 'Komposisi Portofolio Transisi Energi Hijau (EBT vs Fosil)',
                'xAxisTitle' => 'Sektor Pembangkit Listrik',
                'xAxisData'  => json_encode(["PLTP Panas Bumi", "PLTS Tenaga Surya", "PLTB Bayu", "PLTA Hidro", "PLTU Batu Bara"]),
                'createdAt'  => date('Y-m-d H:i:s', strtotime('-8 days')),
                'createdBy'  => 1,
                'updateAt'   => date('Y-m-d H:i:s'),
                'updateBy'   => 1,
                'source'     => 'Kementerian ESDM',
                'chartStyle' => 'pie',
            ],
            [
                'chartTitle' => 'Grafik Pertumbuhan Investor Saham Retail SID Nasional',
                'xAxisTitle' => 'Tahun 2021 s.d 2026',
                'xAxisData'  => json_encode(["2021", "2022", "2023", "2024", "2025", "2026"]),
                'createdAt'  => date('Y-m-d H:i:s', strtotime('-5 days')),
                'createdBy'  => 1,
                'updateAt'   => date('Y-m-d H:i:s'),
                'updateBy'   => 1,
                'source'     => 'KSEI Indonesia',
                'chartStyle' => 'line',
            ],
            [
                'chartTitle' => 'Perbandingan Kapasitas Produksi Smelter Katoda Tembaga',
                'xAxisTitle' => 'Perusahaan Tambang',
                'xAxisData'  => json_encode(["Amman Mineral", "Freeport Indonesia", "Aneka Tambang"]),
                'createdAt'  => date('Y-m-d H:i:s', strtotime('-3 days')),
                'createdBy'  => 1,
                'updateAt'   => date('Y-m-d H:i:s'),
                'updateBy'   => 1,
                'source'     => 'Investor Market Feed',
                'chartStyle' => 'bar-horizontal',
            ],
        ];
        $this->db->table('chartdata')->insertBatch($customCharts);

        // 11. featurecontent (Tipe 'tag' dan 'banner')
        $features = [
            ['type' => 'tag',    'title' => 'Rekomendasi Saham Dividen Jumbo 2026', 'tag' => 'Dividen', 'setby' => 'raka_h', 'setdate' => date('Y-m-d H:i:s'), 'imageUrl' => '/img/article2.jpg', 'linkUrl' => '', 'position' => 'top', 'num' => 1],
            ['type' => 'banner', 'title' => 'Laporan Khusus: Outlook Pasar Modal Semester II', 'tag' => '', 'setby' => 'raka_h', 'setdate' => date('Y-m-d H:i:s'), 'imageUrl' => '/img/article6.jpg', 'linkUrl' => '/artikel/outlook-perekonomian-nasional-semester-ii', 'position' => 'top', 'num' => 2],
            ['type' => 'tag',    'title' => 'Arah Suku Bunga Acuan BI-Rate & Fed Fund Rate', 'tag' => 'BI-Rate', 'setby' => 'dewi_editor', 'setdate' => date('Y-m-d H:i:s'), 'imageUrl' => '/img/article5.jpg', 'linkUrl' => '', 'position' => 'middle', 'num' => 3],
            ['type' => 'banner', 'title' => 'Panduan Investasi Reksa Dana untuk Pemula', 'tag' => '', 'setby' => 'admin', 'setdate' => date('Y-m-d H:i:s'), 'imageUrl' => '/img/article3.jpg', 'linkUrl' => '/artikel/membedah-perbedaan-investasi-reksadana', 'position' => 'middle', 'num' => 4],
        ];
        $this->db->table('featurecontent')->insertBatch($features);

        // 12. pushnotif (Tipe: NewsLanding, TVShow, WebPage)
        $pushes = [
            ['postBy' => 1, 'pushDate' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'pushType' => 'NewsLanding', 'pushUrl' => '/artikel/ihsg-tembus-level-psikologis-7450', 'urlTarget' => 'outbound', 'pushSlug' => 'ihsg-tembus-7450', 'pushTitle' => 'BREAKING: IHSG Melesat Tembus Level 7.450', 'pushBody' => 'Aksi borong saham bank besar mendorong indeks menyentuh rekor penutupan tertinggi.', 'pushImage' => '/img/investor_placeholder.jpg', 'fcm_message_id' => null, 'categoryName' => 'Market', 'status' => 'ready'],
            ['postBy' => 1, 'pushDate' => date('Y-m-d H:i:s', strtotime('-3 hours')), 'pushType' => 'NewsLanding', 'pushUrl' => '/artikel/bank-central-asia-bbca-tebar-dividen-interim', 'urlTarget' => 'outbound', 'pushSlug' => 'bbca-dividen-interim', 'pushTitle' => 'DIVIDEN FLASH: BBCA Tebar Rp 6,16 Triliun', 'pushBody' => 'Bank Central Asia menetapkan dividen interim Rp 50 per lembar saham.', 'pushImage' => '/img/article2.jpg', 'fcm_message_id' => null, 'categoryName' => 'Finance', 'status' => 'ready'],
            ['postBy' => 1, 'pushDate' => date('Y-m-d H:i:s', strtotime('-6 hours')), 'pushType' => 'WebPage', 'pushUrl' => 'https://investor.id/market/live-stream', 'urlTarget' => 'outbound', 'pushSlug' => 'live-market-closing', 'pushTitle' => 'LIVE: Ulasan Penutupan Pasar Saham Hari Ini', 'pushBody' => 'Saksikan rangkuman pergerakan saham teraktif bersama tim analis riset.', 'pushImage' => '/img/article4.jpg', 'fcm_message_id' => null, 'categoryName' => 'Market', 'status' => 'ready'],
            ['postBy' => 1, 'pushDate' => date('Y-m-d H:i:s', strtotime('-12 hours')), 'pushType' => 'TVShow', 'pushUrl' => '/video/investor-daily-roundup', 'urlTarget' => 'outbound', 'pushSlug' => 'daily-roundup', 'pushTitle' => 'INVESTOR DAILY: Prospek Emiten Nikel & EBT', 'pushBody' => 'Ulasan mendalam ekspansi smelter dan hilirisasi rantai pasok baterai EV.', 'pushImage' => '/img/article6.jpg', 'fcm_message_id' => null, 'categoryName' => 'Business', 'status' => 'ready'],
        ];
        $this->db->table('pushnotif')->insertBatch($pushes);

        // 13. editorlog (Aktivitas Login, Naskah & Media dalam Rentang 3 Minggu Operasional)
        $editorLogs = [];
        $staffIds = [1, 2, 3, 4, 5, 6, 7]; // Administrator, Redpel, Editor
        $actionsPool = [
            [
                'action' => 'login',
                'doaction' => 'login',
                'payload' => ['ip' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0']
            ],
            [
                'action' => 'insert',
                'doaction' => 'article',
                'payload' => ['headline' => 'Liputan Kinerja Keuangan Kuartal Emiten Perbankan', 'status' => 'Draft']
            ],
            [
                'action' => 'edit',
                'doaction' => 'article',
                'payload' => ['headline' => 'Review Pergerakan Indeks Sektoral dan Arus Dana Asing', 'status' => 'Publish']
            ],
            [
                'action' => 'insert',
                'doaction' => 'foto',
                'payload' => ['mediaTitle' => 'Dokumentasi RUPS Tahunan Emiten BEI', 'caption' => 'Rapat umum pemegang saham menyepakati pembagian dividen.']
            ],
            [
                'action' => 'edit',
                'doaction' => 'foto',
                'payload' => ['mediaTitle' => 'Fasilitas Smelter Tembaga dan Emas Sumbawa', 'caption' => 'Pembaruan takarir foto dokumentasi smelter.']
            ],
        ];

        for ($logIdx = 0; $logIdx < 45; $logIdx++) {
            $daysAgo = ($logIdx % 21) + 1; // 1 s/d 21 hari yang lalu
            $hoursAgo = ($logIdx * 3) % 24;
            $minsAgo = ($logIdx * 17) % 60;
            $act = $actionsPool[$logIdx % count($actionsPool)];
            $edId = $staffIds[$logIdx % count($staffIds)];

            $editorLogs[] = [
                'editorId'  => $edId,
                'action'    => $act['action'],
                'doaction'  => $act['doaction'],
                'log'       => json_encode($act['payload']),
                'timestamp' => date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -{$hoursAgo} hours -{$minsAgo} minutes")),
            ];
        }
        $this->db->table('editorlog')->insertBatch($editorLogs);

        // 14. member & memberbackup (10 Akun Member Pembaca)
        $members = [
            ['name' => 'Budi Hartono Wijaya',     'email' => 'budi.hartono@djarumcapital.co.id',   'company' => 'PT Djarum Capital Investama (VIP)',         'status' => 'active', 'reg' => '2025-08-15 10:00:00', 'login' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
            ['name' => 'Sri Mulyani Handayani',   'email' => 'sri.mulyani@mandirisekuritas.co.id', 'company' => 'Mandiri Sekuritas Research (Institutional)', 'status' => 'active', 'reg' => '2025-09-01 09:30:00', 'login' => date('Y-m-d H:i:s', strtotime('-1 day'))],
            ['name' => 'Anthony Salim Sutanto',   'email' => 'anthony.s@indofoodasset.com',        'company' => 'Salim Asset Management (Corporate VIP)',    'status' => 'active', 'reg' => '2025-10-12 14:15:00', 'login' => date('Y-m-d H:i:s', strtotime('-3 days'))],
            ['name' => 'Felicia Gunawan',         'email' => 'felicia.gunawan@trimegah.com',       'company' => 'Trimegah Securities Equity Analyst',        'status' => 'active', 'reg' => '2026-01-10 11:20:00', 'login' => date('Y-m-d H:i:s', strtotime('-5 hours'))],
            ['name' => 'Michael Bambang Pratama', 'email' => 'michael.pratama@sucorasset.com',     'company' => 'Sucor Asset Management (Priority)',         'status' => 'active', 'reg' => '2026-02-01 08:45:00', 'login' => date('Y-m-d H:i:s', strtotime('-12 hours'))],
            ['name' => 'Denny Chandra Kusuma',    'email' => 'denny.kusuma@gmail.com',             'company' => 'Retail Trader Community (Retail)',          'status' => 'active', 'reg' => '2026-03-15 16:00:00', 'login' => date('Y-m-d H:i:s', strtotime('-1 week'))],
            ['name' => 'Hendra Setiawan',         'email' => 'hendra.setiawan@bca.co.id',          'company' => 'PT Bank Central Asia Tbk Treasury',         'status' => 'active', 'reg' => '2025-11-20 13:10:00', 'login' => date('Y-m-d H:i:s', strtotime('-4 hours'))],
            ['name' => 'Clarissa Tanoesoedibjo',  'email' => 'clarissa.t@mncgroup.com',            'company' => 'MNC Kapital Indonesia (Corporate VIP)',     'status' => 'inactive','reg' => date('Y-m-d H:i:s', strtotime('-2 days')), 'login' => null],
            ['name' => 'Aditya Wardhana',         'email' => 'aditya.wardhana@indopremier.com',    'company' => 'Indo Premier Investment (Priority)',        'status' => 'banned', 'reg' => '2025-05-10 10:00:00', 'login' => date('Y-m-d H:i:s', strtotime('-20 days'))],
            ['name' => 'Rina Wulandari Putri',    'email' => 'rina.wulandari@yahoo.com',           'company' => 'Universitas Indonesia Akademisi (Retail)',   'status' => 'active', 'reg' => '2026-04-01 14:00:00', 'login' => date('Y-m-d H:i:s', strtotime('-2 days'))],
        ];

        $memberIds = [];
        foreach ($members as $m) {
            $record = [
                'uniq'       => md5($m['email'] . 'salt_seed_512'),
                'email'      => $m['email'],
                'fullname'   => $m['name'],
                'slug'       => url_title($m['name'], '-', true),
                'password'   => md5('member123'),
                'signupdate' => $m['reg'],
                'lastlogin'  => $m['login'],
                'status'     => $m['status'],
                'bio'        => $m['company'],
                'lastupdate' => date('Y-m-d H:i:s'),
            ];
            $this->db->table('member')->insert($record);
            $newMemId = $this->db->insertID();
            $memberIds[] = $newMemId;

            $record['memberID'] = $newMemId;
            $this->db->table('memberbackup')->insert($record);
        }

        // 15. commentar (Komentar pada Artikel Publikasi)
        $commentsList = [
            'Paparan analisis yang sangat mendalam dan berbobot. Memberikan panduan jelas bagi keputusan alokasi investasi.',
            'Analisis teknikal dan fundamentalnya sinkron. Patut masuk watchlist pekan ini.',
            'Terima kasih atas riset eksklusif ini Tim Investor.id, sangat mencerahkan bagi investor institusi.',
            'Apakah ada proyeksi capex untuk ekspansi fasilitas energi terbarukan di semester dua nanti?',
            'Valuasi PE saat ini masih di bawah rata-rata historis 5 tahun, momen akumulasi yang bagus.',
            'Kinerja ekspor komoditas tampaknya masih menjadi penopang utama pendapatan konsolidasi.',
            'Sangat setuju dengan paparan analis di artikel ini. Risk-to-reward ratio terlihat menarik.',
            'Pemberitaan yang lugas dan berimbang. Investor ritel sangat terbantu dengan ringkasan poin utamanya.'
        ];
        foreach ($memberIds as $cIdx => $mId) {
            $targetArtId = $articleMap[$cIdx % 20] ?? 1;
            $this->db->table('commentar')->insert([
                'articleId' => $targetArtId,
                'memberID'  => $mId,
                'replyto'   => 0,
                'posted'    => date('Y-m-d H:i:s', strtotime('-' . ($cIdx * 3) . ' hours')),
                'status'    => '1',
                'comment'   => $commentsList[$cIdx % count($commentsList)],
                'replies'   => ($cIdx % 3 == 0) ? 2 : 0,
                'likes'     => 12 + ($cIdx * 4),
                'dislikes'  => $cIdx % 2,
            ]);
        }

        // 16. memberfavarticle (Bookmark Favorit Member)
        $favs = [];
        foreach ($memberIds as $mIdx => $mId) {
            $favs[] = [
                'memberID'  => $mId,
                'articleID' => $articleMap[($mIdx * 2) % count($articleMap)],
                'timestamp' => date('Y-m-d H:i:s', strtotime('-' . ($mIdx + 1) . ' days')),
            ];
            $favs[] = [
                'memberID'  => $mId,
                'articleID' => $articleMap[(($mIdx * 2) + 1) % count($articleMap)],
                'timestamp' => date('Y-m-d H:i:s', strtotime('-' . ($mIdx + 2) . ' days')),
            ];
        }
        $this->db->table('memberfavarticle')->insertBatch($favs);

        // 17. sahamemiten (75+ Emiten Saham BEI)
        $emitens = [
            ['StockCode' => 'BBCA', 'companyName' => 'PT Bank Central Asia Tbk'],
            ['StockCode' => 'BBRI', 'companyName' => 'PT Bank Rakyat Indonesia (Persero) Tbk'],
            ['StockCode' => 'BMRI', 'companyName' => 'PT Bank Mandiri (Persero) Tbk'],
            ['StockCode' => 'BBNI', 'companyName' => 'PT Bank Negara Indonesia (Persero) Tbk'],
            ['StockCode' => 'TLKM', 'companyName' => 'PT Telkom Indonesia (Persero) Tbk'],
            ['StockCode' => 'ASII', 'companyName' => 'PT Astra International Tbk'],
            ['StockCode' => 'AMMN', 'companyName' => 'PT Amman Mineral Internasional Tbk'],
            ['StockCode' => 'BREN', 'companyName' => 'PT Barito Renewables Energy Tbk'],
            ['StockCode' => 'GOTO', 'companyName' => 'PT GoTo Gojek Tokopedia Tbk'],
            ['StockCode' => 'ICBP', 'companyName' => 'PT Indofood CBP Sukses Makmur Tbk'],
            ['StockCode' => 'INDF', 'companyName' => 'PT Indofood Sukses Makmur Tbk'],
            ['StockCode' => 'UNVR', 'companyName' => 'PT Unilever Indonesia Tbk'],
            ['StockCode' => 'KLBF', 'companyName' => 'PT Kalbe Farma Tbk'],
            ['StockCode' => 'ADRO', 'companyName' => 'PT Adaro Energy Indonesia Tbk'],
            ['StockCode' => 'PTBA', 'companyName' => 'PT Bukit Asam Tbk'],
            ['StockCode' => 'PGAS', 'companyName' => 'PT Perusahaan Gas Negara Tbk'],
            ['StockCode' => 'ANTM', 'companyName' => 'PT Aneka Tambang Tbk'],
            ['StockCode' => 'INCO', 'companyName' => 'PT Vale Indonesia Tbk'],
            ['StockCode' => 'BRIS', 'companyName' => 'PT Bank Syariah Indonesia Tbk'],
            ['StockCode' => 'CPIN', 'companyName' => 'PT Charoen Pokphand Indonesia Tbk'],
            ['StockCode' => 'JPFA', 'companyName' => 'PT Japfa Comfeed Indonesia Tbk'],
            ['StockCode' => 'SMGR', 'companyName' => 'PT Semen Indonesia (Persero) Tbk'],
            ['StockCode' => 'INTP', 'companyName' => 'PT Indocement Tunggal Prakarsa Tbk'],
            ['StockCode' => 'MDKA', 'companyName' => 'PT Merdeka Copper Gold Tbk'],
            ['StockCode' => 'ARTO', 'companyName' => 'PT Bank Jago Tbk'],
            ['StockCode' => 'ESSA', 'companyName' => 'PT ESSA Industries Indonesia Tbk'],
            ['StockCode' => 'MEDC', 'companyName' => 'PT Medco Energi Internasional Tbk'],
            ['StockCode' => 'TPIA', 'companyName' => 'PT Chandra Asri Pacific Tbk'],
            ['StockCode' => 'BRPT', 'companyName' => 'PT Barito Pacific Tbk'],
            ['StockCode' => 'CTRA', 'companyName' => 'PT Ciputra Development Tbk'],
            ['StockCode' => 'BSDE', 'companyName' => 'PT Bumi Serpong Damai Tbk'],
            ['StockCode' => 'PWON', 'companyName' => 'PT Pakuwon Jati Tbk'],
            ['StockCode' => 'SMRA', 'companyName' => 'PT Summarecon Agung Tbk'],
            ['StockCode' => 'ERAA', 'companyName' => 'PT Erajaya Swasembada Tbk'],
            ['StockCode' => 'MYOR', 'companyName' => 'PT Mayora Indah Tbk'],
            ['StockCode' => 'SIDO', 'companyName' => 'PT Industri Jamu dan Farmasi Sido Muncul Tbk'],
            ['StockCode' => 'TBIG', 'companyName' => 'PT Tower Bersama Infrastructure Tbk'],
            ['StockCode' => 'TOWR', 'companyName' => 'PT Sarana Menara Nusantara Tbk'],
            ['StockCode' => 'EXCL', 'companyName' => 'PT XL Axiata Tbk'],
            ['StockCode' => 'ISAT', 'companyName' => 'PT Indosat Tbk'],
            ['StockCode' => 'JSMR', 'companyName' => 'PT Jasa Marga (Persero) Tbk'],
            ['StockCode' => 'AKRA', 'companyName' => 'PT AKR Corporindo Tbk'],
            ['StockCode' => 'UNTR', 'companyName' => 'PT United Tractors Tbk'],
            ['StockCode' => 'ACES', 'companyName' => 'PT Aspirasi Hidup Indonesia Tbk'],
            ['StockCode' => 'MAPI', 'companyName' => 'PT Mitra Adiperkasa Tbk'],
            ['StockCode' => 'HEAL', 'companyName' => 'PT Medikaloka Hermina Tbk'],
            ['StockCode' => 'MIKA', 'companyName' => 'PT Mitra Keluarga Karyasehat Tbk'],
            ['StockCode' => 'SILO', 'companyName' => 'PT Siloam International Hospitals Tbk'],
            ['StockCode' => 'CMRY', 'companyName' => 'PT Cisarua Mountain Dairy Tbk'],
            ['StockCode' => 'BUMI', 'companyName' => 'PT Bumi Resources Tbk'],
            ['StockCode' => 'DEWA', 'companyName' => 'PT Darma Henwa Tbk'],
            ['StockCode' => 'ENRG', 'companyName' => 'PT Energi Mega Persada Tbk'],
            ['StockCode' => 'ADMR', 'companyName' => 'PT Adaro Minerals Indonesia Tbk'],
            ['StockCode' => 'MBMA', 'companyName' => 'PT Merdeka Battery Materials Tbk'],
            ['StockCode' => 'NCKL', 'companyName' => 'PT Trimegah Bangun Persada Tbk'],
            ['StockCode' => 'PGEO', 'companyName' => 'PT Pertamina Geothermal Energy Tbk'],
            ['StockCode' => 'AVIA', 'companyName' => 'PT Avia Avian Tbk'],
            ['StockCode' => 'BTPS', 'companyName' => 'PT Bank BTPN Syariah Tbk'],
            ['StockCode' => 'BBTN', 'companyName' => 'PT Bank Tabungan Negara (Persero) Tbk'],
            ['StockCode' => 'BDMN', 'companyName' => 'PT Bank Danamon Indonesia Tbk'],
            ['StockCode' => 'BNGA', 'companyName' => 'PT Bank CIMB Niaga Tbk'],
            ['StockCode' => 'BNII', 'companyName' => 'PT Bank Maybank Indonesia Tbk'],
            ['StockCode' => 'PNBN', 'companyName' => 'PT Bank Pan Indonesia Tbk'],
            ['StockCode' => 'BJBR', 'companyName' => 'PT BPD Jawa Barat dan Banten Tbk'],
            ['StockCode' => 'BJTM', 'companyName' => 'PT BPD Jawa Timur Tbk'],
            ['StockCode' => 'SRTG', 'companyName' => 'PT Saratoga Investama Sedaya Tbk'],
            ['StockCode' => 'EMTK', 'companyName' => 'PT Elang Mahkota Teknologi Tbk'],
            ['StockCode' => 'SCMA', 'companyName' => 'PT Surya Citra Media Tbk'],
            ['StockCode' => 'MNCN', 'companyName' => 'PT Media Nusantara Citra Tbk'],
            ['StockCode' => 'BMTR', 'companyName' => 'PT Global Mediacom Tbk'],
            ['StockCode' => 'FILM', 'companyName' => 'PT MD Pictures Tbk'],
            ['StockCode' => 'AMRT', 'companyName' => 'PT Sumber Alfaria Trijaya Tbk'],
            ['StockCode' => 'MIDI', 'companyName' => 'PT Midi Utama Indonesia Tbk'],
            ['StockCode' => 'MAPA', 'companyName' => 'PT MAP Aktif Adiperkasa Tbk'],
        ];
        $this->db->table('sahamemiten')->insertBatch($emitens);

        // 18. Indices (saham, saham_topvalue, saham_index, saham_sectoral)
        $indices = [
            ['IndexCode' => 'IHSG', 'PrevVal' => 7420.43, 'HighVal' => 7465.12, 'LowVal' => 7415.80, 'LastVal' => 7452.55, 'ChgVal' => 32.12, 'ChgPct' => 0.43, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'LQ45', 'PrevVal' => 932.60, 'HighVal' => 942.20, 'LowVal' => 931.10, 'LastVal' => 938.40, 'ChgVal' => 5.80, 'ChgPct' => 0.62, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'IDX30', 'PrevVal' => 480.15, 'HighVal' => 486.30, 'LowVal' => 479.50, 'LastVal' => 483.90, 'ChgVal' => 3.75, 'ChgPct' => 0.78, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'KOMPAS100', 'PrevVal' => 1180.20, 'HighVal' => 1192.40, 'LowVal' => 1179.10, 'LastVal' => 1188.60, 'ChgVal' => 8.40, 'ChgPct' => 0.71, 'LastChange' => date('Y-m-d H:i:s')],
        ];
        $this->db->table('saham')->insertBatch($indices);

        $topValues = [
            ['IndexCode' => 'BBCA', 'PrevVal' => 10100, 'HighVal' => 10300, 'LowVal' => 10050, 'LastVal' => 10250, 'ChgVal' => 150, 'ChgPct' => 1.48, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'BBRI', 'PrevVal' => 5125,  'HighVal' => 5225,  'LowVal' => 5100,  'LastVal' => 5200,  'ChgVal' => 75,  'ChgPct' => 1.46, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'BMRI', 'PrevVal' => 7025,  'HighVal' => 7200,  'LowVal' => 7000,  'LastVal' => 7150,  'ChgVal' => 125, 'ChgPct' => 1.78, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'BREN', 'PrevVal' => 9100,  'HighVal' => 9500,  'LowVal' => 9050,  'LastVal' => 9400,  'ChgVal' => 300, 'ChgPct' => 3.30, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'AMMN', 'PrevVal' => 9750,  'HighVal' => 10050, 'LowVal' => 9700,  'LastVal' => 9950,  'ChgVal' => 200, 'ChgPct' => 2.05, 'LastChange' => date('Y-m-d H:i:s')],
        ];
        $this->db->table('saham_topvalue')->insertBatch($topValues);

        // 18b. saham_index (Master Indeks Global / Regional: HSI, NIKKEI, DJI, dll)
        $sahamIndexes = [
            ['IndexCode' => 'HSI',    'PrevVal' => 17950.00, 'HighVal' => 18050.20, 'LowVal' => 17900.50, 'LastVal' => 18009.10, 'ChgVal' => 59.10,  'ChgPct' => 0.33, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'NIKKEI', 'PrevVal' => 38100.00, 'HighVal' => 38350.00, 'LowVal' => 38020.00, 'LastVal' => 38280.50, 'ChgVal' => 180.50, 'ChgPct' => 0.47, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'DJI',    'PrevVal' => 40200.00, 'HighVal' => 40450.00, 'LowVal' => 40150.00, 'LastVal' => 40380.00, 'ChgVal' => 180.00, 'ChgPct' => 0.45, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'S&P500', 'PrevVal' => 5500.00,  'HighVal' => 5540.00,  'LowVal' => 5490.00,  'LastVal' => 5525.20, 'ChgVal' => 25.20,  'ChgPct' => 0.46, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'NASDAQ', 'PrevVal' => 17300.00, 'HighVal' => 17450.00, 'LowVal' => 17280.00, 'LastLast' => 17410.00, 'ChgVal' => 110.00, 'ChgPct' => 0.64, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'STI',    'PrevVal' => 3450.00,  'HighVal' => 3480.00,  'LowVal' => 3445.00,  'LastVal' => 3470.50, 'ChgVal' => 20.50,  'ChgPct' => 0.59, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'KLCI',   'PrevVal' => 1650.00,  'HighVal' => 1665.00,  'LowVal' => 1648.00,  'LastVal' => 1660.00, 'ChgVal' => 10.00,  'ChgPct' => 0.61, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'SET',    'PrevVal' => 1420.00,  'HighVal' => 1435.00,  'LowVal' => 1418.00,  'LastVal' => 1428.80, 'ChgVal' => 8.80,   'ChgPct' => 0.62, 'LastChange' => date('Y-m-d H:i:s')],
        ];
        // Fix column alias for NASDAQ if needed
        foreach ($sahamIndexes as &$si) {
            if (isset($si['LastLast'])) {
                $si['LastVal'] = $si['LastLast'];
                unset($si['LastLast']);
            }
        }
        if ($this->db->tableExists('saham_index')) {
            $this->db->table('saham_index')->insertBatch($sahamIndexes);
        }

        // 18c. saham_sectoral (Master Sektoral BEI: AGRI, MINING, BASIC-IND, MISC-IND, CONSUMER, PROPERTY, INFRASTRUC, FINANCE, TRADE, MANUFACTURE)
        $sectorals = [
            ['IndexCode' => 'AGRI',       'PrevVal' => 1440.92, 'HighVal' => 1461.68, 'LowVal' => 1437.21, 'LastVal' => 1457.95, 'ChgVal' => 17.03,  'ChgPct' => 1.18, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'MINING',     'PrevVal' => 1797.73, 'HighVal' => 1846.62, 'LowVal' => 1797.73, 'LastVal' => 1841.19, 'ChgVal' => 43.46,  'ChgPct' => 2.42, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'BASIC-IND',  'PrevVal' => 1250.40, 'HighVal' => 1270.00, 'LowVal' => 1245.50, 'LastVal' => 1265.80, 'ChgVal' => 15.40,  'ChgPct' => 1.23, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'MISC-IND',   'PrevVal' => 980.50,  'HighVal' => 995.00,  'LowVal' => 978.00,  'LastVal' => 990.20,  'ChgVal' => 9.70,   'ChgPct' => 0.99, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'CONSUMER',   'PrevVal' => 1410.20, 'HighVal' => 1425.00, 'LowVal' => 1405.00, 'LastVal' => 1418.60, 'ChgVal' => 8.40,   'ChgPct' => 0.60, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'PROPERTY',   'PrevVal' => 374.92,  'HighVal' => 377.48,  'LowVal' => 372.70,  'LastVal' => 374.48,  'ChgVal' => -0.44,  'ChgPct' => -0.12,'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'INFRASTRUC', 'PrevVal' => 890.30,  'HighVal' => 905.00,  'LowVal' => 888.00,  'LastVal' => 901.40,  'ChgVal' => 11.10,  'ChgPct' => 1.25, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'FINANCE',    'PrevVal' => 1480.00, 'HighVal' => 1502.00, 'LowVal' => 1475.00, 'LastVal' => 1495.50, 'ChgVal' => 15.50,  'ChgPct' => 1.05, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'TRADE',      'PrevVal' => 780.20,  'HighVal' => 790.00,  'LowVal' => 778.00,  'LastVal' => 786.50,  'ChgVal' => 6.30,   'ChgPct' => 0.81, 'LastChange' => date('Y-m-d H:i:s')],
            ['IndexCode' => 'MANUFACTUR', 'PrevVal' => 1320.00, 'HighVal' => 1340.00, 'LowVal' => 1315.00, 'LastVal' => 1335.20, 'ChgVal' => 15.20,  'ChgPct' => 1.15, 'LastChange' => date('Y-m-d H:i:s')],
        ];
        if ($this->db->tableExists('saham_sectoral')) {
            $this->db->table('saham_sectoral')->insertBatch($sectorals);
        }

        // 19. apps (Konfigurasi Versi Aplikasi)
        $configs = [
            ['key' => 'version', 'value' => '1.2.0'],
            ['key' => 'version', 'value' => '1.2.5'],
        ];
        $this->db->table('apps')->insertBatch($configs);

        // 20. googledocs (Spreadsheet & Datawrapper Embed)
        $resources = [
            ['title' => 'IDX Financial Feed Realtime API', 'url' => 'https://api.idx.co.id/feed/v1', 'userID' => 1, 'timestamp' => date('Y-m-d H:i:s'), 'source' => 'googledocs'],
            ['title' => 'TradingView Interactive Chart Widget CDN', 'url' => 'https://s.tradingview.com/widgetembed/', 'userID' => 1, 'timestamp' => date('Y-m-d H:i:s'), 'source' => 'googledocs'],
            ['title' => 'Bank Indonesia BI-Rate Official Data', 'url' => 'https://www.bi.go.id/bi-rate', 'userID' => 1, 'timestamp' => date('Y-m-d H:i:s'), 'source' => 'datawrapper'],
        ];
        $this->db->table('googledocs')->insertBatch($resources);

        // 21. pushregistered & pushtodevice (Hashed UDID & Device Token dengan SHA-512)
        $devices = [
            ['udid' => hash('sha512', 'dev-ios-a1b2c3d4'), 'fcm_token' => hash('sha512', 'fcm_token_ios_vip_001'), 'os' => 'ios', 'os_version' => '17.5', 'app_version' => '2.4.1', 'notif' => 'true', 'registered' => date('Y-m-d H:i:s', strtotime('-1 month')), 'lastactive' => date('Y-m-d H:i:s'), 'owner' => 'VIP'],
            ['udid' => hash('sha512', 'dev-android-e5f6g7h8'), 'fcm_token' => hash('sha512', 'fcm_token_android_vip_002'), 'os' => 'android', 'os_version' => '14.0', 'app_version' => '2.4.5', 'notif' => 'true', 'registered' => date('Y-m-d H:i:s', strtotime('-2 weeks')), 'lastactive' => date('Y-m-d H:i:s'), 'owner' => 'PRIORITY'],
            ['udid' => hash('sha512', 'dev-android-i9j0k1l2'), 'fcm_token' => hash('sha512', 'fcm_token_android_ret_003'), 'os' => 'android', 'os_version' => '13.0', 'app_version' => '2.4.5', 'notif' => 'true', 'registered' => date('Y-m-d H:i:s', strtotime('-5 days')), 'lastactive' => date('Y-m-d H:i:s'), 'owner' => 'RETAIL'],
        ];
        $this->db->table('pushregistered')->insertBatch($devices);

        $dispatches = [
            [
                'idPush' => 1, 'deviceID' => 1, 'deviceToken' => hash('sha512', 'fcm_token_ios_vip_001'),
                'pushType' => 'NewsLanding', 'pushUrl' => '/artikel/ihsg-tembus-level-psikologis-7450',
                'url_target' => 'outbound', 'pushTitle' => 'BREAKING NEWS: IHSG Menembus Level 7.450',
                'pushBody' => 'IHSG melesat ditopang akumulasi beli asing pada saham perbankan dan tambang.',
                'pushImage' => '/img/investor_placeholder.jpg', 'status' => 'success',
                'log_message' => 'Delivered to APNs successfully in 45ms', 'articleID' => 1,
                'categoryName' => 'Market', 'pushTime' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'push_instance' => 1
            ],
            [
                'idPush' => 2, 'deviceID' => 2, 'deviceToken' => hash('sha512', 'fcm_token_android_vip_002'),
                'pushType' => 'NewsLanding', 'pushUrl' => '/artikel/bank-central-asia-bbca-tebar-dividen-interim',
                'url_target' => 'outbound', 'pushTitle' => 'MARKET FLASH: BBCA Tebar Dividen Rp 6,16 Triliun',
                'pushBody' => 'Bank Central Asia menetapkan dividen interim Rp 50 per saham.',
                'pushImage' => '/img/article2.jpg', 'status' => 'success',
                'log_message' => 'Delivered to FCM gateway successfully in 38ms', 'articleID' => 2,
                'categoryName' => 'Finance', 'pushTime' => date('Y-m-d H:i:s', strtotime('-3 hours')), 'push_instance' => 1
            ],
        ];
        $this->db->table('pushtodevice')->insertBatch($dispatches);

        $crons = [
            ['status' => 1, 'start_push' => date('Y-m-d 08:30:00'), 'end_push' => date('Y-m-d 08:30:05'), 'start_id' => 1, 'end_id' => 150],
            ['status' => 1, 'start_push' => date('Y-m-d 16:30:00'), 'end_push' => date('Y-m-d 16:30:08'), 'start_id' => 151, 'end_id' => 300],
        ];
        $this->db->table('pushtodevicescron')->insertBatch($crons);

        // 22. user2fauth
        $tokens = [
            ['iduser' => 1, 'code' => 849201, 'expired' => date('Y-m-d H:i:s', strtotime('+30 days')), 'isUsed' => '1'],
            ['iduser' => 2, 'code' => 610394, 'expired' => date('Y-m-d H:i:s', strtotime('+15 days')), 'isUsed' => '0'],
            ['iduser' => 3, 'code' => 459128, 'expired' => date('Y-m-d H:i:s', strtotime('+7 days')),  'isUsed' => '1'],
        ];
        $this->db->table('user2fauth')->insertBatch($tokens);

        echo "CmsDataSeeder with 100 Fresh Articles finished successfully!\n";
    }
}

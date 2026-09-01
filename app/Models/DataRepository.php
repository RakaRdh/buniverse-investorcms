<?php

namespace App\Models;

class DataRepository
{
    private static function getJsonDir(): string
    {
        $base = defined('WRITEPATH') ? WRITEPATH : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR;
        $dir = $base . 'data' . DIRECTORY_SEPARATOR . 'json';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir;
    }

    private static function getFilePath(string $key): string
    {
        $primary = self::getJsonDir() . DIRECTORY_SEPARATOR . $key . '.json';
        if (!file_exists($primary)) {
            $fallback = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . $key . '.json';
            if (file_exists($fallback)) {
                @copy($fallback, $primary);
            }
        }
        return $primary;
    }

    private static bool $isInitializing = false;

    public static function initialize(): void
    {
        if (self::$isInitializing) return;
        self::$isInitializing = true;

        self::getJsonDir();

        // 1. Posts (50 articles with full variations)
        if (!file_exists(self::getFilePath('posts'))) {
            $posts = self::generateInitialPosts();
            self::save('posts', $posts);
        }

        // 2. Categories
        if (!file_exists(self::getFilePath('categories'))) {
            $categories = [
                ['id' => 1, 'name' => 'Market Stock', 'slug' => 'market-stock', 'count' => 1420, 'status' => 'Active', 'updated' => '01 Sep 2026 09:30'],
                ['id' => 2, 'name' => 'Market Commodities', 'slug' => 'market-commodities', 'count' => 840, 'status' => 'Active', 'updated' => '01 Sep 2026 08:45'],
                ['id' => 3, 'name' => 'Finance Banking', 'slug' => 'finance-banking', 'count' => 960, 'status' => 'Active', 'updated' => '31 Aug 2026 17:15'],
                ['id' => 4, 'name' => 'Market Corporate Action', 'slug' => 'market-corporate-action', 'count' => 610, 'status' => 'Active', 'updated' => '31 Aug 2026 16:20'],
                ['id' => 5, 'name' => 'Business Infrastructure', 'slug' => 'business-infrastructure', 'count' => 520, 'status' => 'Active', 'updated' => '30 Aug 2026 14:10'],
                ['id' => 6, 'name' => 'Macroeconomy', 'slug' => 'macroeconomy', 'count' => 730, 'status' => 'Active', 'updated' => '30 Aug 2026 11:00'],
                ['id' => 7, 'name' => 'Tech & Digital Assets', 'slug' => 'tech-digital-assets', 'count' => 390, 'status' => 'Active', 'updated' => '29 Aug 2026 15:40'],
                ['id' => 8, 'name' => 'ESG & Green Energy', 'slug' => 'esg-green-energy', 'count' => 280, 'status' => 'Active', 'updated' => '28 Aug 2026 10:20'],
            ];
            self::save('categories', $categories);
        }

        // 3. Users (No avatars, plain text passwords for auth simulation)
        if (!file_exists(self::getFilePath('users'))) {
            $users = [
                ['id' => 1, 'name' => 'Raka Herdika', 'username' => 'raka_h', 'email' => 'raka.herdika@investor.id', 'password' => 'admin123', 'role' => 'Admin', 'status' => 'Active', 'last_active' => 'Just now'],
                ['id' => 2, 'name' => 'Natasha Khairunisa', 'username' => 'natasha_k', 'email' => 'natasha.k@investor.id', 'password' => 'editor123', 'role' => 'Editor', 'status' => 'Active', 'last_active' => '12 mins ago'],
                ['id' => 3, 'name' => 'Eva Fitriani', 'username' => 'eva_id', 'email' => 'eva.fitriani@investor.id', 'password' => 'author123', 'role' => 'Author', 'status' => 'Active', 'last_active' => '35 mins ago'],
                ['id' => 4, 'name' => 'M. Ghafur Fadillah', 'username' => 'ghafur_f', 'email' => 'ghafur.f@investor.id', 'password' => 'author123', 'role' => 'Author', 'status' => 'Active', 'last_active' => '1 hour ago'],
                ['id' => 5, 'name' => 'Erla Darwati', 'username' => 'erla_d', 'email' => 'erla.darwati@investor.id', 'password' => 'editor123', 'role' => 'Editor', 'status' => 'Active', 'last_active' => '2 hours ago'],
                ['id' => 6, 'name' => 'Arnoldus Kristianus', 'username' => 'arnoldus_k', 'email' => 'arnoldus.k@investor.id', 'password' => 'contrib123', 'role' => 'Contributor', 'status' => 'Active', 'last_active' => '1 day ago'],
                ['id' => 7, 'name' => 'Prisma ID Desk', 'username' => 'prisma_id', 'email' => 'desk.prisma@investor.id', 'password' => 'contrib123', 'role' => 'Contributor', 'status' => 'Active', 'last_active' => 'Yesterday'],
            ];
            self::save('users', $users);
        }

        // 4. Media Extended (18 items for media pagination test)
        if (!file_exists(self::getFilePath('media_extended'))) {
            $media = self::generateInitialMedia();
            self::save('media_extended', $media);
        }

        // 5. Files Extended
        if (!file_exists(self::getFilePath('files_extended'))) {
            $files = [
                ['id' => 1, 'name' => 'Laporan_Keuangan_Q2_2026_IDX.pdf', 'date' => '31/08/2026 17:00', 'upload_by' => 'raka_h', 'url' => '/files/download/1', 'type' => 'PDF', 'size' => '4.8 MB'],
                ['id' => 2, 'name' => 'Editorial_Styleguide_InvestorID_2026.pdf', 'date' => '28/08/2026 10:00', 'upload_by' => 'Editorial Board', 'url' => '/files/download/2', 'type' => 'PDF', 'size' => '1.2 MB'],
                ['id' => 3, 'name' => 'Konsensus_Analis_Saham_Banking_Sep2026.xlsx', 'date' => '31/08/2026 08:30', 'upload_by' => 'natasha_k', 'url' => '/files/download/3', 'type' => 'XLSX', 'size' => '780 KB'],
                ['id' => 4, 'name' => 'Press_Release_BUMN_Karya_Merger.docx', 'date' => '30/08/2026 15:20', 'upload_by' => 'prisma_id', 'url' => '/files/download/4', 'type' => 'DOCX', 'size' => '320 KB'],
                ['id' => 5, 'name' => 'Template_Infografis_Investor_Social.ai', 'date' => '25/08/2026 11:15', 'upload_by' => 'Creative Studio', 'url' => '/files/download/5', 'type' => 'AI', 'size' => '18.4 MB'],
                ['id' => 6, 'name' => 'Makroekonomi_APBN_2027_Draft.pdf', 'date' => '24/08/2026 14:00', 'upload_by' => 'arnoldus_k', 'url' => '/files/download/6', 'type' => 'PDF', 'size' => '3.5 MB'],
            ];
            self::save('files_extended', $files);
        }

        // 6. Embed Charts
        if (!file_exists(self::getFilePath('embed_charts'))) {
            $embedCharts = [
                ['id' => 1, 'title' => 'IHSG Composite Daily Candle & Volume', 'type' => 'Candlestick / Area', 'symbol' => 'COMPOSITE:IDX', 'source' => 'TradingView / Bloomberg API', 'author' => 'Raka H.', 'updated' => '01 Sep 2026 09:00', 'status' => 'Active', 'embed_code' => '<iframe src="https://investor.id/embed/charts/ihsg-composite" width="100%" height="450" frameborder="0"></iframe>'],
                ['id' => 2, 'title' => 'Harga Emas Spot Dunia (XAU/USD) vs Antam', 'type' => 'Multi-line Comparison', 'symbol' => 'XAUUSD / ANTM', 'source' => 'Kitco / Antam Feed', 'author' => 'Natasha K.', 'updated' => '31 Aug 2026 18:30', 'status' => 'Active', 'embed_code' => '<iframe src="https://investor.id/embed/charts/gold-xau-antam" width="100%" height="400" frameborder="0"></iframe>'],
                ['id' => 3, 'title' => 'Pergerakan Kurs JISDOR USD/IDR', 'type' => 'Step Line', 'symbol' => 'USDIDR:JISDOR', 'source' => 'Bank Indonesia API', 'author' => 'Erla D.', 'updated' => '31 Aug 2026 16:00', 'status' => 'Active', 'embed_code' => '<iframe src="https://investor.id/embed/charts/usd-idr-jisdor" width="100%" height="380" frameborder="0"></iframe>'],
                ['id' => 4, 'title' => 'Top 10 Market Cap IDX Heatmap', 'type' => 'Treemap Heatmap', 'symbol' => 'IDX:TOP10', 'source' => 'IDX Data Service', 'author' => 'Ghafur F.', 'updated' => '30 Aug 2026 17:15', 'status' => 'Active', 'embed_code' => '<iframe src="https://investor.id/embed/charts/idx-marketcap-heatmap" width="100%" height="500" frameborder="0"></iframe>'],
            ];
            self::save('embed_charts', $embedCharts);
        }

        // 7. App Versions
        if (!file_exists(self::getFilePath('app_versions'))) {
            $appVersions = [
                ['id' => 1, 'app' => 'Investor.id Mobile', 'platform' => 'iOS (App Store)', 'version' => 'v4.2.1', 'build' => '4210', 'release_date' => '28 Aug 2026', 'status' => 'Published', 'mandatory' => false, 'changelog' => 'New live market widget, performance optimization on push alerts.'],
                ['id' => 2, 'app' => 'Investor.id Mobile', 'platform' => 'Android (Play Store)', 'version' => 'v4.2.0', 'build' => '4205', 'release_date' => '25 Aug 2026', 'status' => 'Published', 'mandatory' => false, 'changelog' => 'Added dark mode support, stock watchlist sync improvements.'],
                ['id' => 3, 'app' => 'Investor.id Mobile', 'platform' => 'iOS (App Store)', 'version' => 'v4.3.0-beta', 'build' => '4300', 'release_date' => '02 Sep 2026', 'status' => 'Testing', 'mandatory' => false, 'changelog' => 'AI News Summaries, Interactive Financial Statements viewer.'],
            ];
            self::save('app_versions', $appVersions);
        }

        // 8. Push History
        if (!file_exists(self::getFilePath('push_history'))) {
            $pushHistory = [
                ['id' => 1, 'content' => 'Harga Melandai, Emas Perhiasan Diburu', 'push_date' => '2026-08-21 14:33:50', 'post_by' => 'raka_h', 'status' => 'complete'],
                ['id' => 2, 'content' => 'Rupiah Lagi-lagi Dilanda Pelemahan Beruntun', 'push_date' => '2026-07-02 16:43:08', 'post_by' => 'raka_h', 'status' => 'complete'],
                ['id' => 3, 'content' => 'IHSG Melesat, 5 Saham Melonjak hingga 34%', 'push_date' => '2026-07-02 12:51:07', 'post_by' => 'raka_h', 'status' => 'complete'],
                ['id' => 4, 'content' => 'Celios Nilai Kenaikan BBM Nonsubsidi Sudah Tepat', 'push_date' => '2026-04-28 15:30:45', 'post_by' => 'natasha_k', 'status' => 'complete'],
                ['id' => 5, 'content' => 'Harga Emas Antam (ANTM) Hari Ini: Melonjak Lagi', 'push_date' => '2025-12-11 08:59:14', 'post_by' => 'raka_h', 'status' => 'complete'],
                ['id' => 6, 'content' => 'Purbaya Siapkan Instrumen untuk Pungut Bea Keluar Emas', 'push_date' => '2025-12-08 15:48:08', 'post_by' => 'raka_h', 'status' => 'complete'],
                ['id' => 7, 'content' => 'Saham BUMI dan GOTO Banyak Dibuang Asing!', 'push_date' => '2025-12-05 15:34:32', 'post_by' => 'raka_h', 'status' => 'complete'],
                ['id' => 8, 'content' => 'Harga Emas Perhiasan Sabtu 29 November, Ini Daftar Lengkapnya', 'push_date' => '2025-11-29 12:18:24', 'post_by' => 'raka_h', 'status' => 'complete']
            ];
            self::save('push_history', $pushHistory);
        }

        // 9. Feature Content
        if (!file_exists(self::getFilePath('feature_content'))) {
            $featureContent = [
                ['id' => 1, 'title' => 'Kupas Tuntas Valuasi Bank Jumbo Pasca Penurunan Suku Bunga', 'type' => 'Editorial Deep Dive', 'badge' => 'Special Report', 'order' => 1, 'status' => 'Active', 'image' => '/img/article1.jpg', 'target_url' => '/post/2'],
                ['id' => 2, 'title' => 'Investasi Hijau: Panduan Lengkap Obligasi ESG untuk Pemula', 'type' => 'Academy Series', 'badge' => 'Investor Academy', 'order' => 2, 'status' => 'Active', 'image' => '/img/article4.jpg', 'target_url' => '/post/9'],
                ['id' => 3, 'title' => 'Prospek Pasar IPO Kuartal IV 2026', 'type' => 'Market Outlook', 'badge' => 'Analysis', 'order' => 3, 'status' => 'Active', 'image' => '/img/article3.jpg', 'target_url' => '/post/6'],
            ];
            self::save('feature_content', $featureContent);
        }

        // 10. Active Headlines
        if (!file_exists(self::getFilePath('active_headlines'))) {
            $postsFile = self::getFilePath('posts');
            $postsData = file_exists($postsFile) ? json_decode(file_get_contents($postsFile), true) : [];
            $published = array_values(array_filter($postsData ?? [], fn($p) => ($p['status'] ?? '') === 'Published'));
            $headlines = array_slice($published, 0, 3);
            self::save('active_headlines', $headlines);
        }

        self::$isInitializing = false;
    }

    public static function authenticate(string $email, string $password): ?array
    {
        self::initialize();
        $users = self::get('users');
        foreach ($users as $user) {
            if (strtolower($user['email']) === strtolower($email) && $user['password'] === $password) {
                return $user;
            }
        }
        return null;
    }

    public static function get(string $key = null)
    {
        self::initialize();
        if ($key === null) {
            $all = [];
            foreach (glob(self::getJsonDir() . DIRECTORY_SEPARATOR . '*.json') as $file) {
                $k = basename($file, '.json');
                $all[$k] = json_decode(file_get_contents($file), true);
            }
            return $all;
        }

        $file = self::getFilePath($key);
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?? [];
        }
        return [];
    }

    public static function save(string $key, $data): void
    {
        self::getJsonDir();
        $file = self::getFilePath($key);
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public static function resetAll(): void
    {
        $dir = self::getJsonDir();
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') as $file) {
            @unlink($file);
        }
        self::initialize();
    }

    private static function generateInitialMedia(): array
    {
        $titles = [
            ['title' => 'Gedung Bursa Efek Indonesia (IDX) SCBD', 'caption' => 'Aktivitas perdagangan saham di Gedung Bursa Efek Indonesia, Jakarta Selatan.', 'source' => 'Investor.id / Dokumen', 'user' => 'raka_h'],
            ['title' => 'Kilang Minyak dan Gas Bumi Cepu', 'caption' => 'Fasilitas produksi migas Blok Cepu beroperasi dengan kapasitas optimal.', 'source' => 'Pertamina / Istimewa', 'user' => 'natasha_k'],
            ['title' => 'Pertemuan Tingkat Menteri Keuangan ASEAN', 'caption' => 'Delegasi Indonesia memimpin diskusi integrasi pembayaran lintas negara.', 'source' => 'Kemenkeu RI', 'user' => 'eva_id'],
            ['title' => 'Pembangunan Kereta Cepat Tahap Lanjutan', 'caption' => 'Progres konstruksi jalur ekstensi transportasi rel modern nasional.', 'source' => 'KCIC / Humas', 'user' => 'ghafur_f'],
            ['title' => 'Pabrik Baterai Kendaraan Listrik Karawang', 'caption' => 'Lini perakitan sel baterai EV siap memasok kebutuhan industri otomotif.', 'source' => 'IBC / Dok', 'user' => 'erla_d'],
            ['title' => 'Fasilitas Smelter Nikel Terintegrasi Pomalaa', 'caption' => 'Operasional smelter HPAL menghasilkan material kelas satu untuk baterai hijau.', 'source' => 'Antam Tbk', 'user' => 'arnoldus_k'],
            ['title' => 'Kantor Pusat Bank Sentral Bank Indonesia', 'caption' => 'Kompleks perkantoran Bank Indonesia di kawasan Thamrin Jakarta.', 'source' => 'BI / Humas', 'user' => 'raka_h'],
            ['title' => 'Pembangkit Listrik Tenaga Surya Terapung Cirata', 'caption' => 'Panel PLTS terapung terbesar di kawasan Asia Tenggara beroperasi penuh.', 'source' => 'PLN Nusantara', 'user' => 'natasha_k'],
            ['title' => 'Pusat Logistik Berikat Pelabuhan Tanjung Priok', 'caption' => 'Arus peti kemas ekspor impor nasional terus menunjukkan tren ekspansi.', 'source' => 'Pelindo', 'user' => 'eva_id'],
            ['title' => 'Server Farm Data Center Tier 4 Cikarang', 'caption' => 'Infrastruktur cloud computing mendukung transformasi digital ekonomi perbankan.', 'source' => 'Telkom Data', 'user' => 'ghafur_f'],
            ['title' => 'Perkebunan Kelapa Sawit Berkelanjutan Riau', 'caption' => 'Sertifikasi RSPO pada perkebunan sawit rakyat mendorong daya saing ekspor CPO.', 'source' => 'GAPKI', 'user' => 'erla_d'],
            ['title' => 'Konferensi Pers Rapat Dewan Gubernur Bank Indonesia', 'caption' => 'Gubernur BI menyampaikan asesmen kondisi moneter dan stabilitas nilai tukar Rupiah.', 'source' => 'BI Media', 'user' => 'arnoldus_k'],
            ['title' => 'Pabrik Semen Ramah Lingkungan Tuban', 'caption' => 'Pemanfaatan bahan bakar alternatif biomassa pada industri manufaktur semen.', 'source' => 'SIG Tbk', 'user' => 'raka_h'],
            ['title' => 'Peluncuran Reksa Dana ESG Syariah', 'caption' => 'Manajer investasi merilis instrumen investasi berkelanjutan berbasis prinsip syariah.', 'source' => 'Syailendra', 'user' => 'natasha_k'],
            ['title' => 'Terminal Khusus Ekspor Bauksit Kalimantan Barat', 'caption' => 'Pengapalan produk olahan alumina hasil hilirisasi mineral tambang.', 'source' => 'Inalum', 'user' => 'eva_id'],
            ['title' => 'Kantor Cabang Digital Bank Syariah Indonesia', 'caption' => 'Layanan perbankan digital memudahkan transaksi nasabah segmen milenial.', 'source' => 'BSI Media', 'user' => 'ghafur_f'],
            ['title' => 'Proyek Bendungan Pengendali Banjir Nusantara', 'caption' => 'Pembangunan infrastruktur pengendali air di Kawasan Inti Pusat Pemerintahan.', 'source' => 'PUPR', 'user' => 'erla_d'],
            ['title' => 'Pusat Riset Kecerdasan Buatan AI Bandung', 'caption' => 'Kolaborasi perguruan tinggi dan industri teknologi menciptakan solusi analitik.', 'source' => 'ITB Press', 'user' => 'arnoldus_k']
        ];

        $media = [];
        $images = ['/img/article1.jpg', '/img/article2.jpg', '/img/article3.jpg', '/img/article4.jpg', '/img/article5.jpg', '/img/article6.jpg'];

        foreach ($titles as $idx => $item) {
            $media[] = [
                'id' => $idx + 1,
                'title' => $item['title'],
                'caption' => $item['caption'],
                'image' => $images[$idx % count($images)],
                'source' => $item['source'],
                'date' => date('d/m/y H:i', strtotime('-' . ($idx * 3) . ' hours')),
                'user' => $item['user']
            ];
        }

        return $media;
    }

    private static function generateInitialPosts(): array
    {
        $rawArticles = [
            ['title' => 'Prediksi Harga Emas Antam (ANTM) Selasa 1 September 2026', 'cat' => 'Market Commodities', 'cat_slug' => 'market-commodities', 'author' => 'Natasha Khairunisa', 'post_by' => 'natasha_k', 'status' => 'Published', 'premium' => 0, 'views' => 1240, 'headline' => 'secondary', 'breaking' => 0],
            ['title' => 'September Effect Bayangi Momentum Positif IHSG', 'cat' => 'Market Stock', 'cat_slug' => 'market-stock', 'author' => 'Eva Fitriani dan Ghafur Fadillah', 'post_by' => 'eva_id', 'status' => 'Published', 'premium' => 1, 'views' => 4560, 'headline' => 'main', 'breaking' => 1],
            ['title' => 'IHSG Menanjak, 5 Saham Catatkan Rekor ARA', 'cat' => 'Market Stock', 'cat_slug' => 'market-stock', 'author' => 'Erla Darwati', 'post_by' => 'erla_d', 'status' => 'Published', 'premium' => 0, 'views' => 2890, 'headline' => 'secondary', 'breaking' => 0],
            ['title' => 'OJK Jatuhkan 1.277 Sanksi ke Pelaku Pasar Modal Sepanjang 2026', 'cat' => 'Market Stock', 'cat_slug' => 'market-stock', 'author' => 'Nasori', 'post_by' => 'raka_h', 'status' => 'Published', 'premium' => 0, 'views' => 1840, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'RI-Singapura Perkuat Penggunaan Mata Uang Lokal Lewat Kerangka LCT', 'cat' => 'Finance Banking', 'cat_slug' => 'finance-banking', 'author' => 'Arnoldus Kristianus', 'post_by' => 'arnoldus_k', 'status' => 'Published', 'premium' => 0, 'views' => 970, 'headline' => 'secondary', 'breaking' => 0],
            ['title' => 'Merger PTPP & ADHI Dipacu Beres Tahun Ini', 'cat' => 'Market Corporate Action', 'cat_slug' => 'market-corporate-action', 'author' => 'Addin Anugrah Siwi', 'post_by' => 'prisma_id', 'status' => 'Published', 'premium' => 1, 'views' => 3120, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Rupiah Kembali Diuji Tekanan Ganda Suku Bunga dan Inflasi Global', 'cat' => 'Market Commodities', 'cat_slug' => 'market-commodities', 'author' => 'Natasha Khairunisa', 'post_by' => 'natasha_k', 'status' => 'Scheduled', 'premium' => 0, 'views' => 150, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Progres Underpass Bitung Capai 56,68%, Ditarget Tuntas Akhir 2026', 'cat' => 'Business Infrastructure', 'cat_slug' => 'business-infrastructure', 'author' => 'Heru Febrianto', 'post_by' => 'ghafur_f', 'status' => 'Published', 'premium' => 0, 'views' => 640, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Mesin Uang Baru TBS Energi Utama (TOBA) dari Ekosistem Motor Listrik', 'cat' => 'Market Corporate Action', 'cat_slug' => 'market-corporate-action', 'author' => 'M. Ghafur Fadillah', 'post_by' => 'ghafur_f', 'status' => 'Review', 'premium' => 1, 'views' => 210, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Prabowo: Saya Sudah Hitung, Jutaan Rakyat akan Keluar dari Kemiskinan', 'cat' => 'Macroeconomy', 'cat_slug' => 'macroeconomy', 'author' => 'Celvin Moniaga Sipahutar', 'post_by' => 'prisma_id', 'status' => 'Draft', 'premium' => 0, 'views' => 88, 'headline' => 'none', 'breaking' => 0],
            
            // 11-20
            ['title' => 'Kinerja Emiten Perbankan KBMI 4 Masih Jadi Penopang Laba IHSG', 'cat' => 'Finance Banking', 'cat_slug' => 'finance-banking', 'author' => 'Raka Herdika', 'post_by' => 'raka_h', 'status' => 'Published', 'premium' => 1, 'views' => 5420, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Harga Minyak Mentah WTI Naik Tipis Pasca Pertemuan OPEC+', 'cat' => 'Market Commodities', 'cat_slug' => 'market-commodities', 'author' => 'Natasha Khairunisa', 'post_by' => 'natasha_k', 'status' => 'Published', 'premium' => 0, 'views' => 1430, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Emiten Teknologi Buka Suara Soal Dampak Regulasi AI Uni Eropa', 'cat' => 'Tech & Digital Assets', 'cat_slug' => 'tech-digital-assets', 'author' => 'Eva Fitriani', 'post_by' => 'eva_id', 'status' => 'Published', 'premium' => 0, 'views' => 2150, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'PLN Operasikan 12 PLTS Baru di Wilayah Indonesia Timur', 'cat' => 'ESG & Green Energy', 'cat_slug' => 'esg-green-energy', 'author' => 'M. Ghafur Fadillah', 'post_by' => 'ghafur_f', 'status' => 'Published', 'premium' => 0, 'views' => 890, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Telkom (TLKM) Perluas Kapasitas Data Center AI Hyperscale', 'cat' => 'Tech & Digital Assets', 'cat_slug' => 'tech-digital-assets', 'author' => 'Erla Darwati', 'post_by' => 'erla_d', 'status' => 'Published', 'premium' => 1, 'views' => 3870, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Defisit Neraca Berjalan RI Kuartal II Tetap Rendah Terkendali', 'cat' => 'Macroeconomy', 'cat_slug' => 'macroeconomy', 'author' => 'Arnoldus Kristianus', 'post_by' => 'arnoldus_k', 'status' => 'Published', 'premium' => 0, 'views' => 1120, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Astra International (ASII) Bukukan Kenaikan Penjualan Mobil Hybrid', 'cat' => 'Market Corporate Action', 'cat_slug' => 'market-corporate-action', 'author' => 'Raka Herdika', 'post_by' => 'raka_h', 'status' => 'Published', 'premium' => 0, 'views' => 2780, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Target Pertumbuhan Ekonomi 5,2% di RAPBN 2027 Dinilai Realistis', 'cat' => 'Macroeconomy', 'cat_slug' => 'macroeconomy', 'author' => 'Prisma ID Desk', 'post_by' => 'prisma_id', 'status' => 'Published', 'premium' => 0, 'views' => 1650, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Smelter Tembaga Baru di Gresik Siap Uji Coba Produksi Komersial', 'cat' => 'Business Infrastructure', 'cat_slug' => 'business-infrastructure', 'author' => 'Natasha Khairunisa', 'post_by' => 'natasha_k', 'status' => 'Published', 'premium' => 1, 'views' => 4110, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Bank Mandiri (BMRI) Siapkan Belanja Modal IT Rp 3,5 Triliun', 'cat' => 'Finance Banking', 'cat_slug' => 'finance-banking', 'author' => 'Eva Fitriani', 'post_by' => 'eva_id', 'status' => 'Published', 'premium' => 0, 'views' => 3290, 'headline' => 'none', 'breaking' => 0],

            // 21-30
            ['title' => 'Peluang Rebound Saham Konsumer Menjelang Periode Kampanye Pemilu Daerah', 'cat' => 'Market Stock', 'cat_slug' => 'market-stock', 'author' => 'Erla Darwati', 'post_by' => 'erla_d', 'status' => 'Published', 'premium' => 0, 'views' => 1980, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Bursa Karbon Indonesia Catatkan Rekor Transaksi Sertifikat Penurunan Emisi', 'cat' => 'ESG & Green Energy', 'cat_slug' => 'esg-green-energy', 'author' => 'M. Ghafur Fadillah', 'post_by' => 'ghafur_f', 'status' => 'Published', 'premium' => 1, 'views' => 2450, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Kuartal III/2026, Penyaluran Kredit Sindikasi Tumbuh 14,2%', 'cat' => 'Finance Banking', 'cat_slug' => 'finance-banking', 'author' => 'Arnoldus Kristianus', 'post_by' => 'arnoldus_k', 'status' => 'Draft', 'premium' => 0, 'views' => 45, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Valuasi Saham Sektor Telekomunikasi Masih Menarik untuk Akumulasi', 'cat' => 'Market Stock', 'cat_slug' => 'market-stock', 'author' => 'Raka Herdika', 'post_by' => 'raka_h', 'status' => 'Published', 'premium' => 1, 'views' => 3650, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Pemerintah Terbitkan Sukuk Ritel SR021, Kupon Diprediksi Menarik', 'cat' => 'Finance Banking', 'cat_slug' => 'finance-banking', 'author' => 'Eva Fitriani', 'post_by' => 'eva_id', 'status' => 'Scheduled', 'premium' => 0, 'views' => 310, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Hilirisasi Nikel Bawa Nilai Tambah Ekspor Tembus US$ 35 Miliar', 'cat' => 'Business Infrastructure', 'cat_slug' => 'business-infrastructure', 'author' => 'Natasha Khairunisa', 'post_by' => 'natasha_k', 'status' => 'Published', 'premium' => 0, 'views' => 2870, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Pergerakan Bitcoin Pasca Halving Terus Membentuk Konsolidasi Sehat', 'cat' => 'Tech & Digital Assets', 'cat_slug' => 'tech-digital-assets', 'author' => 'Erla Darwati', 'post_by' => 'erla_d', 'status' => 'Review', 'premium' => 0, 'views' => 120, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'BBCA Catatkan Pertumbuhan Transaksi Mobile Banking 28% YoY', 'cat' => 'Finance Banking', 'cat_slug' => 'finance-banking', 'author' => 'Raka Herdika', 'post_by' => 'raka_h', 'status' => 'Published', 'premium' => 0, 'views' => 4920, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Prospek Pasar Properti Residensial Menengah Pasca Insentif PPN DTP', 'cat' => 'Business Infrastructure', 'cat_slug' => 'business-infrastructure', 'author' => 'M. Ghafur Fadillah', 'post_by' => 'ghafur_f', 'status' => 'Published', 'premium' => 0, 'views' => 1740, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Rekomendasi Saham Sektor Pangan Pasca Rilis Indeks Ketahanan Pangan', 'cat' => 'Market Stock', 'cat_slug' => 'market-stock', 'author' => 'Prisma ID Desk', 'post_by' => 'prisma_id', 'status' => 'Published', 'premium' => 1, 'views' => 2310, 'headline' => 'none', 'breaking' => 0],

            // 31-40
            ['title' => 'Ekspor Kopi Specialty Indonesia Cetak Rekor Penjualan di Pasar Eropa', 'cat' => 'Market Commodities', 'cat_slug' => 'market-commodities', 'author' => 'Natasha Khairunisa', 'post_by' => 'natasha_k', 'status' => 'Published', 'premium' => 0, 'views' => 1560, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Kesiapan Infrastruktur Pelabuhan Patimban Fase 2 Capai 85%', 'cat' => 'Business Infrastructure', 'cat_slug' => 'business-infrastructure', 'author' => 'Arnoldus Kristianus', 'post_by' => 'arnoldus_k', 'status' => 'Published', 'premium' => 0, 'views' => 1290, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Kuartal IV/2026, 3 Calon Unicorn Siap Listing di Papan Utama IDX', 'cat' => 'Tech & Digital Assets', 'cat_slug' => 'tech-digital-assets', 'author' => 'Eva Fitriani', 'post_by' => 'eva_id', 'status' => 'Published', 'premium' => 1, 'views' => 6100, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Bank Mandiri Taspen Salurkan Kredit Pensiunan Rp 42 Triliun', 'cat' => 'Finance Banking', 'cat_slug' => 'finance-banking', 'author' => 'Raka Herdika', 'post_by' => 'raka_h', 'status' => 'Draft', 'premium' => 0, 'views' => 30, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Strategi Emiten Tambang Batubara Diversifikasi ke Energi Terbarukan', 'cat' => 'ESG & Green Energy', 'cat_slug' => 'esg-green-energy', 'author' => 'M. Ghafur Fadillah', 'post_by' => 'ghafur_f', 'status' => 'Published', 'premium' => 0, 'views' => 2670, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Pemerintah Naikkan Kuota Rumah Subsidi FLPP Jadi 220.000 Unit', 'cat' => 'Macroeconomy', 'cat_slug' => 'macroeconomy', 'author' => 'Erla Darwati', 'post_by' => 'erla_d', 'status' => 'Published', 'premium' => 0, 'views' => 3140, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'GOTO Optimistis Pertahankan EBITDA Positif Sepanjang 2026', 'cat' => 'Market Corporate Action', 'cat_slug' => 'market-corporate-action', 'author' => 'Eva Fitriani', 'post_by' => 'eva_id', 'status' => 'Published', 'premium' => 0, 'views' => 4780, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Pasar Obligasi Korporasi Ramai Penerbitan Green Bond Sektor Energi', 'cat' => 'ESG & Green Energy', 'cat_slug' => 'esg-green-energy', 'author' => 'Natasha Khairunisa', 'post_by' => 'natasha_k', 'status' => 'Scheduled', 'premium' => 1, 'views' => 190, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Bank Danamon dan Adira Finance Perkuat Ekosistem Pembiayaan Otomotif', 'cat' => 'Finance Banking', 'cat_slug' => 'finance-banking', 'author' => 'Arnoldus Kristianus', 'post_by' => 'arnoldus_k', 'status' => 'Published', 'premium' => 0, 'views' => 1890, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Antisipasi El Nino, Bulog Amankan Pasokan Cadangan Beras Pemerintah', 'cat' => 'Macroeconomy', 'cat_slug' => 'macroeconomy', 'author' => 'Prisma ID Desk', 'post_by' => 'prisma_id', 'status' => 'Published', 'premium' => 0, 'views' => 2040, 'headline' => 'none', 'breaking' => 0],

            // 41-50
            ['title' => 'Emiten Farmasi Catat Lonjakan Permintaan Produk Preventif Kesehatan', 'cat' => 'Market Stock', 'cat_slug' => 'market-stock', 'author' => 'Erla Darwati', 'post_by' => 'erla_d', 'status' => 'Review', 'premium' => 0, 'views' => 95, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Kawasan Industri Terpadu Batang Tarik Investasi Asing US$ 1,2 Miliar', 'cat' => 'Business Infrastructure', 'cat_slug' => 'business-infrastructure', 'author' => 'M. Ghafur Fadillah', 'post_by' => 'ghafur_f', 'status' => 'Published', 'premium' => 0, 'views' => 3450, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Penyaluran Pinjaman Fintech Lending Tumbuh Sehat Pasca Regulasi Baru OJK', 'cat' => 'Tech & Digital Assets', 'cat_slug' => 'tech-digital-assets', 'author' => 'Eva Fitriani', 'post_by' => 'eva_id', 'status' => 'Published', 'premium' => 0, 'views' => 1780, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Kesiapan Bandara Dhoho Kediri Dongkrak Potensi Agribisnis Jawa Timur', 'cat' => 'Business Infrastructure', 'cat_slug' => 'business-infrastructure', 'author' => 'Arnoldus Kristianus', 'post_by' => 'arnoldus_k', 'status' => 'Published', 'premium' => 0, 'views' => 1420, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Indo Tambangraya (ITMG) Bagikan Dividen Interim Yield Menarik 8%', 'cat' => 'Market Corporate Action', 'cat_slug' => 'market-corporate-action', 'author' => 'Raka Herdika', 'post_by' => 'raka_h', 'status' => 'Draft', 'premium' => 1, 'views' => 50, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Pemerintah Rilis Peta Jalan Pengembangan Industri Semikonduktor', 'cat' => 'Tech & Digital Assets', 'cat_slug' => 'tech-digital-assets', 'author' => 'Natasha Khairunisa', 'post_by' => 'natasha_k', 'status' => 'Published', 'premium' => 0, 'views' => 2890, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Cadangan Devisa RI Agustus 2026 Naik Menjadi US$ 150,2 Miliar', 'cat' => 'Macroeconomy', 'cat_slug' => 'macroeconomy', 'author' => 'Raka Herdika', 'post_by' => 'raka_h', 'status' => 'Published', 'premium' => 0, 'views' => 5230, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'KAI Logistik Tingkatkan Angkutan Kargo Kereta Api 22% Sepanjang Semester I', 'cat' => 'Business Infrastructure', 'cat_slug' => 'business-infrastructure', 'author' => 'Prisma ID Desk', 'post_by' => 'prisma_id', 'status' => 'Published', 'premium' => 0, 'views' => 1180, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Saham Sektor Kesehatan Masuki Fase Konsolidasi Pasca Reli Kuartal II', 'cat' => 'Market Stock', 'cat_slug' => 'market-stock', 'author' => 'Erla Darwati', 'post_by' => 'erla_d', 'status' => 'Draft', 'premium' => 0, 'views' => 40, 'headline' => 'none', 'breaking' => 0],
            ['title' => 'Pemerintah Pastikan Pasokan BBM dan Gas Elpiji Aman Hadapi Akhir Tahun', 'cat' => 'Macroeconomy', 'cat_slug' => 'macroeconomy', 'author' => 'M. Ghafur Fadillah', 'post_by' => 'ghafur_f', 'status' => 'Scheduled', 'premium' => 0, 'views' => 230, 'headline' => 'none', 'breaking' => 0],
        ];

        $images = ['/img/article1.jpg', '/img/article2.jpg', '/img/article3.jpg', '/img/article4.jpg', '/img/article5.jpg', '/img/article6.jpg'];
        $posts = [];

        foreach ($rawArticles as $i => $item) {
            $id = $i + 1;
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $item['title'])));
            $hoursAgo = ($i * 2) + 1;
            $createTime = date('d M Y H:i', strtotime("-{$hoursAgo} hours"));

            $posts[] = [
                'id' => $id,
                'title' => $item['title'],
                'slug' => $slug,
                'author' => $item['author'],
                'category' => $item['cat'],
                'category_slug' => $item['cat_slug'],
                'post_by' => $item['post_by'],
                'create_date' => $createTime,
                'publish_date' => $createTime,
                'status' => $item['status'],
                'is_premium' => $item['premium'],
                'views' => $item['views'],
                'thumbnail' => $images[$i % count($images)],
                'summary' => "Ulasan komprehensif mengenai {$item['title']} dalam lanskap ekonomi dan pasar keuangan Indonesia.",
                'content' => "<p>Laporan mendalam mengenai <strong>{$item['title']}</strong> yang dirilis oleh redaksi Investor.id.</p><p>Analisis sentimen pasar, katalis makroekonomi, dan valuasi fundamental menjadi fokus utama para pelaku pasar dan investor institusional.</p>",
                'headline_type' => $item['headline'],
                'is_breaking' => $item['breaking'],
            ];
        }

        return $posts;
    }
}

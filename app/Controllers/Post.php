<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use App\Models\CategoryModel;
use App\Models\AuthorModel;
use App\Models\MediaModel;
use App\Models\AdminModel;
use App\Models\ChartModel;
use App\Models\TagModel;

class Post extends BaseController
{
    protected ArticleModel $articleModel;
    protected CategoryModel $categoryModel;
    protected AuthorModel $authorModel;
    protected MediaModel $mediaModel;
    protected AdminModel $adminModel;
    protected ChartModel $chartModel;
    protected TagModel $tagModel;

    public function __construct()
    {
        $this->articleModel = new ArticleModel();
        $this->categoryModel = new CategoryModel();
        $this->authorModel = new AuthorModel();
        $this->mediaModel = new MediaModel();
        $this->adminModel = new AdminModel();
        $this->chartModel = new ChartModel();
        $this->tagModel = new TagModel();
    }

    public function index()
    {
        $kanals = $this->categoryModel->getKanalTree();
        $users = $this->adminModel->select('
                ref_entity_principal_id AS id,
                attr_display_name AS name,
                attr_identifier AS username,
                attr_contact_val AS email,
                attr_role_code AS role
            ')->findAll();

        // Filtering parameters
        $keyword = $this->request->getGet('q');
        $kanalFilter = $this->request->getGet('kanal') ?: $this->request->getGet('category');
        $statusFilter = $this->request->getGet('status');
        $premiumFilter = $this->request->getGet('premium');
        $authorFilter = $this->request->getGet('author');
        $editorFilter = $this->request->getGet('editor');
        $dateFromFilter = $this->request->getGet('date_from');
        $dateToFilter = $this->request->getGet('date_to');

        $userRole = session('user_role') ?? 'Admin';
        $userId = session('user_id');

        $filters = [
            'q'         => $keyword,
            'kanal'     => $kanalFilter,
            'status'    => $statusFilter,
            'premium'   => $premiumFilter,
            'author'    => $authorFilter,
            'editor'    => $editorFilter,
            'date_from' => $dateFromFilter,
            'date_to'   => $dateToFilter,
        ];

        // RBAC: Author & Contributor only see their own articles
        if (in_array(strtolower($userRole), ['author', 'contributor'])) {
            $filters['user_id'] = $userId;
        }

        $page = max(1, (int)($this->request->getGet('page') ?? 1));
        $perPage = 25;

        $result = $this->articleModel->getArticlesWithRelations($filters, $page, $perPage);
        $totalItems = $result['total'];
        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $perPage;

        return view('post/index', [
            'title'      => 'Articles & Posts',
            'breadcrumb' => 'Post / All Articles',
            'posts'      => $result['data'],
            'kanals'     => $kanals,
            'users'      => $users,
            'filters'    => [
                'q'         => $keyword,
                'kanal'     => $kanalFilter ?? 'all',
                'status'    => $statusFilter ?? 'all',
                'premium'   => $premiumFilter ?? 'all',
                'author'    => $authorFilter ?? 'all',
                'editor'    => $editorFilter ?? 'all',
                'date_from' => $dateFromFilter ?? '',
                'date_to'   => $dateToFilter ?? '',
            ],
            'pagination' => [
                'page'       => $page,
                'perPage'    => $perPage,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
                'from'       => $totalItems > 0 ? $offset + 1 : 0,
                'to'         => min($offset + $perPage, $totalItems),
            ]
        ]);
    }

    public function new()
    {
        $kanals = $this->categoryModel->getKanalTree();
        $users = $this->adminModel->select('
                ref_entity_principal_id AS id,
                attr_display_name AS name,
                attr_identifier AS username,
                attr_contact_val AS email,
                attr_role_code AS role
            ')->findAll();
        $media = $this->mediaModel->getMediaWithRelations(1, 30)['data'];
        $allPosts = $this->articleModel->getArticlesWithRelations([], 1, 50)['data'];
        $charts = $this->chartModel->getChartsList();

        return view('post/editor', [
            'title'      => 'Create New Post',
            'breadcrumb' => 'Post / New Article',
            'kanals'     => $kanals,
            'categories' => $kanals,
            'users'      => $users,
            'media'      => $media,
            'charts'     => $charts,
            'allPosts'   => $allPosts,
            'article'    => null,
            'isEdit'     => false
        ]);
    }

    public function edit($id)
    {
        $article = $this->articleModel->getArticleDetail((int)$id);

        if (!$article) {
            return redirect()->to('/post')->with('error', 'Article not found');
        }

        $userRole = session('user_role') ?? 'Admin';
        $userId = (int)(session('user_id') ?: 0);

        // RBAC: Author & Contributor can only edit their own articles
        if (in_array(strtolower($userRole), ['author', 'contributor'])) {
            if (!empty($article['author_user_id']) && (int)$article['author_user_id'] !== $userId) {
                return redirect()->to('/post')->with('error', 'Akses ditolak: Anda hanya dapat mengedit artikel naskah milik Anda sendiri.');
            }
        }

        $kanals = $this->categoryModel->getKanalTree();
        $users = $this->adminModel->select('
                ref_entity_principal_id AS id,
                attr_display_name AS name,
                attr_identifier AS username,
                attr_contact_val AS email,
                attr_role_code AS role
            ')->findAll();
        $media = $this->mediaModel->getMediaWithRelations(1, 30)['data'];
        $allPosts = $this->articleModel->getArticlesWithRelations([], 1, 50)['data'];
        $charts = $this->chartModel->getChartsList();

        return view('post/editor', [
            'title'      => 'Edit Post: ' . $article['title'],
            'breadcrumb' => 'Post / Edit Article',
            'kanals'     => $kanals,
            'categories' => $kanals,
            'users'      => $users,
            'media'      => $media,
            'charts'     => $charts,
            'allPosts'   => $allPosts,
            'article'    => $article,
            'isEdit'     => true
        ]);
    }

    public function save()
    {
        $userRole = session('user_role') ?? 'Admin';
        $sessionUser = session()->get('user_id') ?: 1;

        $title = $this->request->getPost('title');
        $kanalName = $this->request->getPost('kanal') ?? 'Market';
        $rawSubKanal = $this->request->getPost('sub_kanal');
        if (is_array($rawSubKanal)) {
            $subKanal = implode(', ', array_filter($rawSubKanal));
        } else {
            $subKanal = $rawSubKanal ?? '';
        }

        $sumberBerita = $this->request->getPost('source') ?: ($this->request->getPost('sumber_berita') ?: 'Investor.id / Riset');
        $authorName = $this->request->getPost('author') ?: 'Raka Herdika';
        $authorId = $this->authorModel->findOrCreate($authorName);

        // Find or fallback category
        $category = null;
        if (!empty($subKanal)) {
            $category = $this->categoryModel->where('charlie_name_a', $subKanal)->first();
        }
        if (!$category) {
            $category = $this->categoryModel->where('charlie_name_a', $kanalName)->first();
        }
        $categoryId = $category ? (int)$category['ref_entity_charlie_id'] : 1;

        $points = [
            $this->request->getPost('point_1') ?: '',
            $this->request->getPost('point_2') ?: '',
            $this->request->getPost('point_3') ?: ''
        ];
        $points = array_values(array_filter($points, fn($pt) => trim($pt) !== ''));

        $rawTags = $this->request->getPost('tags') ?? '';
        $tags = array_values(array_filter(array_map('trim', explode(',', $rawTags))));

        // Parse & normalize publish_date
        $rawPublishDate = $this->request->getPost('publish_date');
        $publishDate = date('Y-m-d H:i:s');
        if (!empty($rawPublishDate)) {
            $ts = strtotime($rawPublishDate);
            if ($ts !== false && $ts > 0) {
                $publishDate = date('Y-m-d H:i:s', $ts);
            }
        }

        // Status rule for Author/Contributor
        $status = $this->request->getPost('status') ?? 'Published';
        if (in_array(strtolower($userRole), ['author', 'contributor'])) {
            if (strtolower($status) === 'published') {
                $status = 'Review';
            }
        }

        $articleData = [
            'title'             => $title,
            'slug'              => url_title($title, '-', true),
            'summary'           => $this->request->getPost('summary') ?? '',
            'content'           => $this->request->getPost('content') ?? '',
            'status'            => $status,
            'publish_date'      => $publishDate,
            'thumbnail'         => $this->request->getPost('thumbnail') ?: '/img/article1.jpg',
            'upper_deck'        => $this->request->getPost('upper_deck') ?? '',
            'source'            => $sumberBerita,
            'kanal'             => $kanalName,
            'sub_kanal'         => $subKanal,
            'is_premium'        => (int)($this->request->getPost('is_premium') ?? 0),
            'headline_type'     => $this->request->getPost('headline_type') ?? 'none',
            'ref_entity_acc_id' => $sessionUser,
        ];

        $this->articleModel->createArticle($articleData, $authorId, $categoryId, $tags, $points);

        return redirect()->to('/post')->with('success', 'Article successfully created and stored in database!');
    }

    public function update($id)
    {
        $article = $this->articleModel->getArticleDetail((int)$id);
        if (!$article) {
            return redirect()->to('/post')->with('error', 'Article not found');
        }

        $userRole = session('user_role') ?? 'Admin';
        $userId = (int)(session('user_id') ?: 0);

        // RBAC: Author & Contributor can only update their own articles
        if (in_array(strtolower($userRole), ['author', 'contributor'])) {
            if (!empty($article['author_user_id']) && (int)$article['author_user_id'] !== $userId) {
                return redirect()->to('/post')->with('error', 'Akses ditolak: Anda tidak memiliki izin memperbarui artikel ini.');
            }
        }

        $title = $this->request->getPost('title');
        $kanalName = $this->request->getPost('kanal') ?? 'Market';
        $rawSubKanal = $this->request->getPost('sub_kanal');
        if (is_array($rawSubKanal)) {
            $subKanal = implode(', ', array_filter($rawSubKanal));
        } else {
            $subKanal = $rawSubKanal ?? '';
        }

        $sumberBerita = $this->request->getPost('source') ?: ($this->request->getPost('sumber_berita') ?: 'Investor.id / Riset');
        $authorName = $this->request->getPost('author') ?: 'Raka Herdika';
        $authorId = $this->authorModel->findOrCreate($authorName);

        // Find or fallback category
        $category = null;
        if (!empty($subKanal)) {
            $category = $this->categoryModel->where('charlie_name_a', $subKanal)->first();
        }
        if (!$category) {
            $category = $this->categoryModel->where('charlie_name_a', $kanalName)->first();
        }
        $categoryId = $category ? (int)$category['ref_entity_charlie_id'] : 1;

        $points = [
            $this->request->getPost('point_1') ?: '',
            $this->request->getPost('point_2') ?: '',
            $this->request->getPost('point_3') ?: ''
        ];
        $points = array_values(array_filter($points, fn($pt) => trim($pt) !== ''));

        $rawTags = $this->request->getPost('tags') ?? '';
        $tags = array_values(array_filter(array_map('trim', explode(',', $rawTags))));

        $sessionUser = session()->get('user_id') ?: 1;

        // Parse & normalize publish_date
        $rawPublishDate = $this->request->getPost('publish_date');
        $publishDate = !empty($article['publish_date']) ? $article['publish_date'] : date('Y-m-d H:i:s');
        if (!empty($rawPublishDate)) {
            $ts = strtotime($rawPublishDate);
            if ($ts !== false && $ts > 0) {
                $publishDate = date('Y-m-d H:i:s', $ts);
            }
        }

        // Status rule for Author/Contributor
        $status = $this->request->getPost('status') ?? 'Published';
        if (in_array(strtolower($userRole), ['author', 'contributor'])) {
            if (strtolower($status) === 'published') {
                $status = 'Review';
            }
        }

        $articleData = [
            'title'         => $title,
            'summary'       => $this->request->getPost('summary') ?? '',
            'content'       => $this->request->getPost('content') ?? '',
            'status'        => $status,
            'publish_date'  => $publishDate,
            'thumbnail'     => $this->request->getPost('thumbnail') ?: '/img/article1.jpg',
            'upper_deck'    => $this->request->getPost('upper_deck') ?? '',
            'source'        => $sumberBerita,
            'kanal'         => $kanalName,
            'sub_kanal'     => $subKanal,
            'is_premium'    => (int)($this->request->getPost('is_premium') ?? 0),
            'headline_type' => $this->request->getPost('headline_type') ?? 'none',
        ];

        $this->articleModel->updateArticle((int)$id, $articleData, $authorId, $categoryId, $tags, $points, (int)$sessionUser);

        return redirect()->to('/post')->with('success', 'Article updated successfully!');
    }

    public function delete($id)
    {
        $userRole = session('user_role') ?? 'Admin';
        $userId = (int)(session('user_id') ?: 0);

        // RBAC: Author & Contributor cannot delete articles
        if (in_array(strtolower($userRole), ['author', 'contributor'])) {
            return redirect()->to('/post')->with('error', 'Akses ditolak: Akun dengan role ' . esc($userRole) . ' tidak memiliki hak menghapus artikel.');
        }

        $this->articleModel->deleteArticle((int)$id);
        return redirect()->to('/post')->with('success', 'Article removed successfully from database.');
    }

    public function bulkAction()
    {
        $action = $this->request->getPost('action');
        $selectedIds = $this->request->getPost('selected_ids');

        if (!empty($selectedIds)) {
            $ids = is_array($selectedIds) ? $selectedIds : explode(',', $selectedIds);
            $ids = array_map('intval', array_filter($ids));

            if ($action === 'publish') {
                $this->articleModel->whereIn('ref_entity_nano_id', $ids)->set(['status_code' => 'Published'])->update();
                return redirect()->to('/post')->with('success', count($ids) . ' articles marked as Published.');
            } elseif ($action === 'draft') {
                $this->articleModel->whereIn('ref_entity_nano_id', $ids)->set(['status_code' => 'Draft'])->update();
                return redirect()->to('/post')->with('success', count($ids) . ' articles moved to Draft.');
            } elseif ($action === 'delete') {
                foreach ($ids as $delId) {
                    $this->articleModel->deleteArticle($delId);
                }
                return redirect()->to('/post')->with('success', count($ids) . ' articles deleted.');
            }
        }

        return redirect()->to('/post');
    }

    public function export()
    {
        $posts = $this->articleModel->getArticlesWithRelations([], 1, 1000)['data'];
        $filename = 'investor_articles_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Title', 'Author', 'Category', 'Created Date', 'Publish Date', 'Status', 'Views', 'Is Premium']);

        foreach ($posts as $row) {
            fputcsv($output, [
                $row['id'],
                $row['title'],
                $row['author'],
                $row['category'],
                $row['create_date'],
                $row['publish_date'],
                $row['status'],
                $row['views'],
                $row['is_premium'] ? 'Yes' : 'No'
            ]);
        }
        fclose($output);
        exit;
    }

    public function searchTags()
    {
        $q = trim($this->request->getGet('q') ?? '');
        if (empty($q)) {
            return $this->response->setJSON([]);
        }

        $tags = $this->tagModel->like('field_label_a', $q)
            ->select('ref_entity_teta_id as id, field_label_a as label, slug_value as slug')
            ->orderBy('field_label_a', 'ASC')
            ->findAll(15);

        return $this->response->setJSON($tags);
    }

    public function searchStocks()
    {
        $q = trim($this->request->getGet('q') ?? '');
        $qUpper = strtoupper($q);

        // Comprehensive Master List of Indonesia Stock Exchange (IDX) Listed Companies
        $allStocks = [
            ['ticker' => 'AADI', 'name' => 'Adaro Andalan Indonesia Tbk', 'sector' => 'Energy / Coal Mining'],
            ['ticker' => 'ADRO', 'name' => 'Adaro Energy Indonesia Tbk', 'sector' => 'Energy / Coal Mining'],
            ['ticker' => 'AKRA', 'name' => 'AKR Corporindo Tbk', 'sector' => 'Energy / Fuel Logistics'],
            ['ticker' => 'AMMN', 'name' => 'Amman Mineral Internasional Tbk', 'sector' => 'Basic Materials / Copper & Gold'],
            ['ticker' => 'ANTM', 'name' => 'Aneka Tambang Tbk', 'sector' => 'Basic Materials / Nickel & Gold (Antam)'],
            ['ticker' => 'ARTO', 'name' => 'Bank Jago Tbk', 'sector' => 'Financials / Digital Banking'],
            ['ticker' => 'ASII', 'name' => 'Astra International Tbk', 'sector' => 'Consumer Cyclical / Conglomerate & Otomotif (Astra)'],
            ['ticker' => 'AUTO', 'name' => 'Astra Otoparts Tbk', 'sector' => 'Consumer Cyclical / Auto Parts'],
            ['ticker' => 'BBCA', 'name' => 'Bank Central Asia Tbk', 'sector' => 'Financials / Banking (BCA)'],
            ['ticker' => 'BBNI', 'name' => 'Bank Negara Indonesia Tbk', 'sector' => 'Financials / Banking (BNI)'],
            ['ticker' => 'BBRI', 'name' => 'Bank Rakyat Indonesia Tbk', 'sector' => 'Financials / Banking (BRI)'],
            ['ticker' => 'BBTN', 'name' => 'Bank Tabungan Negara Tbk', 'sector' => 'Financials / Banking (BTN)'],
            ['ticker' => 'BDMN', 'name' => 'Bank Danamon Indonesia Tbk', 'sector' => 'Financials / Banking (Danamon)'],
            ['ticker' => 'BFIN', 'name' => 'BFI Finance Indonesia Tbk', 'sector' => 'Financials / Multi Finance'],
            ['ticker' => 'BMRI', 'name' => 'Bank Mandiri (Persero) Tbk', 'sector' => 'Financials / Banking (Mandiri)'],
            ['ticker' => 'BNGA', 'name' => 'Bank CIMB Niaga Tbk', 'sector' => 'Financials / Banking (CIMB)'],
            ['ticker' => 'BRIS', 'name' => 'Bank Syariah Indonesia Tbk', 'sector' => 'Financials / Islamic Banking (BSI)'],
            ['ticker' => 'BREN', 'name' => 'Barito Renewables Energy Tbk', 'sector' => 'Energy / Geothermal Renewable'],
            ['ticker' => 'BRPT', 'name' => 'Barito Pacific Tbk', 'sector' => 'Basic Materials / Petrochemical & Energy'],
            ['ticker' => 'BUKA', 'name' => 'Bukalapak.com Tbk', 'sector' => 'Technology / E-commerce (Bukalapak)'],
            ['ticker' => 'BUMI', 'name' => 'Bumi Resources Tbk', 'sector' => 'Energy / Coal Mining (Bumi)'],
            ['ticker' => 'CPIN', 'name' => 'Charoen Pokphand Indonesia Tbk', 'sector' => 'Consumer Non-Cyclicals / Poultry'],
            ['ticker' => 'CTRA', 'name' => 'Ciputra Development Tbk', 'sector' => 'Real Estate / Property (Ciputra)'],
            ['ticker' => 'EMTK', 'name' => 'Elang Mahkota Teknologi Tbk', 'sector' => 'Communication Services / Media & Tech (Emtek)'],
            ['ticker' => 'ERAA', 'name' => 'Erajaya Swasembada Tbk', 'sector' => 'Consumer Cyclical / Retail Gadget & Smartphone (Erafone)'],
            ['ticker' => 'ESSA', 'name' => 'ESSA Industries Indonesia Tbk', 'sector' => 'Basic Materials / Ammonia & LPG'],
            ['ticker' => 'EXCL', 'name' => 'XL Axiata Tbk', 'sector' => 'Communication Services / Telco (XL Axiata)'],
            ['ticker' => 'GOTO', 'name' => 'GoTo Gojek Tokopedia Tbk', 'sector' => 'Technology / Ride Hailing & E-commerce (Gojek Tokopedia)'],
            ['ticker' => 'GGRM', 'name' => 'Gudang Garam Tbk', 'sector' => 'Consumer Non-Cyclicals / Tobacco (Gudang Garam)'],
            ['ticker' => 'HEAL', 'name' => 'Medikaloka Hermina Tbk', 'sector' => 'Healthcare / Hospitals (Hermina)'],
            ['ticker' => 'HMSP', 'name' => 'H.M. Sampoerna Tbk', 'sector' => 'Consumer Non-Cyclicals / Tobacco (Sampoerna)'],
            ['ticker' => 'ICBP', 'name' => 'Indofood CBP Sukses Makmur Tbk', 'sector' => 'Consumer Non-Cyclicals / F&B (Indomie)'],
            ['ticker' => 'INCO', 'name' => 'Vale Indonesia Tbk', 'sector' => 'Basic Materials / Nickel Mining'],
            ['ticker' => 'INDF', 'name' => 'Indofood Sukses Makmur Tbk', 'sector' => 'Consumer Non-Cyclicals / Food Holding (Indofood)'],
            ['ticker' => 'INDY', 'name' => 'Indika Energy Tbk', 'sector' => 'Energy / Diversified'],
            ['ticker' => 'INKP', 'name' => 'Indah Kiat Pulp & Paper Tbk', 'sector' => 'Basic Materials / Pulp & Paper (Sinarmas)'],
            ['ticker' => 'INTP', 'name' => 'Indocement Tunggal Prakarsa Tbk', 'sector' => 'Basic Materials / Cement (Tiga Roda)'],
            ['ticker' => 'ISAT', 'name' => 'Indosat Ooredoo Hutchison Tbk', 'sector' => 'Communication Services / Telco (Indosat IM3)'],
            ['ticker' => 'ITMG', 'name' => 'Indo Tambangraya Megah Tbk', 'sector' => 'Energy / Coal Mining'],
            ['ticker' => 'JPFA', 'name' => 'JAPFA Comfeed Indonesia Tbk', 'sector' => 'Consumer Non-Cyclicals / Poultry'],
            ['ticker' => 'JSMR', 'name' => 'Jasa Marga (Persero) Tbk', 'sector' => 'Infrastructure / Toll Road Operator (Jasa Marga)'],
            ['ticker' => 'KLBF', 'name' => 'Kalbe Farma Tbk', 'sector' => 'Healthcare / Pharmaceuticals (Kalbe)'],
            ['ticker' => 'MAPI', 'name' => 'Mitra Adiperkasa Tbk', 'sector' => 'Consumer Cyclical / Retail Lifestyle (MAP)'],
            ['ticker' => 'MBMA', 'name' => 'Merdeka Battery Materials Tbk', 'sector' => 'Basic Materials / EV Battery & Nickel'],
            ['ticker' => 'MDKA', 'name' => 'Merdeka Copper Gold Tbk', 'sector' => 'Basic Materials / Gold & Copper'],
            ['ticker' => 'MEDC', 'name' => 'Medco Energi Internasional Tbk', 'sector' => 'Energy / Oil & Gas (Medco)'],
            ['ticker' => 'MIKA', 'name' => 'Mitra Keluarga Karyasehat Tbk', 'sector' => 'Healthcare / Hospitals (Mitra Keluarga)'],
            ['ticker' => 'MNCN', 'name' => 'Media Nusantara Citra Tbk', 'sector' => 'Communication Services / Media TV (MNC)'],
            ['ticker' => 'MYOR', 'name' => 'Mayora Indah Tbk', 'sector' => 'Consumer Non-Cyclicals / F&B (Mayora)'],
            ['ticker' => 'NISP', 'name' => 'Bank OCBC NISP Tbk', 'sector' => 'Financials / Banking (OCBC)'],
            ['ticker' => 'PGAS', 'name' => 'Perusahaan Gas Negara Tbk', 'sector' => 'Utilities / Gas Distribution (PGN)'],
            ['ticker' => 'PGEO', 'name' => 'Pertamina Geothermal Energy Tbk', 'sector' => 'Energy / Geothermal (Pertamina)'],
            ['ticker' => 'PTBA', 'name' => 'Bukit Asam Tbk', 'sector' => 'Energy / Coal Mining (Bukit Asam)'],
            ['ticker' => 'PTPP', 'name' => 'PP (Persero) Tbk', 'sector' => 'Industrials / Construction (BUMN)'],
            ['ticker' => 'PWON', 'name' => 'Pakuwon Jati Tbk', 'sector' => 'Real Estate / Property & Mall'],
            ['ticker' => 'SCMA', 'name' => 'Surya Citra Media Tbk', 'sector' => 'Communication Services / SCTV Indosiar'],
            ['ticker' => 'SILO', 'name' => 'Siloam International Hospitals Tbk', 'sector' => 'Healthcare / Hospitals (Siloam)'],
            ['ticker' => 'SMGR', 'name' => 'Semen Indonesia (Persero) Tbk', 'sector' => 'Basic Materials / Cement (SIG)'],
            ['ticker' => 'SMMA', 'name' => 'Sinar Mas Multiartha Tbk', 'sector' => 'Financials / Multi Finance'],
            ['ticker' => 'SMRA', 'name' => 'Summarecon Agung Tbk', 'sector' => 'Real Estate / Property (Summarecon)'],
            ['ticker' => 'SRTG', 'name' => 'Saratoga Investama Sedaya Tbk', 'sector' => 'Financials / Investment (Saratoga)'],
            ['ticker' => 'TBIG', 'name' => 'Tower Bersama Infrastructure Tbk', 'sector' => 'Infrastructure / Telco Towers'],
            ['ticker' => 'TKIM', 'name' => 'Pabrik Kertas Tjiwi Kimia Tbk', 'sector' => 'Basic Materials / Paper'],
            ['ticker' => 'TLKM', 'name' => 'Telkom Indonesia (Persero) Tbk', 'sector' => 'Communication Services / Telco (Telkomsel)'],
            ['ticker' => 'TOWR', 'name' => 'Sarana Menara Nusantara Tbk', 'sector' => 'Infrastructure / Telco Towers'],
            ['ticker' => 'TPIA', 'name' => 'Chandra Asri Pacific Tbk', 'sector' => 'Basic Materials / Petrochemical'],
            ['ticker' => 'UNTR', 'name' => 'United Tractors Tbk', 'sector' => 'Industrials / Heavy Equipment & Mining'],
            ['ticker' => 'UNVR', 'name' => 'Unilever Indonesia Tbk', 'sector' => 'Consumer Non-Cyclicals / Personal Care (Unilever)'],
            ['ticker' => 'WIKA', 'name' => 'Wijaya Karya (Persero) Tbk', 'sector' => 'Industrials / Construction (BUMN)'],
            ['ticker' => 'WSKT', 'name' => 'Waskita Karya (Persero) Tbk', 'sector' => 'Industrials / Construction (BUMN)'],
        ];

        if (empty($q)) {
            return $this->response->setJSON(array_slice($allStocks, 0, 10));
        }

        $results = [];
        $seen = [];

        // 1. Search local curated list first
        foreach ($allStocks as $item) {
            $matched = false;
            if (str_starts_with($item['ticker'], $qUpper)) {
                $matched = true;
            } elseif (str_contains($item['ticker'], $qUpper)) {
                $matched = true;
            } elseif (stripos($item['name'], $q) !== false || stripos($item['sector'], $q) !== false) {
                $matched = true;
            }

            if ($matched) {
                $results[] = $item;
                $seen[$item['ticker']] = true;
            }
        }

        // 2. Query Yahoo Finance API for live IDX stock search if needed
        if (count($results) < 8 && strlen($q) >= 2) {
            try {
                $ctx = stream_context_create([
                    'http' => [
                        'timeout' => 2,
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0',
                    ]
                ]);
                $apiUrl = 'https://query1.finance.yahoo.com/v1/finance/search?q=' . urlencode($q) . '&quotesCount=12';
                $json = @file_get_contents($apiUrl, false, $ctx);
                if ($json) {
                    $resp = json_decode($json, true);
                    if (!empty($resp['quotes'])) {
                        foreach ($resp['quotes'] as $quote) {
                            $sym = $quote['symbol'] ?? '';
                            $exchange = $quote['exchange'] ?? '';
                            if (str_ends_with($sym, '.JK') || $exchange === 'JKT') {
                                $cleanTicker = str_replace('.JK', '', $sym);
                                if (!isset($seen[$cleanTicker])) {
                                    $seen[$cleanTicker] = true;
                                    $results[] = [
                                        'ticker' => $cleanTicker,
                                        'name'   => $quote['longname'] ?? $quote['shortname'] ?? ($cleanTicker . ' Tbk'),
                                        'sector' => $quote['sector'] ?? $quote['industry'] ?? 'Indonesia Stock Exchange (IDX)',
                                    ];
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Graceful fallback
            }
        }

        return $this->response->setJSON(array_slice($results, 0, 15));
    }
}

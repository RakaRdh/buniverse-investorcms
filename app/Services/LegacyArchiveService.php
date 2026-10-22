<?php

namespace App\Services;

use App\Models\TrendingModel;
use App\Models\TrendingExcludeModel;
use App\Models\TagModel;
use App\Models\User2FAuthModel;
use App\Models\CommentModel;
use App\Models\GoogleDocsModel;

/**
 * Class LegacyArchiveService
 *
 * @category Discontinued / Archival Track Record Service
 * @description Service terpadu untuk memelihara, menginspeksi, dan mengelola seluruh fitur
 * dan skema basis data legacy yang berstatus DISCONTINUED pada CMS Investor.id.
 *
 * Seluruh logic di sini TIDAK DITAMPILKAN pada antarmuka visual CMS Redaksi harian,
 * melainkan disimpan murni sebagai TRACK RECORD fungsionalitas historis, audit kontinuitas data,
 * dan referensi backward compatibility jika sewaktu-waktu data lama dibutuhkan.
 */
class LegacyArchiveService
{
    protected TrendingModel $trendingModel;
    protected TrendingExcludeModel $trendingExcludeModel;
    protected TagModel $tagModel;
    protected User2FAuthModel $user2fauthModel;
    protected CommentModel $commentModel;
    protected GoogleDocsModel $googleDocsModel;

    public function __construct()
    {
        $this->trendingModel = new TrendingModel();
        $this->trendingExcludeModel = new TrendingExcludeModel();
        $this->tagModel = new TagModel();
        $this->user2fauthModel = new User2FAuthModel();
        $this->commentModel = new CommentModel();
        $this->googleDocsModel = new GoogleDocsModel();
    }

    /**
     * Mengembalikan ringkasan status inventarisasi seluruh modul discontinued
     */
    public function getDiscontinuedFeaturesInventory(): array
    {
        return [
            [
                'feature_key'   => 'trending_crud',
                'name'          => 'Manajemen Trending Topics Terpisah',
                'legacy_files'  => ['pages/article/trending.php', 'adm_trending.tpl', 'adm_trendingform.tpl'],
                'db_tables'     => ['tblTrending', 'tblTrendingExclude'],
                'status'        => 'DISCONTINUED',
                'reason'        => '0 data di produksi; operasional redaksi sudah 100% beralih ke Headline kategori trending (tblArticleHeadline).',
                'action_taken'  => 'Kode model dan service tetap disediakan untuk track record, namun menu disembunyikan dari CMS.',
            ],
            [
                'feature_key'   => 'legacy_tag',
                'name'          => 'Master Tag Legacy',
                'legacy_files'  => ['pages/article/tag.php', 'Tag.class.php', 'adm_taglist.tpl'],
                'db_tables'     => ['tblTag', 'tblArticleTag'],
                'status'        => 'DISCONTINUED',
                'reason'        => '0 data di produksi; sistem redaksi 100% menggunakan tblKeyword (37k entri) dan tblArticleKeyword (1.36M entri).',
                'action_taken'  => 'Model TagModel disediakan sebagai track record skema, CMS UI murni menggunakan Master Keyword.',
            ],
            [
                'feature_key'   => 'user2fauth_table',
                'name'          => 'Tabel Orphan 2FA (user2FAuth)',
                'legacy_files'  => ['Tidak pernah dipanggil di Smarty CMS'],
                'db_tables'     => ['user2FAuth'],
                'status'        => 'DISCONTINUED / ORPHAN',
                'reason'        => 'Tabel yatim berisi 1 baris uji coba lama; 2FA aktif Smarty & Revamp 100% hidup di tblUserAdministrator.',
                'action_taken'  => 'Model User2FAuthModel disediakan sebagai cermin skema, autentikasi aktif tetap pada AdminModel & tblUserAdministrator.',
            ],
            [
                'feature_key'   => 'google_docs_sync',
                'name'          => 'Sinkronisasi File Google Docs',
                'legacy_files'  => ['pages/files/googledocs.php', 'pages/files/api_googledocs.php'],
                'db_tables'     => ['tblGoogleDocs'],
                'status'        => 'DISCONTINUED (Legacy Integration)',
                'reason'        => 'Tidak tercantum di menu sidebar Smarty; jurnalis modern menyisipkan embed Google Sheet/Doc via iframe di CKEditor.',
                'action_taken'  => 'Model GoogleDocsModel dipertahankan, menu disembunyikan dari CMS utama agar antarmuka ringkas.',
            ],
            [
                'feature_key'   => 'jg_poets',
                'name'          => 'Tipe Konten Puisi (jg-poets)',
                'legacy_files'  => ['pages/article/jg-poets.php'],
                'db_tables'     => ['Tidak memiliki tabel khusus, memodifikasi tipe naskah'],
                'status'        => 'DISCONTINUED / OBSOLETE',
                'reason'        => 'Residu portal Jakarta Globe (2011–2018); Investor.id adalah portal finansial dan pasar modal.',
                'action_taken'  => 'Ditinggalkan dan diarsipkan dalam dokumentasi komparasi.',
            ],
            [
                'feature_key'   => 'reader_comments',
                'name'          => 'Komentar Pembaca Portal',
                'legacy_files'  => ['Tidak ada antarmuka moderasi di Smarty CMS'],
                'db_tables'     => ['tblCommentar'],
                'status'        => 'DISCONTINUED DI EDITORIAL CMS',
                'reason'        => 'Hanya ada 52 data uji coba 2019-2020; redaksi saat ini tidak memoderasi komentar publik.',
                'action_taken'  => 'Model CommentModel disediakan untuk akses 52 data historis jika dibutuhkan.',
            ],
            [
                'feature_key'   => 'image_captcha',
                'name'          => 'Image CAPTCHA (Imagick Binary Native)',
                'legacy_files'  => ['pages/login/captcha.php'],
                'db_tables'     => ['None'],
                'status'        => 'DISCONTINUED',
                'reason'        => 'Ketergantungan binary Imagick C berat; digantikan oleh 2FA OTP Email, CSRF Token, dan rate-limiting.',
                'action_taken'  => 'Digantikan oleh standar keamanan CI4 modern.',
            ],
            [
                'feature_key'   => 'social_autopost',
                'name'          => 'Auto-Post Twitter Codebird & Facebook Webhook',
                'legacy_files'  => ['pages/article/index.php (L327)', 'cron/crontab.txt'],
                'db_tables'     => ['None'],
                'status'        => 'DISCONTINUED',
                'reason'        => 'Twitter API v1.1 ditutup permanen per Juli 2023; dialihkan ke tim medsos organik.',
                'action_taken'  => 'Dihapus dari alur penyimpanan naskah.',
            ],
        ];
    }

    /**
     * Mengakses TrendingModel
     */
    public function getTrendingModel(): TrendingModel
    {
        return $this->trendingModel;
    }

    /**
     * Mengakses TagModel
     */
    public function getTagModel(): TagModel
    {
        return $this->tagModel;
    }

    /**
     * Mengakses User2FAuthModel
     */
    public function getUser2FAuthModel(): User2FAuthModel
    {
        return $this->user2fauthModel;
    }

    /**
     * Mengakses CommentModel
     */
    public function getCommentModel(): CommentModel
    {
        return $this->commentModel;
    }
}

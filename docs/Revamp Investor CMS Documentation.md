# Revamp Investor CMS Technical Documentation

Dokumentasi teknis komprehensif mengenai arsitektur sistem, komparasi mendalam seluruh query database antara Smarty CMS dan Revamp CMS (CodeIgniter 4), spesifikasi seluruh pemanggilan API, panduan pra-produksi (*pre-production checklist*), serta catatan integrasi frontend.

---

## Daftar Isi
1. [Ringkasan Arsitektur & Perubahan Sistem](#1-ringkasan-arsitektur--perubahan-sistem)
   - [1.1 Perbandingan Arsitektur](#11-perbandingan-arsitektur)
   - [1.2 Konvensi Penamaan Tabel & Penanganan DBPrefix](#12-konvensi-penamaan-tabel--penanganan-dbprefix)
   - [1.3 Peningkatan Keamanan & Autentikasi](#13-peningkatan-keamanan--autentikasi)
   - [1.4 Spesifikasi Teknis Sistem Revamp CMS](#14-spesifikasi-teknis-sistem-revamp-cms)
2. [Catatan Kritis: Status UI Preview Artikel](#2-catatan-kritis-status-ui-preview-artikel)
3. [Komparasi Menyeluruh Seluruh Modul & Query Database (Smarty vs Revamp)](#3-komparasi-menyeluruh-seluruh-modul--query-database-smarty-vs-revamp)
   - [3.1 Modul Artikel & Redaksional (Editorial Listing)](#31-modul-artikel--redaksional-editorial-listing)
   - [3.2 Modul Detail Artikel, Master Kanal & Multi Sub-Kanal](#32-modul-detail-artikel-master-kanal--multi-sub-kanal)
   - [3.3 Modul Penulis, Editor & Fotografer (Authors)](#33-modul-penulis-editor--fotografer-authors)
   - [3.4 Modul Taksonomi Kata Kunci & Tag (Keywords)](#34-modul-taksonomi-kata-kunci--tag-keywords)
   - [3.5 Modul Slot Headline Terkurasi & Sorting](#35-modul-slot-headline-terkurasi--sorting)
   - [3.6 Modul Media & Manajemen Gambar](#36-modul-media--manajemen-gambar)
   - [3.7 Modul Pengguna Administrator & RBAC](#37-modul-pengguna-administrator--rbac)
   - [3.8 Modul Penguncian Artikel Bersamaan (Edit Lock)](#38-modul-penguncian-artikel-bersamaan-edit-lock)
   - [3.9 Modul Riwayat Revisi & Audit Trail (Article History)](#39-modul-riwayat-revisi--audit-trail-article-history)
   - [3.10 Modul Visualisasi Grafik & Embed Data (ChartData)](#310-modul-visualisasi-grafik--embed-data-chartdata)
   - [3.11 Modul Arsip Dokumen & Integrasi Google Docs (Files)](#311-modul-arsip-dokumen--integrasi-google-docs-files)
   - [3.12 Modul Notifikasi Push & Ekosistem Aplikasi Mobile](#312-modul-notifikasi-push--ekosistem-aplikasi-mobile)
   - [3.13 Modul Statistik, Trafik & Laporan Produktivitas](#313-modul-statistik-trafik--laporan-produktivitas)
   - [3.14 Modul Konten Fitur (Promotional Banners & Tags)](#314-modul-konten-fitur-promotional-banners--tags)
   - [3.15 Modul Keanggotaan Pembaca (Member)](#315-modul-keanggotaan-pembaca-member)
   - [3.16 Modul Trending Topics & Peringkat Baca](#316-modul-trending-topics--peringkat-baca)
   - [3.17 Modul Background Scheduler & Cron Jobs](#317-modul-background-scheduler--cron-jobs)
   - [3.18 Modul Evaluasi & Kesiapan SEO (SEO Readiness Scoring)](#318-modul-evaluasi--kesiapan-seo-seo-readiness-scoring)
4. [Spesifikasi & Pemanggilan Seluruh Endpoint API](#4-spesifikasi--pemanggilan-seluruh-endpoint-api)
   - [4.1 Reader Public APIs](#41-reader-public-apis)
   - [4.2 Market Data & Bursa APIs](#42-market-data--bursa-apis)
   - [4.3 Editorial & Newsroom APIs](#43-editorial--newsroom-apis)
   - [4.4 Reader Authentication APIs](#44-reader-authentication-apis)
5. [Hal-Hal yang Wajib Dilakukan Sebelum Naik ke Produksi (Pre-Production Checklist & Runbook)](#5-hal-hal-yang-wajib-dilakukan-sebelum-naik-ke-produksi-pre-production-checklist--runbook)
   - [5.1 Verifikasi Konfigurasi Environment (`.env`)](#51-verifikasi-konfigurasi-environment-env)
   - [5.2 Eksekusi Skema Database, Tabel Baru & Indexing Kinerja](#52-eksekusi-skema-database-tabel-baru--indexing-kinerja)
   - [5.3 Konfigurasi Web Server & Isolasi Direktori Publik](#53-konfigurasi-web-server--isolasi-direktori-publik)
   - [5.4 Perizinan Hak Akses Folder Server (`writable/`)](#54-perizinan-hak-akses-folder-server-writable)
   - [5.5 Penjadwalan Crontab Otomatisasi Linux](#55-penjadwalan-crontab-otomatisasi-linux)
   - [5.6 Pengujian Pre-Flight Operasional (Smoke Test Checklist)](#56-pengujian-pre-flight-operasional-smoke-test-checklist)
   - [5.7 Rencana Kontingensi & Prosedur Rollback](#57-rencana-kontingensi--prosedur-rollback)


---

## 1. Ringkasan Arsitektur & Perubahan Sistem

### 1.1 Perbandingan Arsitektur

| Karakteristik | Smarty CMS | Revamp CMS |
| :--- | :--- | :--- |
| **Framework & Versi** | PHP 5.6 Prosedural Native + Smarty 2/3 | CodeIgniter v4.7.4 (PHP 8.3.28 / min. PHP ^8.2, Strict Types, PSR-4) |
| **Pola Routing** | Berbasis file fisik (`/administration/pages/article/index.php`) | Centralized Router (`app/Config/Routes.php`) dengan Route Guard & Filter |
| **Interaksi Database** | Prosedural `adodb` / raw string MySQL query concats | CodeIgniter 4 Query Builder & Model Layer |
| **Konvensi Case-Sensitivity** | Raw SQL dengan string literal (`tblArticle`, `tblCategory`) | Model & Query Builder CamelCase (`Article`, `Category`), otomatis di-prefix `tbl` |
| **Pemisahan Publik** | File PHP terekspos di root aplikasi | Web root terisolasi di `/public`, file inti di `/app` aman dari akses publik |
| **Pengujian (Testing)** | Manual via browser | 111 Automated Unit Tests (PHPUnit v10.5.16) dengan 428 assertions |
| **Integrasi Social Share** | `SocialShare.class.php` (AddThis, Twitter API, URL Shortener internal) | **Didepresiasi/Dihapus**. API pihak ketiga lama sudah usang; sharing di-handle native oleh frontend pembaca |

---

### 1.2 Konvensi Penamaan Tabel & Penanganan DBPrefix

Pada CodeIgniter 4, prefix tabel dikonfigurasi secara global pada:
- [`.env`](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/.env#L22): `database.default.DBPrefix = tbl`
- [`app/Config/Database.php`](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Config/Database.php#L34): `'DBPrefix' => 'tbl'`

#### Aturan Eksekusi Query Builder:
1. **Penulisan di Kode CI4**: Seluruh pemanggilan model dan builder menggunakan **CamelCase tanpa awalan `tbl`**, misalnya:
   ```php
   $builder = $this->db->table('Article');
   $builder->join('ArticleCategory ac', 'ac.articleID = a.articleID');
   ```
2. **Kompilasi Query SQL**: Query Builder secara otomatis meng-append prefix `'tbl'`, sehingga query yang dieksekusi ke database MySQL menjadi:
   ```sql
   FROM tblArticle JOIN tblArticleCategory ac ON ac.articleID = a.articleID
   ```
3. **Peringatan Double Prefix**: Menuliskan `$db->table('tblArticle')` secara manual adalah **kesalahan**, karena CI4 akan mengeksekusi `tbltblArticle` yang menyebabkan error *table not found*.
4. **Paritas Linux Server**: Di server produksi berbasis Linux (yang bersifat *case-sensitive*), penulisan CamelCase seperti `'ArticleHeadline'` menjamin query merujuk persis ke tabel fisik `tblArticleHeadline`.

---

### 1.3 Peningkatan Keamanan & Autentikasi

1. **Formula Salt & Password Deterministik**:
   ```php
   $salt = hash('SHA512', '[' . strtolower($username) . '>|<' . $password . ']', false);
   $hash = hash('SHA512', $salt . 'B3!21t454t03' . $password . $salt, false);
   ```
2. **Multi-Strategy Authentication & Auto-Upgrade**:
   - Sistem memvalidasi akun menggunakan formula SHA-512 Smarty di atas.
   - Jika akun lama terdeteksi masih menggunakan hash MD5 murni (`strlen(password) === 32`), sistem memvalidasi `md5($password) === $user['password']`.
   - Jika lolos, sistem **secara otomatis meng-upgrade hash akun ke format SHA-512 (128 karakter)** ke dalam database tanpa mengharuskan pengguna melakukan reset password manual.
3. **Autentikasi Dua Faktor (2FA) & Interval Verifikasi**:
   - Sistem login menerapkan autentikasi dua faktor berbasis kode verifikasi OTP 6-digit acak yang dikirimkan ke alamat email resmi staf redaksi via Brevo SMTP TLS.
   - Status kode OTP (`authCode`) dan masa aktifnya (`authExpired`, berlaku 5 menit) dikelola langsung pada entitas `tblUserAdministrator`. *(Catatan Keputusan Arsitektur: Smarty menetapkan `INTERVAL 60 SECOND` / 1 menit. Pada Revamp, masa berlaku diperpanjang menjadi 5 menit sebagai optimalisasi UX untuk mengakomodasi potensi latency pengiriman email gateway SMTP/Brevo tanpa mengorbankan keamanan).*
   - Setelah kode berhasil diverifikasi, sistem memberikan masa berlaku verifikasi selama 5 hari (`nextVerify = +5 days`) pada tabel pengguna. Dengan demikian, staf tidak diwajibkan menginput ulang kode verifikasi pada setiap sesi login harian selama periode tersebut masih aktif.
4. **Kebijakan Rotasi Sandi & Proteksi Akun**:
   - Batas masa aktif kata sandi staf ditetapkan selama 90 hari (`passwordExpired`). Filter rute `AuthGuard` secara otomatis mendeteksi akun yang telah melewati batas kedaluwarsa dan mengalihkannya ke halaman profil untuk pembaruan kata sandi.
   - Sesi pengguna diamankan menggunakan cookie bertanda tangan HMAC-SHA256 (`cmsinvestorid`) dengan atribut `HttpOnly`, `SameSite=Lax`, dan `Secure` pada server produksi.
5. **Sanitasi HTML Tingkat Lanjut (`HtmlSanitizer`)**:
   - Menggantikan sanitasi parsial `addslashes` pada Smarty.
   - Menghapus tag `<script>`, `<style>`, `<iframe>` tidak terpercaya, dan atribut inline event seperti `onclick`, `onload`, serta protokol pseudo `javascript:`.
   - Tetap mempertahankan tag format jurnalistik sah (`<p>`, `<strong>`, `<em>`, `<blockquote>`, `<a>`, `<img>`, `<table>`).

---

### 1.4 Spesifikasi Teknis Sistem Revamp CMS

Berikut adalah rincian spesifikasi teknis dan kebutuhan infrastruktur dari sistem Revamp CMS yang tengah dibangun:

| Komponen | Spesifikasi & Rincian Versi |
| :--- | :--- |
| **Bahasa & Runtime Engine** | **PHP 8.3.28 (64-bit)** *(Kebutuhan versi minimum: PHP ^8.2)*.<br>Fitur bahasa aktif: PSR-4 Autoloading, Strict Typing (`declare(strict_types=1)`), First-class callables, Enums, Readonly properties, dan JIT Compiler. |
| **Ekstensi PHP Wajib & Peran** | • `ext-intl`: Internasionalisasi & lokalisasi format tanggal/waktu bahasa Indonesia.<br>• `ext-mbstring`: Pemrosesan string multibyte UTF-8 untuk naskah berita, judul, dan slug.<br>• `ext-mysqli`: Koneksi native MySQL 8 dengan dukungan query builder transaksional.<br>• `ext-curl`: HTTP client untuk integrasi Brevo SMTP TLS, Cluster Media Storage, & Gemini API.<br>• `ext-json`: Enkoding & dekoding payload JSON pada autosave draft, AJAX, dan REST API.<br>• `ext-openssl`: Keamanan HMAC-SHA256 session token, hashing SHA-512, dan enkripsi payload.<br>• `ext-gd` / `ext-imagick`: Manipulasi gambar lokal, pembuatan thumbnail, dan konversi WebP.<br>• `ext-zip`: Pustaka kompresi dan ekstraksi berkas pada File Management. |
| **Framework Fondasi** | **CodeIgniter v4.7.4** (`codeigniter4/framework: v4.7.4` via Composer AppStarter).<br>Komponen arsitektur: MVC terstruktur, Service Container (`Config\Services`), Route Filters / Middleware Guards (`AuthGuard`, `CsrfFilter`, `CorsFilter`), dan Spark CLI Engine. |
| **Sistem Pengujian Mutu (Testing)** | • **Unit Testing:** PHPUnit v10.5.16 (`phpunit/phpunit: ^10.5.16`) dengan 111 Test Cases dan 428 Assertions.<br>• **Mocking Fixtures:** `mikey179/vfsStream: ^1.6` (Virtual Filesystem) & `fakerphp/faker: ^1.9`.<br>• **End-to-End UI Testing:** Node.js v22.15.0 dengan Playwright Chromium Test Suite. |
| **Web Server & Gateway** | Nginx 1.22+ / Apache 2.4+ dengan PHP-FPM FastCGI (`pm = dynamic`, `max_children = 50`, `request_terminate_timeout = 120s`). |
| **Struktur Web Root** | Direktori *DocumentRoot* server diarahkan eksklusif ke folder `/public`. Seluruh file aplikasi inti (`/app`), pustaka (`/system`), dan berkas konfigurasi (`.env`) terisolasi dari akses web langsung. |
| **Basis Data Engine** | MySQL 8.0 / Percona Server 5.7+, Collation: `utf8mb4_general_ci` / `utf8mb4_unicode_ci`. Mendukung tabel InnoDB (transaksional) dan MyISAM legacy. |
| **Penamaan Tabel (DBPrefix)** | Prefix global `tbl` (`database.default.DBPrefix = tbl`). Model memanggil nama CamelCase tanpa prefix (`Article`, `Category`). |
| **Antarmuka Redaksional (UI)** | Native PHP Views terstruktur, Tailwind CSS, Lucide Icons, dan komponen JavaScript modern tanpa ketergantungan jQuery berat pada halaman baru. |
| **Editor Teks Redaksi** | CKEditor 4 tersanitasi dua lapis (browser-side & server-side), mendukung penataan paragraf, blockquote aman, embed video, dan pembersih spasi ganda. |
| **Visualisasi Data** | Highcharts & Chart.js 4 untuk render tabel finansial interaktif, template grafik (bar, line, pie), dan upload custom data CSV. |
| **Penyimpanan Media & Berkas** | Arsitektur Dual-Mode: <br>1. *Penyimpanan Intranet Produksi:* Integrasi REST API cluster media internal (`10.0.4.16` & `172.20.6.110`) dengan URL CDN publik `https://img.beritasatu.com/`.<br>2. *Fallback Lokal:* Folder terproteksi `/public/uploads/` dengan konvensi path `YYYY/MM/timestamp_name.webp`. |
| **Email Gateway 2FA** | Brevo SMTP TLS (Port 587) untuk pengiriman notifikasi kode verifikasi login staf redaksi. |
| **Otomatisasi Background** | Task runner berbasis CodeIgniter Spark CLI yang dieksekusi melalui Linux Crontab (penerbitan artikel terjadwal, truncate newsletter, sinkronisasi data saham IDX, dan pemrosesan antrean push notifikasi FCM). |


---

## 2. Catatan Kritis: Status UI Preview Artikel

> **PERHATIAN KHUSUS TIM REDAKSI & FRONTEND INTEGRATOR:**
>
> Halaman pratinjau artikel pada rute `/post/preview/{id}` saat ini masih berupa **wrapper shell CMS internal** untuk memverifikasi kelengkapan relasi konten, metadata, thumbnail, dan pemformatan teks. Halaman ini **belum mengimplementasikan stylesheet dan komponen UI otentik dari portal publik investor.id**.
>
> **Tindakan yang harus dilakukan sebelum peluncuran penuh:**
> 1. Ekstrak layout styling artikel dari frontend publik portal `investor.id` (font family, typography lead paragraph, quote block styling, penataan gambar utama beserta caption, serta box saham terkait).
> 2. Perbarui view [app/Views/post/preview.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Views/post/preview.php) menggunakan CSS otentik tersebut agar redaktur melihat tampilan yang 100% presisi dengan apa yang dilihat pembaca di situs live.

---

## 3. Komparasi Menyeluruh Seluruh Modul & Query Database (Smarty vs Revamp)

---

### 3.1 Modul Artikel & Redaksional (Editorial Listing)

- **Tabel Terkait**: `tblArticle`, `tblArticleCategory`, `tblCategory`, `tblUserAdministrator`, `tblMediaArticle`, `tblMedia`
- **Lokasi File Smarty**:
  - `Backup_CMS_ID/lib/Article.class.php` $\rightarrow$ fungsi `Article::read()`
  - `Backup_CMS_ID/administration/pages/article/ajax/ajax_articlelist.php`
- **Lokasi File Revamp**:
  - [InvestorCMS/app/Models/ArticleModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/ArticleModel.php) $\rightarrow$ fungsi `getArticlesWithRelations()`
  - [InvestorCMS/app/Controllers/Post.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/Post.php) $\rightarrow$ fungsi `index()`

#### Query di Smarty CMS:
```sql
SELECT a.*, c.categoryName, u.username, m.image, m.caption
FROM tblArticle a
LEFT JOIN tblArticleCategory ac ON ac.articleID = a.articleID
LEFT JOIN tblCategory c ON c.categoryID = ac.categoryID
LEFT JOIN tblUserAdministrator u ON u.userID = a.userID
LEFT JOIN tblMediaArticle ma ON ma.articleID = a.articleID
LEFT JOIN tblMedia m ON m.mediaID = ma.mediaID
WHERE a.status != 'Deleted'
ORDER BY a.articleDate DESC
LIMIT 0, 25;
```

#### Query di Revamp CMS:
```php
$builder = $this->db->table('Article a')
    ->select('
        a.articleID,
        a.headline,
        a.slug,
        a.articleDate,
        a.status,
        a.viewed,
        a.source,
        a.editlock,
        u.name as author_name,
        u.username as author_username,
        c.categoryName as primary_category,
        c.categoryID as primary_category_id,
        m.image as thumbnail,
        m.caption as thumbnail_caption,
        lock_user.name as locked_by_name
    ')
    ->join('UserAdministrator u', 'u.userID = a.userID', 'left')
    ->join('UserAdministrator lock_user', 'lock_user.userID = a.editlock', 'left')
    ->join('ArticleCategory ac', 'ac.articleID = a.articleID', 'left')
    ->join('Category c', 'c.categoryID = ac.categoryID', 'left')
    ->join('MediaArticle ma', 'ma.articleID = a.articleID AND ma.position = 0', 'left')
    ->join('Media m', 'm.mediaID = ma.mediaID', 'left')
    ->where('a.status !=', 'Deleted')
    ->groupBy('a.articleID')
    ->orderBy('a.articleDate', 'DESC');
```
*Optimasi*: Thumbnail di Revamp dikunci secara eksplisit pada `ma.position = 0` guna mencegah duplikasi baris akibat multi-gambar, serta ditambahkan relasi ke `lock_user` untuk status kuncian editor.

---

### 3.2 Modul Detail Artikel, Master Kanal & Multi Sub-Kanal

- **Tabel Terkait**: `tblArticle`, `tblArticleCategory`, `tblCategory`, `tblArticleMeta`, `tblArticleAuthor`, `tblAuthors`, `tblArticleKeyword`, `tblKeyword`
- **Lokasi File Smarty**:
  - `Backup_CMS_ID/lib/Article.class.php` $\rightarrow$ fungsi `Article::edit()`, `Article::getAuthor()`, `Article::getTags()`
  - `Backup_CMS_ID/administration/pages/article/ajax/ajax_articledetail.php`
- **Lokasi File Revamp**:
  - [InvestorCMS/app/Models/ArticleModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/ArticleModel.php) $\rightarrow$ fungsi `getArticleDetail()`
  - [InvestorCMS/app/Controllers/Post.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/Post.php) $\rightarrow$ fungsi `edit()`

#### Pola di Smarty CMS:
Query dijalankan secara prosedural terpisah berkali-kali:
```sql
SELECT * FROM tblArticle WHERE articleID = 100;
SELECT * FROM tblArticleCategory WHERE articleID = 100;
SELECT * FROM tblArticleAuthor WHERE articleID = 100;
SELECT * FROM tblArticleKeyword WHERE articleID = 100;
```

#### Query di Revamp CMS:
```php
// Ekstraksi data utama dan metadata dalam satu eksekusi
$article = $this->db->table('Article n')
    ->select('
        n.*,
        u.name as author_name,
        u.username as author_username,
        m.image as thumbnail,
        m.caption as thumbnail_caption,
        nm_prem.metavalue as is_premium,
        nm_deck.metavalue as upper_deck,
        nm_source.metavalue as news_source
    ')
    ->join('UserAdministrator u', 'u.userID = n.userID', 'left')
    ->join('MediaArticle ma', 'ma.articleID = n.articleID AND ma.position = 0', 'left')
    ->join('Media m', 'm.mediaID = ma.mediaID', 'left')
    ->join('ArticleMeta nm_prem', 'nm_prem.articleID = n.articleID AND nm_prem.metakey = "premium"', 'left')
    ->join('ArticleMeta nm_deck', 'nm_deck.articleID = n.articleID AND nm_deck.metakey = "upper_deck"', 'left')
    ->join('ArticleMeta nm_source', 'nm_source.articleID = n.articleID AND nm_source.metakey = "news_source"', 'left')
    ->where('n.articleID', $id)
    ->where('n.status !=', 'Deleted')
    ->get()->getRowArray();

// Ekstraksi seluruh kanal terikat (Master & Sub-Kanal)
$linkedCategories = $this->db->table('ArticleCategory ac')
    ->select('c.categoryID, c.categoryName, c.label, c.parent')
    ->join('Category c', 'c.categoryID = ac.categoryID')
    ->where('ac.articleID', $id)
    ->get()->getResultArray();
```

---

### 3.3 Modul Penulis, Editor & Fotografer (Authors)

- **Tabel Terkait**: `tblAuthors`, `tblArticleAuthor`
- **Lokasi File Smarty**:
  - `Backup_CMS_ID/lib/Author.class.php` $\rightarrow$ fungsi `saveAuthor()`, `getAuthor()`, `searchAuthor()`
  - `Backup_CMS_ID/administration/pages/article/ajax/ajax_getauthor.php`
- **Lokasi File Revamp**:
  - [InvestorCMS/app/Models/AuthorModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/AuthorModel.php) $\rightarrow$ fungsi `getAuthorsWithCount()`, `findOrCreate()`

#### Query di Smarty CMS:
```sql
-- Smarty pencarian autocomplete penulis:
SELECT authorID, authorName FROM tblAuthors 
WHERE authorName LIKE '%budi%' AND valid != 3 
ORDER BY authorName LIMIT 0, 10;

-- Smarty hitung jumlah artikel penulis:
SELECT COUNT(authorID) FROM tblArticleAuthor WHERE authorID = 25;
```

#### Query di Revamp CMS:
```php
public function getAuthorsWithCount(?string $keyword = null, int $limit = 20, int $offset = 0): array
{
    $builder = $this->db->table('Authors a')
        ->select('a.authorID, a.authorName, a.slug, a.type, COUNT(aa.articleID) as total_articles')
        ->join('ArticleAuthor aa', 'aa.authorID = a.authorID', 'left')
        ->where('a.valid !=', '3')
        ->groupBy('a.authorID')
        ->orderBy('a.authorName', 'ASC');

    if (!empty($keyword)) {
        $builder->like('a.authorName', $keyword);
    }

    return $builder->limit($limit, $offset)->get()->getResultArray();
}
```

---

### 3.4 Modul Taksonomi Kata Kunci & Tag (Keywords)

- **Tabel Terkait**: `tblKeyword`, `tblArticleKeyword`, `tblTag`
- **Lokasi File Smarty**:
  - `Backup_CMS_ID/lib/Tag.class.php` $\rightarrow$ fungsi `getTagList()`, `saveTag()`, `getKeyword()`
  - `Backup_CMS_ID/administration/pages/article/ajax/ajax_taglist.php`
  - `Backup_CMS_ID/administration/pages/article/ajax/ajax_keywordlist.php`
- **Lokasi File Revamp**:
  - [InvestorCMS/app/Models/KeywordModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/KeywordModel.php) $\rightarrow$ fungsi `syncArticleKeywords()`, `getArticleKeywords()`

#### Query di Smarty CMS:
```sql
SELECT SQL_CALC_FOUND_ROWS tagID, label, slug FROM tblTag 
WHERE label LIKE 'saham%' ORDER BY label ASC LIMIT 0, 10;
```

#### Query di Revamp CMS:
```php
public function syncArticleKeywords(int $articleId, array $keywords): void
{
    // Bersihkan relasi lama
    $this->db->table('ArticleKeyword')->where('articleID', $articleId)->delete();

    foreach ($keywords as $kw) {
        $cleanKw = trim($kw);
        if ($cleanKw === '') continue;

        // Cari atau buat master keyword baru
        $existing = $this->db->table('Keyword')->where('keyword', $cleanKw)->get()->getRowArray();
        if ($existing) {
            $keywordId = $existing['keywordID'];
        } else {
            $slug = url_title($cleanKw, '-', true);
            $this->db->table('Keyword')->insert(['keyword' => $cleanKw, 'slug' => $slug]);
            $keywordId = $this->db->insertID();
        }

        // Tautkan pivot
        $this->db->table('ArticleKeyword')->insert([
            'articleID' => $articleId,
            'keywordID' => $keywordId
        ]);
    }
}
```

---

### 3.5 Modul Slot Headline Terkurasi & Sorting

- **Tabel Terkait**: `tblArticleHeadline`, `tblArticleHeadlineHistory`, `tblArticle`
- **Lokasi File Smarty**:
  - `Backup_CMS_ID/lib/Article.class.php` (Headline handlers)
  - `Backup_CMS_ID/administration/pages/article/headline.php`
  - `Backup_CMS_ID/administration/pages/article/ajax/ajax_headlinelist.php`
  - `Backup_CMS_ID/administration/pages/article/ajax/ajax_headlinesave.php`
- **Lokasi File Revamp**:
  - [InvestorCMS/app/Models/HeadlineModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/HeadlineModel.php) $\rightarrow$ fungsi `getActiveHeadlines()`, `batchReorder()`
  - [InvestorCMS/app/Controllers/Headline.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/Headline.php)
  - [InvestorCMS/app/Commands/TruncateNewsletterHeadline.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Commands/TruncateNewsletterHeadline.php)

#### Query di Smarty CMS:
```sql
SELECT h.*, a.headline, a.articleDate 
FROM tblArticleHeadline h
INNER JOIN tblArticle a ON a.articleID = h.articleID
WHERE h.category = 'home' AND h.status = 1
ORDER BY h.num ASC;
```

#### Query di Revamp CMS:
```php
public function batchReorder(string $category, array $orderedIds, string $user = 'admin'): bool
{
    $this->db->transStart();
    foreach ($orderedIds as $position => $headlineId) {
        $this->db->table('ArticleHeadline')
            ->where('id', $headlineId)
            ->where('category', $category)
            ->update([
                'num'       => $position + 1,
                'set_by'    => $user,
                'timestamp' => date('Y-m-d H:i:s'),
            ]);

        // Audit log otomatis ke ArticleHeadlineHistory
        $this->db->table('ArticleHeadlineHistory')->insert([
            'articleID' => $headlineId,
            'category'  => $category,
            'action'    => 'REORDER',
            'position'  => $position + 1,
            'user'      => $user,
            'notes'     => 'Urutan headline diperbarui via drag & drop editor',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    $this->db->transComplete();
    return $this->db->transStatus();
}
```

---

### 3.6 Modul Media & Manajemen Gambar

- **Tabel Terkait**: `tblMedia`, `tblMediaArticle`, `tblAlbum`
- **Lokasi File Smarty**:
  - `Backup_CMS_ID/lib/Media.class.php` $\rightarrow$ fungsi `getMediaList()`, `totalMedia()`
  - `Backup_CMS_ID/administration/pages/media/index.php`
  - `Backup_CMS_ID/administration/pages/media/ajax/ajax_medialist.php`
- **Lokasi File Revamp**:
  - [InvestorCMS/app/Models/MediaModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/MediaModel.php) $\rightarrow$ fungsi `getMediaWithRelations()`, `getArticlesUsingMedia()`
  - [InvestorCMS/app/Controllers/Media.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/Media.php)

#### Query di Smarty CMS:
```sql
SELECT SQL_CALC_FOUND_ROWS t1.mediaID, t1.caption, t1.description, t1.filename, t1.timestamp, t1.mediaTitle, t1.source, t1.author, t2.username 
FROM tblMedia AS t1, tblUserAdministrator AS t2 
WHERE t1.userID = t2.userID AND t1.filename != ''
ORDER BY t1.mediaID DESC LIMIT 0, 20;
```

#### Query di Revamp CMS:
```php
public function getMediaWithRelations(?string $search = null, int $page = 1, int $perPage = 20): array
{
    $builder = $this->db->table('Media m')
        ->select('m.*, u.username as uploader_name, COUNT(ma.articleID) as total_used')
        ->join('UserAdministrator u', 'u.userID = m.userID', 'left')
        ->join('MediaArticle ma', 'ma.mediaID = m.mediaID', 'left')
        ->where('m.filename !=', '')
        ->groupBy('m.mediaID')
        ->orderBy('m.mediaID', 'DESC');

    if (!empty($search)) {
        $builder->groupStart()
            ->like('m.caption', $search)
            ->orLike('m.mediaTitle', $search)
            ->groupEnd();
    }

    return $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
}
```

---

### 3.7 Modul Pengguna Administrator & RBAC

- **Tabel Terkait**: `tblUserAdministrator`
- **Lokasi File Smarty**:
  - `Backup_CMS_ID/lib/Administrator.class.php` $\rightarrow$ fungsi `auth()`, `encodePassword()`, `getAdminList()`
  - `Backup_CMS_ID/administration/pages/user/index.php`
  - `Backup_CMS_ID/administration/pages/user/add.php`
- **Lokasi File Revamp**:
  - [InvestorCMS/app/Models/AdminModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/AdminModel.php) $\rightarrow$ fungsi `authenticate()`, `getAdminsWithActivity()`
  - [InvestorCMS/app/Controllers/User.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/User.php)

#### Query di Smarty CMS:
```sql
SELECT * FROM tblUserAdministrator 
WHERE username = 'radyan' AND active = 1 LIMIT 0, 1;
```

#### Query di Revamp CMS:
```php
public function getAdminsWithActivity(): array
{
    return $this->db->table('UserAdministrator u')
        ->select('
            u.userID,
            u.username,
            u.name,
            u.email,
            u.roleName,
            u.active,
            MAX(l.timestamp) as last_active_log,
            COUNT(DISTINCT a.articleID) as total_articles_created
        ')
        ->join('EditorLog l', 'l.userID = u.userID', 'left')
        ->join('Article a', 'a.userID = u.userID', 'left')
        ->groupBy('u.userID')
        ->orderBy('u.userID', 'ASC')
        ->get()->getResultArray();
}
```

#### Mekanisme Custom Permission per User (Granular Access Override)
Selain RBAC berbasis peran standar (`master`, `administrator`, `editor`, `reporter`, dll), Revamp CMS menyediakan fitur *Custom Permission* berbasis file JSON (`writable/data/json/permissions.json`) yang dievaluasi langsung oleh filter [RoleGuard.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Filters/RoleGuard.php#L31-L61).

Fitur ini memungkinkan Administrator memberikan atau membatasi izin akses spesifik modul untuk user tertentu (misal: membatasi reporter hanya bisa mengakses modul `post` dan `media`, atau memberi staf akses ke `embed-chart`). Format konfigurasi JSON:
```json
{
  "users": {
    "12": {
      "dashboard": true,
      "post": true,
      "media": true,
      "headline": false,
      "files": false
    }
  }
}
```
Pengelolaan izin ini dapat diakses melalui controller [InvestorCMS/app/Controllers/User.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/User.php) via metode `getPermissions()` dan `savePermissions()`.


---

### 3.8 Modul Penguncian Artikel Bersamaan (Edit Lock)

- **Tabel Terkait**: `tblArticle`, `tblUserAdministrator`
- **Lokasi File Smarty**:
  - `Backup_CMS_ID/lib/Article.class.php` $\rightarrow$ fungsi `Article::PostLock()`, `Article::ReleaseLock()`
  - `Backup_CMS_ID/administration/pages/article/ajax/ajax_releaselock.php`
- **Lokasi File Revamp**:
  - [InvestorCMS/app/Models/ArticleModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/ArticleModel.php) $\rightarrow$ fungsi `acquireLock()`, `releaseLock()`, `forceUnlock()`

#### Mekanisme Query di Revamp CMS:
```php
// Acquire Lock
$row = $this->db->table('Article n')
    ->select('n.editlock, u.name as locked_by_name, u.username as locked_by_username')
    ->join('UserAdministrator u', 'u.userID = n.editlock', 'left')
    ->where('n.articleID', $articleId)
    ->get()->getRowArray();

if ($row['editlock'] === null || (int)$row['editlock'] === $userId) {
    $this->db->table('Article')
        ->where('articleID', $articleId)
        ->update(['editlock' => $userId]);
    return ['status' => true, 'locked' => false];
}

// Release Lock
$this->db->table('Article')
    ->where('articleID', $articleId)
    ->where('editlock', $userId)
    ->update(['editlock' => null]);
```

---

### 3.9 Modul Riwayat Revisi & Audit Trail (Article History)

- **Tabel Terkait**: `tblArticleHistory`, `tblArticle`, `tblUserAdministrator`
- **Lokasi File Smarty**:
  - `Backup_CMS_ID/lib/Article.class.php` $\rightarrow$ fungsi `Article::Revision()`
  - `Backup_CMS_ID/administration/pages/article/ajax/ajax_articlehistory.php`
- **Lokasi File Revamp**:
  - [InvestorCMS/app/Models/ArticleModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/ArticleModel.php) $\rightarrow$ fungsi `getRevisionHistory()`, `getRevisionDetail()`

#### Query di Revamp CMS:
```php
public function getRevisionHistory(int $page = 1, int $perPage = 25, array $filters = []): array
{
    $builder = $this->db->table('ArticleHistory h')
        ->select('
            h.id,
            h.timestamp,
            h.articleID,
            h.headline,
            h.editorID,
            u.name as editor_name,
            u.username as editor_username,
            h.action,
            h.status_prev,
            h.status_after
        ')
        ->join('Article a', 'a.articleID = h.articleID', 'left')
        ->join('UserAdministrator u', 'u.userID = h.editorID', 'left')
        ->orderBy('h.timestamp', 'DESC');

    if (!empty($filters['article_id'])) {
        $builder->where('h.articleID', (int)$filters['article_id']);
    }

    return $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
}
```

---

### 3.10 Modul Visualisasi Grafik & Embed Data (ChartData)

- **Tabel Terkait**: `tblChartData`, `tblUserAdministrator`
- **Lokasi File Smarty**:
  - `Backup_CMS_ID/administration/pages/files/chart.php`
- **Lokasi File Revamp**:
  - [InvestorCMS/app/Models/ChartModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/ChartModel.php)
  - [InvestorCMS/app/Controllers/EmbedChart.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/EmbedChart.php)

#### Query di Smarty CMS:
```sql
SELECT SQL_CALC_FOUND_ROWS t1.chartID AS id, t1.chartTitle AS title, t1.createdAt, t2.username, t1.source 
FROM tblChartData AS t1, tblUserAdministrator AS t2 
WHERE deletedAt IS NULL AND t1.createdBy = t2.userID
ORDER BY t1.chartID DESC;
```

#### Query di Revamp CMS:
```php
public function getChartsList(?string $search = null, int $limit = 20, int $offset = 0): array
{
    $builder = $this->db->table('ChartData c')
        ->select('c.chartID, c.chartTitle, c.chartType, c.dataJson, c.source, c.createdAt, u.username as creator_name')
        ->join('UserAdministrator u', 'u.userID = c.createdBy', 'left')
        ->where('c.deletedAt IS NULL', null, false)
        ->orderBy('c.chartID', 'DESC');

    if (!empty($search)) {
        $builder->like('c.chartTitle', $search);
    }

    return $builder->limit($limit, $offset)->get()->getResultArray();
}
```

---

### 3.11 Modul Arsip Dokumen & Integrasi Google Docs (Files)

- **Tabel Terkait**: `tblFiles`, `tblUserAdministrator`
- **Lokasi File Smarty**:
  - `Backup_CMS_ID/administration/pages/files/index.php`
  - `Backup_CMS_ID/administration/pages/files/googledocs.php`
  - `Backup_CMS_ID/administration/pages/files/api_googledocs.php`
- **Lokasi File Revamp**:
  - [InvestorCMS/app/Models/FileModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/FileModel.php)
  - [InvestorCMS/app/Models/GoogleDocsModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/GoogleDocsModel.php)
  - [InvestorCMS/app/Controllers/Files.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/Files.php)

#### Query di Smarty CMS:
```sql
SELECT SQL_CALC_FOUND_ROWS t1.fileID, t1.title, t1.filename, t1.timestamp, t2.username 
FROM tblFiles AS t1, tblUserAdministrator AS t2 
WHERE t1.userID = t2.userID ORDER BY t1.title ASC;
```

#### Query di Revamp CMS:
```php
public function getFilesWithUploader(?string $keyword = null, int $limit = 20, int $offset = 0): array
{
    $builder = $this->db->table('Files f')
        ->select('f.fileID, f.title, f.filename, f.filetype, f.filesize, f.timestamp, u.username as uploader_name')
        ->join('UserAdministrator u', 'u.userID = f.userID', 'left')
        ->orderBy('f.timestamp', 'DESC');

    if (!empty($keyword)) {
        $builder->like('f.title', $keyword);
    }

    return $builder->limit($limit, $offset)->get()->getResultArray();
}
```

---

### 3.12 Modul Notifikasi Push & Ekosistem Aplikasi Mobile

- **Tabel Terkait**: `tblPushNotif`, `tblPushToDevices`, `tblPushRegistered`
- **Lokasi File Smarty**:
  - `Backup_CMS_ID/lib/PushNotif.php`
  - `Backup_CMS_ID/administration/pages/push/index.php`
  - `Backup_CMS_ID/administration/pages/push/save.php`
  - `Backup_CMS_ID/administration/pages/misc/apps.php`
  - `Backup_CMS_ID/cron/pushnotif.php` & `Backup_CMS_ID/cron/pushfcm.php`
- **Lokasi File Revamp**:
  - [InvestorCMS/app/Models/NotificationModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/NotificationModel.php)
  - [InvestorCMS/app/Controllers/Apps.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/Apps.php)
  - [InvestorCMS/app/Commands/ProcessPushNotif.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Commands/ProcessPushNotif.php)

#### Alur Eksekusi Push Notification:
1. **Redaksi Mengirim Pesan**: Disimpan ke `tblPushNotif` dengan status `'ready'`.
2. **Cron Scheduler CLI**: Perintah `php spark cron:process-push` memproses antrean:
   ```php
   $pending = $db->table('PushNotif')->where('status', 'ready')->get()->getResultArray();
   foreach ($pending as $item) {
       // Kirim ke Google FCM HTTP v1 API
       $db->table('PushNotif')->where('id', $item['id'])->update(['status' => 'sent']);
   }
   ```

---

### 3.13 Modul Statistik, Trafik & Laporan Produktivitas

- **Tabel Terkait**: `tblArticle`, `tblUserAdministrator`, `tblAuthors`, `tblCategory`
- **Lokasi File Smarty**:
  - `Backup_CMS_ID/lib/Report.class.php` $\rightarrow$ fungsi `getProductivity()`
  - `Backup_CMS_ID/administration/pages/statistic/index.php`
  - `Backup_CMS_ID/administration/pages/statistic/export.php`
- **Lokasi File Revamp**:
  - [InvestorCMS/app/Controllers/Statistic.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/Statistic.php)
  - [InvestorCMS/app/Controllers/StatisticReport.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/StatisticReport.php)
  - [InvestorCMS/app/Models/ArticleModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/ArticleModel.php) $\rightarrow$ fungsi `getTrafficChartData()`, `getPublishingChartData()`

#### Query Agregasi Produktivitas Editor di Smarty:
```sql
SELECT tmp.editor, count(tmp.articleID) as ttl_article, sum(tmp.views) as ttl_views
FROM (
    SELECT a.articleid, a.userid, a.headline, a.articledate, a.viewed as views, e.name as editor
    FROM tblArticle a
    INNER JOIN tblUserAdministrator e ON a.userid = e.userid
    WHERE a.status = 'publish' AND a.articledate BETWEEN '2026-10-01 00:00:00' AND '2026-10-20 23:59:59'
) tmp
GROUP BY tmp.editor ORDER BY tmp.editor ASC;
```

#### Query Heatmap & Hourly Traffic di Revamp CMS:
```php
$todayRows = $this->db->table('Article')
    ->select('HOUR(articleDate) as h, SUM(COALESCE(viewed, 0)) as total_v')
    ->where('DATE(articleDate)', $todayStr)
    ->groupBy('HOUR(articleDate)')
    ->get()->getResultArray();
```

---

### 3.14 Modul Konten Fitur (Promotional Banners & Tags)

- **Tabel Terkait**: `tblFeatureContent`
- **Lokasi File Smarty**:
  - `Backup_CMS_ID/administration/pages/feature/index.php`
  - `Backup_CMS_ID/administration/pages/feature/save.php`
- **Lokasi File Revamp**:
  - [InvestorCMS/app/Models/FeatureContentModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/FeatureContentModel.php)
  - [InvestorCMS/app/Controllers/FeatureContent.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/FeatureContent.php)

#### Query di Smarty CMS:
```sql
SELECT * FROM tblFeatureContent WHERE id = '5' AND type = 'banner' AND position = 'top';
```

#### Query di Revamp CMS:
```php
public function getFeaturesList(string $type = 'banner'): array
{
    return $this->db->table('FeatureContent')
        ->where('type', $type)
        ->orderBy('position', 'ASC')
        ->get()->getResultArray();
}
```

---

### 3.15 Modul Keanggotaan Pembaca (Member)

- **Tabel Terkait**: `tblMember`
- **Lokasi File Revamp**:
  - [InvestorCMS/app/Models/MemberModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/MemberModel.php)
  - [InvestorCMS/app/Controllers/Member.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/Member.php)

#### Query di Revamp CMS:
```php
public function getMembers(array $filters = [], int $page = 1, int $perPage = 10): array
{
    $builder = $this->db->table('Member');
    if (!empty($filters['q'])) {
        $q = trim($filters['q']);
        $builder->groupStart()
            ->like('fullname', $q)
            ->orLike('email', $q)
            ->groupEnd();
    }
    if (!empty($filters['status'])) {
        $builder->where('status', $filters['status']);
    }
    return $builder->orderBy('signupdate', 'DESC')
        ->limit($perPage, ($page - 1) * $perPage)
        ->get()->getResultArray();
}
```

---

### 3.16 Modul Trending Topics & Peringkat Baca

- **Tabel Terkait**: `tblArticle`, `tblArticleKeyword`, `tblKeyword`
- **Lokasi File Smarty**:
  - `Backup_CMS_ID/administration/pages/article/trending.php`
  - `Backup_CMS_ID/administration/pages/article/ajax/ajax_trendingtopic.php`
- **Lokasi File Revamp**:
  - [InvestorCMS/app/Controllers/Trending.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/Trending.php)
  - [InvestorCMS/app/Models/ArticleModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/ArticleModel.php) $\rightarrow$ fungsi `getTopArticles()`

#### Query di Revamp CMS:
```php
public function getTopArticles(int $limit = 10, string $interval = '7d'): array
{
    $days = ($interval === '24h') ? 1 : (($interval === '30d') ? 30 : 7);
    $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    return $this->db->table('Article a')
        ->select('a.articleID, a.headline, a.slug, a.viewed, a.articleDate, c.categoryName')
        ->join('ArticleCategory ac', 'ac.articleID = a.articleID', 'left')
        ->join('Category c', 'c.categoryID = ac.categoryID', 'left')
        ->where('a.status', 'Publish')
        ->where('a.articleDate >=', $since)
        ->groupBy('a.articleID')
        ->orderBy('a.viewed', 'DESC')
        ->limit($limit)
        ->get()->getResultArray();
}
```

---

### 3.17 Modul Background Scheduler & Cron Jobs

| Tugas Cron | File Smarty | File Revamp | Pola Query & Aksi |
| :--- | :--- | :--- | :--- |
| **Auto-Publish Scheduled** | `Backup_CMS_ID/cron/updatedraft.php` | [app/Commands/PublishScheduled.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Commands/PublishScheduled.php) | `UPDATE tblArticle SET status = 'Publish' WHERE status = 'Hold' AND articleDate <= NOW()` |
| **Truncate Newsletter Headline** | `Backup_CMS_ID/cron/truncate_newsletter.php` | [app/Commands/TruncateNewsletterHeadline.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Commands/TruncateNewsletterHeadline.php) | `UPDATE tblArticleHeadline SET status = 0 WHERE category = 'newsletter'` (pukul 08:00, 14:00, 20:00 WIB) |
| **Proses Antrean Push FCM** | `Backup_CMS_ID/cron/pushnotif.php` | [app/Commands/ProcessPushNotif.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Commands/ProcessPushNotif.php) | `SELECT FROM tblPushNotif WHERE status = 'ready'`, dispatch FCM v1 |
| **Pembersihan Log Audit Lama** | `Backup_CMS_ID/cron/deletelog.php` | [app/Commands/CleanupLogs.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Commands/CleanupLogs.php) | `DELETE FROM tblEditorLog WHERE timestamp < NOW() - INTERVAL 90 DAY` |
| **Feed Data Saham & Indeks** | `Backup_CMS_ID/cron/index-saham.php` | [app/Commands/FetchIdxStock.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Commands/FetchIdxStock.php) | Parsing XML/FTP feed IDX, update cache ticker |

---

### 3.18 Modul Evaluasi & Kesiapan SEO (SEO Readiness Scoring)

- **Tabel Terkait**: `tblSeoScores` (skema DDL di §5.2)
- **Status di Smarty CMS**: ❌ Tidak ada (Fitur baru pada Revamp CMS)
- **Lokasi File Revamp**:
  - Controller: [InvestorCMS/app/Controllers/Scoring.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/Scoring.php)
  - Library Evaluator: [InvestorCMS/app/Libraries/SeoScorer.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Libraries/SeoScorer.php)
  - Model: [InvestorCMS/app/Models/SeoScoreModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/SeoScoreModel.php)
  - View: `app/Views/scoring/index.php`

#### Logika Evaluasi Algoritma SEO:
Modul ini menghitung skor kesiapan naskah (0 - 100) sebelum diterbitkan redaksi berdasarkan bobot parameter berikut:
1. **Title Tag Optimization (20%)**: Panjang judul ideal (50-65 karakter), ketiadaan duplikasi, dan penempatan kata kunci utama.
2. **Focus Keyword Presence & Density (25%)**: Keberadaan keyword di paragraf pembuka (lead), kepadatan kata kunci optimal (1.5% - 2.5%), serta kemunculan pada sub-judul.
3. **Content Length & Readability (25%)**: Minimal panjang naskah editorial berita (300+ kata) dan keterbacaan paragraf.
4. **Heading Structure (15%)**: Keberadaan hierarki sub-heading H2 dan H3 dalam naskah.
5. **Meta Description & Media Caption (15%)**: Kelengkapan ringkasan naskah (summary) dan caption gambar penjelas.

Histori penilaian disimpan ke dalam tabel `tblSeoScores` untuk memantau peningkatan kualitas artikel sebelum dipublikasikan. Modul ini juga terhubung dengan layanan Google Gemini AI (`GeminiProvider.php`) untuk memberikan saran penyempurnaan judul dan paragraf pembuka secara instan.

---

## 4. Spesifikasi & Pemanggilan Seluruh Endpoint API

### 4.1 Reader Public APIs

#### 1. List Artikel Publik
- **Endpoint**: `GET /api/articles`
- **Controller**: [InvestorCMS/app/Controllers/Api/ArticleApi.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/Api/ArticleApi.php)
- **Parameters**: `page` (default: 1), `per_page` (default: 10, max: 50), `category` (slug/ID), `q` (kata kunci)
- **Sample Output**:
```json
{
  "status": "success",
  "data": [
    {
      "articleID": 982145,
      "headline": "IHSG Ditutup Menguat Menjelang Keputusan Suku Bunga BI",
      "slug": "ihsg-ditutup-menguat-menjelang-keputusan-suku-bunga-bi",
      "articleDate": "2026-10-20 16:00:00",
      "primary_category": "Market",
      "author_name": "Raka Herdika",
      "thumbnail": "http://localhost:8080/uploads/media/2026/10/ihsg.jpg",
      "viewed": 1420
    }
  ],
  "pagination": { "current_page": 1, "per_page": 10, "total_rows": 145020, "total_pages": 14502 }
}
```

#### 2. Detail Lengkap Artikel
- **Endpoint**: `GET /api/articles/(:num)`
- **Sample Output**:
```json
{
  "status": "success",
  "data": {
    "articleID": 982145,
    "headline": "IHSG Ditutup Menguat Menjelang Keputusan Suku Bunga BI",
    "summary": "Indeks Harga Saham Gabungan melaju di zona hijau...",
    "body": "<p>Jakarta, Investor.id - Indeks Harga Saham Gabungan...</p>",
    "articleDate": "2026-10-20 16:00:00",
    "categories": [{ "categoryID": 10, "categoryName": "Market", "label": "market" }],
    "authors": [{ "authorID": 45, "authorName": "Raka Herdika", "type": "reporter" }],
    "tags": ["IHSG", "Bursa Efek Indonesia", "Suku Bunga"],
    "is_premium": 0
  }
}
```

---

### 4.2 Market Data & Bursa APIs

- **Market Ticker**: `GET /api/market/ticker` $\rightarrow$ Indeks gabungan (IHSG, LQ45, IDX30), nilai tukar mata uang asing, harga komoditas (Minyak WTI, Emas, CPO).
- **Market Movers**: `GET /api/market/movers?type=gainers` $\rightarrow$ 10 saham top gainers, losers, dan most active hari ini.

---

### 4.3 Editorial & Newsroom APIs

#### 1. Autosave Draft Artikel (Asinkron)
- **Endpoint**: `POST /api/post/autosave`
- **Controller**: [InvestorCMS/app/Controllers/Api/PostApi.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/Api/PostApi.php)
- **Request Body (JSON)**:
```json
{
  "articleID": 982145,
  "headline": "IHSG Ditutup Menguat Menjelang Keputusan Suku Bunga BI",
  "summary": "Ringkasan draf...",
  "body": "<p>Isi naskah berita...</p>",
  "categoryID": 10
}
```

#### 2. AI Editorial Assistant (Gemini API)
- **Endpoint**: `POST /api/ai/generate`
- **Request Body**:
```json
{
  "action": "suggest_titles",
  "content": "Isi lengkap naskah berita...",
  "tone": "formal"
}
```
- **Actions yang Didukung**: `suggest_titles` (3 opsi judul SEO-friendly), `generate_summary` (ringkasan 2 paragraf), `proofread` (koreksi EYD/PUEBI), `extract_tags` (rekomendasi tag relevan).

#### 3. Autocomplete Tag & Keyword Redaksi
- **Endpoint**: `POST /post/search-tags` (Payload: `{ "term": "saham" }`)

#### 4. SEO Readiness Scoring & AI Recommendations
- **Endpoint Analisis**: `POST /api/scoring/analyze`
  - **Payload**: `{ "article_id": 982145 }` atau `{ "headline": "...", "body": "...", "keywords": ["saham", "ihsg"] }`
  - **Response**: Breakdown skor parameter SEO (Title, Keyword, Body Length, Headings, Meta) beserta saran perbaikan.
- **Endpoint Histori**: `GET /api/scoring/history/(:num)`
  - Mengambil daftar histori penilaian artikel berdasarkan ID artikel.

---

### 4.4 Reader Authentication APIs

- **Client Login**: `POST /api/auth/client-login` (Payload: `email`, `password`)
- **Client Register**: `POST /api/auth/client-register` (Payload: `name`, `email`, `password`)

---

## 5. Hal-Hal yang Wajib Dilakukan Sebelum Naik ke Produksi (Pre-Production Checklist & Runbook)

Sebelum sistem Revamp CMS dialihkan menjadi sistem produksi utama (*cutover*), tim teknis (Sysadmin, DevOps, dan Software Engineer) wajib menjalankan serangkaian tahapan persiapan dan validasi berikut secara berurutan:

### 5.1 Verifikasi Konfigurasi Environment (`.env`)

Pastikan seluruh parameter pada file `.env` di server produksi telah disesuaikan dengan lingkungan operasional live:

```ini
# 1. Mode Operasional Aplikasi
CI_ENVIRONMENT = production

# 2. Base URL Resmi Portal CMS
app.baseURL = 'https://cms.investor.id/'

# 3. Konfigurasi Koneksi Database Produksi (Wajib DBPrefix = tbl)
database.default.hostname = 127.0.0.1
database.default.database = investor
database.default.username = prod_user
database.default.password = [PROD_PASSWORD]
database.default.DBDriver = MySQLi
database.default.DBPrefix = tbl
database.default.DBDebug = false
database.default.charset  = utf8mb4
database.default.DBCollat = utf8mb4_general_ci

> **Catatan Konsistensi Collation**: Database eksisting Smarty dan server database produksi menggunakan `utf8mb4_general_ci`. Pastikan konfigurasi `.env` produksi tidak diubah menjadi `utf8mb4_unicode_ci` agar tidak terjadi *illegal mix of collations* saat melakukan operasi `JOIN` atau `UNION` pada tabel-tabel lama.

# 4. Kredensial Email Gateway 2FA (Brevo SMTP TLS)
email.SMTPHost = 'smtp-relay.brevo.com'
email.SMTPPort = 587
email.SMTPCrypto = 'tls'
email.SMTPUser = '[BREVO_SMTP_USER]'
email.SMTPPass = '[BREVO_SMTP_PASSWORD]'
email.fromEmail = 'noreply@b-universe.id'
email.fromName = 'CMS Investor.ID'

# 5. Media Storage Driver (Cluster Intranet Produksi)
MEDIA_STORAGE_DRIVER = smarty_api
MEDIA_API_ENDPOINT = 'http://10.0.4.16/imgapi/upload/'
MEDIA_SECONDARY_API_ENDPOINT = 'http://172.20.6.110:8080/imgapi/upload/'
MEDIA_CDN_BASE_URL = 'https://img.beritasatu.com/'
MEDIA_CDN_ORIGINAL_URL = 'https://img2.beritasatu.com/investor/'

# 6. Integrasi AI Editorial (Gemini API)
GEMINI_API_KEY = [LIVE_GEMINI_KEY]
```

---

### 5.2 Eksekusi Skema Database, Tabel Baru & Indexing Kinerja

Jalankan perintah migrasi atau eksekusi DDL berikut pada database `investor` untuk memastikan tabel-tabel pendukung baru dan indeks query kinerja tinggi telah terpasang:

```sql
-- 1. Tabel Sub-headline & Poin Penting Artikel
CREATE TABLE IF NOT EXISTS `tblArticlePoints` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `articleID` INT(11) NOT NULL,
  `point_order` INT(11) NOT NULL DEFAULT 1,
  `point_text` TEXT NOT NULL,
  `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_articleID` (`articleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel Audit Trail Perubahan Posisi Headline
CREATE TABLE IF NOT EXISTS `tblArticleHeadlineHistory` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `articleID` INT(11) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'home',
  `action` ENUM('ADD','REMOVE','REORDER','TRUNCATE') NOT NULL,
  `position` INT(11) DEFAULT NULL,
  `user` VARCHAR(100) NOT NULL DEFAULT 'SYSTEM',
  `notes` VARCHAR(255) DEFAULT NULL,
  `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_history_articleID` (`articleID`),
  KEY `idx_history_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel Skoring SEO Editorial
CREATE TABLE IF NOT EXISTS `tblSeoScores` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `articleID` INT(11) NOT NULL,
  `score` INT(3) NOT NULL DEFAULT 0,
  `keyword` VARCHAR(255) DEFAULT NULL,
  `density` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `recommendations` TEXT DEFAULT NULL,
  `updatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_article_seo` (`articleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel Audit Log Asisten AI
CREATE TABLE IF NOT EXISTS `tblAiGeneratedLogs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userID` INT(11) NOT NULL,
  `articleID` INT(11) DEFAULT NULL,
  `action` VARCHAR(50) NOT NULL,
  `prompt_tokens` INT(11) DEFAULT 0,
  `completion_tokens` INT(11) DEFAULT 0,
  `model_used` VARCHAR(50) NOT NULL,
  `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ai_user` (`userID`),
  KEY `idx_ai_article` (`articleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Indexing Kinerja untuk Tabel Utama Berkapasitas Jutaan Baris
ALTER TABLE `tblArticle` ADD INDEX IF NOT EXISTS `idx_status_date` (`status`, `articleDate`);
ALTER TABLE `tblArticleCategory` ADD INDEX IF NOT EXISTS `idx_art_cat` (`articleID`, `categoryID`);
ALTER TABLE `tblMediaArticle` ADD INDEX IF NOT EXISTS `idx_media_art_pos` (`articleID`, `position`);
ALTER TABLE `tblArticleHeadline` ADD INDEX IF NOT EXISTS `idx_cat_status_num` (`category`, `status`, `num`);
ALTER TABLE `tblArticleKeyword` ADD INDEX IF NOT EXISTS `idx_kw_art` (`keywordID`, `articleID`);
```

*Alternatif eksekusi via Spark CLI*:
```bash
php spark migrate
```

---

### 5.3 Konfigurasi Web Server & Isolasi Direktori Publik

Arahkan *DocumentRoot* virtual host Nginx atau Apache ke sub-direktori `/public`. File-file sensitif seperti `.env`, folder `/app`, `/system`, dan `/writable` sama sekali tidak boleh terekspos ke internet.

**Contoh Blok Konfigurasi Nginx (`/etc/nginx/sites-available/investorcms.conf`):**
```nginx
server {
    listen 80;
    server_name cms.investor.id;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name cms.investor.id;

    # SSL Certificate
    ssl_certificate /etc/letsencrypt/live/cms.investor.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/cms.investor.id/privkey.pem;

    # Document Root wajib mengarah ke folder /public
    root /var/www/investorcms/public;
    index index.php index.html;

    client_max_body_size 32M;

    # Routing CodeIgniter 4
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    # Blokir akses langsung ke berkas tersembunyi
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # PHP-FPM FastCGI
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock; # Atau /run/php/php8.2-fpm.sock (sesuai versi PHP aktif)
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

### 5.4 Perizinan Hak Akses Folder Server (`writable/`)

Aplikasi CodeIgniter 4 membutuhkan hak akses tulis pada direktori kerja untuk penyimpanan file cache, sesi, file log, dan folder unggahan lokal:

```bash
# Menetapkan ownership ke user web server
chown -R www-data:www-data /var/www/investorcms

# Memberikan akses tulis ke direktori kerja aplikasi
chmod -R 775 /var/www/investorcms/writable
chmod -R 775 /var/www/investorcms/public/uploads

# Memastikan direktori cache dan logs siap digunakan
mkdir -p /var/www/investorcms/writable/cache
mkdir -p /var/www/investorcms/writable/logs
mkdir -p /var/www/investorcms/writable/session
```

---

### 5.5 Penjadwalan Crontab Otomatisasi Linux

Buka editor crontab pengguna web server (`crontab -e -u www-data`) dan pasang tugas otomatisasi berikut:

```bash
# 1. Terbitkan naskah terjadwal yang telah melewati jadwal tayang (tiap 1 menit)
* * * * * cd /var/www/investorcms && php spark cron:publish-scheduled > /dev/null 2>&1

# 2. Kosongkan / truncate slot headline newsletter (pukul 08:00, 14:00, 20:00 WIB)
0 8,14,20 * * * cd /var/www/investorcms && php spark cron:truncate-newsletter > /dev/null 2>&1

# 3. Tarik pergerakan data bursa saham IDX (tiap 10 menit pada jam perdagangan Senin-Jumat)
*/10 9-16 * * 1-5 cd /var/www/investorcms && php spark cron:fetch-idx-stock > /dev/null 2>&1

# 4. Proses antrean siaran push notification FCM (tiap 1 menit)
* * * * * cd /var/www/investorcms && php spark cron:process-push-notif > /dev/null 2>&1

# 5. Pembersihan log audit editor berusia lebih dari 90 hari (tiap hari pukul 02:00 WIB)
0 2 * * * cd /var/www/investorcms && php spark cron:cleanup-logs > /dev/null 2>&1
```

---

### 5.6 Pengujian Pre-Flight Operasional (Smoke Test Checklist)

Sebelum merilis URL CMS ke seluruh staf redaksi, lakukan verifikasi manual pada poin-poin berikut:

- [ ] **Uji Login & 2FA**: Lakukan login menggunakan akun uji coba. Pastikan email OTP 6-digit masuk ke inbox dalam < 10 detik, input kode verifikasi, dan pastikan dashboard terbuka normal.
- [ ] **Uji Sesi 5 Hari**: Logout dan login kembali dengan akun yang sama dalam hari yang sama. Pastikan sistem langsung masuk tanpa meminta OTP ulang (validasi grace period 5 hari).
- [ ] **Uji Pembuatan Artikel**: Buat draft naskah berita baru lengkap dengan judul, slug auto-generate, kanal, sub-kanal, keyword chips, dan cover foto. Simpan naskah dan pastikan muncul di daftar artikel.
- [ ] **Uji Pessimistic Lock**: Buka naskah yang sama dari dua browser berbeda dengan akun berbeda. Pastikan browser kedua menampilkan peringatan bahwa naskah sedang dikunci oleh redaktur pertama.
- [ ] **Uji Kurasi Headline**: Masukkan naskah ke slot Headline kategori 'home' dan lakukan drag-and-drop urutan nomor 1 s.d. 5. Pastikan perubahan nomor tersimpan di database.
- [ ] **Uji Upload Media & Reverse Lookup**: Unggah foto baru ke Media Library, gunakan sebagai cover naskah, lalu coba hapus foto tersebut dari Media Library. Pastikan sistem memblokir penghapusan karena foto sedang aktif dipakai artikel.
- [ ] **Uji Ekspor Laporan**: Buka menu Laporan Editorial dan ekspor publish log ke format CSV. Buka di spreadsheet dan pastikan karakter serta inisial alias editor terbaca rapi.

---

### 5.7 Rencana Kontingensi & Prosedur Rollback

Jika pada hari peluncuran ditemukan kendala kritis yang tidak dapat diselesaikan dalam window maintenance (misal: gangguan koneksi database cluster atau kendala jaringan intranet):

1. **Pengalihan DNS / Virtual Host**:
   - Kembalikan konfigurasi web server Nginx/Apache untuk mengarahkan domain `cms.investor.id` ke direktori sistem lama Smarty CMS (`/var/www/backup_cms_id/administration/pages/login/index.php`).
   - Reload web server: `sudo systemctl reload nginx`.
2. **Integritas Basis Data**:
   - Karena Revamp CMS menggunakan skema nama tabel fisik asli (`tblArticle`, `tblArticleAuthor`, `tblCategory`, `tblUserAdministrator`), data naskah baru yang sempat dibuat di Revamp tetap tersimpan aman dan dapat langsung dibaca oleh sistem Smarty CMS lama tanpa perlu konversi ulang.
3. **Penyelidikan Masalah**:
   - Periksa file log error CodeIgniter pada direktori `/var/www/investorcms/writable/logs/log-YYYY-MM-DD.php` untuk menelusuri penyebab kegagalan teknis.


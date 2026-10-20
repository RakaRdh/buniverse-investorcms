# Revamp Investor CMS Technical Documentation

Dokumentasi teknis komprehensif mengenai arsitektur sistem, komparasi mendalam seluruh query database antara Smarty CMS dan Revamp CMS (CodeIgniter 4), spesifikasi seluruh pemanggilan API, panduan pra-produksi (*pre-production checklist*), serta catatan integrasi frontend.

---

## Daftar Isi
1. [Ringkasan Arsitektur & Perubahan Sistem](#1-ringkasan-arsitektur--perubahan-sistem)
   - [1.1 Perbandingan Arsitektur](#11-perbandingan-arsitektur)
   - [1.2 Konvensi Penamaan Tabel & Penanganan DBPrefix](#12-konvensi-penamaan-tabel--penanganan-dbprefix)
   - [1.3 Peningkatan Keamanan & Autentikasi](#13-peningkatan-keamanan--autentikasi)
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
4. [Spesifikasi & Pemanggilan Seluruh Endpoint API](#4-spesifikasi--pemanggilan-seluruh-endpoint-api)
   - [4.1 Reader Public APIs](#41-reader-public-apis)
   - [4.2 Market Data & Bursa APIs](#42-market-data--bursa-apis)
   - [4.3 Editorial & Newsroom APIs](#43-editorial--newsroom-apis)
   - [4.4 Reader Authentication APIs](#44-reader-authentication-apis)
5. [Panduan Lengkap Pra-Produksi (Pre-Production Checklist)](#5-panduan-lengkap-pra-produksi-pre-production-checklist)
   - [5.1 Database DDL untuk Tabel Baru & Indexing Performa](#51-database-ddl-untuk-tabel-baru--indexing-performa)
   - [5.2 Penyesuaian Variabel Environment (`.env`)](#52-penyesuaian-variabel-environment-env)
   - [5.3 Konfigurasi Crontab Linux Produksi](#53-konfigurasi-crontab-linux-produksi)
   - [5.4 Hak Akses Folder & Konfigurasi Web Server](#54-hak-akses-folder--konfigurasi-web-server)

---

## 1. Ringkasan Arsitektur & Perubahan Sistem

### 1.1 Perbandingan Arsitektur

| Karakteristik | Smarty CMS | Revamp CMS |
| :--- | :--- | :--- |
| **Framework & Versi** | PHP Prosedural Native + Smarty 2/3 | CodeIgniter 4.7 (PHP 8.2+, MVC, Strict Types) |
| **Pola Routing** | Berbasis file fisik (`/administration/pages/article/index.php`) | Centralized Router (`app/Config/Routes.php`) dengan Route Guard & Filter |
| **Interaksi Database** | Prosedural `adodb` / raw string MySQL query concats | CodeIgniter 4 Query Builder & Model Layer |
| **Konvensi Case-Sensitivity** | Raw SQL dengan string literal (`tblArticle`, `tblCategory`) | Model & Query Builder CamelCase (`Article`, `Category`), otomatis di-prefix `tbl` |
| **Pemisahan Publik** | File PHP terekspos di root aplikasi | Web root terisolasi di `/public`, file inti di `/app` aman dari akses publik |
| **Pengujian (Testing)** | Manual via browser | 111 Automated Unit Tests (PHPUnit 10.5) dengan 428 assertions |

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

1. **Formula Salt & Password Smarty**:
   ```php
   $salt = hash('SHA512', '[' . strtolower($username) . '>|<' . $password . ']', false);
   $hash = hash('SHA512', $salt . 'B3!21t454t03' . $password . $salt, false);
   ```
2. **Multi-Strategy Authentication & Auto-Upgrade**:
   - Sistem memvalidasi akun menggunakan formula SHA-512 Smarty di atas.
   - Jika akun lama terdeteksi masih menggunakan hash MD5 murni (`strlen(password) === 32`), sistem memvalidasi `md5($password) === $user['password']`.
   - Jika lolos, sistem **secara otomatis meng-upgrade hash akun ke format SHA-512 (128 karakter)** ke dalam database tanpa mengharuskan pengguna melakukan reset password manual.
3. **Sanitasi HTML Tingkat Lanjut (`HtmlSanitizer`)**:
   - Menggantikan sanitasi parsial `addslashes` pada Smarty.
   - Menghapus tag `<script>`, `<style>`, `<iframe>` tidak terpercaya, dan atribut inline event seperti `onclick`, `onload`, serta protokol pseudo `javascript:`.
   - Tetap mempertahankan tag format jurnalistik sah (`<p>`, `<strong>`, `<em>`, `<blockquote>`, `<a>`, `<img>`, `<table>`).

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

- **Tabel Terkait**: `tblArticle`, `tblArticleCategory`, `tblCategory`, `tblArticleMeta`, `tblArticleAuthors`, `tblAuthors`, `tblArticleKeyword`, `tblKeyword`
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
SELECT * FROM tblArticleAuthors WHERE articleID = 100;
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

- **Tabel Terkait**: `tblAuthors`, `tblArticleAuthors` (atau `tblArticleAuthor`)
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
        ->join('ArticleAuthors aa', 'aa.authorID = a.authorID', 'left')
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
    $builder = $this->db->table('File f')
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

---

### 4.4 Reader Authentication APIs

- **Client Login**: `POST /api/auth/client-login` (Payload: `email`, `password`)
- **Client Register**: `POST /api/auth/client-register` (Payload: `name`, `email`, `password`)

---

## 5. Panduan Lengkap Pra-Produksi (Pre-Production Checklist)

Berikut adalah daftar pekerjaan teknis yang **wajib dieksekusi** sebelum aplikasi Revamp CMS di-deploy ke server produksi:

### 5.1 Database DDL untuk Tabel Baru & Indexing Performa

Eksekusi skrip SQL berikut di database MySQL produksi (`investor`):

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

-- 5. Indexing Kinerja untuk Tabel Utama
ALTER TABLE `tblArticle` ADD INDEX IF NOT EXISTS `idx_status_date` (`status`, `articleDate`);
ALTER TABLE `tblArticleCategory` ADD INDEX IF NOT EXISTS `idx_art_cat` (`articleID`, `categoryID`);
ALTER TABLE `tblMediaArticle` ADD INDEX IF NOT EXISTS `idx_media_art_pos` (`articleID`, `position`);
ALTER TABLE `tblArticleHeadline` ADD INDEX IF NOT EXISTS `idx_cat_status_num` (`category`, `status`, `num`);
```

*Atau melalui CLI*:
```bash
php spark migrate
```

---

### 5.2 Penyesuaian Variabel Environment (`.env`)

Ubah parameter kunci di file `.env` server produksi:
```ini
# Environment Mode
CI_ENVIRONMENT = production

# URL Domain CMS Live
app.baseURL = 'https://cms.investor.id/'

# Konfigurasi Database (DBPrefix WAJIB 'tbl')
database.default.hostname = 127.0.0.1
database.default.database = investor
database.default.username = prod_user
database.default.password = [PROD_PASSWORD]
database.default.DBDriver = MySQLi
database.default.DBPrefix = tbl
database.default.DBDebug = false

# Driver Media Penyimpanan (AWS S3 & CDN Produksi)
MEDIA_STORAGE_DRIVER = smarty_api
MEDIA_API_ENDPOINT = 'http://10.0.4.16/imgapi/upload/'
MEDIA_SECONDARY_API_ENDPOINT = 'http://172.20.6.110:8080/imgapi/upload/'
MEDIA_CDN_BASE_URL = 'https://img.beritasatu.com/'
MEDIA_CDN_ORIGINAL_URL = 'https://img2.beritasatu.com/investor/'

# Gemini AI API Key
GEMINI_API_KEY = [LIVE_GEMINI_KEY]
```

---

### 5.3 Konfigurasi Crontab Linux Produksi

Buka crontab user web server (`crontab -e -u www-data`) dan pasang jadwal berikut:

```bash
# 1. Publikasikan artikel Hold yang telah melewati articleDate (tiap 1 menit)
* * * * * cd /var/www/investorcms && php spark cron:publish-scheduled > /dev/null 2>&1

# 2. Reset / Truncate slot headline newsletter (pukul 08:00, 14:00, 20:00 WIB)
0 8,14,20 * * * cd /var/www/investorcms && php spark cron:truncate-newsletter > /dev/null 2>&1

# 3. Proses antrean pengiriman push notification FCM (tiap 5 menit)
*/5 * * * * cd /var/www/investorcms && php spark cron:process-push > /dev/null 2>&1

# 4. Bersihkan log audit editor yang berusia lebih dari 90 hari (tiap hari pukul 02:00 WIB)
0 2 * * * cd /var/www/investorcms && php spark cron:cleanup-logs > /dev/null 2>&1
```

---

### 5.4 Hak Akses Folder & Konfigurasi Web Server

1. **Permissions**:
   ```bash
   chown -R www-data:www-data /var/www/investorcms
   chmod -R 775 /var/www/investorcms/writable
   chmod -R 775 /var/www/investorcms/public/uploads
   ```

2. **Web Server Root Pointing**:
   Pastikan web server (Nginx / Apache) mengarahkan *root direktori* ke `/var/www/investorcms/public`, **bukan** ke folder root proyek, agar file inti aplikasi dan file `.env` terlindungi dari akses publik.

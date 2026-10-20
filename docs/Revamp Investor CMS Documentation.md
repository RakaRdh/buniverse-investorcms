# Revamp Investor CMS Documentation

Dokumentasi teknis menyeluruh mengenai arsitektur, komparasi query database antara Smarty CMS dan Revamp CMS, pemanggilan API, panduan pra-produksi (pre-prod checklist), serta catatan integrasi frontend.

---

## Daftar Isi
1. [Ringkasan Arsitektur & Perubahan Utama](#1-ringkasan-arsitektur--perubahan-utama)
2. [Catatan Kritis: Status UI Preview Artikel](#2-catatan-kritis-status-ui-preview-artikel)
3. [Komparasi Query Database (Smarty vs Revamp)](#3-komparasi-query-database-smarty-vs-revamp)
   - [3.1 Daftar Artikel (Listing & Filter)](#31-daftar-artikel-listing--filter)
   - [3.2 Detail Artikel & Relasi Multi Sub-Kanal](#32-detail-artikel--relasi-multi-sub-kanal)
   - [3.3 Headline Management & Sorting Slot](#33-headline-management--sorting-slot)
   - [3.4 Media & Pengelolaan Gambar](#34-media--pengelolaan-gambar)
   - [3.5 Autentikasi Pengguna & Skema Hash Password](#35-autentikasi-pengguna--skema-hash-password)
   - [3.6 Edit Lock (Post Locking)](#36-edit-lock-post-locking)
   - [3.7 Riwayat Revisi (Revision History)](#37-riwayat-revisi-revision-history)
4. [Spesifikasi & Pemanggilan API](#4-spesifikasi--pemanggilan-api)
   - [4.1 Public Reader APIs](#41-public-reader-apis)
   - [4.2 Market Data APIs](#42-market-data-apis)
   - [4.3 Editorial & CMS APIs](#43-editorial--cms-apis)
   - [4.4 Reader Auth APIs](#44-reader-auth-apis)
5. [Panduan Sebelum Produksi (Pre-Production Checklist)](#5-panduan-sebelum-produksi-pre-production-checklist)
   - [5.1 Database DDL untuk Tabel Baru & Indexing](#51-database-ddl-untuk-tabel-baru--indexing)
   - [5.2 Konfigurasi Environment (`.env`)](#52-konfigurasi-environment-env)
   - [5.3 Konfigurasi Crontab / Task Scheduler](#53-konfigurasi-crontab--task-scheduler)
   - [5.4 Izin Direktori (Permissions) & Web Server](#54-izin-direktori-permissions--web-server)

---

## 1. Ringkasan Arsitektur & Perubahan Utama

| Aspek | Smarty CMS | Revamp CMS |
| :--- | :--- | :--- |
| **Framework** | PHP Native / Prosedural + Smarty 2/3 Template Engine | CodeIgniter 4.7 (PHP 8.2+, Strict Types, MVC) |
| **Database Layer** | `adodb` / raw MySQL query string | CI4 Query Builder & Model Layer |
| **Konvensi Tabel** | MySQL Case-sensitive (`tblArticle`, `tblCategory`, dll.) | Model menggunakan CamelCase (`Article`, `Category`), otomatis di-prefix `tbl` via `database.default.DBPrefix = tbl` |
| **Autentikasi CMS** | SHA-512 bertingkat + Salt internal & legacy MD5 | Multi-strategy auth: SHA-512 bertingkat (paritas Smarty) + auto-upgrade dari MD5 saat login pertama |
| **Sanitasi HTML** | `addslashes` / sanitasi parsial manual | `HtmlSanitizer` library: whitelist tag jurnalistik, pembersihan tag `<script>` dan atribut event inline |
| **Asinkron & Autosave** | Belum terintegrasi di form editor | Endpoint autosave berkala (`/api/post/autosave`) dengan verifikasi lock |
| **Fitur AI** | Belum ada | Integrasi Google Gemini API untuk saran judul, ringkasan, dan audit editorial |

---

## 2. Catatan Kritis: Status UI Preview Artikel

> **PERHATIAN PENTING (EDITORIAL & FRONTEND INTEGRATION):**
> 
> Halaman preview artikel yang dapat diakses melalui rute `/post/preview/{id}` saat ini menggunakan template pratinjau internal berbasis layout shell CMS. Halaman ini **belum menggunakan template dan styling CSS otentik dari portal publik investor.id**.
>
> **Langkah yang harus dilakukan sebelum perilisan penuh ke tim redaksi:**
> 1. Ekstrak CSS layout artikel dari portal publik `investor.id` (khususnya typography font body, styling quote/blockquote, tata letak gambar lead, inline image caption, dan embed box).
> 2. Sesuaikan file view [app/Views/post/preview.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Views/post/preview.php) agar mencerminkan tampilan visual live portal investor.id secara presisi, sehingga preview yang dilihat redaktur sebelum publish 100% identik dengan hasil akhir pembaca.

---

## 3. Komparasi Query Database (Smarty vs Revamp)

### 3.1 Daftar Artikel (Listing & Filter)

#### Lokasi Berkas:
- **Smarty CMS**: `Backup_CMS_ID/class/Article.class.php` $\rightarrow$ fungsi `Article::read()`
- **Revamp CMS**: [InvestorCMS/app/Models/ArticleModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/ArticleModel.php) $\rightarrow$ fungsi `getArticlesWithRelations()`

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
*Catatan Perubahan*: Di Revamp, thumbnail diikat secara spesifik pada `ma.position = 0` dan ditambahkan join ke `lock_user` untuk visualisasi status artikel yang sedang diedit redaktur lain secara real-time.

---

### 3.2 Detail Artikel & Relasi Multi Sub-Kanal

#### Lokasi Berkas:
- **Smarty CMS**: `Backup_CMS_ID/class/Article.class.php` $\rightarrow$ fungsi `Article::edit()`, `Article::getAuthor()`, `Article::getTags()`
- **Revamp CMS**: [InvestorCMS/app/Models/ArticleModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/ArticleModel.php) $\rightarrow$ fungsi `getArticleDetail()`

#### Pola di Smarty CMS:
Query dilakukan secara terpisah-pisah melalui pemanggilan prosedural beruntun:
1. `SELECT * FROM tblArticle WHERE articleID = ?`
2. `SELECT * FROM tblArticleCategory WHERE articleID = ?`
3. `SELECT * FROM tblArticleAuthors WHERE articleID = ?`
4. `SELECT * FROM tblArticleKeyword WHERE articleID = ?`

#### Query Terstruktur di Revamp CMS:
Revamp mengambil data utama beserta metadata dalam satu eksekusi, lalu melengkapi relasi relasionalnya secara terstruktur:
```php
// 1. Ekstraksi artikel dan metadata (upper_deck, news_source, premium)
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

// 2. Multi Sub-Kanal (Master vs Secondary Channels)
$linkedCategories = $this->db->table('ArticleCategory ac')
    ->select('c.categoryID, c.categoryName, c.label, c.parent')
    ->join('Category c', 'c.categoryID = ac.categoryID')
    ->where('ac.articleID', $id)
    ->get()->getResultArray();

// 3. Authors (Reporter, Editor, Fotografer)
$linkedAuthors = $this->db->table('ArticleAuthors aa')
    ->select('a.authorID, a.authorName, a.type')
    ->join('Authors a', 'a.authorID = aa.authorID')
    ->where('aa.articleID', $id)
    ->get()->getResultArray();
```

---

### 3.3 Headline Management & Sorting Slot

#### Lokasi Berkas:
- **Smarty CMS**: `Backup_CMS_ID/class/ArticleHeadline.class.php` $\rightarrow$ fungsi `ArticleHeadline::read()`, `ArticleHeadline::update()`
- **Revamp CMS**: [InvestorCMS/app/Models/HeadlineModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/HeadlineModel.php) & [InvestorCMS/app/Commands/TruncateNewsletterHeadline.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Commands/TruncateNewsletterHeadline.php)

#### Query di Smarty CMS:
```sql
-- Mengambil headline aktif per kategori kanal
SELECT h.*, a.headline, a.articleDate 
FROM tblArticleHeadline h
INNER JOIN tblArticle a ON a.articleID = h.articleID
WHERE h.category = 'home' AND h.status = 1
ORDER BY h.num ASC;
```

#### Query di Revamp CMS:
```php
public function getActiveHeadlines(?string $category = null): array
{
    $builder = $this->db->table('ArticleHeadline h')
        ->select('
            h.id,
            h.articleID,
            h.num,
            h.category,
            h.timestamp,
            h.set_by,
            a.headline,
            a.slug,
            a.articleDate,
            a.status as article_status,
            c.categoryName,
            m.image as thumbnail
        ')
        ->join('Article a', 'a.articleID = h.articleID', 'inner')
        ->join('ArticleCategory ac', 'ac.articleID = a.articleID', 'left')
        ->join('Category c', 'c.categoryID = ac.categoryID', 'left')
        ->join('MediaArticle ma', 'ma.articleID = a.articleID AND ma.position = 0', 'left')
        ->join('Media m', 'm.mediaID = ma.mediaID', 'left')
        ->where('h.status', 1);

    if ($category !== null) {
        $builder->where('h.category', $category);
    }

    return $builder->orderBy('h.num', 'ASC')->get()->getResultArray();
}
```

---

### 3.4 Media & Pengelolaan Gambar

#### Lokasi Berkas:
- **Smarty CMS**: `Backup_CMS_ID/class/Media.class.php`
- **Revamp CMS**: [InvestorCMS/app/Models/MediaModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/MediaModel.php)

#### Relasi Database:
- `tblMedia`: Berisi metadata gambar utama (`mediaID`, `caption`, `image`, `photographer`, `createdAt`).
- `tblMediaArticle`: Tabel pivot relasi banyak-ke-banyak antara media dan artikel (`mediaID`, `articleID`, `position`).

#### Query Deteksi Penggunaan Media pada Revamp:
```php
public function getArticlesUsingMedia(int $mediaId): array
{
    return $this->db->table('MediaArticle ma')
        ->select('a.articleID, a.headline, a.articleDate, a.status, ma.position')
        ->join('Article a', 'a.articleID = ma.articleID')
        ->where('ma.mediaID', $mediaId)
        ->orderBy('a.articleDate', 'DESC')
        ->get()->getResultArray();
}
```

---

### 3.5 Autentikasi Pengguna & Skema Hash Password

#### Lokasi Berkas:
- **Smarty CMS**: `Backup_CMS_ID/class/Administrator.class.php` $\rightarrow$ fungsi `Administrator::auth()`
- **Revamp CMS**: [InvestorCMS/app/Models/AdminModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/AdminModel.php) $\rightarrow$ fungsi `authenticate()` & `encodePassword()`

#### Formula Hash Smarty CMS:
```php
// Salt diturunkan dari lowercase username dan plain password:
$salt = hash('SHA512', '[' . strtolower($username) . '>|<' . $password . ']', false);
// Password hash final:
$hash = hash('SHA512', $salt . 'B3!21t454t03' . $password . $salt, false);
```

#### Implementasi Multi-Strategy & Auto-Upgrade di Revamp CMS:
1. **Strategy 1**: Verifikasi menggunakan skema SHA-512 Smarty di atas (`verifyPassword()`).
2. **Strategy 2**: Verifikasi format hash bawaan PHP `password_verify()` (Bcrypt/Argon2).
3. **Strategy 3**: Verifikasi SHA-512 dengan salt custom per-pengguna jika ada.
4. **Strategy 4 (Auto-Upgrade)**: Jika akun lama masih tersimpan dengan hash MD5 murni (`strlen(password) === 32`), sistem memvalidasi `md5($password) === $user['password']`. Jika lolos, hash akun otomatis di-*upgrade* di database ke format SHA-512 128 karakter tanpa meminta pengguna melakukan reset password manual.

---

### 3.6 Edit Lock (Post Locking)

#### Lokasi Berkas:
- **Smarty CMS**: `Backup_CMS_ID/class/Article.class.php` $\rightarrow$ fungsi `Article::PostLock()`, `Article::ReleaseLock()`
- **Revamp CMS**: [InvestorCMS/app/Models/ArticleModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/ArticleModel.php) $\rightarrow$ fungsi `acquireLock()`, `releaseLock()`, `forceUnlock()`

#### Perilaku:
1. Kolom `editlock` pada tabel `tblArticle` menyimpan `userID` admin yang sedang membuka artikel.
2. Saat redaktur lain mencoba membuka artikel yang sedang dikunci, sistem mengembalikan data redaktur pemegang lock (`locked_by_name`, `locked_by_username`).
3. Administrator atau Redpel memiliki hak untuk mengeksekusi `forceUnlock()` untuk melepaskan kuncian yang tertinggal akibat browser tertutup mendadak.

---

### 3.7 Riwayat Revisi (Revision History)

#### Lokasi Berkas:
- **Smarty CMS**: `Backup_CMS_ID/class/Article.class.php` $\rightarrow$ fungsi `Article::Revision()`
- **Revamp CMS**: [InvestorCMS/app/Models/ArticleModel.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Models/ArticleModel.php) $\rightarrow$ fungsi `getRevisionHistory()`, `getRevisionDetail()`

#### Skema Tabel: `tblArticleHistory`
Menyimpan snapshot tiap kali artikel diubah:
```sql
SELECT 
    h.id, h.timestamp, h.articleID, h.headline, h.editorID,
    u.name as editor_name, h.action, h.status_prev, h.status_after
FROM tblArticleHistory h
LEFT JOIN tblArticle a ON a.articleID = h.articleID
LEFT JOIN tblUserAdministrator u ON u.userID = h.editorID
WHERE h.articleID = ?
ORDER BY h.timestamp DESC;
```

---

## 4. Spesifikasi & Pemanggilan API

Seluruh endpoint API mengembalikan format response JSON standar:
```json
{
  "status": "success",
  "data": { ... },
  "message": "Deskripsi singkat hasil eksekusi"
}
```

### 4.1 Public Reader APIs

#### 1. List Artikel Publik
- **Endpoint**: `GET /api/articles`
- **Controller**: [InvestorCMS/app/Controllers/Api/ArticleApi.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/Api/ArticleApi.php)
- **Query Parameters**:
  - `page` (int, default: 1)
  - `per_page` (int, default: 10, max: 50)
  - `category` (int/string, opsional)
  - `q` (string pencarian judul/keyword)
- **Response Sample**:
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
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total_rows": 145020,
    "total_pages": 14502
  }
}
```

#### 2. Detail Artikel Publik
- **Endpoint**: `GET /api/articles/(:num)`
- **Response Sample**:
```json
{
  "status": "success",
  "data": {
    "articleID": 982145,
    "headline": "IHSG Ditutup Menguat Menjelang Keputusan Suku Bunga BI",
    "summary": "Indeks Harga Saham Gabungan melaju di zona hijau...",
    "body": "<p>Jakarta, Investor.id - Indeks Harga Saham Gabungan...</p>",
    "articleDate": "2026-10-20 16:00:00",
    "categories": [
      { "categoryID": 10, "categoryName": "Market", "label": "market" }
    ],
    "authors": [
      { "authorID": 45, "authorName": "Raka Herdika", "type": "reporter" }
    ],
    "tags": ["IHSG", "Bursa Efek Indonesia", "Suku Bunga"],
    "is_premium": 0
  }
}
```

---

### 4.2 Market Data APIs

#### 1. Market Ticker
- **Endpoint**: `GET /api/market/ticker`
- **Controller**: [InvestorCMS/app/Controllers/Api/MarketApi.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/Api/MarketApi.php)
- **Output**: Indeks bursa (IHSG, LQ45, IDX30), nilai tukar (USD/IDR, EUR/IDR), dan harga komoditas utama (Minyak, Emas, CPO).

#### 2. Market Movers
- **Endpoint**: `GET /api/market/movers`
- **Query Parameters**: `type` (`gainers`, `losers`, `active`)
- **Output**: Daftar 10 saham teratas berdasarkan kenaikan, penurunan, atau volume nilai transaksi harian.

---

### 4.3 Editorial & CMS APIs

#### 1. Autosave Draft Artikel
- **Endpoint**: `POST /api/post/autosave`
- **Controller**: [InvestorCMS/app/Controllers/Api/PostApi.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/Api/PostApi.php)
- **Headers**: `X-Requested-With: XMLHttpRequest`, `Content-Type: application/json`
- **Request Body**:
```json
{
  "articleID": 982145,
  "headline": "IHSG Ditutup Menguat...",
  "summary": "Ringkasan draf...",
  "body": "<p>Isi draf sementara...</p>",
  "categoryID": 10
}
```
- **Response**:
```json
{
  "status": "success",
  "message": "Draf otomatis berhasil disimpan",
  "articleID": 982145,
  "savedAt": "2026-10-20 15:35:10"
}
```

#### 2. AI Editorial Assistant (Gemini API)
- **Endpoint**: `POST /api/ai/generate`
- **Controller**: [InvestorCMS/app/Controllers/AiAssistant.php](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/app/Controllers/AiAssistant.php)
- **Request Body**:
```json
{
  "action": "suggest_titles",
  "content": "Isi naskah berita...",
  "tone": "formal"
}
```
- **Action types**: `suggest_titles`, `generate_summary`, `proofread`, `extract_tags`.

#### 3. Autocomplete Tag / Keyword
- **Endpoint**: `POST /post/search-tags`
- **Request**: Form data atau JSON `{ "term": "saham" }`
- **Response**: Array label dan value keyword terdaftar untuk dropdown form editor.

---

### 4.4 Reader Auth APIs

- **Client Login**: `POST /api/auth/client-login` (Payload: `email`, `password`)
- **Client Register**: `POST /api/auth/client-register` (Payload: `name`, `email`, `password`)

---

## 5. Panduan Sebelum Produksi (Pre-Production Checklist)

Berikut adalah daftar pekerjaan teknis yang **wajib dieksekusi** sebelum aplikasi Revamp CMS di-deploy ke server produksi:

### 5.1 Database DDL untuk Tabel Baru & Indexing

Jalankan query DDL berikut di database MySQL produksi (`investor`) untuk memastikan seluruh tabel fitur baru tersedia:

```sql
-- 1. Tabel Poin / Sub-headline Artikel
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

-- 4. Tabel Audit Log Penggunaan AI Assistant
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

-- 5. Indexing Kinerja Tambahan pada Tabel Eksisting
ALTER TABLE `tblArticle` ADD INDEX IF NOT EXISTS `idx_status_date` (`status`, `articleDate`);
ALTER TABLE `tblArticleCategory` ADD INDEX IF NOT EXISTS `idx_art_cat` (`articleID`, `categoryID`);
ALTER TABLE `tblMediaArticle` ADD INDEX IF NOT EXISTS `idx_media_art_pos` (`articleID`, `position`);
```

*Alternatif via Spark*: Anda juga dapat menjalankan migrasi resmi melalui terminal:
```bash
php spark migrate
```

---

### 5.2 Konfigurasi Environment (`.env`)

Ubah parameter kunci di file `.env` server produksi:
```ini
# Ubah ke mode production untuk menonaktifkan debug toolbar dan error trace publik
CI_ENVIRONMENT = production

# URL Domain CMS Live
app.baseURL = 'https://cms.investor.id/'

# Konfigurasi Database (DBPrefix WAJIB 'tbl')
database.default.hostname = 127.0.0.1
database.default.database = investor
database.default.username = prod_user
database.default.password = [PROD_STRONG_PASSWORD]
database.default.DBDriver = MySQLi
database.default.DBPrefix = tbl
database.default.DBDebug = false

# Driver Media Penyimpanan
# Aktifkan microservice S3 / CDN jika terhubung pada jaringan server live:
MEDIA_STORAGE_DRIVER = smarty_api
MEDIA_API_ENDPOINT = 'http://10.0.4.16/imgapi/upload/'
MEDIA_SECONDARY_API_ENDPOINT = 'http://172.20.6.110:8080/imgapi/upload/'
MEDIA_CDN_BASE_URL = 'https://img.beritasatu.com/'
MEDIA_CDN_ORIGINAL_URL = 'https://img2.beritasatu.com/investor/'

# Gemini AI API Key Live
GEMINI_API_KEY = [LIVE_GEMINI_KEY]
```

---

### 5.3 Konfigurasi Crontab / Task Scheduler

Buka crontab user web server (`crontab -e -u www-data`) dan pasang entri berikut:

```bash
# 1. Publikasikan artikel terjadwal (Hold) yang telah melewati waktu tayang (tiap menit)
* * * * * cd /var/www/investorcms && php spark cron:publish-scheduled > /dev/null 2>&1

# 2. Reset / Truncate slot headline newsletter (jam 08:00, 14:00, dan 20:00 WIB)
0 8,14,20 * * * cd /var/www/investorcms && php spark cron:truncate-newsletter > /dev/null 2>&1

# 3. Proses antrean push notification ke FCM (tiap 5 menit)
*/5 * * * * cd /var/www/investorcms && php spark cron:process-push > /dev/null 2>&1

# 4. Bersihkan log audit editor yang berusia lebih dari 90 hari (tiap hari pukul 02:00 WIB)
0 2 * * * cd /var/www/investorcms && php spark cron:cleanup-logs > /dev/null 2>&1
```

---

### 5.4 Izin Direktori (Permissions) & Web Server

1. **Hak Akses Direktori `writable/`**:
   ```bash
   chown -R www-data:www-data /var/www/investorcms
   chmod -R 775 /var/www/investorcms/writable
   chmod -R 775 /var/www/investorcms/public/uploads
   ```

2. **Web Server Root Pointing**:
   Pastikan Nginx atau Apache *DocumentRoot* diarahkan ke direktori `/var/www/investorcms/public`, **bukan** ke root folder aplikasi, demi menjaga keamanan kode sumber dan file konfigurasi `.env`.

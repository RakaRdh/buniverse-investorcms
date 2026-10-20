# Investor.ID Revamp CMS

Sistem Manajemen Konten (CMS) modern untuk portal berita pasar modal dan finansial **Investor.ID** (B-Universe), dibangun menggunakan arsitektur CodeIgniter 4 (PHP 8.2+), standar PSR-4, Model-View-Controller (MVC), dan paritas database penuh dengan sistem Smarty CMS.

---

## Fitur Utama

- **Editorial & Newsroom Management**: Manajemen artikel lengkap (Draft, Publish, Hold, Deleted), multi sub-kanal, assignment jurnalis/editor/fotografer, tag kata kunci, sub-headline/poin berita, dan pratinjau artikel.
- **Edit Lock Mechanism**: Mencegah tabrakan edit antar-redaktur dengan sistem kuncian real-time dan hak force unlock bagi role Administrator / Redpel.
- **Headline Management & Reordering**: Pengaturan urutan slot headline untuk kanal Beranda, Tropical, Trending, dan Newsletter.
- **Media Asset Management**: Manajemen gambar, integrasi thumbnail artikel position-based, dan deteksi artikel terikat.
- **Autosave & Asinkron**: Penyimpanan draft otomatis di latar belakang saat artikel sedang diketik.
- **AI Editorial Assistant**: Asistensi pembuatan judul alternatif, ringkasan, dan audit tata bahasa menggunakan Google Gemini API.
- **SEO Scorer**: Evaluasi kepadatan kata kunci, panjang judul, dan kesiapan SEO artikel jurnalistik sebelum tayang.
- **Audit Trail & Revision History**: Pencatatan riwayat revisi artikel per redaktur beserta pelacakan status perubahan.
- **CLI Scheduler & Background Cron**: Otomasi penerbitan artikel terjadwal, truncate berkala headline newsletter, dan pembersihan log audit lama.
- **API Services**: Reader APIs, Market Indices & Stock Movers APIs, serta Auth APIs untuk integrasi aplikasi mobile / web portal.

---

## Persyaratan Sistem

- **PHP**: Versi 8.2 atau lebih tinggi
- **Ekstensi PHP Wajib**: `intl`, `mbstring`, `mysqli`, `curl`, `json`
- **Database**: MySQL 8.0+ / MariaDB 10.4+
- **Composer**: Versi 2.x

---

## Instalasi & Menjalankan Lokal

1. **Clone repositori**:
   ```bash
   git clone https://github.com/RakaRdh/buniverse-investorcms.git
   cd buniverse-investorcms
   ```

2. **Install dependensi Composer**:
   ```bash
   composer install
   ```

3. **Konfigurasi Environment**:
   Salin file `.env` atau sesuaikan dari `env`:
   ```bash
   cp env .env
   ```
   Pastikan parameter database disesuaikan dengan konfigurasi lokal Anda:
   ```ini
   CI_ENVIRONMENT = development
   database.default.hostname = 127.0.0.1
   database.default.database = investor
   database.default.username = root
   database.default.password = [password_anda]
   database.default.DBDriver = MySQLi
   database.default.DBPrefix = tbl
   ```
   *(PENTING: Nilai `database.default.DBPrefix` WAJIB disetel ke `tbl` agar sesuai dengan konvensi penamaan tabel Smarty).*

4. **Jalankan Migrasi Database**:
   ```bash
   php spark migrate
   ```

5. **Jalankan Development Server**:
   ```bash
   php spark serve
   ```
   Akses CMS di browser melalui `http://localhost:8080`.

---

## Pengujian Otomatis (Automated Testing)

Seluruh logika bisnis, sanitasi HTML, paritas hashing password Smarty, dan controller CMS diuji menggunakan PHPUnit:

```bash
# Menjalankan seluruh unit test (111 tests, 428 assertions):
vendor/bin/phpunit

# Menjalankan test spesifik model:
vendor/bin/phpunit tests/unit/Models/ArticleModelTest.php
```

---

## Dokumentasi Teknis Lengkap

Dokumentasi teknis mendalam mengenai komparasi query Smarty vs Revamp, payload API lengkap, panduan pra-produksi, dan catatan integrasi styling preview artikel tersedia pada:

👉 **[docs/Revamp Investor CMS Documentation.md](file:///d:/Raka/B-Universe/Revamp%20CMS%20Investor/InvestorCMS/docs/Revamp%20Investor%20CMS%20Documentation.md)**

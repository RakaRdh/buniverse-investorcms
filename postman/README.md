# InvestorCMS Postman Collection & Environment

Koleksi pengujian API otomatis untuk InvestorCMS.

## File Utama
1. **`InvestorCMS_API.postman_collection.json`**  
   Koleksi request lengkap berisi 6 modul (Public Articles, Market Financial, Client Auth, Editorial Search, AI Assistant, CMS Auth & Security) beserta test script assertion otomatis (`pm.test`).
2. **`InvestorCMS.postman_environment.json`**  
   Environment variables dengan konfigurasi awal `base_url`: `http://localhost:8080`.

---

## Cara Import ke Postman GUI (Desktop):
1. Buka aplikasi **Postman**.
2. Klik tombol **Import** di pojok kiri atas (atau tekan `Ctrl + O`).
3. Pilih / Drag & Drop file:
   - `InvestorCMS_API.postman_collection.json`
   - `InvestorCMS.postman_environment.json`
4. Di pojok kanan atas Postman, pilih environment aktif: **InvestorCMS (Local)**.
5. Jalankan request secara manual atau gunakan **Run Collection** untuk automated test suite.

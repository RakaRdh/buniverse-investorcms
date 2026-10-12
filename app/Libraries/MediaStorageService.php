<?php

namespace App\Libraries;

/**
 * MediaStorageService
 * Layanan terpadu penyimpanan dan pemrosesan media aset:
 * - Driver 'local': Simulasi lokal 100% identik arsitektur legacy (struktur YYYY/MM/, format DB YYYY/MM/xxx, optimasi GD).
 * - Driver 'legacy_api': Pengiriman remote cURL Base64 ke microservice image API internal AWS S3 (http://10.0.4.16/imgapi/upload/).
 */
class MediaStorageService
{
    protected string $driver;
    protected string $apiEndpoint;
    protected string $cdnBaseUrl;
    protected ImageProcessor $imageProcessor;

    public function __construct()
    {
        // Default selalu 'local' murni di komputer pengembang
        $this->driver = (string) (env('MEDIA_STORAGE_DRIVER', 'local'));
        
        // CATATAN: Endpoint remote legacy di-comment out agar tidak ada kontak ke server/API internal saat development lokal
        // $this->apiEndpoint = (string) (env('MEDIA_API_ENDPOINT', 'http://10.0.4.16/imgapi/upload/'));
        // $this->cdnBaseUrl  = (string) (env('MEDIA_CDN_BASE_URL', 'https://img.beritasatu.com/'));
        $this->apiEndpoint = (string) (env('MEDIA_API_ENDPOINT', ''));
        $this->cdnBaseUrl  = (string) (env('MEDIA_CDN_BASE_URL', ''));
        
        $this->imageProcessor = new ImageProcessor();
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Upload / proses file dari UploadedFile instance
     * Mengembalikan array [ 'success' => bool, 'filename' => string, 'url' => string, 'error' => ?string ]
     */
    public function storeUploadedFile($file, string $prefix = 'upload'): array
    {
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return ['success' => false, 'error' => 'File tidak valid atau rusak.'];
        }

        $binaryData = @file_get_contents($file->getTempName());
        if (!$binaryData) {
            return ['success' => false, 'error' => 'Gagal membaca konten berkas.'];
        }

        $mimeType = $file->getMimeType() ?: 'image/jpeg';
        return $this->storeBinary($binaryData, $mimeType, $prefix);
    }

    /**
     * Simpan file dari binary string (misal dari unduhan scrape / base64)
     */
    public function storeBinary(string $binaryData, string $mimeType = 'image/jpeg', string $prefix = 'img'): array
    {
        if (empty($binaryData)) {
            return ['success' => false, 'error' => 'Data gambar kosong.'];
        }

        if ($this->driver === 'legacy_api') {
            return $this->uploadToLegacyApi($binaryData, $mimeType);
        }

        return $this->storeLocalSimulation($binaryData, $prefix);
    }

    /**
     * Driver Local: Simulasi lokal dengan struktur folder YYYY/MM/ dan format filename YYYY/MM/timestamp_name.jpg
     */
    protected function storeLocalSimulation(string $binaryData, string $prefix = 'img'): array
    {
        $yearMonth = date('Y/m');
        $targetDir = FCPATH . 'uploads/media/' . str_replace('/', DIRECTORY_SEPARATOR, $yearMonth);

        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        // Jalankan kompresi GD standar max 1200px
        $src = @imagecreatefromstring($binaryData);
        if (!$src) {
            return ['success' => false, 'error' => 'Format gambar tidak dapat diproses oleh GD Library.'];
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        $maxDim = 1200;
        $newW = $origW;
        $newH = $origH;

        if ($origW > $maxDim || $origH > $maxDim) {
            $ratio = min($maxDim / $origW, $maxDim / $origH);
            $newW = (int) round($origW * $ratio);
            $newH = (int) round($origH * $ratio);
        }

        $dst = imagecreatetruecolor($newW, $newH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        $baseName = sprintf('%d_%s_%s.jpg', time(), $prefix, substr(bin2hex(random_bytes(3)), 0, 6));
        $fullPath = $targetDir . DIRECTORY_SEPARATOR . $baseName;

        $saved = imagejpeg($dst, $fullPath, 85);
        imagedestroy($dst);

        if (!$saved || !file_exists($fullPath)) {
            return ['success' => false, 'error' => 'Gagal menulis file gambar ke penyimpanan lokal.'];
        }

        // Format yang disimpan di tabel database media.filename identik 100% dengan legacy: YYYY/MM/namafile.jpg
        $dbFilename = $yearMonth . '/' . $baseName;
        $publicUrl = '/uploads/media/' . $dbFilename;

        return [
            'success'  => true,
            'filename' => $dbFilename,
            'url'      => $publicUrl,
        ];
    }

    /**
     * Driver Remote: Mengirim Base64 payload ke Image API S3 legacy
     */
    protected function uploadToLegacyApi(string $binaryData, string $mimeType = 'image/jpeg'): array
    {
        // Proteksi mutlak: Jika endpoint API kosong, jangan pernah jalankan cURL dan langsung fallback lokal
        if (empty($this->apiEndpoint)) {
            return $this->storeLocalSimulation($binaryData, 'img');
        }

        $yearMonth = date('Y/m');
        $encodedFile = 'data:' . $mimeType . ';base64,' . base64_encode($binaryData);

        $postData = [
            'dir_save'    => '/data/media/investor/' . $yearMonth . '/',
            'webp'        => true,
            'file_name'   => time(),
            'base64_file' => $encodedFile,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->apiEndpoint,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr || $httpCode !== 200 || empty($response)) {
            // Fallback ke simulasi lokal jika koneksi microservice tidak terjangkau (misal saat testing offline)
            log_message('warning', "Legacy Image API unreachable ({$curlErr}, HTTP {$httpCode}), falling back to local simulation.");
            return $this->storeLocalSimulation($binaryData, 'fallback');
        }

        $decoded = json_decode($response, true);
        if (isset($decoded['result']['status']) && (int) $decoded['result']['status'] === 200 && !empty($decoded['result']['file_name'])) {
            $remoteName = $decoded['result']['file_name'];
            $dbFilename = $yearMonth . '/' . $remoteName;
            $publicUrl = rtrim($this->cdnBaseUrl, '/') . '/' . $dbFilename;

            return [
                'success'  => true,
                'filename' => $dbFilename,
                'url'      => $publicUrl,
            ];
        }

        return ['success' => false, 'error' => 'Microservice Image API mengembalikan status gagal: ' . $response];
    }
}

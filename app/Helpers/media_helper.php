<?php

if (!function_exists('media_url')) {
    /**
     * Resolver URL Media Global
     * Menangani secara transparan:
     * 1. URL eksternal penuh (https://...)
     * 2. Path lokal /uploads/media/
     * 3. Path format legacy YYYY/MM/namafile.jpg (baik lokal maupun CDN img.beritasatu.com)
     */
    function media_url(?string $filename): string
    {
        if (empty($filename)) {
            return base_url('img/investor_placeholder.jpg');
        }

        $trimmed = trim($filename);

        // 1. Sudah berupa URL penuh (https://img.beritasatu.com/..., AWS S3, atau eksternal)
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            return $trimmed;
        }

        // 2. Format path lokal dengan prefix /uploads/
        if (str_starts_with($trimmed, '/uploads/')) {
            return base_url(ltrim($trimmed, '/'));
        }

        // 3. Format legacy / simulasi YYYY/MM/namafile.jpg
        $driver = (string)(env('MEDIA_STORAGE_DRIVER', 'local'));
        if ($driver === 'local') {
            $localPhysical = FCPATH . 'uploads/media/' . str_replace('/', DIRECTORY_SEPARATOR, ltrim($trimmed, '/'));
            if (file_exists($localPhysical)) {
                return base_url('uploads/media/' . ltrim($trimmed, '/'));
            }
        }

        // Fallback: Jika di .env didefinisikan MEDIA_CDN_BASE_URL, gunakan CDN tsb. Jika kosong, fallback ke base_url lokal
        $cdnBase = rtrim((string)(env('MEDIA_CDN_BASE_URL', '')), '/');
        if (!empty($cdnBase)) {
            return $cdnBase . '/' . ltrim($trimmed, '/');
        }

        return base_url('uploads/media/' . ltrim($trimmed, '/'));
    }
}

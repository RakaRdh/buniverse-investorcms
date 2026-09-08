<?php

namespace App\Libraries;

/**
 * ImageProcessor
 * Standardizes images across the Investor CMS (Uploads, Scraping, Drag & Drop).
 * - Constrains dimensions to max 1200px (proportional)
 * - Auto-orients based on EXIF metadata
 * - Standardizes output to high-efficiency JPEG (85% quality)
 * - Guarantees lightweight, optimized storage and fast client loads
 */
class ImageProcessor
{
    protected int $maxWidth = 1200;
    protected int $maxHeight = 1200;
    protected int $quality = 85;
    protected string $uploadDir;

    public function __construct(int $maxWidth = 1200, int $maxHeight = 1200, int $quality = 85)
    {
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
        $this->quality = $quality;
        $this->uploadDir = FCPATH . 'uploads/media';

        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Process raw image binary string and return saved relative URL.
     */
    public function processFromString(string $binaryData, string $prefix = 'img'): ?string
    {
        if (empty($binaryData)) {
            return null;
        }

        // Try reading EXIF orientation before manipulating stream if possible
        $orientation = 1;
        if (function_exists('exif_read_data')) {
            $stream = @fopen('php://memory', 'r+');
            if ($stream) {
                @fwrite($stream, $binaryData);
                @rewind($stream);
                $exif = @exif_read_data($stream);
                if ($exif && !empty($exif['Orientation'])) {
                    $orientation = (int) $exif['Orientation'];
                }
                @fclose($stream);
            }
        }

        $src = @imagecreatefromstring($binaryData);
        if (!$src) {
            return null;
        }

        return $this->processGdResource($src, $orientation, $prefix);
    }

    /**
     * Process an existing file from path.
     */
    public function processFile(string $filePath, string $prefix = 'img'): ?string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        $data = @file_get_contents($filePath);
        if (!$data) {
            return null;
        }

        return $this->processFromString($data, $prefix);
    }

    /**
     * Process a CodeIgniter 4 UploadedFile instance.
     */
    public function processUploadedFile($file, string $prefix = 'upload'): ?string
    {
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return null;
        }

        $realPath = $file->getTempName();
        return $this->processFile($realPath, $prefix);
    }

    /**
     * Core GD transformation & saving routine.
     */
    protected function processGdResource($src, int $orientation, string $prefix): ?string
    {
        // Handle EXIF orientation
        switch ($orientation) {
            case 3:
                $src = imagerotate($src, 180, 0);
                break;
            case 6:
                $src = imagerotate($src, -90, 0);
                break;
            case 8:
                $src = imagerotate($src, 90, 0);
                break;
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        if ($origW <= 0 || $origH <= 0) {
            imagedestroy($src);
            return null;
        }

        // Calculate target dimensions
        $newW = $origW;
        $newH = $origH;

        if ($origW > $this->maxWidth || $origH > $this->maxHeight) {
            $ratioW = $this->maxWidth / $origW;
            $ratioH = $this->maxHeight / $origH;
            $ratio = min($ratioW, $ratioH);

            $newW = (int) round($origW * $ratio);
            $newH = (int) round($origH * $ratio);
        }

        // Create truecolor destination image
        $dst = imagecreatetruecolor($newW, $newH);

        // Fill background with white in case of transparency
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);

        // High-quality resampling
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        // Generate standardized filename
        $fileName = sprintf('%s_%s_%s.jpg', $prefix, date('Ymd_His'), substr(bin2hex(random_bytes(4)), 0, 8));
        $targetPath = $this->uploadDir . DIRECTORY_SEPARATOR . $fileName;

        // Save as JPEG with standardized quality
        $success = imagejpeg($dst, $targetPath, $this->quality);
        imagedestroy($dst);

        if ($success && file_exists($targetPath)) {
            return '/uploads/media/' . $fileName;
        }

        return null;
    }
}

<?php

namespace Tests\Unit\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\ImageProcessor;
use App\Libraries\GeminiProvider;

class LibrariesTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testImageProcessorProcessFromString(): void
    {
        $processor = new ImageProcessor(800, 600, 80);

        // 1x1 transparent PNG sample binary
        $pngBinary = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

        $resultUrl = $processor->processFromString($pngBinary, 'test');
        $this->assertNotNull($resultUrl);
        $this->assertStringStartsWith('/uploads/media/test_', $resultUrl);
        $this->assertStringEndsWith('.jpg', $resultUrl);

        // Verify physical file created
        $physicalPath = FCPATH . ltrim($resultUrl, '/');
        $this->assertFileExists($physicalPath);

        // Cleanup
        @unlink($physicalPath);
    }

    public function testImageProcessorEmptyBinaryReturnsNull(): void
    {
        $processor = new ImageProcessor();
        $this->assertNull($processor->processFromString(''));
        $this->assertNull($processor->processFromString('not-a-valid-image-binary'));
    }

    public function testImageProcessorProcessFile(): void
    {
        $processor = new ImageProcessor();
        
        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_img_' . uniqid() . '.png';
        $pngBinary = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        file_put_contents($tempFile, $pngBinary);

        $resultUrl = $processor->processFile($tempFile, 'unit');
        $this->assertNotNull($resultUrl);

        $physicalPath = FCPATH . ltrim($resultUrl, '/');
        $this->assertFileExists($physicalPath);

        // Cleanup
        @unlink($tempFile);
        @unlink($physicalPath);

        // Non-existent file
        $this->assertNull($processor->processFile('/path/to/nonexistent/file.jpg'));
    }

    public function testGeminiProviderConfigurationAndModels(): void
    {
        $provider = new GeminiProvider();

        $this->assertNotEmpty($provider->getPrimaryModel());
        $this->assertNotEmpty($provider->getFallbackModel());
        $this->assertNotEmpty($provider->getTertiaryModel());
        $this->assertNull($provider->getLastModelUsed());
    }

    public function testGeminiProviderValidateScrapeUrl(): void
    {
        $provider = new GeminiProvider();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('validateScrapeUrl');
        $method->setAccessible(true);

        // Valid whitelisted domains
        $this->assertNull($method->invokeArgs($provider, ['https://www.antaranews.com/berita/123/market-update']));
        $this->assertNull($method->invokeArgs($provider, ['https://investor.id/market/456/ihsg-menguat']));
        $this->assertNull($method->invokeArgs($provider, ['https://www.beritasatu.com/ekonomi/789/rupiah']));

        // Invalid domain should throw InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);
        $method->invokeArgs($provider, ['https://malicious-website.com/payload']);
    }

    public function testGeminiProviderThrowsExceptionOnEmptyApiKey(): void
    {
        // Reflection to clear API key
        $provider = new GeminiProvider();
        $reflection = new \ReflectionClass($provider);
        $apiKeyProp = $reflection->getProperty('apiKey');
        $apiKeyProp->setAccessible(true);
        $apiKeyProp->setValue($provider, '');

        $this->expectException(\RuntimeException::class);
        $provider->generateSummary('Test Title', 'Test body article');
    }
}

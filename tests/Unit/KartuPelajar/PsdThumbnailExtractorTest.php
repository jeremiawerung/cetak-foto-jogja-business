<?php

namespace Tests\Unit\KartuPelajar;

use App\Services\KartuPelajar\PsdThumbnailExtractor;
use PHPUnit\Framework\TestCase;

class PsdThumbnailExtractorTest extends TestCase
{
    private const SAMPLE_PSD = 'Sample/SMPN 15 YOGYAKARTA/KARTU BELAKANG/VII A/1_0137272203.psd';

    public function test_extracts_valid_embedded_jpeg_thumbnail_from_real_psd(): void
    {
        $path = dirname(__DIR__, 3).'/'.self::SAMPLE_PSD;

        if (! is_file($path)) {
            $this->markTestSkipped('File sample PSD tidak ditemukan.');
        }

        $jpeg = (new PsdThumbnailExtractor)->extract($path);

        $this->assertNotNull($jpeg);
        $this->assertStringStartsWith("\xFF\xD8\xFF", $jpeg);
    }

    public function test_returns_null_for_non_psd_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'not-psd');
        file_put_contents($path, 'bukan file psd');

        try {
            $jpeg = (new PsdThumbnailExtractor)->extract($path);

            $this->assertNull($jpeg);
        } finally {
            unlink($path);
        }
    }
}

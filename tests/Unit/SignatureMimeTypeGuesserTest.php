<?php

namespace Tests\Unit;

use App\Services\Upload\SignatureMimeTypeGuesser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SignatureMimeTypeGuesserTest extends TestCase
{
    private SignatureMimeTypeGuesser $guesser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guesser = new SignatureMimeTypeGuesser;
    }

    public function test_is_always_supported(): void
    {
        $this->assertTrue($this->guesser->isGuesserSupported());
    }

    #[DataProvider('signatureProvider')]
    public function test_guesses_mime_type_from_signature(string $bytes, string $expected): void
    {
        $path = tempnam(sys_get_temp_dir(), 'sig-test-');
        file_put_contents($path, $bytes);

        $this->assertSame($expected, $this->guesser->guessMimeType($path));

        unlink($path);
    }

    public static function signatureProvider(): array
    {
        return [
            'jpeg' => ["\xFF\xD8\xFF\xE0restofjpeg", 'image/jpeg'],
            'png' => ["\x89PNG\r\n\x1a\nrestofpng", 'image/png'],
            'gif87' => ['GIF87arestofgif', 'image/gif'],
            'gif89' => ['GIF89arestofgif', 'image/gif'],
            'webp' => ["RIFF\x00\x00\x00\x00WEBPrest", 'image/webp'],
            'pdf' => ['%PDF-1.7 rest of pdf', 'application/pdf'],
            'psd' => ['8BPSrestofpsd', 'image/vnd.adobe.photoshop'],
            'xls' => ["\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1restofxls", 'application/vnd.ms-excel'],
            'xlsx (zip)' => ["PK\x03\x04restofzip", 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ];
    }

    public function test_returns_null_for_unrecognized_content(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'sig-test-');
        file_put_contents($path, 'plain text, not a known signature');

        $this->assertNull($this->guesser->guessMimeType($path));

        unlink($path);
    }

    public function test_returns_null_for_missing_file(): void
    {
        $this->assertNull($this->guesser->guessMimeType(sys_get_temp_dir().'/does-not-exist-'.uniqid()));
    }
}

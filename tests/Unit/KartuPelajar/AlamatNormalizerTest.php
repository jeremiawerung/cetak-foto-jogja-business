<?php

namespace Tests\Unit\KartuPelajar;

use App\Services\KartuPelajar\Normalizers\AlamatNormalizer;
use PHPUnit\Framework\TestCase;

class AlamatNormalizerTest extends TestCase
{
    private AlamatNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new AlamatNormalizer;
    }

    public function test_clean_address_is_clean(): void
    {
        $result = $this->normalizer->normalize(
            'Lempuyangan, DN3/366 RT017 RW005 Bausasran, Danurejan, Yogyakarta'
        );

        $this->assertSame('clean', $result->status);
    }

    public function test_excel_style_address_with_slashes_is_clean(): void
    {
        $result = $this->normalizer->normalize(
            'Iromejan Gk 3/617 030/007 Desa/kel Klitren Kec Gondokusuman Yogyakarta'
        );

        $this->assertSame('clean', $result->status);
    }

    public function test_interleaved_lowercase_uppercase_token_is_invalid(): void
    {
        $result = $this->normalizer->normalize(
            'KLITREN LOR GK 3/274 RT. 014 RW. 04 KLITREN, GOLNaDkOi-LKaUkSiUMAN , YOGYAKARTA'
        );

        $this->assertSame('invalid', $result->status);
        $this->assertStringContainsString('pola tidak biasa', $result->note);
    }

    public function test_digit_letter_sandwich_is_invalid(): void
    {
        $result = $this->normalizer->normalize('Warungboto UH = 4 / 1035 Yogya0k1a2r6ta338159 BULHARJO');

        $this->assertSame('invalid', $result->status);
    }

    public function test_empty_address_is_warning_not_invalid(): void
    {
        $result = $this->normalizer->normalize('');

        $this->assertSame('warning', $result->status);
    }

    public function test_preserves_original_value_when_invalid(): void
    {
        $raw = 'Gemblakan Bawah DN 1/505 SuryaPtemreamjapnuaKnec Danurejan';
        $result = $this->normalizer->normalize($raw);

        $this->assertSame('invalid', $result->status);
        $this->assertSame($raw, $result->value);
    }
}

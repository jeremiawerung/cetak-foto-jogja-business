<?php

namespace Tests\Unit\KartuPelajar;

use App\Services\KartuPelajar\Normalizers\NisnNormalizer;
use PHPUnit\Framework\TestCase;

class NisnNormalizerTest extends TestCase
{
    private NisnNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new NisnNormalizer;
    }

    public function test_valid_10_digit_nisn_is_clean(): void
    {
        $result = $this->normalizer->normalize('0131566560');

        $this->assertSame('clean', $result->status);
        $this->assertSame('0131566560', $result->value);
    }

    public function test_ocr_letter_o_is_corrected_with_warning(): void
    {
        $result = $this->normalizer->normalize('O135674728');

        $this->assertSame('warning', $result->status);
        $this->assertSame('0135674728', $result->value);
    }

    public function test_nine_digits_gets_leading_zero_padded_with_warning(): void
    {
        $result = $this->normalizer->normalize('133204197');

        $this->assertSame('warning', $result->status);
        $this->assertSame('0133204197', $result->value);
    }

    public function test_eleven_digits_is_invalid(): void
    {
        $result = $this->normalizer->normalize('01310415632');

        $this->assertSame('invalid', $result->status);
    }

    public function test_empty_is_invalid(): void
    {
        $result = $this->normalizer->normalize('');

        $this->assertSame('invalid', $result->status);
    }
}

<?php

namespace Tests\Unit\KartuPelajar;

use App\Services\KartuPelajar\Normalizers\KelasNormalizer;
use PHPUnit\Framework\TestCase;

class KelasNormalizerTest extends TestCase
{
    private KelasNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new KelasNormalizer;
    }

    public function test_roman_vii_c_converts_to_decimal(): void
    {
        $result = $this->normalizer->normalize('VII C');

        $this->assertSame('clean', $result->status);
        $this->assertSame('7C', $result->value);
    }

    public function test_roman_vii_j_converts_to_decimal(): void
    {
        $result = $this->normalizer->normalize('VII J');

        $this->assertSame('clean', $result->status);
        $this->assertSame('7J', $result->value);
    }

    public function test_decimal_format_stays_as_is(): void
    {
        $result = $this->normalizer->normalize('7A');

        $this->assertSame('clean', $result->status);
        $this->assertSame('7A', $result->value);
    }

    public function test_mixed_case_and_extra_spaces_tolerated(): void
    {
        $result = $this->normalizer->normalize('vii   c');

        $this->assertSame('clean', $result->status);
        $this->assertSame('7C', $result->value);
    }

    public function test_unrecognized_shape_is_invalid(): void
    {
        $result = $this->normalizer->normalize('Kelas Tujuh');

        $this->assertSame('invalid', $result->status);
    }

    public function test_empty_is_invalid(): void
    {
        $result = $this->normalizer->normalize('');

        $this->assertSame('invalid', $result->status);
    }
}

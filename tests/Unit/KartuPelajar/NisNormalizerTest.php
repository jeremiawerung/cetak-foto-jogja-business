<?php

namespace Tests\Unit\KartuPelajar;

use App\Services\KartuPelajar\Normalizers\NisNormalizer;
use PHPUnit\Framework\TestCase;

class NisNormalizerTest extends TestCase
{
    private NisNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new NisNormalizer;
    }

    public function test_numeric_nis_is_clean(): void
    {
        $result = $this->normalizer->normalize('11454');

        $this->assertSame('clean', $result->status);
        $this->assertSame('11454', $result->value);
    }

    public function test_trims_whitespace(): void
    {
        $result = $this->normalizer->normalize('  2807  ');

        $this->assertSame('clean', $result->status);
        $this->assertSame('2807', $result->value);
    }

    public function test_empty_is_invalid(): void
    {
        $result = $this->normalizer->normalize('');

        $this->assertSame('invalid', $result->status);
    }

    public function test_alphabetic_value_is_invalid(): void
    {
        $result = $this->normalizer->normalize('ABC123');

        $this->assertSame('invalid', $result->status);
    }
}

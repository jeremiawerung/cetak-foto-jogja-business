<?php

namespace Tests\Unit\KartuPelajar;

use App\Services\KartuPelajar\Normalizers\NamaNormalizer;
use PHPUnit\Framework\TestCase;

class NamaNormalizerTest extends TestCase
{
    private NamaNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new NamaNormalizer;
    }

    public function test_all_caps_converted_to_title_case(): void
    {
        $result = $this->normalizer->normalize('BELFA LIA ABDUL GAFAR');

        $this->assertSame('clean', $result->status);
        $this->assertSame('Belfa Lia Abdul Gafar', $result->value);
    }

    public function test_mixed_case_converted_to_title_case(): void
    {
        $result = $this->normalizer->normalize('Muhamad hafidz Andika latif');

        $this->assertSame('clean', $result->status);
        $this->assertSame('Muhamad Hafidz Andika Latif', $result->value);
    }

    public function test_hyphenated_name_capitalized_on_both_sides(): void
    {
        $result = $this->normalizer->normalize('AL-KHALIFI AIDAN ADHA');

        $this->assertSame('clean', $result->status);
        $this->assertSame('Al-Khalifi Aidan Adha', $result->value);
    }

    public function test_apostrophe_preserved_and_not_flagged(): void
    {
        $result = $this->normalizer->normalize("Fa'iq Ramadhan");

        $this->assertSame('clean', $result->status);
        $this->assertSame("Fa'iq Ramadhan", $result->value);
    }

    public function test_empty_name_is_invalid(): void
    {
        $result = $this->normalizer->normalize('');

        $this->assertSame('invalid', $result->status);
    }

    public function test_null_name_is_invalid(): void
    {
        $result = $this->normalizer->normalize(null);

        $this->assertSame('invalid', $result->status);
    }

    public function test_collapses_extra_whitespace(): void
    {
        $result = $this->normalizer->normalize('  Giska   Syaquita  ');

        $this->assertSame('clean', $result->status);
        $this->assertSame('Giska Syaquita', $result->value);
    }
}

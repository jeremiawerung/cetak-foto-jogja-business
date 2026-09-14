<?php

namespace Tests\Unit\KartuPelajar;

use App\Services\KartuPelajar\Normalizers\TanggalLahirNormalizer;
use PHPUnit\Framework\TestCase;

class TanggalLahirNormalizerTest extends TestCase
{
    private TanggalLahirNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new TanggalLahirNormalizer;
    }

    public function test_clean_combined_place_and_date(): void
    {
        $result = $this->normalizer->normalize('Yogyakarta 01 September 2013');

        $this->assertSame('clean', $result->status);
        $this->assertSame('2013-09-01', $result->value);
    }

    public function test_comma_separated_place_and_date(): void
    {
        $result = $this->normalizer->normalize('Yogyakarta, 04 April 2013');

        $this->assertSame('clean', $result->status);
        $this->assertSame('2013-04-04', $result->value);
    }

    public function test_single_digit_day(): void
    {
        $result = $this->normalizer->normalize('YOGYAKARTA, 9 NOVEMBER 2013');

        $this->assertSame('clean', $result->status);
        $this->assertSame('2013-11-09', $result->value);
    }

    public function test_comma_without_space_before_day(): void
    {
        $result = $this->normalizer->normalize('Sleman,21 Februari 2014');

        $this->assertSame('clean', $result->status);
        $this->assertSame('2014-02-21', $result->value);
    }

    public function test_no_space_before_month_name(): void
    {
        $result = $this->normalizer->normalize('Yogyakarta, 19AGUSTUS 2013');

        $this->assertSame('clean', $result->status);
        $this->assertSame('2013-08-19', $result->value);
    }

    public function test_no_space_before_month_name_lowercase_variant(): void
    {
        $result = $this->normalizer->normalize('Yogyakarta,17September 2013');

        $this->assertSame('clean', $result->status);
        $this->assertSame('2013-09-17', $result->value);
    }

    public function test_typo_month_name_mey_is_corrected_with_warning(): void
    {
        $result = $this->normalizer->normalize('Sleman ,4 mey 2013');

        $this->assertSame('warning', $result->status);
        $this->assertSame('2013-05-04', $result->value);
    }

    public function test_ocr_letter_o_instead_of_zero_is_corrected_with_warning(): void
    {
        $result = $this->normalizer->normalize('O6 Maret 2014');

        $this->assertSame('warning', $result->status);
        $this->assertSame('2014-03-06', $result->value);
    }

    public function test_english_month_name_accepted_without_warning(): void
    {
        $result = $this->normalizer->normalize('Tangerang, 7 july 2013');

        $this->assertSame('clean', $result->status);
        $this->assertSame('2013-07-07', $result->value);
    }

    public function test_incomplete_date_without_day_is_warning_with_null_value(): void
    {
        $result = $this->normalizer->normalize('Yogyakarta juli 2013');

        $this->assertSame('warning', $result->status);
        $this->assertNull($result->value);
    }

    public function test_invalid_calendar_date_is_invalid(): void
    {
        $result = $this->normalizer->normalize('31 Februari 2013');

        $this->assertSame('invalid', $result->status);
    }

    public function test_empty_is_invalid(): void
    {
        $result = $this->normalizer->normalize('');

        $this->assertSame('invalid', $result->status);
    }

    public function test_null_is_invalid(): void
    {
        $result = $this->normalizer->normalize(null);

        $this->assertSame('invalid', $result->status);
    }

    public function test_completely_unparseable_text_is_invalid(): void
    {
        $result = $this->normalizer->normalize('tidak ada tanggal di sini');

        $this->assertSame('invalid', $result->status);
    }

    public function test_extract_tempat_lahir_from_combined_string(): void
    {
        $this->assertSame('Yogyakarta', $this->normalizer->extractTempatLahir('Yogyakarta 01 September 2013'));
    }

    public function test_extract_tempat_lahir_with_comma(): void
    {
        $this->assertSame('Yogyakarta', $this->normalizer->extractTempatLahir('Yogyakarta, 04 April 2013'));
    }

    public function test_extract_tempat_lahir_with_multiple_words(): void
    {
        $this->assertSame('Tanah Laut', $this->normalizer->extractTempatLahir('Tanah Laut 25 Januari 2014'));
    }

    public function test_extract_tempat_lahir_returns_null_when_no_date_found(): void
    {
        $this->assertNull($this->normalizer->extractTempatLahir('Yogyakarta juli 2013'));
    }

    public function test_extract_tempat_lahir_returns_null_for_empty_or_null(): void
    {
        $this->assertNull($this->normalizer->extractTempatLahir(''));
        $this->assertNull($this->normalizer->extractTempatLahir(null));
    }
}

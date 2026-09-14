<?php

namespace Tests\Unit\VerifikasiSiswa;

use App\Services\VerifikasiSiswa\Readers\RosterPdfReader;
use PHPUnit\Framework\TestCase;

class RosterPdfReaderTest extends TestCase
{
    /**
     * Verifikasi baca 319 baris dari sample nyata (multi-section, ->read() yang shell ke
     * pdftotext lewat config()) ada di tests/Feature/VerifikasiSiswaRosterUploadTest.php —
     * butuh container Laravel dibootstrap untuk config(), jadi tidak cocok jadi unit test
     * murni di sini. Test di bawah fokus ke parse() (fungsi murni, tanpa I/O/config).
     */
    public function test_parse_handles_simple_single_section_format_without_warnings(): void
    {
        $text = <<<'TEXT'
        No                      Nama            JK NIS NISN  Tempat Lahir

        1 SISWA SATU                            L 100 1000000001 Yogyakarta

        2 SISWA DUA                             P 101 1000000002 Sleman
        TEXT;

        $result = (new RosterPdfReader)->parse($text);

        $this->assertSame([], $result['warnings']);
        $this->assertCount(2, $result['rows']);
        $this->assertSame('SISWA SATU', $result['rows'][0]['nama']);
        $this->assertNull($result['rows'][0]['tanggal_lahir']);
        $this->assertNull($result['rows'][0]['kelas']);
    }

    public function test_parse_returns_empty_with_warning_when_no_identitas_section_found(): void
    {
        $result = (new RosterPdfReader)->parse("baris acak tanpa struktur apapun\nbaris lain");

        $this->assertSame([], $result['rows']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_parse_discards_misaligned_section_but_keeps_identitas(): void
    {
        $text = <<<'TEXT'
        No                      Nama            JK NIS NISN  Tempat Lahir

        1 SISWA SATU                            L 100 1000000001 Yogyakarta

        2 SISWA DUA                             P 101 1000000002 Sleman

        Tanggal Lahir Agama

        2013-01-01 Islam
        TEXT;

        $result = (new RosterPdfReader)->parse($text);

        $this->assertCount(2, $result['rows']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertNull($result['rows'][0]['tanggal_lahir']);
        $this->assertNull($result['rows'][0]['agama']);
    }
}

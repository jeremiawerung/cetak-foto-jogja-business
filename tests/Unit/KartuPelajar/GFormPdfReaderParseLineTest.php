<?php

namespace Tests\Unit\KartuPelajar;

use App\Services\KartuPelajar\Readers\GFormPdfReader;
use PHPUnit\Framework\TestCase;

class GFormPdfReaderParseLineTest extends TestCase
{
    private GFormPdfReader $reader;

    protected function setUp(): void
    {
        $this->reader = new GFormPdfReader;
    }

    public function test_sparse_line_with_only_timestamp_and_kelas(): void
    {
        $result = $this->reader->parseLine('11/08/2026 15:04:36 VII C', 1);

        $this->assertNotNull($result);
        $this->assertSame('VII C', $result['kelas']);
        $this->assertNull($result['nama']);
        $this->assertSame('2026-08-11 15:04:36', $result['timestamp']);
    }

    public function test_fully_populated_line_splits_into_all_fields(): void
    {
        $line = '11/08/2026 14:59:22 VII C  BELFA LIA ABDUL GAFAR  Yogyakarta, 04 April 2013  Islam  Lempuyangan, DN3/366 RT017 RW005 Bausasran, Danurejan, Yogyakarta 0138215118';

        $result = $this->reader->parseLine($line, 2);

        $this->assertNotNull($result);
        $this->assertSame('VII C', $result['kelas']);
        $this->assertSame('BELFA LIA ABDUL GAFAR', $result['nama']);
        $this->assertSame('Yogyakarta, 04 April 2013', $result['tanggal_lahir']);
        $this->assertSame('0138215118', $result['nisn']);
        $this->assertStringContainsString('Lempuyangan', $result['alamat']);
    }

    public function test_header_or_instruction_line_returns_null(): void
    {
        $result = $this->reader->parseLine('Harap isi dengan huruf Kapital, Contoh Format : Magelang, 17 Agustus 2011', 3);

        $this->assertNull($result);
    }

    public function test_line_without_timestamp_returns_null(): void
    {
        $result = $this->reader->parseLine('Timestamp KELAS NAMA LENGKAP', 4);

        $this->assertNull($result);
    }
}

<?php

namespace Tests\Unit\KartuPelajar;

use App\Services\KartuPelajar\RowAuditor;
use PHPUnit\Framework\TestCase;

class RowAuditorTest extends TestCase
{
    private RowAuditor $auditor;

    protected function setUp(): void
    {
        $this->auditor = new RowAuditor;
    }

    public function test_all_clean_fields_result_in_clean_row(): void
    {
        $result = $this->auditor->auditRow('excel', 3, [
            'nama' => 'ADINDA NINDYA KIRANA DESKA PUTRI',
            'kelas' => '7A',
            'tanggal_lahir' => 'Yogyakarta 01 September 2013',
            'nis' => '11454',
            'nisn' => '0133204197',
            'alamat' => 'Patangpuluhan Wb 3/259 004/001 Kec Wirobrajan Yogyakarta',
        ]);

        $this->assertSame('clean', $result->status);
    }

    public function test_one_warning_field_downgrades_row_to_normalized_with_warning(): void
    {
        $result = $this->auditor->auditRow('excel', 4, [
            'nama' => 'GISKA SYAQUITA',
            'kelas' => '7A',
            'tanggal_lahir' => 'O6 Maret 2014',
            'nis' => '11467',
            'nisn' => '0141041040',
            'alamat' => 'Jl Veteran No 204 014/003 Kec Umbulharjo Yogyakarta',
        ]);

        $this->assertSame('normalized_with_warning', $result->status);
    }

    public function test_one_invalid_field_escalates_row_to_needs_manual_review(): void
    {
        $result = $this->auditor->auditRow('excel', 5, [
            'nama' => 'AZARINE DIARA QUEENA',
            'kelas' => '7A',
            'tanggal_lahir' => '31 Februari 2014',
            'nis' => '11461',
            'nisn' => '0145315680',
            'alamat' => 'Iromejan Gk 3/617 030/007 Kec Gondokusuman Yogyakarta',
        ]);

        $this->assertSame('needs_manual_review', $result->status);
    }

    public function test_identical_nis_and_nisn_forces_manual_review(): void
    {
        $result = $this->auditor->auditRow('excel', 6, [
            'nama' => 'CONTOH SISWA',
            'kelas' => '7A',
            'tanggal_lahir' => 'Yogyakarta 01 Januari 2013',
            'nis' => '1234567890',
            'nisn' => '1234567890',
            'alamat' => 'Jl Contoh No 1 Yogyakarta',
        ]);

        $this->assertSame('needs_manual_review', $result->status);
        $this->assertTrue(
            collect($result->reasons)->contains(fn ($r) => str_contains($r, 'kemungkinan tertukar'))
        );
    }

    public function test_missing_field_for_source_is_skipped_not_treated_as_error(): void
    {
        // Sumber GForm tidak punya kolom NIS sama sekali.
        $result = $this->auditor->auditRow('gform', 1, [
            'nama' => 'BELFA LIA ABDUL GAFAR',
            'kelas' => 'VII C',
            'tanggal_lahir' => 'Yogyakarta, 04 April 2013',
            'nisn' => '0138215118',
            'alamat' => 'Lempuyangan, DN3/366 RT017 RW005 Bausasran, Yogyakarta',
        ]);

        $this->assertArrayNotHasKey('nis', $result->fields);
        $this->assertSame('clean', $result->status);
    }

    public function test_completely_sparse_gform_row_needs_manual_review(): void
    {
        $result = $this->auditor->auditRow('gform', 2, [
            'nama' => null,
            'kelas' => 'VII C',
            'tanggal_lahir' => null,
            'nisn' => null,
            'alamat' => null,
        ]);

        $this->assertSame('needs_manual_review', $result->status);
    }
}

<?php

namespace Tests\Unit\KartuPelajar;

use App\Services\KartuPelajar\FieldResult;
use App\Services\KartuPelajar\MatchingKeyBuilder;
use App\Services\KartuPelajar\RowAuditResult;
use PHPUnit\Framework\TestCase;

class MatchingKeyBuilderTest extends TestCase
{
    private MatchingKeyBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new MatchingKeyBuilder;
    }

    public function test_valid_nisn_becomes_nisn_key(): void
    {
        $result = new RowAuditResult('excel', 1, [
            'nisn' => FieldResult::clean('0133204197'),
            'nama' => FieldResult::clean('Adinda Nindya Kirana'),
            'tanggal_lahir' => FieldResult::clean('2013-09-01'),
            'kelas' => FieldResult::clean('7A'),
        ], 'clean', []);

        $keys = $this->builder->build($result);

        $this->assertSame('0133204197', $keys['nisn']);
    }

    public function test_invalid_nisn_yields_null_nisn_key(): void
    {
        $result = new RowAuditResult('excel', 1, [
            'nisn' => FieldResult::invalid('123', 'NISN harus 10 digit'),
            'nama' => FieldResult::clean('Adinda Nindya Kirana'),
            'tanggal_lahir' => FieldResult::clean('2013-09-01'),
            'kelas' => FieldResult::clean('7A'),
        ], 'needs_manual_review', []);

        $keys = $this->builder->build($result);

        $this->assertNull($keys['nisn']);
    }

    public function test_missing_nisn_field_yields_null_nisn_key(): void
    {
        // Sumber GForm baris sparse: field nisn bahkan tidak ada sama sekali.
        $result = new RowAuditResult('gform', 1, [
            'nama' => FieldResult::clean('Belfa Lia Abdul Gafar'),
            'tanggal_lahir' => FieldResult::clean('2013-04-04'),
            'kelas' => FieldResult::clean('VII C'),
        ], 'needs_manual_review', []);

        $keys = $this->builder->build($result);

        $this->assertNull($keys['nisn']);
    }

    public function test_complete_nama_tanggal_kelas_forms_composite_key(): void
    {
        $result = new RowAuditResult('gform', 1, [
            'nama' => FieldResult::clean('Belfa Lia Abdul Gafar'),
            'tanggal_lahir' => FieldResult::clean('2013-04-04'),
            'kelas' => FieldResult::clean('7C'),
        ], 'clean', []);

        $keys = $this->builder->build($result);

        $this->assertSame('BELFA LIA ABDUL GAFAR|2013-04-04|7C', $keys['composite']);
    }

    public function test_composite_key_is_null_when_any_component_missing(): void
    {
        $result = new RowAuditResult('gform', 1, [
            'nama' => FieldResult::clean('Belfa Lia Abdul Gafar'),
            'tanggal_lahir' => FieldResult::invalid(null, 'Tanggal lahir tidak bisa diparse'),
            'kelas' => FieldResult::clean('7C'),
        ], 'needs_manual_review', []);

        $keys = $this->builder->build($result);

        $this->assertNull($keys['composite']);
    }

    public function test_composite_key_normalizes_case_for_exact_comparison(): void
    {
        $resultA = new RowAuditResult('excel', 1, [
            'nama' => FieldResult::clean('Belfa Lia Abdul Gafar'),
            'tanggal_lahir' => FieldResult::clean('2013-04-04'),
            'kelas' => FieldResult::clean('7c'),
        ], 'clean', []);

        $resultB = new RowAuditResult('gform', 2, [
            'nama' => FieldResult::clean('BELFA LIA ABDUL GAFAR'),
            'tanggal_lahir' => FieldResult::clean('2013-04-04'),
            'kelas' => FieldResult::clean('7C'),
        ], 'clean', []);

        $keysA = $this->builder->build($resultA);
        $keysB = $this->builder->build($resultB);

        $this->assertSame($keysA['composite'], $keysB['composite']);
    }
}

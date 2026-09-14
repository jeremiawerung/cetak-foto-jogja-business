<?php

namespace Tests\Unit\VerifikasiSiswa;

use App\Models\VerifikasiSiswaFieldDefinition;
use App\Services\VerifikasiSiswa\ProyekFieldSeeder;
use App\Services\VerifikasiSiswa\Readers\RosterExcelReader;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

class RosterExcelReaderTest extends TestCase
{
    /**
     * @return Collection<int, VerifikasiSiswaFieldDefinition>
     */
    private function defaultFieldDefinitions(): Collection
    {
        return collect((new ProyekFieldSeeder)->defaultFieldDefinitions())
            ->map(fn (array $attrs) => new VerifikasiSiswaFieldDefinition($attrs));
    }

    private function writeXlsx(array $header, array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray($header, null, 'A1');

        foreach ($rows as $i => $row) {
            $sheet->fromArray($row, null, 'A'.($i + 2));
        }

        $path = tempnam(sys_get_temp_dir(), 'roster').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function test_reads_rows_with_standard_header_order(): void
    {
        $path = $this->writeXlsx(
            ['No', 'Nama', 'JK', 'NIS', 'NISN', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat', 'Agama', 'Kelas'],
            [[1, 'Siswa Satu', 'L', '100', '1000000001', 'Yogyakarta', '2013-01-01', 'Jl Contoh', 'Islam', '7A']],
        );

        try {
            $rows = (new RosterExcelReader)->read($path, $this->defaultFieldDefinitions());

            $this->assertCount(1, $rows);
            $this->assertSame('Siswa Satu', $rows[0]['nama']);
            $this->assertSame('L', $rows[0]['jenis_kelamin']);
            $this->assertSame('100', $rows[0]['nis']);
            $this->assertSame('1000000001', $rows[0]['nisn']);
            $this->assertSame('7A', $rows[0]['kelas']);
        } finally {
            unlink($path);
        }
    }

    public function test_reads_rows_with_different_header_names_and_order(): void
    {
        // Urutan & penamaan kolom beda dari sample utama — mensimulasikan sekolah lain
        // dengan format Excel yang berbeda. "Rombel" dipakai alih-alih "Kelas", dan
        // "Jenis Kelamin" alih-alih "JK".
        $path = $this->writeXlsx(
            ['Nama Lengkap', 'Rombel', 'NISN', 'NIS', 'Jenis Kelamin', 'Tempat, Tanggal Lahir'],
            [['Siswa Dua', '8B', '2000000002', '200', 'P', 'Sleman']],
        );

        try {
            $rows = (new RosterExcelReader)->read($path, $this->defaultFieldDefinitions());

            $this->assertCount(1, $rows);
            $this->assertSame('Siswa Dua', $rows[0]['nama']);
            $this->assertSame('8B', $rows[0]['kelas']);
            $this->assertSame('2000000002', $rows[0]['nisn']);
            $this->assertSame('200', $rows[0]['nis']);
            $this->assertSame('P', $rows[0]['jenis_kelamin']);
        } finally {
            unlink($path);
        }
    }

    public function test_skips_blank_rows(): void
    {
        $path = $this->writeXlsx(
            ['Nama', 'Kelas'],
            [['Siswa Satu', '7A'], ['', ''], ['Siswa Dua', '7B']],
        );

        try {
            $rows = (new RosterExcelReader)->read($path, $this->defaultFieldDefinitions());

            $this->assertCount(2, $rows);
        } finally {
            unlink($path);
        }
    }

    public function test_school_without_agama_field_leaves_other_columns_intact(): void
    {
        // Sekolah B: tidak punya kolom Agama sama sekali di roster-nya.
        $fieldDefinitions = $this->defaultFieldDefinitions()->reject(fn ($f) => $f->key === 'agama');

        $path = $this->writeXlsx(
            ['Nama', 'Kelas', 'NIS', 'NISN'],
            [['Siswa Tanpa Agama', '7A', '100', '1000000001']],
        );

        try {
            $rows = (new RosterExcelReader)->read($path, $fieldDefinitions);

            $this->assertCount(1, $rows);
            $this->assertSame('Siswa Tanpa Agama', $rows[0]['nama']);
            $this->assertSame('7A', $rows[0]['kelas']);
            $this->assertSame('100', $rows[0]['nis']);
            $this->assertSame('1000000001', $rows[0]['nisn']);
            $this->assertArrayNotHasKey('agama', $rows[0]);
        } finally {
            unlink($path);
        }
    }

    public function test_custom_field_is_read_when_registered_with_keywords(): void
    {
        $fieldDefinitions = $this->defaultFieldDefinitions()->push(new VerifikasiSiswaFieldDefinition([
            'key' => 'nomor_sekolah', 'label' => 'Nomor Sekolah', 'tipe' => 'text',
            'is_core' => false, 'sumber_utama' => 'roster', 'urutan' => 8,
            'kata_kunci_header' => [['nomor', 'sekolah']],
        ]));

        $path = $this->writeXlsx(
            ['Nama', 'Kelas', 'Nomor Sekolah'],
            [['Siswa Custom', '7A', 'SKL-001']],
        );

        try {
            $rows = (new RosterExcelReader)->read($path, $fieldDefinitions);

            $this->assertSame('SKL-001', $rows[0]['nomor_sekolah']);
        } finally {
            unlink($path);
        }
    }
}

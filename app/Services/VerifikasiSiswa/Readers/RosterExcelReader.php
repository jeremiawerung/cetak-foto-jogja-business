<?php

namespace App\Services\VerifikasiSiswa\Readers;

use App\Models\VerifikasiSiswaFieldDefinition;
use App\Services\VerifikasiSiswa\FieldHeaderMatcher;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Baca roster dari Excel. Beda dari ExcelMasterReader lama (KartuPelajar) yang hardcode
 * kolom by huruf — reader ini baca baris header dulu lalu cocokkan NAMA kolom (fleksibel,
 * fuzzy, lewat FieldHeaderMatcher) ke field APA SAJA yang didaftarkan proyek ini —
 * bukan cuma 9 field tetap, supaya sekolah yang tidak punya field tertentu (mis. Agama)
 * atau punya field custom (mis. Nomor Sekolah) tetap terbaca benar.
 */
class RosterExcelReader
{
    private const HEADER_ROW = 1;

    public function __construct(
        private readonly FieldHeaderMatcher $matcher = new FieldHeaderMatcher,
    ) {}

    /**
     * @param  Collection<int, VerifikasiSiswaFieldDefinition>  $fieldDefinitions  field aktif proyek (core + dinamis)
     * @return list<array<string, ?string>> key = field key (mis. "nama", "agama", "nomor_sekolah")
     */
    public function read(string $filePath, Collection $fieldDefinitions): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheet(0);

        $headers = $this->readHeaderRow($sheet);
        $definitions = $this->matcher->definitionsForReading($fieldDefinitions);
        $columnMap = $this->matcher->buildColumnMap($headers, $definitions);

        $keys = array_keys($columnMap);
        $rows = [];

        for ($rowNumber = self::HEADER_ROW + 1; $rowNumber <= $sheet->getHighestRow(); $rowNumber++) {
            $nama = $this->mappedValue($sheet, $columnMap, 'nama', $rowNumber);

            if ($this->isBlank($nama)) {
                continue;
            }

            $row = [];

            foreach ($keys as $key) {
                $row[$key] = $this->mappedValue($sheet, $columnMap, $key, $rowNumber);
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return list<string> nama kolom berurutan (index 0 = kolom pertama)
     */
    private function readHeaderRow(Worksheet $sheet): array
    {
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $headers = [];

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $headers[] = trim((string) $sheet->getCell($colLetter.self::HEADER_ROW)->getValue());
        }

        return $headers;
    }

    /**
     * @param  array<string, int>  $columnMap  field key => index kolom (0-based)
     */
    private function mappedValue(Worksheet $sheet, array $columnMap, string $field, int $row): ?string
    {
        if (! isset($columnMap[$field])) {
            return null;
        }

        $colLetter = Coordinate::stringFromColumnIndex($columnMap[$field] + 1);

        return $this->cellValue($sheet, $colLetter, $row);
    }

    private function cellValue(Worksheet $sheet, string $column, int $row): ?string
    {
        $value = $sheet->getCell($column.$row)->getValue();

        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return number_format((float) $value, 0, '', '');
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function isBlank(?string $value): bool
    {
        return $value === null || $value === '';
    }
}

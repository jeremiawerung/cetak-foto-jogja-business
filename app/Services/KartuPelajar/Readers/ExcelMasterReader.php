<?php

namespace App\Services\KartuPelajar\Readers;

use App\Services\KartuPelajar\Normalizers\TanggalLahirNormalizer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class ExcelMasterReader
{
    private const SHEET_NAME = 'Sheet3';

    private const FIRST_DATA_ROW = 3;

    public function __construct(
        private readonly TanggalLahirNormalizer $tanggalLahirNormalizer = new TanggalLahirNormalizer,
    ) {}

    /**
     * @return list<array{row_number: int, nama: ?string, kelas: ?string, tanggal_lahir: ?string, tempat_lahir: ?string, alamat: ?string, nis: ?string, nisn: ?string, agama: ?string, jenis_kelamin: ?string}>
     */
    public function read(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName(self::SHEET_NAME);

        if ($sheet === null) {
            throw new RuntimeException('Sheet "'.self::SHEET_NAME.'" tidak ditemukan di file Excel master');
        }

        $rows = [];
        $rowNumber = self::FIRST_DATA_ROW;

        while ($rowNumber <= $sheet->getHighestRow()) {
            $kelas = $this->cellValue($sheet, 'B', $rowNumber);
            $nama = $this->cellValue($sheet, 'C', $rowNumber);

            if ($this->isBlank($kelas) && $this->isBlank($nama)) {
                break;
            }

            $tanggalLahirRaw = $this->cellValue($sheet, 'D', $rowNumber);

            $rows[] = [
                'row_number' => $rowNumber,
                'nama' => $nama,
                'kelas' => $kelas,
                'tanggal_lahir' => $tanggalLahirRaw,
                'tempat_lahir' => $this->tanggalLahirNormalizer->extractTempatLahir($tanggalLahirRaw),
                'alamat' => $this->cellValue($sheet, 'E', $rowNumber),
                'nis' => $this->cellValue($sheet, 'G', $rowNumber),
                'nisn' => $this->cellValue($sheet, 'H', $rowNumber),
                'agama' => $this->cellValue($sheet, 'F', $rowNumber),
                'jenis_kelamin' => $this->cellValue($sheet, 'I', $rowNumber),
            ];

            $rowNumber++;
        }

        return $rows;
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

        return trim((string) $value);
    }

    private function isBlank(?string $value): bool
    {
        return $value === null || $value === '';
    }
}

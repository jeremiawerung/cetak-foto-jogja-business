<?php

namespace App\Services\KartuPelajar\Readers;

use App\Services\KartuPelajar\Normalizers\TanggalLahirNormalizer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

/**
 * Baca hasil Google Form dari ekspor Excel (Google Sheets -> Download -> .xlsx) —
 * jauh lebih andal daripada GFormPdfReader karena datanya sudah per-kolom, bukan
 * hasil ekstraksi teks PDF yang rawan kolom tumpang tindih. Dipakai kalau tim
 * internal yang mengelola Google Form-nya bisa ekspor sheet-nya langsung.
 *
 * Sheet diambil berdasarkan URUTAN (sheet pertama), bukan nama — Google Forms
 * otomatis menamai sheet "Form Responses N" dan angkanya bisa beda-beda.
 */
class GFormExcelReader
{
    private const FIRST_DATA_ROW = 2;

    public function __construct(
        private readonly TanggalLahirNormalizer $tanggalLahirNormalizer = new TanggalLahirNormalizer,
    ) {}

    /**
     * @return list<array{row_number: int, timestamp: string, kelas: ?string, nama: ?string, tanggal_lahir: ?string, tempat_lahir: ?string, alamat: ?string, nis: ?string, nisn: ?string, agama: ?string, jenis_kelamin: ?string}>
     */
    public function read(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheet(0);

        if ($sheet === null) {
            throw new RuntimeException('File Excel Google Form tidak punya sheet sama sekali');
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
                'timestamp' => $this->timestampValue($sheet, 'A', $rowNumber),
                'kelas' => $kelas,
                'nama' => $nama,
                'tanggal_lahir' => $tanggalLahirRaw,
                'tempat_lahir' => $this->tanggalLahirNormalizer->extractTempatLahir($tanggalLahirRaw),
                'alamat' => $this->cellValue($sheet, 'F', $rowNumber),
                'nis' => $this->cellValue($sheet, 'H', $rowNumber),
                'nisn' => $this->cellValue($sheet, 'I', $rowNumber),
                'agama' => $this->cellValue($sheet, 'E', $rowNumber),
                'jenis_kelamin' => $this->cellValue($sheet, 'G', $rowNumber),
            ];

            $rowNumber++;
        }

        return $rows;
    }

    private function timestampValue(Worksheet $sheet, string $column, int $row): string
    {
        $cell = $sheet->getCell($column.$row);
        $raw = $cell->getValue();

        if ($raw !== null && Date::isDateTime($cell)) {
            return Date::excelToDateTimeObject($raw)->format('Y-m-d H:i:s');
        }

        // Fallback kalau ternyata tersimpan sebagai teks, bukan tanggal asli.
        return trim((string) $raw);
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

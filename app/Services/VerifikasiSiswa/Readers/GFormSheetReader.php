<?php

namespace App\Services\VerifikasiSiswa\Readers;

use App\Models\VerifikasiSiswaFieldDefinition;
use App\Models\VerifikasiSiswaProyek;
use App\Services\KartuPelajar\Normalizers\KelasNormalizer;
use App\Services\KartuPelajar\Normalizers\TanggalLahirNormalizer;
use App\Services\VerifikasiSiswa\FieldHeaderMatcher;
use App\Services\VerifikasiSiswa\GoogleSheetsClient;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Baca baris BARU dari sheet respons Google Form (via GoogleSheetsClient), mulai dari
 * proyek->last_synced_row + 1.
 *
 * Dulu posisi kolom di-hardcode tetap (A-I) — kalau sekolah B tidak punya pertanyaan
 * Agama, semua kolom setelahnya salah geser. SEKARANG baris header (baris 1 sheet)
 * dibaca dulu & dicocokkan lewat FieldHeaderMatcher (sama seperti RosterExcelReader)
 * ke field APA SAJA yang didaftarkan proyek ini — field yang tidak ada di form sekolah
 * itu otomatis null, tidak menggeser field lain.
 *
 * Beberapa normalisasi tetap diterapkan berdasarkan KEY field-nya (bukan generik),
 * karena ini pola nyata yang ditemukan dari sample Google Form asli:
 * - "tanggal_lahir": selalu lewat TanggalLahirNormalizer (bisa berupa tanggal murni
 *   ATAU gabungan "Tempat, Tanggal Lahir" dalam 1 kolom — normalizer ini cari pola
 *   tanggalnya di mana pun, mengabaikan teks tempat di depannya).
 * - "tempat_lahir": kalau punya kolom sendiri, dibaca langsung. Kalau TIDAK (karena
 *   tergabung dengan tanggal_lahir dalam 1 kolom seperti sample asli), diekstrak dari
 *   nilai mentah kolom tanggal_lahir lewat TanggalLahirNormalizer::extractTempatLahir().
 * - "kelas": dinormalisasi dari format romawi ("VII A") ke desimal ("7A") kalau perlu.
 * - "jenis_kelamin": dinormalisasi dari kata penuh ("Laki-laki"/"Perempuan") ke 1 huruf.
 * - field lain (termasuk field custom seperti "nomor_sekolah"): nilai mentah apa adanya.
 */
class GFormSheetReader
{
    public function __construct(
        private readonly GoogleSheetsClient $client = new GoogleSheetsClient,
        private readonly TanggalLahirNormalizer $tanggalLahirNormalizer = new TanggalLahirNormalizer,
        private readonly KelasNormalizer $kelasNormalizer = new KelasNormalizer,
        private readonly FieldHeaderMatcher $matcher = new FieldHeaderMatcher,
    ) {}

    /**
     * @param  Collection<int, VerifikasiSiswaFieldDefinition>  $fieldDefinitions  field aktif proyek (core + dinamis)
     * @return list<array<string, mixed>> selalu ada "sheet_row_number" & "timestamp", sisanya field key => nilai
     */
    public function readNewRows(VerifikasiSiswaProyek $proyek, Collection $fieldDefinitions): array
    {
        if ($proyek->google_sheet_id === null) {
            return [];
        }

        $sheetName = $proyek->google_sheet_range ?? $this->client->firstSheetTitle($proyek->google_sheet_id);

        $headerRows = $this->client->fetchRows($proyek->google_sheet_id, "'{$sheetName}'!1:1");
        $headers = $headerRows[0] ?? [];

        $definitions = $this->matcher->definitionsForReading($fieldDefinitions);
        $columnMap = $this->matcher->buildColumnMap($headers, $definitions);

        $fromRow = max(2, $proyek->last_synced_row + 1);
        $values = $this->client->fetchRows($proyek->google_sheet_id, "'{$sheetName}'!A{$fromRow}:Z");

        $rows = [];

        foreach ($values as $index => $columns) {
            if ($this->isBlankRow($columns)) {
                continue;
            }

            $row = [
                'sheet_row_number' => $fromRow + $index,
                'timestamp' => $this->parseTimestamp($columns[0] ?? null),
            ];

            foreach ($columnMap as $key => $colIndex) {
                $row[$key] = $this->normalizeFieldValue($key, $this->nullableTrim($columns[$colIndex] ?? null));
            }

            if (! isset($row['tempat_lahir']) && isset($columnMap['tanggal_lahir'])) {
                $rawTtl = $this->nullableTrim($columns[$columnMap['tanggal_lahir']] ?? null);
                $row['tempat_lahir'] = $this->tanggalLahirNormalizer->extractTempatLahir($rawTtl);
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function normalizeFieldValue(string $key, ?string $raw): ?string
    {
        return match ($key) {
            'tanggal_lahir' => $this->normalizeTanggalLahir($raw),
            'kelas' => $this->normalizeKelas($raw),
            'jenis_kelamin' => $this->normalizeJenisKelamin($raw),
            default => $raw,
        };
    }

    private function normalizeTanggalLahir(?string $raw): ?string
    {
        $result = $this->tanggalLahirNormalizer->normalize($raw);

        return $result->status !== 'invalid' ? $result->value : null;
    }

    /**
     * "VII A" (format form) -> "7A" (format roster). Kalau gagal dikenali polanya,
     * dikembalikan apa adanya (trim) supaya datanya tidak hilang — cuma tidak akan
     * otomatis cocok dengan kelas di roster, tetap bisa dihubungkan manual.
     */
    private function normalizeKelas(?string $raw): ?string
    {
        $result = $this->kelasNormalizer->normalize($raw);

        return $result->status !== 'invalid' ? $result->value : $raw;
    }

    /** "Laki-laki"/"Perempuan" (format form) -> "L"/"P" (format roster). */
    private function normalizeJenisKelamin(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        return match (true) {
            str_starts_with(mb_strtolower($raw), 'l') => 'L',
            str_starts_with(mb_strtolower($raw), 'p') => 'P',
            default => $raw,
        };
    }

    private function isBlankRow(array $columns): bool
    {
        foreach ($columns as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function parseTimestamp(?string $raw): ?string
    {
        $raw = $this->nullableTrim($raw);

        if ($raw === null) {
            return null;
        }

        // Format asli Google Sheets (sample nyata): "18/08/2026 7:37:03" — DD/MM/YYYY,
        // ambigu buat Carbon::parse() generik (bisa disangka MM/DD), jadi format persis
        // ini dicoba dulu sebelum fallback ke parser umum.
        try {
            return Carbon::createFromFormat('d/m/Y G:i:s', $raw)->format('Y-m-d H:i:s');
        } catch (Throwable) {
            // lanjut ke fallback di bawah
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return $raw;
        }
    }
}

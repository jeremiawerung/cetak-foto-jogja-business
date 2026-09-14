<?php

namespace App\Services\KartuPelajar;

use App\Models\KartuPelajarBatch;
use App\Models\KartuPelajarRow;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Membangun pratinjau & CSV ekspor siap-Photoshop dari pasangan baris yang sudah
 * tercocok (auto_matched/manual_resolved dengan matched_row_id terisi) — baris
 * "Tanpa Pasangan" TIDAK diikutkan (sesuai keputusan: hanya yang tercocok 2 sisi).
 *
 * Aturan penggabungan field: Excel diutamakan (dianggap lebih bersih/resmi),
 * GForm cuma dipakai kalau field yang sama di Excel kosong. Setiap field yang
 * ADA di kedua sisi tapi NILAINYA BEDA ditandai sebagai "discrepancy" supaya
 * admin bisa meninjau sebelum unduh — Excel tetap dipakai untuk nilai akhir,
 * tapi bedanya tidak disembunyikan.
 */
class ExportBuilder
{
    private const EXPORT_FIELDS = ['nama', 'kelas', 'tanggal_lahir', 'tempat_lahir', 'alamat', 'agama', 'jenis_kelamin', 'nis', 'nisn'];

    private const CSV_HEADER = ['NAMA', 'TTL', 'ALAMAT', 'JK', 'AGAMA', 'NIS', 'NISN', 'ID_FILE'];

    /**
     * @return list<array{id_file: string, excel_row: KartuPelajarRow, gform_row: KartuPelajarRow, merged: array<string, ?string>, discrepancies: list<string>}>
     */
    public function buildPreview(KartuPelajarBatch $batch): array
    {
        $excelRows = $batch->rows()
            ->whereIn('matching_status', ['auto_matched', 'manual_resolved'])
            ->whereNotNull('matched_row_id')
            ->where('source', 'excel')
            ->with('matchedRow')
            ->orderBy('source_row_number')
            ->get();

        $pairs = [];

        foreach ($excelRows as $excelRow) {
            $gformRow = $excelRow->matchedRow;

            if ($gformRow === null) {
                continue;
            }

            $merged = [];
            $discrepancies = [];

            foreach (self::EXPORT_FIELDS as $field) {
                $excelValue = $this->fieldValue($excelRow, $field);
                $gformValue = $this->fieldValue($gformRow, $field);

                $merged[$field] = $excelValue ?? $gformValue;

                if ($excelValue !== null && $gformValue !== null && $excelValue !== $gformValue) {
                    $discrepancies[] = $field;
                }
            }

            $pairs[] = [
                'id_file' => $excelRow->idFile(),
                'excel_row' => $excelRow,
                'gform_row' => $gformRow,
                'merged' => $merged,
                'discrepancies' => $discrepancies,
            ];
        }

        return $pairs;
    }

    public function toCsv(KartuPelajarBatch $batch): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new \RuntimeException('Gagal membuat buffer CSV');
        }

        fputcsv($handle, self::CSV_HEADER);

        foreach ($this->buildPreview($batch) as $pair) {
            $merged = $pair['merged'];

            fputcsv($handle, [
                $merged['nama'],
                $this->formatTtl($merged['tempat_lahir'], $merged['tanggal_lahir']),
                $merged['alamat'],
                $merged['jenis_kelamin'],
                $merged['agama'],
                $merged['nis'],
                $merged['nisn'],
                $pair['id_file'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }

    private function fieldValue(KartuPelajarRow $row, string $field): ?string
    {
        if ($field === 'tanggal_lahir') {
            return $row->tanggal_lahir?->format('Y-m-d');
        }

        return $row->{$field};
    }

    private function formatTtl(?string $tempatLahir, ?string $tanggalLahirIso): string
    {
        $tanggalFormatted = null;

        if ($tanggalLahirIso !== null) {
            try {
                $tanggalFormatted = Carbon::createFromFormat('Y-m-d', $tanggalLahirIso)->translatedFormat('d F Y');
            } catch (Throwable) {
                $tanggalFormatted = $tanggalLahirIso;
            }
        }

        return trim(implode(' ', array_filter([$tempatLahir, $tanggalFormatted])));
    }
}

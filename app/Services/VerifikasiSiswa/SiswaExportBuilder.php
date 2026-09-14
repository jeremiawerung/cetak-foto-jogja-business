<?php

namespace App\Services\VerifikasiSiswa;

use App\Models\VerifikasiSiswa;
use App\Models\VerifikasiSiswaExportColumn;
use App\Models\VerifikasiSiswaProyek;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

/**
 * Ekspor CSV siap-Photoshop per kelas — SEMUA siswa di kelas itu diikutkan (termasuk
 * yang "Belum Isi", field yang kosong ya dikosongkan saja di CSV), karena roster sendiri
 * sudah cukup lengkap dan kartu tetap perlu dibuat untuk semua siswa, bukan cuma yang
 * sudah lengkap datanya. Kolom, urutan, & label CSV-nya diambil dari `export_columns`
 * milik proyek (bisa beda-beda per sekolah) — bukan format tetap lagi.
 */
class SiswaExportBuilder
{
    private const CORE_KEYS = ['nama', 'kelas', 'nis', 'nisn'];

    /**
     * @return Collection<int, VerifikasiSiswa>
     */
    public function siswaUntukKelas(VerifikasiSiswaProyek $proyek, string $kelas): Collection
    {
        return $proyek->siswa()->where('kelas', $kelas)->orderBy('nama')->get();
    }

    public function toCsv(VerifikasiSiswaProyek $proyek, string $kelas): string
    {
        $siswa = $this->siswaUntukKelas($proyek, $kelas);
        $columns = $proyek->exportColumns;

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new RuntimeException('Gagal membuat buffer CSV');
        }

        fputcsv($handle, $columns->pluck('label')->all(), ',', '"', '');

        foreach ($siswa as $s) {
            $row = $columns->map(fn (VerifikasiSiswaExportColumn $column) => $this->valueForColumn($s, $column))->all();
            fputcsv($handle, $row, ',', '"', '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }

    private function valueForColumn(VerifikasiSiswa $s, VerifikasiSiswaExportColumn $column): ?string
    {
        return match ($column->sumber_tipe) {
            'ttl' => $this->formatTtl($this->fieldValue($s, 'tempat_lahir'), $this->fieldValue($s, 'tanggal_lahir')),
            'id_file' => $s->idFile(),
            'field' => $this->fieldValue($s, $column->field_key),
            default => null,
        };
    }

    private function fieldValue(VerifikasiSiswa $s, ?string $fieldKey): ?string
    {
        if ($fieldKey === null) {
            return null;
        }

        if (in_array($fieldKey, self::CORE_KEYS, true)) {
            return $s->{$fieldKey};
        }

        return ($s->data ?? [])[$fieldKey] ?? null;
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

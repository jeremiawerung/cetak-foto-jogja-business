<?php

namespace App\Services\VerifikasiSiswa;

use App\Models\VerifikasiSiswaFormResponse;
use App\Models\VerifikasiSiswaProyek;
use App\Services\VerifikasiSiswa\Readers\GFormSheetReader;

/**
 * Satu proyek: ambil respons baru dari Google Sheets, simpan sebagai
 * VerifikasiSiswaFormResponse, lalu jalankan pencocokan. Dipakai bareng oleh command
 * terjadwal (`verifikasi-siswa:sync`) dan tombol "Sinkron Sekarang" di dashboard,
 * supaya logikanya konsisten di 1 tempat.
 */
class GFormSyncer
{
    private const CORE_KEYS_TETAP = ['nis', 'nisn'];

    public function __construct(
        private readonly GFormSheetReader $reader = new GFormSheetReader,
        private readonly SiswaMatcher $matcher = new SiswaMatcher,
    ) {}

    /**
     * @return array{jumlah_baru: int, status: array<string, int>}
     */
    public function sync(VerifikasiSiswaProyek $proyek): array
    {
        if ($proyek->google_sheet_id === null) {
            return ['jumlah_baru' => 0, 'status' => []];
        }

        $fieldDefinitions = $proyek->fieldDefinitions;
        $coreKeys = array_merge(
            $fieldDefinitions->where('is_core', true)->pluck('key')->all(),
            self::CORE_KEYS_TETAP,
        );

        $newRows = $this->reader->readNewRows($proyek, $fieldDefinitions);

        foreach ($newRows as $row) {
            VerifikasiSiswaFormResponse::updateOrCreate(
                ['proyek_id' => $proyek->id, 'sheet_row_number' => $row['sheet_row_number']],
                $this->splitCoreAndDynamic($row, $coreKeys),
            );
        }

        if ($newRows !== []) {
            $proyek->update(['last_synced_row' => max(array_column($newRows, 'sheet_row_number'))]);
        }

        $summary = $this->matcher->processNewResponses($proyek);

        return ['jumlah_baru' => count($newRows), 'status' => $summary];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $coreKeys
     * @return array<string, mixed>
     */
    private function splitCoreAndDynamic(array $row, array $coreKeys): array
    {
        $attributes = ['submitted_at' => $row['timestamp'], 'status' => 'unmatched'];
        $dynamicData = [];

        foreach ($row as $key => $value) {
            if (in_array($key, ['sheet_row_number', 'timestamp'], true)) {
                continue;
            }

            if (in_array($key, $coreKeys, true)) {
                $attributes[$key] = $value;
            } else {
                $dynamicData[$key] = $value;
            }
        }

        $attributes['data'] = $dynamicData;

        return $attributes;
    }
}

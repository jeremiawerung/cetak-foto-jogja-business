<?php

namespace App\Services\VerifikasiSiswa;

use App\Models\VerifikasiSiswa;
use App\Models\VerifikasiSiswaFieldDefinition;
use App\Models\VerifikasiSiswaFormResponse;
use Carbon\Carbon;
use Throwable;

/**
 * Isi field kosong di siswa (roster) dari respons form yang match — field bertipe
 * sumber_utama "roster" (default) diisi kalau kosong & ditandai discrepancy kalau beda;
 * field "form" (mis. alamat, keputusan user — roster hasil parsing PDF sering cuma
 * sebagian) selalu diambil dari form tanpa pernah ditandai discrepancy. Field APA SAJA
 * yang dipakai (termasuk field custom per sekolah) ditentukan oleh field_definitions
 * proyek itu, bukan daftar tetap — field core (nama/kelas) baca/tulis kolom asli,
 * field dinamis lewat kolom `data` (json). Dipakai bareng oleh SiswaMatcher (otomatis)
 * & SiswaResolver (manual confirm/link) supaya logikanya konsisten di 1 tempat.
 */
class SiswaMergeApplier
{
    /**
     * @return list<string> nama field yang beda nilainya
     */
    public function apply(VerifikasiSiswa $siswa, VerifikasiSiswaFormResponse $response): array
    {
        $discrepancies = [];
        $coreUpdates = [];
        $dynamicData = $siswa->data ?? [];

        foreach ($siswa->proyek->fieldDefinitions as $field) {
            $siswaValue = $this->valueFor($siswa, $field, $dynamicData);
            $responseValue = $this->valueFor($response, $field, $response->data ?? []);

            if ($field->sumber_utama === 'form') {
                if ($responseValue !== null) {
                    $this->setValue($field, $coreUpdates, $dynamicData, $responseValue);
                }

                continue;
            }

            if ($siswaValue === null && $responseValue !== null) {
                $this->setValue($field, $coreUpdates, $dynamicData, $responseValue);
            } elseif ($siswaValue !== null && $responseValue !== null && ! $this->sameValue($field, $siswaValue, $responseValue)) {
                $discrepancies[] = $field->key;
            }
        }

        $coreUpdates['data'] = $dynamicData;
        $coreUpdates['status'] = 'terisi';
        $coreUpdates['matched_form_response_id'] = $response->id;

        $siswa->update($coreUpdates);

        return $discrepancies;
    }

    /**
     * @param  array<string, mixed>  $dynamicData
     */
    private function valueFor(VerifikasiSiswa|VerifikasiSiswaFormResponse $model, VerifikasiSiswaFieldDefinition $field, array $dynamicData): mixed
    {
        return $field->is_core ? $model->{$field->key} : ($dynamicData[$field->key] ?? null);
    }

    /**
     * @param  array<string, mixed>  $coreUpdates
     * @param  array<string, mixed>  $dynamicData
     */
    private function setValue(VerifikasiSiswaFieldDefinition $field, array &$coreUpdates, array &$dynamicData, mixed $value): void
    {
        if ($field->is_core) {
            $coreUpdates[$field->key] = $value;
        } else {
            $dynamicData[$field->key] = $value;
        }
    }

    /**
     * Dari data sungguhan (sample Google Form asli): banyak "beda" yang muncul cuma
     * beda huruf besar/kecil atau spasi berlebih (mis. roster "Islam" vs form "ISLAM",
     * "YOGYAKARTA" vs "Yogyakarta") — bukan perbedaan data yang perlu ditinjau admin.
     * Dibandingkan case-insensitive + spasi dirapikan, supaya cuma perbedaan yang
     * benar-benar berarti yang tercatat sebagai discrepancy. Field tipe "date" dicoba
     * diparse dulu supaya format tanggal yang beda-beda tapi maksudnya sama tidak
     * salah ketandai beda.
     */
    private function sameValue(VerifikasiSiswaFieldDefinition $field, mixed $a, mixed $b): bool
    {
        if (! is_string($a) || ! is_string($b)) {
            return $a === $b;
        }

        if ($field->tipe === 'date') {
            $parsedA = $this->tryParseDate($a);
            $parsedB = $this->tryParseDate($b);

            if ($parsedA !== null && $parsedB !== null) {
                return $parsedA === $parsedB;
            }
        }

        $normalize = fn (string $v) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $v) ?? $v));

        return $normalize($a) === $normalize($b);
    }

    private function tryParseDate(string $value): ?string
    {
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }
}

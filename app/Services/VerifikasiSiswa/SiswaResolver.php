<?php

namespace App\Services\VerifikasiSiswa;

use App\Models\VerifikasiSiswa;
use App\Models\VerifikasiSiswaFormResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Aksi resolusi manual oleh admin atas hasil SiswaMatcher — konfirmasi/tolak saran
 * konflik, hubungkan manual, atau kunci/buka-kunci siswa supaya sinkron berikutnya
 * tidak menimpa data yang sudah final.
 */
class SiswaResolver
{
    public function confirm(VerifikasiSiswaFormResponse $response): void
    {
        if ($response->matched_siswa_id === null) {
            throw new InvalidArgumentException('Respons ini tidak punya kandidat siswa untuk dikonfirmasi.');
        }

        $siswa = VerifikasiSiswa::findOrFail($response->matched_siswa_id);

        if ($siswa->is_locked) {
            throw new InvalidArgumentException('Siswa ini sudah dikunci, buka kunci dulu sebelum mengonfirmasi.');
        }

        DB::transaction(function () use ($response, $siswa) {
            $discrepancies = (new SiswaMergeApplier)->apply($siswa, $response);

            $response->update(['status' => 'matched', 'discrepancies' => $discrepancies]);
        });
    }

    public function reject(VerifikasiSiswaFormResponse $response): void
    {
        $response->update(['status' => 'unmatched', 'matched_siswa_id' => null, 'discrepancies' => null]);
    }

    public function linkManually(VerifikasiSiswaFormResponse $response, VerifikasiSiswa $siswa): void
    {
        if ($response->proyek_id !== $siswa->proyek_id) {
            throw new InvalidArgumentException('Respons dan siswa harus berada di proyek yang sama.');
        }

        if ($siswa->is_locked) {
            throw new InvalidArgumentException('Siswa ini sudah dikunci, buka kunci dulu sebelum menghubungkan.');
        }

        DB::transaction(function () use ($response, $siswa) {
            $discrepancies = (new SiswaMergeApplier)->apply($siswa, $response);

            $response->update(['status' => 'matched', 'matched_siswa_id' => $siswa->id, 'discrepancies' => $discrepancies]);
        });
    }

    /**
     * Selesaikan 1 field yang tercatat "beda" antara roster & respons form (lihat
     * SiswaMergeApplier) — bukan konfirmasi seluruh respons, cuma 1 field spesifik.
     * $useForm true: timpa nilai siswa dengan nilai dari respons. false: pertahankan
     * nilai siswa yang sekarang (dianggap sudah benar). Kedua kasus: field itu dicoret
     * dari daftar discrepancies respons ini supaya tidak muncul lagi di dashboard.
     */
    public function resolveDiscrepancy(VerifikasiSiswaFormResponse $response, string $field, bool $useForm): void
    {
        if ($response->matched_siswa_id === null) {
            throw new InvalidArgumentException('Respons ini tidak punya siswa terhubung.');
        }

        $siswa = VerifikasiSiswa::findOrFail($response->matched_siswa_id);

        $fieldDefinition = $siswa->proyek->fieldDefinitions->firstWhere('key', $field);

        if ($fieldDefinition === null) {
            throw new InvalidArgumentException("Field '{$field}' tidak dikenal di proyek ini.");
        }

        if ($siswa->is_locked) {
            throw new InvalidArgumentException('Siswa ini sudah dikunci, buka kunci dulu sebelum menyelesaikan perbedaan data.');
        }

        $discrepancies = $response->discrepancies ?? [];

        if (! in_array($field, $discrepancies, true)) {
            throw new InvalidArgumentException("Field '{$field}' tidak sedang tercatat sebagai perbedaan pada respons ini.");
        }

        DB::transaction(function () use ($response, $siswa, $fieldDefinition, $useForm, $discrepancies) {
            if ($useForm) {
                if ($fieldDefinition->is_core) {
                    $siswa->update([$fieldDefinition->key => $response->{$fieldDefinition->key}]);
                } else {
                    $data = $siswa->data ?? [];
                    $data[$fieldDefinition->key] = ($response->data ?? [])[$fieldDefinition->key] ?? null;
                    $siswa->update(['data' => $data]);
                }
            }

            $response->update(['discrepancies' => array_values(array_diff($discrepancies, [$fieldDefinition->key]))]);
        });
    }

    public function lock(VerifikasiSiswa $siswa): void
    {
        $siswa->update(['is_locked' => true]);
    }

    public function unlock(VerifikasiSiswa $siswa): void
    {
        $siswa->update(['is_locked' => false]);
    }
}

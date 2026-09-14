<?php

namespace App\Services\VerifikasiSiswa;

use App\Models\VerifikasiSiswa;
use App\Models\VerifikasiSiswaFormResponse;
use App\Models\VerifikasiSiswaProyek;
use App\Services\KartuPelajar\NameSimilarity;
use Illuminate\Support\Collection;

/**
 * Cocokkan respons Google Form yang baru masuk ke siswa di roster (proyek yang sama).
 * Roster jadi ACUAN UTAMA — kunci pencocokan: NISN (utama) -> NIS (fallback) -> fuzzy
 * nama+kelas (fallback terakhir). Field yang cocok tidak otomatis menimpa roster —
 * field kosong ditutup, field yang beda ditandai discrepancy (lihat mergeIntoSiswa()).
 */
class SiswaMatcher
{
    private readonly float $fuzzyNameThreshold;

    private readonly float $nisnCrosscheckNameThreshold;

    public function __construct(
        ?float $fuzzyNameThreshold = null,
        ?float $nisnCrosscheckNameThreshold = null,
    ) {
        $this->fuzzyNameThreshold = $fuzzyNameThreshold ?? (float) config('services.verifikasi_siswa.fuzzy_name_threshold', 0.75);
        $this->nisnCrosscheckNameThreshold = $nisnCrosscheckNameThreshold ?? (float) config('services.verifikasi_siswa.nisn_crosscheck_name_threshold', 0.5);
    }

    /**
     * @return array<string, int> ringkasan jumlah respons per status setelah diproses
     */
    public function processNewResponses(VerifikasiSiswaProyek $proyek): array
    {
        $siswaByNisn = $proyek->siswa()->whereNotNull('nisn')->get()->groupBy('nisn');
        $siswaByNis = $proyek->siswa()->whereNotNull('nis')->get()->groupBy('nis');
        $siswaByKelas = $proyek->siswa()->whereNotNull('nama')->get()->groupBy('kelas');

        $responses = $proyek->formResponses()->where('status', 'unmatched')->get();

        foreach ($responses as $response) {
            $this->matchOne($response, $siswaByNisn, $siswaByNis, $siswaByKelas);
        }

        return $proyek->formResponses()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    /**
     * @param  Collection<string, Collection<int, VerifikasiSiswa>>  $siswaByNisn
     * @param  Collection<string, Collection<int, VerifikasiSiswa>>  $siswaByNis
     * @param  Collection<string, Collection<int, VerifikasiSiswa>>  $siswaByKelas
     */
    private function matchOne(
        VerifikasiSiswaFormResponse $response,
        Collection $siswaByNisn,
        Collection $siswaByNis,
        Collection $siswaByKelas,
    ): void {
        $siswa = null;
        $crosscheck = false;

        if ($response->nisn !== null && $siswaByNisn->has($response->nisn) && $siswaByNisn->get($response->nisn)->count() === 1) {
            $siswa = $siswaByNisn->get($response->nisn)->first();
            $crosscheck = true;
        } elseif ($response->nis !== null && $siswaByNis->has($response->nis) && $siswaByNis->get($response->nis)->count() === 1) {
            $siswa = $siswaByNis->get($response->nis)->first();
            $crosscheck = true;
        }

        if ($siswa !== null && $crosscheck) {
            $similarity = NameSimilarity::score($siswa->nama ?? '', $response->nama ?? '');

            if ($similarity < $this->nisnCrosscheckNameThreshold) {
                $response->update(['status' => 'conflict', 'matched_siswa_id' => $siswa->id]);

                return;
            }
        }

        if ($siswa === null) {
            $siswa = $this->findByFuzzyName($response, $siswaByKelas);
        }

        if ($siswa === null) {
            $response->update(['status' => 'conflict', 'matched_siswa_id' => null]);

            return;
        }

        if ($siswa->is_locked) {
            $response->update(['status' => 'conflict', 'matched_siswa_id' => $siswa->id]);

            return;
        }

        $discrepancies = (new SiswaMergeApplier)->apply($siswa, $response);

        $response->update(['status' => 'matched', 'matched_siswa_id' => $siswa->id, 'discrepancies' => $discrepancies]);
    }

    private function findByFuzzyName(VerifikasiSiswaFormResponse $response, Collection $siswaByKelas): ?VerifikasiSiswa
    {
        if ($response->nama === null || $response->kelas === null || ! $siswaByKelas->has($response->kelas)) {
            return null;
        }

        $scored = $siswaByKelas->get($response->kelas)
            ->map(fn (VerifikasiSiswa $s) => ['siswa' => $s, 'score' => NameSimilarity::score($s->nama ?? '', $response->nama)])
            ->sortByDesc('score')
            ->values();

        $best = $scored->first();

        if ($best === null || $best['score'] < $this->fuzzyNameThreshold) {
            return null;
        }

        if ($scored->count() > 1 && ($best['score'] - $scored[1]['score']) < 0.05) {
            return null;
        }

        return $best['siswa'];
    }
}

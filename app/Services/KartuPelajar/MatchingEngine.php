<?php

namespace App\Services\KartuPelajar;

use App\Models\KartuPelajarRow;
use Illuminate\Support\Collection;

/**
 * Mesin pencocokan Excel <-> GForm untuk satu batch, dijalankan 3 tahap berurutan:
 *
 * 1. Exact match via NISN (matching_key_nisn) — kunci utama.
 * 2. Exact match via kunci komposit nama+tanggal_lahir+kelas (matching_key_composite)
 *    — fallback untuk baris yang NISN-nya invalid/kosong.
 * 3. Fuzzy match nama (Levenshtein) dengan tanggal_lahir & kelas WAJIB sama persis
 *    sebagai pagar — untuk baris yang masih tersisa setelah tahap 1 & 2.
 *
 * Hasil tahap 3 berstatus 'auto_matched' langsung kalau kemiripan nama SANGAT tinggi
 * (>= fuzzyAutoMatchThreshold, default 95%) — supaya admin tidak perlu konfirmasi manual
 * untuk typo-typo kecil yang sudah jelas maksudnya. Kalau kemiripannya cuma "cukup tinggi"
 * (antara fuzzyNameThreshold dan fuzzyAutoMatchThreshold), tetap 'fuzzy_candidate' — perlu
 * dikonfirmasi manual, karena belum cukup meyakinkan untuk dipercaya buta.
 *
 * Cek silang: kalau NISN cocok tapi namanya sangat berbeda (indikasi NISN salah
 * ketik/tertukar), status diturunkan jadi 'conflict' meski NISN-nya cocok — NISN
 * tidak dipercaya buta. Ini TIDAK terpengaruh oleh ambang auto-match di atas, karena
 * masalahnya bukan soal kemiripan nama, tapi indikasi data (NISN) yang saling bertentangan.
 */
class MatchingEngine
{
    private readonly float $fuzzyNameThreshold;

    private readonly float $fuzzyAutoMatchThreshold;

    private readonly float $nisnCrosscheckNameThreshold;

    public function __construct(
        ?float $fuzzyNameThreshold = null,
        ?float $fuzzyAutoMatchThreshold = null,
        ?float $nisnCrosscheckNameThreshold = null,
    ) {
        $this->fuzzyNameThreshold = $fuzzyNameThreshold ?? (float) config('services.kartu_pelajar.fuzzy_name_threshold', 0.75);
        $this->fuzzyAutoMatchThreshold = $fuzzyAutoMatchThreshold ?? (float) config('services.kartu_pelajar.fuzzy_auto_match_threshold', 0.95);
        $this->nisnCrosscheckNameThreshold = $nisnCrosscheckNameThreshold ?? (float) config('services.kartu_pelajar.nisn_crosscheck_name_threshold', 0.5);
    }

    /**
     * @return array<string, int> ringkasan jumlah baris per matching_status setelah diproses
     */
    public function matchBatch(int $batchId): array
    {
        $excelRows = $this->unprocessedRows($batchId, 'excel');
        $gformRows = $this->unprocessedRows($batchId, 'gform');

        $this->matchByExactKey($excelRows, $gformRows, 'matching_key_nisn', 'nisn', crosscheckName: true);

        $excelRows = $this->unprocessedRows($batchId, 'excel');
        $gformRows = $this->unprocessedRows($batchId, 'gform');

        $this->matchByExactKey($excelRows, $gformRows, 'matching_key_composite', 'fallback', crosscheckName: false);

        $excelRows = $this->unprocessedRows($batchId, 'excel');
        $gformRows = $this->unprocessedRows($batchId, 'gform');

        $this->matchByFuzzyName($excelRows, $gformRows);

        return KartuPelajarRow::where('batch_id', $batchId)
            ->selectRaw('matching_status, count(*) as total')
            ->groupBy('matching_status')
            ->pluck('total', 'matching_status')
            ->all();
    }

    private function unprocessedRows(int $batchId, string $source): Collection
    {
        return KartuPelajarRow::query()
            ->where('batch_id', $batchId)
            ->where('source', $source)
            ->where('matching_status', 'unprocessed')
            ->get();
    }

    private function matchByExactKey(Collection $excelRows, Collection $gformRows, string $keyColumn, string $matchedVia, bool $crosscheckName): void
    {
        $excelGroups = $excelRows->filter(fn (KartuPelajarRow $r) => $r->{$keyColumn} !== null)->groupBy($keyColumn);
        $gformGroups = $gformRows->filter(fn (KartuPelajarRow $r) => $r->{$keyColumn} !== null)->groupBy($keyColumn);

        foreach ($excelGroups as $key => $excelGroup) {
            if (! $gformGroups->has($key)) {
                continue;
            }

            $gformGroup = $gformGroups->get($key);

            if ($excelGroup->count() > 1 || $gformGroup->count() > 1) {
                $excelGroup->each(fn (KartuPelajarRow $r) => $r->update(['matching_status' => 'conflict']));
                $gformGroup->each(fn (KartuPelajarRow $r) => $r->update(['matching_status' => 'conflict']));

                continue;
            }

            $excelRow = $excelGroup->first();
            $gformRow = $gformGroup->first();

            $status = 'auto_matched';

            if ($crosscheckName) {
                $similarity = NameSimilarity::score($excelRow->nama ?? '', $gformRow->nama ?? '');

                if ($similarity < $this->nisnCrosscheckNameThreshold) {
                    $status = 'conflict';
                }
            }

            $excelRow->update(['matching_status' => $status, 'matched_via' => $matchedVia, 'matched_row_id' => $gformRow->id]);
            $gformRow->update(['matching_status' => $status, 'matched_via' => $matchedVia, 'matched_row_id' => $excelRow->id]);
        }
    }

    private function matchByFuzzyName(Collection $excelRows, Collection $gformRows): void
    {
        $usedGformIds = [];

        foreach ($excelRows as $excelRow) {
            if ($excelRow->nama === null || $excelRow->tanggal_lahir === null || $excelRow->kelas === null) {
                continue;
            }

            $candidates = $gformRows->filter(function (KartuPelajarRow $g) use ($excelRow, $usedGformIds) {
                return ! in_array($g->id, $usedGformIds, true)
                    && $g->nama !== null
                    && $g->tanggal_lahir !== null
                    && $g->kelas === $excelRow->kelas
                    && $g->tanggal_lahir->toDateString() === $excelRow->tanggal_lahir->toDateString();
            });

            if ($candidates->isEmpty()) {
                continue;
            }

            $scored = $candidates
                ->map(fn (KartuPelajarRow $g) => ['row' => $g, 'score' => NameSimilarity::score($excelRow->nama, $g->nama)])
                ->sortByDesc('score')
                ->values();

            $best = $scored->first();

            if ($best['score'] < $this->fuzzyNameThreshold) {
                continue;
            }

            // Kalau kandidat ke-2 skornya nyaris sama, terlalu ambigu untuk disarankan otomatis.
            if ($scored->count() > 1 && ($best['score'] - $scored[1]['score']) < 0.05) {
                continue;
            }

            $gformRow = $best['row'];
            $status = $best['score'] >= $this->fuzzyAutoMatchThreshold ? 'auto_matched' : 'fuzzy_candidate';

            $excelRow->update(['matching_status' => $status, 'matched_via' => 'fuzzy', 'matched_row_id' => $gformRow->id]);
            $gformRow->update(['matching_status' => $status, 'matched_via' => 'fuzzy', 'matched_row_id' => $excelRow->id]);

            $usedGformIds[] = $gformRow->id;
        }
    }
}

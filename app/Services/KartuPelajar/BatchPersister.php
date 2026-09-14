<?php

namespace App\Services\KartuPelajar;

use App\Models\KartuPelajarBatch;
use App\Models\KartuPelajarRow;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan hasil audit (RowAuditResult dari Prompt 1) sebagai satu batch + baris-baris
 * staging permanen di database, lengkap dengan kandidat kunci pencocokan (MatchingKeyBuilder).
 * TIDAK melakukan pencocokan apa pun di sini — setiap baris disimpan dengan
 * matching_status = 'unprocessed', siap diproses mesin matching di Prompt 3.
 */
class BatchPersister
{
    public function __construct(
        private readonly MatchingKeyBuilder $keyBuilder = new MatchingKeyBuilder,
    ) {}

    /**
     * @param  list<RowAuditResult>  $excelResults
     * @param  list<RowAuditResult>  $gformResults
     */
    public function persist(
        array $excelResults,
        array $gformResults,
        ?string $excelFileName = null,
        ?string $gformFileName = null,
    ): KartuPelajarBatch {
        return DB::transaction(function () use ($excelResults, $gformResults, $excelFileName, $gformFileName) {
            $batch = KartuPelajarBatch::create([
                'sumber_file_excel' => $excelFileName,
                'sumber_file_gform' => $gformFileName,
            ]);

            foreach ($excelResults as $result) {
                $this->persistRow($batch, $result);
            }

            foreach ($gformResults as $result) {
                $this->persistRow($batch, $result);
            }

            return $batch;
        });
    }

    private function persistRow(KartuPelajarBatch $batch, RowAuditResult $result): KartuPelajarRow
    {
        $keys = $this->keyBuilder->build($result);

        return $batch->rows()->create([
            'source' => $result->source,
            'source_row_number' => $result->rowNumber,
            'status' => $result->status,
            'reasons' => $result->reasons,
            'nama' => $result->fieldValue('nama'),
            'kelas' => $result->fieldValue('kelas'),
            'tanggal_lahir' => $result->fieldValue('tanggal_lahir'),
            'tempat_lahir' => $result->tempatLahir,
            'nis' => $result->fieldValue('nis'),
            'nisn' => $result->fieldValue('nisn'),
            'alamat' => $result->fieldValue('alamat'),
            'agama' => $result->agama,
            'jenis_kelamin' => $result->jenisKelamin,
            'matching_key_nisn' => $keys['nisn'],
            'matching_key_composite' => $keys['composite'],
            'matching_status' => 'unprocessed',
        ]);
    }
}

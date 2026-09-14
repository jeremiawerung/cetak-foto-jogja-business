<?php

namespace Tests\Feature;

use App\Models\KartuPelajarRow;
use App\Services\KartuPelajar\BatchPersister;
use App\Services\KartuPelajar\FieldResult;
use App\Services\KartuPelajar\RowAuditResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KartuPelajarBatchPersisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_persists_batch_and_rows_with_matching_keys(): void
    {
        $excelResults = [
            new RowAuditResult('excel', 3, [
                'nama' => FieldResult::clean('Adinda Nindya Kirana'),
                'kelas' => FieldResult::clean('7A'),
                'tanggal_lahir' => FieldResult::clean('2013-09-01'),
                'nis' => FieldResult::clean('11454'),
                'nisn' => FieldResult::clean('0133204197'),
                'alamat' => FieldResult::clean('Jl Contoh No 1'),
            ], 'clean', []),
        ];

        $gformResults = [
            new RowAuditResult('gform', 1, [
                'nama' => FieldResult::clean('Belfa Lia Abdul Gafar'),
                'kelas' => FieldResult::clean('7C'),
                'tanggal_lahir' => FieldResult::clean('2013-04-04'),
                'nisn' => FieldResult::invalid(null, 'NISN kosong'),
                'alamat' => FieldResult::clean('Jl Contoh No 2'),
            ], 'needs_manual_review', ['NISN kosong']),
        ];

        $batch = (new BatchPersister)->persist($excelResults, $gformResults, 'master.xlsx', 'gform.pdf');

        $this->assertDatabaseHas('kartu_pelajar_batches', [
            'id' => $batch->id,
            'sumber_file_excel' => 'master.xlsx',
            'sumber_file_gform' => 'gform.pdf',
        ]);

        $this->assertSame(2, KartuPelajarRow::where('batch_id', $batch->id)->count());

        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'excel')->first();
        $this->assertSame('0133204197', $excelRow->matching_key_nisn);
        $this->assertSame('ADINDA NINDYA KIRANA|2013-09-01|7A', $excelRow->matching_key_composite);
        $this->assertSame('unprocessed', $excelRow->matching_status);
        $this->assertSame('KP-'.str_pad((string) $excelRow->id, 6, '0', STR_PAD_LEFT), $excelRow->idFile());

        $gformRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'gform')->first();
        $this->assertNull($gformRow->matching_key_nisn);
        $this->assertSame('BELFA LIA ABDUL GAFAR|2013-04-04|7C', $gformRow->matching_key_composite);
        $this->assertSame('needs_manual_review', $gformRow->status);
        $this->assertSame(['NISN kosong'], $gformRow->reasons);
    }
}

<?php

namespace Tests\Feature;

use App\Models\VerifikasiSiswa;
use App\Models\VerifikasiSiswaFormResponse;
use App\Models\VerifikasiSiswaProyek;
use App\Services\VerifikasiSiswa\GFormSyncer;
use App\Services\VerifikasiSiswa\GoogleSheetsClient;
use App\Services\VerifikasiSiswa\ProyekFieldSeeder;
use App\Services\VerifikasiSiswa\Readers\GFormSheetReader;
use App\Services\VerifikasiSiswa\SiswaMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class VerifikasiSiswaSyncTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER_ROW = ['Timestamp', 'Kelas', 'Nama', 'Tempat Tanggal Lahir', 'Agama', 'Alamat', 'Jenis Kelamin', 'NIS', 'NISN'];

    private function makeSyncer(GoogleSheetsClient $client): GFormSyncer
    {
        return new GFormSyncer(new GFormSheetReader($client), new SiswaMatcher);
    }

    private function makeProyek(array $overrides = []): VerifikasiSiswaProyek
    {
        $proyek = VerifikasiSiswaProyek::create(array_merge(['nama' => 'Sekolah Uji'], $overrides));
        (new ProyekFieldSeeder)->seedDefaults($proyek);

        return $proyek;
    }

    public function test_sync_fetches_new_rows_and_advances_last_synced_row(): void
    {
        $proyek = $this->makeProyek([
            'google_sheet_id' => 'sheet-123',
            'last_synced_row' => 1,
        ]);

        $siswa = VerifikasiSiswa::create(['proyek_id' => $proyek->id, 'kelas' => '7A', 'nama' => 'Siswa Satu', 'nisn' => '1000000001', 'status' => 'belum_isi']);

        // Format persis seperti Google Form asli: kelas romawi ("VII A") & 1 kolom
        // gabungan tempat+tanggal lahir ("Yogyakarta,1 September 2013").
        $client = Mockery::mock(GoogleSheetsClient::class);
        $client->shouldReceive('firstSheetTitle')->with('sheet-123')->andReturn('Form Responses 1');
        $client->shouldReceive('fetchRows')->with('sheet-123', "'Form Responses 1'!1:1")->andReturn([self::HEADER_ROW]);
        $client->shouldReceive('fetchRows')->with('sheet-123', "'Form Responses 1'!A2:Z")->andReturn([
            ['11/09/2026 10:00:00', 'VII A', 'Siswa Satu', 'Yogyakarta,1 September 2013', 'Islam', 'Jl Contoh', 'L', '', '1000000001'],
            ['11/09/2026 10:05:00', 'VII B', 'Siswa Dua', 'Sleman,2 Februari 2013', 'Kristen', 'Jl Lain', 'P', '', '2000000002'],
        ]);

        $result = $this->makeSyncer($client)->sync($proyek);

        $this->assertSame(2, $result['jumlah_baru']);
        $this->assertSame(3, $proyek->refresh()->last_synced_row);
        $this->assertSame(2, VerifikasiSiswaFormResponse::where('proyek_id', $proyek->id)->count());

        $siswa->refresh();
        $this->assertSame('terisi', $siswa->status);
        $this->assertSame('Yogyakarta', $siswa->data['tempat_lahir'] ?? null);
        $this->assertSame('2013-09-01', $siswa->data['tanggal_lahir'] ?? null);

        $response = VerifikasiSiswaFormResponse::where('nisn', '1000000001')->first();
        $this->assertSame('7A', $response->kelas);
    }

    public function test_sync_twice_does_not_reprocess_same_rows(): void
    {
        $proyek = $this->makeProyek([
            'google_sheet_id' => 'sheet-123',
            'last_synced_row' => 1,
        ]);

        $client = Mockery::mock(GoogleSheetsClient::class);
        $client->shouldReceive('firstSheetTitle')->andReturn('Form Responses 1');
        $client->shouldReceive('fetchRows')->with('sheet-123', "'Form Responses 1'!1:1")->andReturn([self::HEADER_ROW]);
        $client->shouldReceive('fetchRows')->once()->with('sheet-123', "'Form Responses 1'!A2:Z")->andReturn([
            ['11/09/2026 10:00:00', '7A', 'Siswa Satu', '2013-01-01', 'Islam', 'Jl Contoh', 'L', '', '1000000001'],
        ]);
        $client->shouldReceive('fetchRows')->once()->with('sheet-123', "'Form Responses 1'!A3:Z")->andReturn([]);

        $syncer = $this->makeSyncer($client);
        $first = $syncer->sync($proyek);
        $second = $syncer->sync($proyek->refresh());

        $this->assertSame(1, $first['jumlah_baru']);
        $this->assertSame(0, $second['jumlah_baru']);
        $this->assertSame(1, VerifikasiSiswaFormResponse::where('proyek_id', $proyek->id)->count());
    }

    public function test_sync_does_nothing_when_no_google_sheet_id(): void
    {
        $proyek = $this->makeProyek(['nama' => 'Sekolah Tanpa Sheet']);

        $client = Mockery::mock(GoogleSheetsClient::class);
        $client->shouldNotReceive('fetchRows');

        $result = $this->makeSyncer($client)->sync($proyek);

        $this->assertSame(0, $result['jumlah_baru']);
    }
}

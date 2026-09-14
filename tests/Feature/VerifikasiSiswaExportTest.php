<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerifikasiSiswa;
use App\Models\VerifikasiSiswaProyek;
use App\Services\VerifikasiSiswa\ProyekFieldSeeder;
use App\Services\VerifikasiSiswa\SiswaExportBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifikasiSiswaExportTest extends TestCase
{
    use RefreshDatabase;

    private function makeProyek(): VerifikasiSiswaProyek
    {
        $proyek = VerifikasiSiswaProyek::create(['nama' => 'Sekolah Uji']);
        (new ProyekFieldSeeder)->seedDefaults($proyek);

        return $proyek;
    }

    public function test_csv_includes_all_students_in_kelas_regardless_of_status(): void
    {
        $proyek = $this->makeProyek();

        $lengkap = VerifikasiSiswa::create([
            'proyek_id' => $proyek->id, 'kelas' => '7A', 'nama' => 'Siswa Lengkap',
            'nis' => '100', 'nisn' => '1000000001', 'status' => 'terisi',
            'data' => ['jenis_kelamin' => 'L', 'agama' => 'Islam', 'tempat_lahir' => 'Yogyakarta', 'tanggal_lahir' => '2013-09-01', 'alamat' => 'Jl Contoh'],
        ]);

        $belumIsi = VerifikasiSiswa::create([
            'proyek_id' => $proyek->id, 'kelas' => '7A', 'nama' => 'Siswa Belum Isi',
            'nis' => '101', 'nisn' => '1000000002', 'status' => 'belum_isi',
        ]);

        // Kelas beda, tidak boleh ikut kena ekspor kelas 7A.
        VerifikasiSiswa::create(['proyek_id' => $proyek->id, 'kelas' => '7B', 'nama' => 'Siswa Kelas Lain', 'status' => 'belum_isi']);

        $csv = (new SiswaExportBuilder)->toCsv($proyek, '7A');

        $this->assertStringContainsString('NAMA,TTL,ALAMAT,JK,AGAMA,NIS,NISN,ID_FILE', $csv);
        $this->assertStringContainsString('Siswa Lengkap', $csv);
        $this->assertStringContainsString('Yogyakarta 01 September 2013', $csv);
        $this->assertStringContainsString('Jl Contoh', $csv);
        $this->assertStringContainsString($lengkap->idFile(), $csv);
        $this->assertStringContainsString('Siswa Belum Isi', $csv);
        $this->assertStringContainsString($belumIsi->idFile(), $csv);
        $this->assertStringNotContainsString('Siswa Kelas Lain', $csv);
    }

    public function test_ttl_combines_tempat_and_formatted_tanggal(): void
    {
        $proyek = $this->makeProyek();
        VerifikasiSiswa::create([
            'proyek_id' => $proyek->id, 'kelas' => '7A', 'nama' => 'Siswa Satu', 'status' => 'terisi',
            'data' => ['tempat_lahir' => 'Sleman', 'tanggal_lahir' => '2014-01-25'],
        ]);

        $csv = (new SiswaExportBuilder)->toCsv($proyek, '7A');

        $this->assertStringContainsString('Sleman 25 Januari 2014', $csv);
    }

    public function test_export_columns_can_be_customized_per_proyek(): void
    {
        $proyek = $this->makeProyek();
        // Sekolah ini tidak mau kolom Agama di kartu -- hapus dari template ekspor.
        $proyek->exportColumns()->where('field_key', 'agama')->delete();

        VerifikasiSiswa::create([
            'proyek_id' => $proyek->id, 'kelas' => '7A', 'nama' => 'Siswa Satu', 'status' => 'terisi',
            'data' => ['agama' => 'Islam'],
        ]);

        $csv = (new SiswaExportBuilder)->toCsv($proyek, '7A');

        $this->assertStringNotContainsString('AGAMA', $csv);
        $this->assertStringNotContainsString('Islam', $csv);
    }

    public function test_download_route_returns_csv_with_correct_headers_and_logs_activity(): void
    {
        $proyek = $this->makeProyek();
        VerifikasiSiswa::create(['proyek_id' => $proyek->id, 'kelas' => '7A', 'nama' => 'Siswa Satu', 'status' => 'belum_isi']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('internal.verifikasi-siswa.ekspor', [$proyek, '7A']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Siswa Satu', $response->getContent());

        $this->assertDatabaseHas('verifikasi_siswa_activity_logs', [
            'proyek_id' => $proyek->id,
            'action' => 'export_downloaded',
        ]);
    }

    public function test_guest_cannot_download_export(): void
    {
        $proyek = $this->makeProyek();

        $this->get(route('internal.verifikasi-siswa.ekspor', [$proyek, '7A']))->assertRedirect(route('internal.login'));
    }
}

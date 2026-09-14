<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerifikasiSiswa;
use App\Models\VerifikasiSiswaProyek;
use App\Services\VerifikasiSiswa\ProyekFieldSeeder;
use App\Services\VerifikasiSiswa\Readers\RosterPdfReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VerifikasiSiswaRosterUploadTest extends TestCase
{
    use RefreshDatabase;

    private const SAMPLE_PDF = 'Sample/SMPN 15 YOGYAKARTA/DATA/profil kartu pelajar 2026.pdf';

    private function makeProyek(string $nama = 'Sekolah Uji'): VerifikasiSiswaProyek
    {
        $proyek = VerifikasiSiswaProyek::create(['nama' => $nama]);
        (new ProyekFieldSeeder)->seedDefaults($proyek);

        return $proyek;
    }

    public function test_upload_review_and_save_flow_persists_all_rows_from_real_roster(): void
    {
        $path = base_path(self::SAMPLE_PDF);

        if (! is_file($path)) {
            $this->markTestSkipped('File sample roster PDF tidak ditemukan.');
        }

        $proyek = $this->makeProyek('SMPN 15 Yogyakarta Uji');
        $user = User::factory()->create();

        $file = new UploadedFile($path, 'roster.pdf', 'application/pdf', null, true);

        $uploadResponse = $this->actingAs($user)->post(
            route('internal.verifikasi-siswa.roster.upload', $proyek),
            ['file' => $file],
        );

        $uploadResponse->assertRedirect(route('internal.verifikasi-siswa.roster.tinjau', $proyek));

        $tinjauResponse = $this->actingAs($user)->get(route('internal.verifikasi-siswa.roster.tinjau', $proyek));
        $tinjauResponse->assertOk();
        $tinjauResponse->assertViewHas('rows', fn ($rows) => count($rows) === 319);

        // Simulasikan hasil parse langsung (bukan lewat JS di browser) untuk uji "simpan".
        $rows = (new RosterPdfReader)->read($path)['rows'];

        $simpanResponse = $this->actingAs($user)->post(
            route('internal.verifikasi-siswa.roster.simpan', $proyek),
            ['data' => json_encode($rows)],
        );

        $simpanResponse->assertRedirect(route('internal.verifikasi-siswa.show', $proyek));

        $this->assertSame(319, VerifikasiSiswa::where('proyek_id', $proyek->id)->count());

        $abizar = VerifikasiSiswa::where('proyek_id', $proyek->id)->where('nis', '11453')->first();
        $this->assertNotNull($abizar);
        $this->assertSame('ABIZAR RIZQI ALGHIFAHRI', $abizar->nama);
        $this->assertSame('7A', $abizar->kelas);
        $this->assertSame('0137272203', $abizar->nisn);
        $this->assertSame('SURABAYA', $abizar->data['tempat_lahir'] ?? null);
    }

    /**
     * simpan() cuma menerima request kalau sesi tinjau (dari upload sebelumnya) masih ada
     * — jadi tes ini "titipkan" sesi itu manual, sama seperti yang controller upload()
     * lakukan, tanpa perlu upload file sungguhan lagi.
     */
    private function withRosterReviewSession(VerifikasiSiswaProyek $proyek, array $rows): self
    {
        return $this->withSession([
            "verifikasi_siswa.roster_review.{$proyek->id}" => ['rows' => $rows, 'warnings' => []],
        ]);
    }

    public function test_reupload_matches_existing_siswa_by_nisn_instead_of_duplicating(): void
    {
        $proyek = $this->makeProyek();
        $user = User::factory()->create();

        $rows = [[
            'nama' => 'Siswa Satu', 'kelas' => '7A', 'jenis_kelamin' => 'L',
            'nis' => '100', 'nisn' => '1000000001', 'tempat_lahir' => 'Yogyakarta',
            'tanggal_lahir' => null, 'alamat' => null, 'agama' => null,
        ]];

        $this->withRosterReviewSession($proyek, $rows)
            ->actingAs($user)
            ->post(route('internal.verifikasi-siswa.roster.simpan', $proyek), ['data' => json_encode($rows)]);

        $this->assertSame(1, VerifikasiSiswa::where('proyek_id', $proyek->id)->count());

        // Upload ulang file yang sama (mis. tidak sengaja) - jangan sampai jadi baris baru.
        $this->withRosterReviewSession($proyek, $rows)
            ->actingAs($user)
            ->post(route('internal.verifikasi-siswa.roster.simpan', $proyek), ['data' => json_encode($rows)]);

        $this->assertSame(1, VerifikasiSiswa::where('proyek_id', $proyek->id)->count());
    }

    /**
     * Keputusan eksplisit: upload roster kedua kali TIDAK boleh menimpa field yang
     * sudah terisi (baik dari roster pertama maupun dari hasil gabungan Google Form) —
     * cuma field yang masih kosong yang boleh diisi. Ini supaya upload ulang yang tidak
     * sengaja tidak menghapus data yang sudah lebih baik (lihat insiden nyata: alamat
     * hasil gabungan Google Form sempat tertimpa balik ke versi pendek roster).
     */
    public function test_reupload_does_not_overwrite_already_filled_fields(): void
    {
        $proyek = $this->makeProyek();
        $user = User::factory()->create();

        $siswa = VerifikasiSiswa::create([
            'proyek_id' => $proyek->id, 'nama' => 'Siswa Satu', 'kelas' => '7A',
            'nis' => '100', 'nisn' => '1000000001', 'status' => 'terisi',
            'data' => ['alamat' => 'Alamat Lengkap Dari Form'],
        ]);

        $rows = [[
            'nama' => 'Siswa Satu', 'kelas' => '7B', 'jenis_kelamin' => 'L',
            'nis' => '100', 'nisn' => '1000000001', 'tempat_lahir' => 'Yogyakarta',
            'alamat' => 'Alamat Pendek Hasil Parsing Roster Baru', 'agama' => 'Islam',
        ]];

        $this->withRosterReviewSession($proyek, $rows)
            ->actingAs($user)
            ->post(route('internal.verifikasi-siswa.roster.simpan', $proyek), ['data' => json_encode($rows)]);

        $siswa->refresh();

        // Sudah ada isinya sebelum upload ulang -> tidak berubah.
        $this->assertSame('7A', $siswa->kelas);
        $this->assertSame('Alamat Lengkap Dari Form', $siswa->data['alamat'] ?? null);
        // Masih kosong sebelum upload ulang -> boleh diisi.
        $this->assertSame('Islam', $siswa->data['agama'] ?? null);
        $this->assertSame('Yogyakarta', $siswa->data['tempat_lahir'] ?? null);
    }

    public function test_reupload_does_not_overwrite_locked_siswa(): void
    {
        $proyek = $this->makeProyek();
        $user = User::factory()->create();

        $siswa = VerifikasiSiswa::create([
            'proyek_id' => $proyek->id, 'nama' => 'Siswa Satu', 'kelas' => '7A',
            'nis' => '100', 'nisn' => '1000000001', 'is_locked' => true, 'status' => 'terisi',
        ]);

        $rows = [[
            'nama' => 'Siswa Satu', 'kelas' => '7Z', 'jenis_kelamin' => 'L',
            'nis' => '100', 'nisn' => '1000000001',
        ]];

        $this->withRosterReviewSession($proyek, $rows)
            ->actingAs($user)
            ->post(route('internal.verifikasi-siswa.roster.simpan', $proyek), ['data' => json_encode($rows)]);

        $this->assertSame('7A', $siswa->refresh()->kelas);
    }
}

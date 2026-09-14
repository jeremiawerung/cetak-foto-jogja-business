<?php

namespace Tests\Feature;

use App\Models\VerifikasiSiswa;
use App\Models\VerifikasiSiswaFormResponse;
use App\Models\VerifikasiSiswaProyek;
use App\Services\VerifikasiSiswa\ProyekFieldSeeder;
use App\Services\VerifikasiSiswa\SiswaMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifikasiSiswaMatcherTest extends TestCase
{
    use RefreshDatabase;

    private function makeProyek(): VerifikasiSiswaProyek
    {
        $proyek = VerifikasiSiswaProyek::create(['nama' => 'Sekolah Uji']);
        (new ProyekFieldSeeder)->seedDefaults($proyek);

        return $proyek;
    }

    private function makeSiswa(VerifikasiSiswaProyek $proyek, array $data = [], array $overrides = []): VerifikasiSiswa
    {
        return VerifikasiSiswa::create(array_merge([
            'proyek_id' => $proyek->id,
            'kelas' => '7A',
            'nama' => 'Siswa Satu',
            'nis' => '100',
            'nisn' => '1000000001',
            'status' => 'belum_isi',
            'data' => array_merge(['jenis_kelamin' => 'L', 'tempat_lahir' => 'Yogyakarta'], $data),
        ], $overrides));
    }

    private function makeResponse(VerifikasiSiswaProyek $proyek, array $data = [], array $overrides = []): VerifikasiSiswaFormResponse
    {
        return VerifikasiSiswaFormResponse::create(array_merge([
            'proyek_id' => $proyek->id,
            'sheet_row_number' => 2,
            'kelas' => '7A',
            'nama' => 'Siswa Satu',
            'nis' => '100',
            'nisn' => '1000000001',
            'status' => 'unmatched',
            'data' => array_merge(['tanggal_lahir' => '2013-01-01', 'alamat' => 'Jl Contoh', 'agama' => 'Islam', 'jenis_kelamin' => 'L'], $data),
        ], $overrides));
    }

    public function test_exact_nisn_match_fills_blank_fields_on_roster(): void
    {
        $proyek = $this->makeProyek();
        $siswa = $this->makeSiswa($proyek);
        $response = $this->makeResponse($proyek);

        $summary = (new SiswaMatcher)->processNewResponses($proyek);

        $this->assertSame(['matched' => 1], $summary);

        $siswa->refresh();
        $response->refresh();

        $this->assertSame('terisi', $siswa->status);
        $this->assertSame('2013-01-01', $siswa->data['tanggal_lahir']);
        $this->assertSame('Jl Contoh', $siswa->data['alamat']);
        $this->assertSame($response->id, $siswa->matched_form_response_id);
        $this->assertSame('matched', $response->status);
    }

    public function test_existing_roster_value_is_kept_and_difference_recorded_as_discrepancy(): void
    {
        $proyek = $this->makeProyek();
        $siswa = $this->makeSiswa($proyek, ['agama' => 'Islam']);
        $response = $this->makeResponse($proyek, ['agama' => 'Kristen']);

        (new SiswaMatcher)->processNewResponses($proyek);

        $siswa->refresh();
        $response->refresh();

        $this->assertSame('Islam', $siswa->data['agama']);
        $this->assertSame(['agama'], $response->discrepancies);
        $this->assertSame('matched', $response->status);
    }

    public function test_alamat_always_taken_from_form_not_roster(): void
    {
        // Pengecualian yang diminta user: alamat dari roster (hasil parsing PDF) sering
        // cuma sebagian, jadi alamat dari Google Form selalu dipakai — bukan cuma
        // mengisi yang kosong seperti field lain, dan tidak dicatat sebagai discrepancy.
        $proyek = $this->makeProyek();
        $siswa = $this->makeSiswa($proyek, ['alamat' => 'Alamat Pendek Dari Roster']);
        $response = $this->makeResponse($proyek, ['alamat' => 'Alamat Lengkap Dari Google Form']);

        (new SiswaMatcher)->processNewResponses($proyek);

        $siswa->refresh();
        $response->refresh();

        $this->assertSame('Alamat Lengkap Dari Google Form', $siswa->data['alamat']);
        $this->assertSame([], $response->discrepancies);
    }

    public function test_alamat_keeps_roster_value_when_form_alamat_is_blank(): void
    {
        $proyek = $this->makeProyek();
        $siswa = $this->makeSiswa($proyek, ['alamat' => 'Alamat Dari Roster']);
        $response = $this->makeResponse($proyek, ['alamat' => null]);

        (new SiswaMatcher)->processNewResponses($proyek);

        $this->assertSame('Alamat Dari Roster', $siswa->refresh()->data['alamat']);
    }

    public function test_same_nisn_but_very_different_name_becomes_conflict(): void
    {
        $proyek = $this->makeProyek();
        $siswa = $this->makeSiswa($proyek);
        $response = $this->makeResponse($proyek, [], ['nama' => 'Zzz Qqq Wwww Beda Sama Sekali']);

        $summary = (new SiswaMatcher)->processNewResponses($proyek);

        $this->assertSame(['conflict' => 1], $summary);

        $siswa->refresh();
        $response->refresh();

        $this->assertSame('belum_isi', $siswa->status);
        $this->assertSame('conflict', $response->status);
        $this->assertSame($siswa->id, $response->matched_siswa_id);
    }

    public function test_nisn_not_found_falls_back_to_nis(): void
    {
        $proyek = $this->makeProyek();
        $siswa = $this->makeSiswa($proyek, [], ['nisn' => null]);
        $response = $this->makeResponse($proyek, [], ['nisn' => null]);

        $summary = (new SiswaMatcher)->processNewResponses($proyek);

        $this->assertSame(['matched' => 1], $summary);
        $this->assertSame('terisi', $siswa->refresh()->status);
    }

    public function test_no_identifier_match_falls_back_to_fuzzy_name_within_same_kelas(): void
    {
        $proyek = $this->makeProyek();
        $siswa = $this->makeSiswa($proyek, [], ['nama' => 'Muhammad Fadillah Rahman', 'nis' => null, 'nisn' => null]);
        $response = $this->makeResponse($proyek, [], ['nama' => 'Muhamad Fadilah Rahman', 'nis' => null, 'nisn' => null]);

        $summary = (new SiswaMatcher)->processNewResponses($proyek);

        $this->assertSame(['matched' => 1], $summary);
        $this->assertSame('terisi', $siswa->refresh()->status);
    }

    public function test_completely_unrecognized_response_becomes_conflict_without_candidate(): void
    {
        $proyek = $this->makeProyek();
        $this->makeSiswa($proyek);
        $response = $this->makeResponse($proyek, [], [
            'nama' => 'Anak Sekolah Lain',
            'kelas' => '9Z',
            'nis' => '99999',
            'nisn' => '9999999999',
        ]);

        $summary = (new SiswaMatcher)->processNewResponses($proyek);

        $this->assertSame(['conflict' => 1], $summary);
        $this->assertNull($response->refresh()->matched_siswa_id);
    }

    public function test_locked_siswa_is_not_overwritten_and_response_flagged_conflict(): void
    {
        $proyek = $this->makeProyek();
        $siswa = $this->makeSiswa($proyek, ['alamat' => 'Alamat Terkunci'], ['is_locked' => true]);
        $response = $this->makeResponse($proyek, ['alamat' => 'Alamat Baru Telat']);

        $summary = (new SiswaMatcher)->processNewResponses($proyek);

        $this->assertSame(['conflict' => 1], $summary);

        $siswa->refresh();
        $response->refresh();

        $this->assertSame('Alamat Terkunci', $siswa->data['alamat']);
        $this->assertSame('conflict', $response->status);
        $this->assertSame($siswa->id, $response->matched_siswa_id);
    }
}

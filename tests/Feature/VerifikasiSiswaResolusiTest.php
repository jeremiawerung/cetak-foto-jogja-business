<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerifikasiSiswa;
use App\Models\VerifikasiSiswaFormResponse;
use App\Models\VerifikasiSiswaProyek;
use App\Services\VerifikasiSiswa\ProyekFieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifikasiSiswaResolusiTest extends TestCase
{
    use RefreshDatabase;

    private function makeProyek(): VerifikasiSiswaProyek
    {
        $proyek = VerifikasiSiswaProyek::create(['nama' => 'Sekolah Uji']);
        (new ProyekFieldSeeder)->seedDefaults($proyek);

        return $proyek;
    }

    private function makeProyekWithConflict(): array
    {
        $proyek = $this->makeProyek();

        $siswa = VerifikasiSiswa::create([
            'proyek_id' => $proyek->id,
            'kelas' => '7A',
            'nama' => 'Siswa Satu',
            'nis' => '100',
            'nisn' => '1000000001',
            'status' => 'belum_isi',
        ]);

        $response = VerifikasiSiswaFormResponse::create([
            'proyek_id' => $proyek->id,
            'sheet_row_number' => 2,
            'kelas' => '7A',
            'nama' => 'Siswa Satu Beda Jauh Namanya',
            'nis' => '100',
            'nisn' => '1000000001',
            'status' => 'conflict',
            'matched_siswa_id' => $siswa->id,
        ]);

        return [$proyek, $siswa, $response];
    }

    public function test_guest_cannot_access_verifikasi_siswa_pages(): void
    {
        $proyek = $this->makeProyek();

        $this->get(route('internal.verifikasi-siswa.show', $proyek))->assertRedirect(route('internal.login'));
    }

    public function test_confirm_merges_response_into_siswa(): void
    {
        [$proyek, $siswa, $response] = $this->makeProyekWithConflict();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('internal.verifikasi-siswa.respons.confirm', [$proyek, $response]))
            ->assertRedirect();

        $this->assertSame('terisi', $siswa->refresh()->status);
        $this->assertSame('matched', $response->refresh()->status);
    }

    public function test_reject_returns_response_to_unmatched(): void
    {
        [$proyek, $siswa, $response] = $this->makeProyekWithConflict();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('internal.verifikasi-siswa.respons.reject', [$proyek, $response]))
            ->assertRedirect();

        $this->assertSame('unmatched', $response->refresh()->status);
        $this->assertNull($response->matched_siswa_id);
        $this->assertSame('belum_isi', $siswa->refresh()->status);
    }

    public function test_link_manually_connects_response_to_chosen_siswa(): void
    {
        $proyek = $this->makeProyek();
        $siswa = VerifikasiSiswa::create(['proyek_id' => $proyek->id, 'nama' => 'Siswa Tujuan', 'status' => 'belum_isi']);
        $response = VerifikasiSiswaFormResponse::create([
            'proyek_id' => $proyek->id, 'sheet_row_number' => 3, 'nama' => 'Nama Tak Cocok Sama Sekali', 'status' => 'conflict',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('internal.verifikasi-siswa.respons.link', [$proyek, $response]), ['siswa_id' => $siswa->id])
            ->assertRedirect();

        $this->assertSame('matched', $response->refresh()->status);
        $this->assertSame($siswa->id, $response->matched_siswa_id);
        $this->assertSame('terisi', $siswa->refresh()->status);
    }

    public function test_lock_prevents_confirm_from_changing_siswa(): void
    {
        [$proyek, $siswa, $response] = $this->makeProyekWithConflict();
        $siswa->update(['is_locked' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('internal.verifikasi-siswa.respons.confirm', [$proyek, $response]))
            ->assertSessionHasErrors('resolusi');

        $this->assertSame('belum_isi', $siswa->refresh()->status);
    }

    public function test_unlock_allows_confirm_again(): void
    {
        [$proyek, $siswa, $response] = $this->makeProyekWithConflict();
        $siswa->update(['is_locked' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('internal.verifikasi-siswa.siswa.unlock', [$proyek, $siswa]))->assertRedirect();
        $this->assertFalse($siswa->refresh()->is_locked);

        $this->actingAs($user)
            ->post(route('internal.verifikasi-siswa.respons.confirm', [$proyek, $response]))
            ->assertRedirect();

        $this->assertSame('terisi', $siswa->refresh()->status);
    }

    public function test_bulk_resolve_processes_multiple_responses_in_one_submit(): void
    {
        $proyek = $this->makeProyek();

        $siswaA = VerifikasiSiswa::create(['proyek_id' => $proyek->id, 'nama' => 'Siswa A', 'status' => 'belum_isi']);
        $responseA = VerifikasiSiswaFormResponse::create(['proyek_id' => $proyek->id, 'sheet_row_number' => 1, 'nama' => 'Siswa A Beda', 'status' => 'conflict', 'matched_siswa_id' => $siswaA->id]);

        $responseB = VerifikasiSiswaFormResponse::create(['proyek_id' => $proyek->id, 'sheet_row_number' => 2, 'nama' => 'Siswa B', 'status' => 'conflict']);

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('internal.verifikasi-siswa.respons.bulk-resolve', $proyek), [
            'action' => [
                $responseA->id => 'confirm',
                $responseB->id => 'reject',
            ],
        ])->assertRedirect();

        $this->assertSame('matched', $responseA->refresh()->status);
        $this->assertSame('unmatched', $responseB->refresh()->status);
    }

    private function makeMatchedWithDiscrepancy(array $siswaOverrides = [], array $responseOverrides = []): array
    {
        $proyek = $this->makeProyek();

        $siswa = VerifikasiSiswa::create(array_merge([
            'proyek_id' => $proyek->id, 'nama' => 'Siswa Satu', 'status' => 'terisi',
            'data' => ['agama' => 'Islam'],
        ], $siswaOverrides));

        $response = VerifikasiSiswaFormResponse::create(array_merge([
            'proyek_id' => $proyek->id, 'sheet_row_number' => 2, 'nama' => 'Siswa Satu',
            'data' => ['agama' => 'Kristen'],
            'status' => 'matched', 'matched_siswa_id' => $siswa->id, 'discrepancies' => ['agama'],
        ], $responseOverrides));

        return [$proyek, $siswa, $response];
    }

    public function test_bulk_resolve_discrepancy_using_form_value_overwrites_siswa(): void
    {
        [$proyek, $siswa, $response] = $this->makeMatchedWithDiscrepancy();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('internal.verifikasi-siswa.perbedaan.bulk-resolve', $proyek), [
            'resolusi' => [$response->id => ['agama' => 'form']],
        ])->assertRedirect();

        $this->assertSame('Kristen', $siswa->refresh()->data['agama'] ?? null);
        $this->assertSame([], $response->refresh()->discrepancies);
    }

    public function test_bulk_resolve_discrepancy_using_roster_value_keeps_siswa_unchanged(): void
    {
        [$proyek, $siswa, $response] = $this->makeMatchedWithDiscrepancy();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('internal.verifikasi-siswa.perbedaan.bulk-resolve', $proyek), [
            'resolusi' => [$response->id => ['agama' => 'roster']],
        ])->assertRedirect();

        $this->assertSame('Islam', $siswa->refresh()->data['agama'] ?? null);
        $this->assertSame([], $response->refresh()->discrepancies);
    }

    public function test_bulk_resolve_discrepancy_skips_fields_left_blank(): void
    {
        [$proyek, $siswa, $response] = $this->makeMatchedWithDiscrepancy();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('internal.verifikasi-siswa.perbedaan.bulk-resolve', $proyek), [
            'resolusi' => [$response->id => ['agama' => '']],
        ])->assertSessionHasErrors('resolusi');

        $this->assertSame('Islam', $siswa->refresh()->data['agama'] ?? null);
        $this->assertSame(['agama'], $response->refresh()->discrepancies);
    }

    public function test_bulk_resolve_discrepancy_blocked_when_siswa_locked(): void
    {
        [$proyek, $siswa, $response] = $this->makeMatchedWithDiscrepancy(['is_locked' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('internal.verifikasi-siswa.perbedaan.bulk-resolve', $proyek), [
            'resolusi' => [$response->id => ['agama' => 'form']],
        ])->assertSessionHasErrors('resolusi');

        $this->assertSame('Islam', $siswa->refresh()->data['agama'] ?? null);
        $this->assertSame(['agama'], $response->refresh()->discrepancies);
    }
}

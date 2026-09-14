<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerifikasiSiswaProyek;
use App\Services\VerifikasiSiswa\ProyekFieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifikasiSiswaFieldControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeProyek(): VerifikasiSiswaProyek
    {
        $proyek = VerifikasiSiswaProyek::create(['nama' => 'Sekolah Uji']);
        (new ProyekFieldSeeder)->seedDefaults($proyek);

        return $proyek;
    }

    public function test_guest_cannot_access_atur_field(): void
    {
        $proyek = $this->makeProyek();

        $this->get(route('internal.verifikasi-siswa.field.index', $proyek))->assertRedirect(route('internal.login'));
    }

    public function test_admin_can_add_custom_field(): void
    {
        $proyek = $this->makeProyek();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('internal.verifikasi-siswa.field.store', $proyek), [
            'key' => 'nomor_sekolah',
            'label' => 'Nomor Sekolah',
            'tipe' => 'text',
            'sumber_utama' => 'roster',
            'kata_kunci' => 'nomor sekolah, no sekolah',
        ])->assertRedirect();

        $field = $proyek->fieldDefinitions()->where('key', 'nomor_sekolah')->first();
        $this->assertNotNull($field);
        $this->assertSame('Nomor Sekolah', $field->label);
        $this->assertFalse($field->is_core);
        $this->assertSame([['nomor', 'sekolah'], ['no', 'sekolah']], $field->kata_kunci_header);
    }

    public function test_cannot_add_duplicate_field_key(): void
    {
        $proyek = $this->makeProyek();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('internal.verifikasi-siswa.field.store', $proyek), [
            'key' => 'agama', 'label' => 'Agama Lagi', 'tipe' => 'text', 'sumber_utama' => 'roster',
        ])->assertSessionHasErrors('key');
    }

    public function test_core_field_cannot_be_deleted(): void
    {
        $proyek = $this->makeProyek();
        $user = User::factory()->create();
        $nama = $proyek->fieldDefinitions()->where('key', 'nama')->first();

        $this->actingAs($user)->post(route('internal.verifikasi-siswa.field.destroy', [$proyek, $nama]))
            ->assertSessionHasErrors('field');

        $this->assertNotNull($proyek->fieldDefinitions()->where('key', 'nama')->first());
    }

    public function test_non_core_field_can_be_deleted(): void
    {
        $proyek = $this->makeProyek();
        $user = User::factory()->create();
        $agama = $proyek->fieldDefinitions()->where('key', 'agama')->first();

        $this->actingAs($user)->post(route('internal.verifikasi-siswa.field.destroy', [$proyek, $agama]))
            ->assertRedirect();

        $this->assertNull($proyek->fieldDefinitions()->where('key', 'agama')->first());
    }

    public function test_admin_can_add_export_column_for_custom_field(): void
    {
        $proyek = $this->makeProyek();
        $proyek->fieldDefinitions()->create([
            'key' => 'nomor_sekolah', 'label' => 'Nomor Sekolah', 'tipe' => 'text',
            'is_core' => false, 'sumber_utama' => 'roster', 'urutan' => 8,
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('internal.verifikasi-siswa.ekspor-kolom.store', $proyek), [
            'label' => 'NOMOR SEKOLAH', 'sumber_tipe' => 'field', 'field_key' => 'nomor_sekolah',
        ])->assertRedirect();

        $this->assertDatabaseHas('verifikasi_siswa_export_columns', [
            'proyek_id' => $proyek->id, 'label' => 'NOMOR SEKOLAH', 'field_key' => 'nomor_sekolah',
        ]);
    }

    public function test_export_column_can_be_deleted(): void
    {
        $proyek = $this->makeProyek();
        $user = User::factory()->create();
        $column = $proyek->exportColumns()->where('field_key', 'agama')->first();

        $this->actingAs($user)->post(route('internal.verifikasi-siswa.ekspor-kolom.destroy', [$proyek, $column]))
            ->assertRedirect();

        $this->assertDatabaseMissing('verifikasi_siswa_export_columns', ['id' => $column->id]);
    }
}

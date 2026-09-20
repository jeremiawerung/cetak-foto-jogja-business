<?php

namespace Tests\Feature;

use App\Models\PengaturanPembayaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PengaturanPembayaranControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_pengaturan_pembayaran(): void
    {
        $this->get(route('admin.pengaturan-pembayaran.edit'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_default_pengaturan_seeded_from_config(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.pengaturan-pembayaran.edit'))
            ->assertOk()
            ->assertSee(config('services.bank.nama_bank'));
    }

    public function test_admin_can_update_rekening_bank(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.pengaturan-pembayaran.update'), [
            'nama_bank' => 'Mandiri',
            'no_rekening' => '9988776655',
            'atas_nama' => 'Toko Baru',
        ])->assertRedirect();

        $pengaturan = PengaturanPembayaran::current();
        $this->assertSame('Mandiri', $pengaturan->nama_bank);
        $this->assertSame('9988776655', $pengaturan->no_rekening);
        $this->assertSame('Toko Baru', $pengaturan->atas_nama);
    }

    public function test_admin_can_upload_new_qris_image(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.pengaturan-pembayaran.update'), [
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama' => 'Cetak Foto Jogja',
            'qris_gambar' => UploadedFile::fake()->image('qris.jpg'),
        ])->assertRedirect();

        $pengaturan = PengaturanPembayaran::current();
        $this->assertNotNull($pengaturan->qris_gambar);
        $this->assertFileExists(public_path($pengaturan->qris_gambar));

        @unlink(public_path($pengaturan->qris_gambar));
    }

    public function test_update_requires_bank_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.pengaturan-pembayaran.update'), [])
            ->assertSessionHasErrors(['nama_bank', 'no_rekening', 'atas_nama']);
    }
}

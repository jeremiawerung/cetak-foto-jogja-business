<?php

namespace Tests\Feature;

use App\Models\KategoriProduk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class KategoriProdukControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_kategori_produk_pages(): void
    {
        $this->get(route('admin.kategori-produk.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_create_kategori_with_image(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.kategori-produk.store'), [
            'slug' => 'pigura_baru',
            'label' => 'Pigura Baru',
            'pricing_mode' => 'varian',
            'satuan_label' => 'pcs',
            'gambar' => UploadedFile::fake()->image('pigura.jpg'),
        ])->assertRedirect();

        $kategori = KategoriProduk::where('slug', 'pigura_baru')->first();
        $this->assertNotNull($kategori);
        $this->assertTrue($kategori->is_active);
        $this->assertNotNull($kategori->gambar);
        $this->assertFileExists(public_path($kategori->gambar));

        @unlink(public_path($kategori->gambar));
    }

    public function test_cannot_create_kategori_with_duplicate_slug(): void
    {
        $user = User::factory()->create();
        KategoriProduk::create(['slug' => 'sudah_ada', 'label' => 'A', 'pricing_mode' => 'varian', 'satuan_label' => 'pcs']);

        $this->actingAs($user)->post(route('admin.kategori-produk.store'), [
            'slug' => 'sudah_ada',
            'label' => 'B',
            'pricing_mode' => 'varian',
            'satuan_label' => 'pcs',
        ])->assertSessionHasErrors('slug');
    }

    public function test_admin_can_toggle_kategori_active_status(): void
    {
        $user = User::factory()->create();
        $kategori = KategoriProduk::create(['slug' => 'toggle_test', 'label' => 'Toggle', 'pricing_mode' => 'varian', 'satuan_label' => 'pcs', 'is_active' => true]);

        $this->actingAs($user)->post(route('admin.kategori-produk.toggle-active', $kategori))->assertRedirect();
        $this->assertFalse($kategori->fresh()->is_active);

        $this->actingAs($user)->post(route('admin.kategori-produk.toggle-active', $kategori))->assertRedirect();
        $this->assertTrue($kategori->fresh()->is_active);
    }

    public function test_admin_can_delete_kategori(): void
    {
        $user = User::factory()->create();
        $kategori = KategoriProduk::create(['slug' => 'hapus_test', 'label' => 'Hapus', 'pricing_mode' => 'varian', 'satuan_label' => 'pcs']);

        $this->actingAs($user)->post(route('admin.kategori-produk.destroy', $kategori))->assertRedirect();

        $this->assertNull(KategoriProduk::find($kategori->id));
    }

    public function test_admin_can_add_item_to_kategori(): void
    {
        $user = User::factory()->create();
        $kategori = KategoriProduk::create(['slug' => 'item_test', 'label' => 'Item Test', 'pricing_mode' => 'varian', 'satuan_label' => 'pcs']);

        $this->actingAs($user)->post(route('admin.kategori-produk.item.store', $kategori), [
            'nama' => 'Varian A',
            'slug' => 'varian_a',
            'harga' => 20000,
        ])->assertRedirect();

        $item = $kategori->items()->where('slug', 'varian_a')->first();
        $this->assertNotNull($item);
        $this->assertSame(20000, $item->harga);
        $this->assertTrue($item->is_active);
    }

    public function test_custom_price_item_stores_zero_harga_regardless_of_input(): void
    {
        $user = User::factory()->create();
        $kategori = KategoriProduk::create(['slug' => 'custom_test', 'label' => 'Custom Test', 'pricing_mode' => 'varian', 'satuan_label' => 'pcs']);

        $this->actingAs($user)->post(route('admin.kategori-produk.item.store', $kategori), [
            'nama' => 'Custom Motif',
            'slug' => 'custom_motif',
            'is_custom' => '1',
        ])->assertRedirect();

        $item = $kategori->items()->where('slug', 'custom_motif')->first();
        $this->assertTrue($item->is_custom);
        $this->assertSame(0, $item->harga);
    }

    public function test_item_slug_must_be_unique_within_same_kategori_only(): void
    {
        $user = User::factory()->create();
        $kategoriA = KategoriProduk::create(['slug' => 'kat_a', 'label' => 'A', 'pricing_mode' => 'varian', 'satuan_label' => 'pcs']);
        $kategoriB = KategoriProduk::create(['slug' => 'kat_b', 'label' => 'B', 'pricing_mode' => 'varian', 'satuan_label' => 'pcs']);
        $kategoriA->items()->create(['slug' => 'sama', 'nama' => 'X', 'harga' => 1000, 'urutan' => 1]);

        // Slug sama tapi beda kategori - harus boleh.
        $this->actingAs($user)->post(route('admin.kategori-produk.item.store', $kategoriB), [
            'nama' => 'Y', 'slug' => 'sama', 'harga' => 2000,
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        // Slug sama di kategori yang sama - harus ditolak.
        $this->actingAs($user)->post(route('admin.kategori-produk.item.store', $kategoriA), [
            'nama' => 'Z', 'slug' => 'sama', 'harga' => 3000,
        ])->assertSessionHasErrors('slug');
    }

    public function test_admin_can_toggle_and_delete_item(): void
    {
        $user = User::factory()->create();
        $kategori = KategoriProduk::create(['slug' => 'toggle_item', 'label' => 'Toggle Item', 'pricing_mode' => 'varian', 'satuan_label' => 'pcs']);
        $item = $kategori->items()->create(['slug' => 'x', 'nama' => 'X', 'harga' => 1000, 'urutan' => 1]);

        $this->actingAs($user)->post(route('admin.kategori-produk.item.toggle-active', [$kategori, $item]))->assertRedirect();
        $this->assertFalse($item->fresh()->is_active);

        $this->actingAs($user)->post(route('admin.kategori-produk.item.destroy', [$kategori, $item]))->assertRedirect();
        $this->assertNull($item->fresh());
    }

    public function test_admin_can_set_item_dimensions_for_ongkir(): void
    {
        $user = User::factory()->create();
        $kategori = KategoriProduk::create(['slug' => 'dimensi_test', 'label' => 'Dimensi Test', 'pricing_mode' => 'varian', 'satuan_label' => 'pcs']);
        $item = $kategori->items()->create(['slug' => 'x', 'nama' => 'X', 'harga' => 1000, 'urutan' => 1]);

        $this->actingAs($user)->post(route('admin.kategori-produk.item.update', [$kategori, $item]), [
            'nama' => 'X', 'slug' => 'x', 'harga' => 1000,
            'panjang' => 10.5, 'lebar' => 15, 'berat' => 50,
        ])->assertRedirect();

        $item->refresh();
        $this->assertEquals(10.5, $item->panjang);
        $this->assertEquals(15, $item->lebar);
        $this->assertSame(50, $item->berat);
    }

    public function test_admin_can_set_kategori_dimensions_for_tiered_ongkir(): void
    {
        $user = User::factory()->create();
        $kategori = KategoriProduk::create(['slug' => 'dimensi_tiered', 'label' => 'Dimensi Tiered', 'pricing_mode' => 'tiered', 'satuan_label' => 'foto']);

        $this->actingAs($user)->post(route('admin.kategori-produk.update', $kategori), [
            'label' => 'Dimensi Tiered', 'satuan_label' => 'foto',
            'panjang' => 10, 'lebar' => 15, 'berat' => 5,
        ])->assertRedirect();

        $kategori->refresh();
        $this->assertEquals(10, $kategori->panjang);
        $this->assertEquals(15, $kategori->lebar);
        $this->assertSame(5, $kategori->berat);
    }

    public function test_admin_can_view_kategori_detail_page_with_items(): void
    {
        $user = User::factory()->create();
        $kategori = KategoriProduk::create(['slug' => 'lihat_detail', 'label' => 'Lihat Detail', 'pricing_mode' => 'varian', 'satuan_label' => 'pcs']);
        $kategori->items()->create(['slug' => 'x', 'nama' => 'X', 'harga' => 1000, 'urutan' => 1]);

        $this->actingAs($user)->get(route('admin.kategori-produk.show', $kategori))
            ->assertOk()
            ->assertSee('X');
    }

    public function test_admin_can_manage_tiers_for_tiered_kategori(): void
    {
        $user = User::factory()->create();
        $kategori = KategoriProduk::create(['slug' => 'tier_test', 'label' => 'Tier Test', 'pricing_mode' => 'tiered', 'satuan_label' => 'foto']);

        $this->actingAs($user)->post(route('admin.kategori-produk.tier.store', $kategori), [
            'min' => 1, 'max' => 9, 'harga' => 2000,
        ])->assertRedirect();

        $tier = $kategori->tiers()->first();
        $this->assertNotNull($tier);

        $this->actingAs($user)->post(route('admin.kategori-produk.tier.update', [$kategori, $tier]), [
            'min' => 1, 'max' => 19, 'harga' => 1900,
        ])->assertRedirect();
        $this->assertSame(1900, $tier->fresh()->harga);

        $this->actingAs($user)->post(route('admin.kategori-produk.tier.destroy', [$kategori, $tier]))->assertRedirect();
        $this->assertNull($tier->fresh());
    }
}

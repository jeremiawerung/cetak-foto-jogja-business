<?php

namespace Tests\Feature;

use App\Models\KategoriProduk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CetakFotoKatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_only_shows_active_categories_and_items(): void
    {
        $aktif = KategoriProduk::create(['slug' => 'aktif', 'label' => 'Aktif', 'pricing_mode' => 'varian', 'satuan_label' => 'pcs', 'is_active' => true, 'urutan' => 1]);
        $aktif->items()->create(['slug' => 'item_aktif', 'nama' => 'Item Aktif', 'harga' => 10000, 'is_active' => true, 'urutan' => 1]);
        $aktif->items()->create(['slug' => 'item_nonaktif', 'nama' => 'Item Nonaktif', 'harga' => 5000, 'is_active' => false, 'urutan' => 2]);

        KategoriProduk::create(['slug' => 'nonaktif', 'label' => 'Nonaktif', 'pricing_mode' => 'varian', 'satuan_label' => 'pcs', 'is_active' => false, 'urutan' => 2]);

        $response = $this->get(route('cetak-foto.index'));

        $response->assertOk();
        $catalog = $response->viewData('catalog');

        $this->assertArrayHasKey('aktif', $catalog);
        $this->assertArrayNotHasKey('nonaktif', $catalog);
        $this->assertCount(1, $catalog['aktif']['items']);
        $this->assertSame('item_aktif', $catalog['aktif']['items'][0]['id']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\KategoriProduk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_has_meta_description_canonical_and_schema(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('FAQPage', false);
    }

    public function test_produk_show_page_has_product_schema_and_description(): void
    {
        $kategori = KategoriProduk::create([
            'slug' => 'pas-foto',
            'label' => 'Pas Foto',
            'pricing_mode' => 'varian',
            'satuan_label' => 'lembar',
            'is_active' => true,
            'urutan' => 1,
        ]);
        $kategori->items()->create([
            'slug' => 'pas-foto-3x4',
            'nama' => 'Pas Foto 3x4',
            'harga' => 5000,
            'is_active' => true,
            'urutan' => 1,
        ]);

        $response = $this->get(route('produk.show', ['kategori' => 'pas-foto', 'item' => 'pas-foto-3x4']));

        $response->assertOk();
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('"@type":"Product"', false);
    }

    public function test_cart_page_is_marked_noindex(): void
    {
        $response = $this->get(route('keranjang.index'));

        $response->assertOk();
        $response->assertSee('noindex, nofollow', false);
    }

    public function test_sitemap_lists_active_categories_and_items(): void
    {
        $kategori = KategoriProduk::create([
            'slug' => 'pas-foto',
            'label' => 'Pas Foto',
            'pricing_mode' => 'varian',
            'satuan_label' => 'lembar',
            'is_active' => true,
            'urutan' => 1,
        ]);
        $kategori->items()->create([
            'slug' => 'pas-foto-3x4',
            'nama' => 'Pas Foto 3x4',
            'harga' => 5000,
            'is_active' => true,
            'urutan' => 1,
        ]);

        KategoriProduk::create([
            'slug' => 'nonaktif',
            'label' => 'Nonaktif',
            'pricing_mode' => 'varian',
            'satuan_label' => 'pcs',
            'is_active' => false,
            'urutan' => 2,
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee(route('produk.show', ['kategori' => 'pas-foto', 'item' => 'pas-foto-3x4']), false);
        $response->assertDontSee('nonaktif');
    }
}

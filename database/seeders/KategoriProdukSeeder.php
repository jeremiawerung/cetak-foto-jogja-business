<?php

namespace Database\Seeders;

use App\Models\KategoriProduk;
use Illuminate\Database\Seeder;

/**
 * Migrasi satu kali dari config/catalog.php (data katalog lama, hardcode) ke
 * database - supaya bisa dikelola admin lewat CRUD di /internal/kategori-produk.
 * Idempotent: dilewati kalau kategori_produks sudah pernah diisi.
 */
class KategoriProdukSeeder extends Seeder
{
    public function run(): void
    {
        if (KategoriProduk::query()->exists()) {
            return;
        }

        $catalog = config('catalog', []);
        $urutan = 0;

        foreach ($catalog as $slug => $data) {
            $kategori = KategoriProduk::create([
                'slug' => $slug,
                'label' => $data['label'],
                'gambar' => $data['gambar'] ?? null,
                'pricing_mode' => $data['pricing_mode'],
                'satuan_label' => $data['satuan_label'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'is_active' => true,
                'urutan' => $urutan++,
            ]);

            if ($data['pricing_mode'] === 'tiered') {
                foreach ($data['tiers'] as $tier) {
                    $kategori->tiers()->create([
                        'min' => $tier['min'],
                        'max' => $tier['max'],
                        'harga' => $tier['harga'],
                    ]);
                }

                continue;
            }

            $itemUrutan = 0;
            foreach ($data['items'] as $item) {
                $kategori->items()->create([
                    'slug' => $item['id'],
                    'nama' => $item['nama'],
                    'harga' => $item['harga'] ?? 0,
                    'harga_normal' => $item['harga_normal'] ?? null,
                    'rincian' => $item['rincian'] ?? null,
                    'is_custom' => ! empty($item['custom']),
                    'is_active' => true,
                    'urutan' => $itemUrutan++,
                ]);
            }
        }
    }
}

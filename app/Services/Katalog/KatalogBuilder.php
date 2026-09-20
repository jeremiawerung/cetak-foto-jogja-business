<?php

namespace App\Services\Katalog;

use App\Models\KategoriProduk;

/**
 * Menyusun ulang data katalog dari database jadi array dengan bentuk PERSIS SAMA
 * seperti config/catalog.php lama (keyed by slug kategori) - supaya view publik
 * dan window.CATALOG di JS tidak perlu diubah sama sekali.
 */
class KatalogBuilder
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function build(): array
    {
        $kategoris = KategoriProduk::query()
            ->where('is_active', true)
            ->orderBy('urutan')
            ->with(['items' => fn ($q) => $q->where('is_active', true), 'tiers'])
            ->get();

        $catalog = [];

        foreach ($kategoris as $kategori) {
            $data = [
                'label' => $kategori->label,
                'gambar' => $kategori->gambar,
                'pricing_mode' => $kategori->pricing_mode,
                'satuan_label' => $kategori->satuan_label,
                'panjang' => $kategori->panjang,
                'lebar' => $kategori->lebar,
                'berat' => $kategori->berat,
            ];

            if ($kategori->deskripsi) {
                $data['deskripsi'] = $kategori->deskripsi;
            }

            if ($kategori->catatan) {
                $data['catatan'] = $kategori->catatan;
            }

            if ($kategori->pricing_mode === 'tiered') {
                $data['tiers'] = $kategori->tiers->map(fn ($tier) => [
                    'min' => $tier->min,
                    'max' => $tier->max,
                    'harga' => $tier->harga,
                ])->values()->all();
            } else {
                $data['items'] = $kategori->items->map(function ($item) {
                    $row = [
                        'id' => $item->slug,
                        'nama' => $item->nama,
                        'gambar' => $item->gambar,
                        'harga' => $item->harga,
                    ];

                    if ($item->harga_normal) {
                        $row['harga_normal'] = $item->harga_normal;
                    }

                    if ($item->rincian) {
                        $row['rincian'] = $item->rincian;
                    }

                    if ($item->is_custom) {
                        $row['custom'] = true;
                    }

                    $row['panjang'] = $item->panjang;
                    $row['lebar'] = $item->lebar;
                    $row['berat'] = $item->berat;

                    return $row;
                })->values()->all();
            }

            $catalog[$kategori->slug] = $data;
        }

        return $catalog;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\KategoriProduk;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [
            ['loc' => route('home'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => route('cetak-foto.index'), 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['loc' => route('sewa-fotografer.index'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('cek-pesanan'), 'changefreq' => 'monthly', 'priority' => '0.3'],
        ];

        $kategoris = KategoriProduk::query()
            ->where('is_active', true)
            ->with(['items' => fn ($q) => $q->where('is_active', true)])
            ->get();

        foreach ($kategoris as $kategori) {
            if ($kategori->pricing_mode === 'varian') {
                foreach ($kategori->items as $item) {
                    $urls[] = [
                        'loc' => route('produk.show', ['kategori' => $kategori->slug, 'item' => $item->slug]),
                        'lastmod' => $item->updated_at?->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.7',
                    ];
                }
            } else {
                $urls[] = [
                    'loc' => route('produk.show', ['kategori' => $kategori->slug]),
                    'lastmod' => $kategori->updated_at?->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            }
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}

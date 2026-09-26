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

        return response($this->toXml($urls), 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Dirender lewat string PHP biasa (bukan Blade view) dan deklarasi XML
     * ditulis terpisah-pisah (concat) - di hosting produksi ini, deklarasi
     * XML apa adanya di file .php sempat salah dibaca parser PHP sebagai
     * pembuka tag PHP sungguhan dan bikin 500 (syntax error).
     *
     * @param  array<int, array<string, mixed>>  $urls
     */
    private function toXml(array $urls): string
    {
        $entries = array_map(function (array $url) {
            $lastmod = ! empty($url['lastmod'])
                ? sprintf("        <lastmod>%s</lastmod>\n", htmlspecialchars($url['lastmod'], ENT_XML1))
                : '';

            return sprintf(
                "    <url>\n        <loc>%s</loc>\n%s        <changefreq>%s</changefreq>\n        <priority>%s</priority>\n    </url>\n",
                htmlspecialchars($url['loc'], ENT_XML1),
                $lastmod,
                htmlspecialchars($url['changefreq'], ENT_XML1),
                htmlspecialchars($url['priority'], ENT_XML1)
            );
        }, $urls);

        return '<'.'?xml version="1.0" encoding="UTF-8"?'.'>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .implode('', $entries)
            .'</urlset>'."\n";
    }
}

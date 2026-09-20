<?php

namespace App\Http\Controllers;

use App\Services\Katalog\KatalogBuilder;
use Illuminate\View\View;

class ProdukDetailController extends Controller
{
    public function show(KatalogBuilder $katalogBuilder, string $kategori, ?string $item = null): View
    {
        $catalog = $katalogBuilder->build();
        $kategoriData = $catalog[$kategori] ?? null;

        abort_if(! $kategoriData, 404);

        $itemData = null;

        if ($kategoriData['pricing_mode'] === 'varian') {
            abort_if(! $item, 404);
            $itemData = collect($kategoriData['items'])->firstWhere('id', $item);
            abort_if(! $itemData, 404);
        } else {
            abort_if($item !== null, 404);
        }

        return view('produk.show', [
            'kategoriSlug' => $kategori,
            'kategoriData' => $kategoriData,
            'item' => $itemData,
        ]);
    }
}

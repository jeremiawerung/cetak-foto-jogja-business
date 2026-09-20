<?php

namespace App\Http\Controllers;

use App\Services\Katalog\KatalogBuilder;

class CetakFotoController extends Controller
{
    public function index(KatalogBuilder $katalogBuilder)
    {
        $catalog = $katalogBuilder->build();

        return view('cetak-foto.index', compact('catalog'));
    }
}

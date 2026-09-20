<?php

namespace App\Http\Controllers;

use App\Models\PrintOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CekPesananController extends Controller
{
    public function index(Request $request): View
    {
        $nomor = trim((string) $request->query('nomor'));
        $order = null;
        $tidakDitemukan = false;

        if ($nomor !== '') {
            $order = PrintOrder::with('items')->where('nomor_pesanan', $nomor)->first();
            $tidakDitemukan = ! $order;
        }

        return view('cek-pesanan.index', [
            'nomor' => $nomor,
            'order' => $order,
            'tidakDitemukan' => $tidakDitemukan,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\PrintOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CekPesananTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_without_query(): void
    {
        $this->get(route('cek-pesanan'))->assertOk();
    }

    public function test_shows_order_detail_when_nomor_found(): void
    {
        $order = PrintOrder::create([
            'nomor_pesanan' => PrintOrder::generateNomorPesanan(),
            'kategori' => 'pas_foto',
            'jumlah' => 3,
            'estimasi_harga' => 15000,
            'biaya_ongkir' => 7000,
            'metode_ambil' => 'dikirim',
            'status' => 'diproses',
            'status_pembayaran' => 'lunas',
        ]);

        $response = $this->get(route('cek-pesanan', ['nomor' => $order->nomor_pesanan]));

        $response->assertOk();
        $response->assertSee($order->nomor_pesanan);
        $response->assertSee('Diproses');
        $response->assertSee('Lunas');
        $response->assertSee('Rp22.000');
    }

    public function test_shows_not_found_message_for_unknown_nomor(): void
    {
        $response = $this->get(route('cek-pesanan', ['nomor' => 'CFJ-TIDAK-ADA']));

        $response->assertOk();
        $response->assertSee('tidak ditemukan');
    }

    public function test_shows_resi_when_order_is_dikirim_and_resi_filled(): void
    {
        $order = PrintOrder::create([
            'nomor_pesanan' => PrintOrder::generateNomorPesanan(),
            'estimasi_harga' => 15000,
            'biaya_ongkir' => 7000,
            'metode_ambil' => 'dikirim',
            'ongkir_label' => 'JNE - REG',
            'resi' => 'JNE1234567890',
            'status' => 'diproses',
            'status_pembayaran' => 'lunas',
        ]);

        $response = $this->get(route('cek-pesanan', ['nomor' => $order->nomor_pesanan]));

        $response->assertOk();
        $response->assertSee('JNE1234567890');
        $response->assertSee('Paket sudah dikirim');
    }

    public function test_does_not_show_resi_block_when_resi_empty(): void
    {
        $order = PrintOrder::create([
            'nomor_pesanan' => PrintOrder::generateNomorPesanan(),
            'estimasi_harga' => 15000,
            'biaya_ongkir' => 7000,
            'metode_ambil' => 'dikirim',
            'status' => 'diproses',
            'status_pembayaran' => 'lunas',
        ]);

        $response = $this->get(route('cek-pesanan', ['nomor' => $order->nomor_pesanan]));

        $response->assertOk();
        $response->assertDontSee('Paket sudah dikirim');
    }

    public function test_custom_order_shows_menunggu_konfirmasi_admin_instead_of_payment_status(): void
    {
        $order = PrintOrder::create([
            'nomor_pesanan' => PrintOrder::generateNomorPesanan(),
            'kategori' => 'custom_test',
            'jumlah' => 1,
            'estimasi_harga' => 0,
            'is_custom' => true,
            'status' => 'baru',
            'status_pembayaran' => 'belum_bayar',
        ]);

        $response = $this->get(route('cek-pesanan', ['nomor' => $order->nomor_pesanan]));

        $response->assertOk();
        $response->assertSee('Menunggu Konfirmasi Admin');
    }
}

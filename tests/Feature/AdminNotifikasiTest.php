<?php

namespace Tests\Feature;

use App\Models\KategoriProduk;
use App\Models\PhotographerBooking;
use App\Models\PrintOrder;
use App\Models\User;
use App\Notifications\BookingMasukNotification;
use App\Notifications\OrderMasukNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotifikasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_katalog_admin_gets_notified_when_print_order_created(): void
    {
        $admin = User::factory()->create(['role' => 'katalog']);
        $internalOnly = User::factory()->create(['role' => 'internal']);

        $kategori = KategoriProduk::create(['slug' => 'polaroid_test', 'label' => 'Polaroid', 'pricing_mode' => 'varian', 'satuan_label' => 'pcs', 'is_active' => true]);
        $kategori->items()->create(['slug' => 'ecer', 'nama' => 'Ecer', 'harga' => 3000, 'is_active' => true, 'urutan' => 1]);

        $this->post(route('keranjang.tambah'), [
            'kategori' => 'polaroid_test',
            'varian' => 'ecer',
            'jumlah' => 2,
        ]);
        $this->post(route('checkout.informasi.simpan'), ['nama' => 'Budi', 'no_hp' => '08123456789']);
        $this->post(route('checkout.pengiriman.simpan'), ['metode_ambil' => 'ambil_toko']);
        $this->post(route('checkout.konfirmasi.simpan'), [
            'metode_bayar' => 'bayar_toko',
            'syarat_setuju' => '1',
        ]);

        $order = PrintOrder::first();
        $this->assertNotNull($order);

        $notified = $admin->fresh()->notifications;
        $this->assertCount(1, $notified);
        $this->assertSame(OrderMasukNotification::class, $notified->first()->type);
        $this->assertCount(0, $internalOnly->fresh()->notifications);
    }

    public function test_katalog_admin_gets_notified_when_booking_created(): void
    {
        $admin = User::factory()->create(['role' => 'super']);

        $this->postJson(route('sewa-fotografer.booking'), [
            'nama' => 'Siti',
            'no_hp' => '08123456789',
            'lokasi' => 'Malioboro',
            'tanggal' => now()->addDays(3)->toDateString(),
            'jam' => config('booking.jam_slot')[0],
        ])->assertOk();

        $booking = PhotographerBooking::first();
        $this->assertNotNull($booking);
        $this->assertCount(1, $admin->fresh()->notifications);
    }

    public function test_notifikasi_endpoint_returns_unread_count_and_list(): void
    {
        $admin = User::factory()->create();
        $booking = PhotographerBooking::create([
            'nama' => 'Siti', 'no_hp' => '08123456789', 'lokasi' => 'Malioboro',
            'tanggal' => now()->addDays(3)->toDateString(), 'jam' => config('booking.jam_slot')[0],
        ]);
        $admin->notify(new BookingMasukNotification($booking));

        $response = $this->actingAs($admin)->getJson(route('admin.notifikasi.index'))->assertOk();
        $response->assertJsonPath('belum_dibaca', 1);
        $response->assertJsonCount(1, 'notifikasi');
    }

    public function test_admin_can_mark_single_notification_as_read(): void
    {
        $admin = User::factory()->create();
        $booking = PhotographerBooking::create([
            'nama' => 'Siti', 'no_hp' => '08123456789', 'lokasi' => 'Malioboro',
            'tanggal' => now()->addDays(3)->toDateString(), 'jam' => config('booking.jam_slot')[0],
        ]);
        $admin->notify(new BookingMasukNotification($booking));
        $notifId = $admin->fresh()->notifications->first()->id;

        $this->actingAs($admin)->postJson(route('admin.notifikasi.baca', $notifId))->assertOk();

        $this->assertNotNull($admin->fresh()->notifications->first()->read_at);
    }

    public function test_admin_can_mark_all_notifications_as_read(): void
    {
        $admin = User::factory()->create();
        $booking = PhotographerBooking::create([
            'nama' => 'Siti', 'no_hp' => '08123456789', 'lokasi' => 'Malioboro',
            'tanggal' => now()->addDays(3)->toDateString(), 'jam' => config('booking.jam_slot')[0],
        ]);
        $admin->notify(new BookingMasukNotification($booking));
        $admin->notify(new BookingMasukNotification($booking));

        $this->actingAs($admin)->postJson(route('admin.notifikasi.baca-semua'))->assertOk();

        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());
    }

    public function test_guest_cannot_access_notifikasi_endpoint(): void
    {
        $this->getJson(route('admin.notifikasi.index'))->assertUnauthorized();
    }
}

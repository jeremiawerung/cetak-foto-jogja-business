<?php

namespace Tests\Feature;

use App\Models\PhotographerBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SewaFotograferBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_no_longer_requires_jenis_acara_or_paket(): void
    {
        $response = $this->postJson(route('sewa-fotografer.booking'), [
            'nama' => 'Budi',
            'no_hp' => '08123456789',
            'lokasi' => 'Studio Jogja',
            'tanggal' => now()->addDay()->format('Y-m-d'),
            'jam' => config('booking.jam_slot')[0],
        ]);

        $response->assertOk();

        $booking = PhotographerBooking::first();
        $this->assertNotNull($booking);
        $this->assertArrayNotHasKey('jenis_acara', $booking->getAttributes());
        $this->assertArrayNotHasKey('paket', $booking->getAttributes());
    }

    public function test_index_page_no_longer_passes_paket_or_jenis_acara(): void
    {
        $response = $this->get(route('sewa-fotografer.index'));

        $response->assertOk();
        $response->assertViewMissing('paket');
        $response->assertViewMissing('jenisAcara');
    }
}

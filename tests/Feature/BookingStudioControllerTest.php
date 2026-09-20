<?php

namespace Tests\Feature;

use App\Models\PhotographerBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingStudioControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_booking_studio_pages(): void
    {
        $this->get(route('admin.booking-studio.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_list_bookings(): void
    {
        $user = User::factory()->create();
        PhotographerBooking::create([
            'nama' => 'Siti',
            'no_hp' => '08123456789',
            'lokasi' => 'Studio Jogja',
            'tanggal' => now()->addDay()->format('Y-m-d'),
            'jam' => config('booking.jam_slot')[0],
        ]);

        $this->actingAs($user)->get(route('admin.booking-studio.index'))
            ->assertOk()
            ->assertSee('Siti');
    }

    public function test_admin_can_view_booking_detail(): void
    {
        $user = User::factory()->create();
        $booking = PhotographerBooking::create([
            'nama' => 'Siti',
            'no_hp' => '08123456789',
            'lokasi' => 'Studio Jogja',
            'tanggal' => now()->addDay()->format('Y-m-d'),
            'jam' => config('booking.jam_slot')[0],
        ]);

        $this->actingAs($user)->get(route('admin.booking-studio.show', $booking))
            ->assertOk()
            ->assertSee('Siti')
            ->assertSee('Studio Jogja');
    }

    public function test_admin_can_update_booking_status(): void
    {
        $user = User::factory()->create();
        $booking = PhotographerBooking::create([
            'nama' => 'Siti',
            'no_hp' => '08123456789',
            'lokasi' => 'Studio Jogja',
            'tanggal' => now()->addDay()->format('Y-m-d'),
            'jam' => config('booking.jam_slot')[0],
        ]);

        $this->actingAs($user)
            ->post(route('admin.booking-studio.status', $booking), ['status' => 'dikonfirmasi'])
            ->assertRedirect();

        $this->assertSame('dikonfirmasi', $booking->fresh()->status);
    }
}

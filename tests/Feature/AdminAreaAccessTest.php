<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAreaAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_katalog_admin_can_access_admin_area_but_not_internal(): void
    {
        $user = User::factory()->create(['role' => 'katalog']);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('internal.dashboard'))->assertForbidden();
    }

    public function test_internal_admin_can_access_internal_area_but_not_admin(): void
    {
        $user = User::factory()->create(['role' => 'internal']);

        $this->actingAs($user)->get(route('internal.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_super_admin_can_access_both_areas(): void
    {
        $user = User::factory()->create(['role' => 'super']);

        $this->actingAs($user)->get(route('internal.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_katalog_admin_cannot_log_in_via_internal_login_form(): void
    {
        $user = User::factory()->create(['role' => 'katalog', 'password' => bcrypt('rahasia123')]);

        $this->post(route('internal.login.attempt'), [
            'email' => $user->email,
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_internal_admin_cannot_log_in_via_admin_login_form(): void
    {
        $user = User::factory()->create(['role' => 'internal', 'password' => bcrypt('rahasia123')]);

        $this->post(route('admin.login.attempt'), [
            'email' => $user->email,
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_hitting_admin_area_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_guest_hitting_internal_area_is_redirected_to_internal_login(): void
    {
        $this->get(route('internal.dashboard'))->assertRedirect(route('internal.login'));
    }
}

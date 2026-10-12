<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $this->get('/dashboard')->assertRedirectToRoute('login');
    }

    public function test_authenticated_user_can_open_dashboard_and_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertViewIs('app');
        $this->actingAs($user)->get('/settings')->assertOk()->assertViewIs('app');
    }
}

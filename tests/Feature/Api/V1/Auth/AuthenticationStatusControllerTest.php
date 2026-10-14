<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticationStatusControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_receives_null_user_with_successful_status(): void
    {
        $this->getJson('/api/v1/auth/status')
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_authenticated_user_receives_user_resource(): void
    {
        $user = User::factory()->create([
            'name' => 'Randy Mays',
            'email' => 'randy@example.com',
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/auth/status')
            ->assertOk()
            ->assertJsonPath('data.name', 'Randy Mays')
            ->assertJsonPath('data.email', 'randy@example.com')
            ->assertJsonMissingPath('data.password');
    }
}

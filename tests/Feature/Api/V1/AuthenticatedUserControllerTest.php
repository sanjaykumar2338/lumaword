<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticatedUserControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_when_user_is_unauthenticated(): void
    {
        $this->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_returns_authenticated_user_resource(): void
    {
        $user = User::factory()->create(['name' => 'Randy Mays', 'email' => 'randy@example.com']);

        $this->actingAs($user)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.name', 'Randy Mays')
            ->assertJsonMissingPath('data.password');
    }
}

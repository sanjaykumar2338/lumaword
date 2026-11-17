<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class MobileAuthenticationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_mobile_login_returns_a_bearer_token_without_creating_a_web_session(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com', 'password' => 'password']);

        $response = $this->postJson('/api/v1/mobile/auth/login', [
            'email' => 'ada@example.com', 'password' => 'password', 'device_name' => 'test-device',
        ]);

        $response->assertOk()->assertJsonPath('data.id', $user->id)->assertJsonPath('data.email', 'ada@example.com');
        $this->assertNotEmpty($response->json('token'));
        $this->assertGuest('web');
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_mobile_registration_returns_a_token_and_can_use_it_for_protected_api_calls(): void
    {
        $response = $this->postJson('/api/v1/mobile/auth/register', [
            'name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password',
            'password_confirmation' => 'password', 'device_name' => 'test-device',
        ])->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$response->json('token'))
            ->getJson('/api/v1/user')->assertOk()->assertJsonPath('data.name', 'Ada Lovelace');
    }

    public function test_mobile_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;
        $otherToken = $user->createToken('other-device')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/mobile/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/user')->assertUnauthorized();

        $this->withHeader('Authorization', 'Bearer '.$otherToken)
            ->getJson('/api/v1/user')->assertOk();
    }

    public function test_mobile_logout_does_not_log_out_a_website_session(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-device')->plainTextToken;

        $this->actingAs($user, 'web')
            ->postJson('/api/v1/mobile/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertTrue(Auth::guard('web')->check());

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/user')->assertOk();
    }
}

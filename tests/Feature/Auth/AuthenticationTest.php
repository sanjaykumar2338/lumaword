<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_credentials_authenticate_user(): void
    {
        $user = User::factory()->create(['email' => 'randy@example.com']);

        $response = $this->withHeader('Origin', config('app.url'))->postJson('/api/v1/auth/login', [
            'email' => 'randy@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonPath('data.id', $user->id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_return_422_and_leave_user_unauthenticated(): void
    {
        User::factory()->create(['email' => 'randy@example.com']);

        $response = $this->withHeader('Origin', config('app.url'))->postJson('/api/v1/auth/login', [
            'email' => 'randy@example.com',
            'password' => 'incorrect-password',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        User::factory()->create(['email' => 'randy@example.com']);

        $this->withHeader('Origin', config('app.url'))->postJson('/api/v1/auth/login', [
            'email' => 'randy@example.com',
            'password' => 'password',
        ])->assertOk();

        $response = $this->withHeader('Origin', config('app.url'))
            ->postJson('/api/v1/auth/logout');

        $response->assertNoContent();
        $this->assertGuest('web');
    }

    public function test_sixth_failed_login_attempt_returns_429(): void
    {
        User::factory()->create(['email' => 'randy@example.com']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withHeader('Origin', config('app.url'))->postJson('/api/v1/auth/login', [
                'email' => 'randy@example.com',
                'password' => 'incorrect-password',
            ])->assertUnprocessable();
        }

        $this->withHeader('Origin', config('app.url'))->postJson('/api/v1/auth/login', [
            'email' => 'randy@example.com',
            'password' => 'incorrect-password',
        ])->assertTooManyRequests();
    }
}

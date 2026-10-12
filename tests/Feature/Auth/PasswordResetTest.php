<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_existing_user_receives_password_reset_notification(): void
    {
        $user = User::factory()->create(['email' => 'randy@example.com']);
        Notification::fake();

        $response = $this->withHeader('Origin', config('app.url'))->postJson('/api/v1/auth/forgot-password', [
            'email' => 'randy@example.com',
        ]);

        $response->assertOk()->assertJsonPath('message', 'If an account exists for that email, a reset link has been sent.');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_valid_token_resets_password(): void
    {
        $user = User::factory()->create(['email' => 'randy@example.com']);
        $token = Password::broker()->createToken($user);
        Event::fake([PasswordReset::class]);

        $response = $this->withHeader('Origin', config('app.url'))->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'randy@example.com',
            'password' => 'renewed-password',
            'password_confirmation' => 'renewed-password',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('renewed-password', $user->refresh()->password));
        Event::assertDispatched(PasswordReset::class);
    }

    public function test_invalid_token_returns_422_and_keeps_existing_password(): void
    {
        $user = User::factory()->create(['email' => 'randy@example.com']);

        $response = $this->withHeader('Origin', config('app.url'))->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => 'randy@example.com',
            'password' => 'renewed-password',
            'password_confirmation' => 'renewed-password',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }
}

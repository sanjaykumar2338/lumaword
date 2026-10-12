<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_details_create_account_and_return_201(): void
    {
        $response = $this->withHeader('Origin', config('app.url'))->postJson('/api/v1/auth/register', [
            'name' => 'Randy Mays',
            'email' => 'randy@example.com',
            'password' => 'faithful-password',
            'password_confirmation' => 'faithful-password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Randy Mays')
            ->assertJsonPath('data.email', 'randy@example.com');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'randy@example.com']);
        $this->assertTrue(Hash::check('faithful-password', User::where('email', 'randy@example.com')->sole()->password));
    }

    public function test_duplicate_email_returns_422_and_does_not_create_account(): void
    {
        User::factory()->create(['email' => 'randy@example.com']);

        $response = $this->withHeader('Origin', config('app.url'))->postJson('/api/v1/auth/register', [
            'name' => 'Another Randy',
            'email' => 'randy@example.com',
            'password' => 'faithful-password',
            'password_confirmation' => 'faithful-password',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('users', 1);
    }
}

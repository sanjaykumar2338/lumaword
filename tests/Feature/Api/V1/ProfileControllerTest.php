<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/api/v1/user/profile', [
            'name' => 'Randy Mays',
            'email' => 'randy@example.com',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'Randy Mays');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'randy@example.com']);
    }

    public function test_profile_update_rejects_email_owned_by_another_user(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($user)->patchJson('/api/v1/user/profile', [
            'name' => 'Randy Mays',
            'email' => 'taken@example.com',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_authenticated_user_can_update_password_with_current_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/v1/user/password', [
            'current_password' => 'password',
            'password' => 'renewed-password',
            'password_confirmation' => 'renewed-password',
        ]);

        $response->assertOk()->assertJsonPath('message', 'Password updated.');
        $this->assertTrue(Hash::check('renewed-password', $user->refresh()->password));
    }
}

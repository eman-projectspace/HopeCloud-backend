<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_an_access_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Hope Donor',
            'email' => 'donor@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Account created successfully')
            ->assertJsonPath('user.name', 'Hope Donor')
            ->assertJsonPath('user.email', 'donor@example.com')
            ->assertJsonStructure(['token']);

        $user = User::where('email', 'donor@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_registration_rejects_invalid_and_duplicate_email_data(): void
    {
        $this->postJson('/api/register', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);

        $user = User::factory()->create(['email' => 'existing@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Another Donor',
            'email' => $user->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'donor@example.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['token']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid email or password');
    }

    public function test_logout_revokes_the_bearer_token(): void
    {
        $user = User::factory()->create();
        $newToken = $user->createToken('test-token');
        $tokenId = $newToken->accessToken->id;

        $this->withToken($newToken->plainTextToken)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_protected_api_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/api/donations')->assertUnauthorized();
    }
}

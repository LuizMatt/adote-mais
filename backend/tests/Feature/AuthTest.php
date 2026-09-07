<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_requires_valid_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'secret123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@adotamais.local',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@adotamais.local',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role'],
            ])
            ->assertJson([
                'message' => 'Login successful',
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'email' => 'admin@adotamais.local',
                    'role' => 'admin',
                ],
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@adotamais.local',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@adotamais.local',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_revokes_previous_tokens(): void
    {
        User::factory()->create([
            'email' => 'user@test.local',
            'password' => bcrypt('password123'),
        ]);

        $firstLogin = $this->postJson('/api/login', [
            'email' => 'user@test.local',
            'password' => 'password123',
        ]);

        $firstToken = $firstLogin->json('access_token');

        $this->postJson('/api/login', [
            'email' => 'user@test.local',
            'password' => 'password123',
        ]);

        // Attempting to access protected endpoint with old token should fail (401)
        $response = $this->withHeader('Authorization', 'Bearer ' . $firstToken)
            ->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_me_endpoint(): void
    {
        $user = User::factory()->create(['role' => 'agent']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => 'agent',
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_access_user_endpoint(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Successfully logged out']);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Reset auth state in test container to ensure token cannot authenticate again
        auth('sanctum')->forgetUser();

        $check = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');
        $check->assertStatus(401);
    }

    public function test_admin_role_middleware_blocks_agent_and_allows_admin(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Agent should be blocked with 403
        $agentResponse = $this->actingAs($agent, 'sanctum')->getJson('/api/admin-check');
        $agentResponse->assertStatus(403)
            ->assertJson(['message' => 'Access denied. Action restricted to administrators.']);

        // Admin should be allowed with 200
        $adminResponse = $this->actingAs($admin, 'sanctum')->getJson('/api/admin-check');
        $adminResponse->assertStatus(200)
            ->assertJson(['status' => 'admin-confirmed']);
    }
}

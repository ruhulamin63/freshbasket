<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_registration_always_creates_a_normal_user_and_returns_jwt(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Amina Rahman',
            'email' => 'amina@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'role' => 'admin',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.token_type', 'bearer')
            ->assertJsonPath('data.user.roles.0', RoleName::User->value)
            ->assertJsonMissing(['role' => 'admin']);

        $user = User::query()->where('email', 'amina@example.com')->firstOrFail();
        $this->assertTrue($user->hasExactRoles(RoleName::User->value));
    }

    public function test_login_returns_a_token_that_authenticates_me(): void
    {
        $user = User::factory()->create(['password' => 'Password123']);
        $user->assignRole(RoleName::User->value);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ])->assertOk()->json('data.access_token');

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()->assertJsonPath('data.email', $user->email);

    }

    public function test_refresh_rotates_the_access_token(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::User->value);
        $token = auth('api')->login($user);

        $this->app['auth']->forgetGuards();
        $refreshed = $this->withToken($token)->postJson('/api/v1/auth/refresh')
            ->assertOk()->json('data.access_token');

        $this->assertNotSame($token, $refreshed);
    }

    public function test_logout_blacklists_the_access_token(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::User->value);
        $token = auth('api')->login($user);

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }
}

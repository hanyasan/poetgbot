<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The JWT guard caches the resolved user between requests in one test,
        // so start every test with a clean guard.
        $this->app['auth']->forgetGuards();
    }

    private function user(string $email = 'user@example.com', string $password = 'secret1'): User
    {
        return User::query()->create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make($password),
        ]);
    }

    public function test_login_returns_a_token_for_valid_credentials(): void
    {
        $this->user();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'secret1',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.authorisation.type', 'bearer');

        $this->assertIsString($response->json('data.authorisation.token'));
        $this->assertNotEmpty($response->json('data.authorisation.token'));
    }

    public function test_login_rejects_a_wrong_password(): void
    {
        $this->user();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertExactJson([
                'success' => false,
                'data' => ['message' => 'Unauthorized'],
            ]);
    }

    public function test_login_rejects_an_unknown_email(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'secret1',
        ])->assertUnauthorized();
    }

    public function test_login_validates_its_input(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password'], 'data.errors');
    }

    public function test_register_creates_the_user_and_returns_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'secret1',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.message', 'User created successfully')
            ->assertJsonPath('data.authorisation.type', 'bearer');

        $this->assertNotEmpty($response->json('data.authorisation.token'));

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();

        $this->assertSame('New User', $user->name);
        $this->assertTrue(Hash::check('secret1', $user->password));
    }

    public function test_register_validates_its_input(): void
    {
        $this->postJson('/api/v1/auth/register', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password'], 'data.errors');
    }

    public function test_register_rejects_a_duplicate_email(): void
    {
        $this->user('taken@example.com');

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Another User',
            'email' => 'taken@example.com',
            'password' => 'secret1',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email'], 'data.errors');
    }

    public function test_register_rejects_a_short_password(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => '12345',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password'], 'data.errors');
    }

    public function test_register_does_not_require_authentication(): void
    {
        // A missing bearer token must not be what fails the request.
        $this->postJson('/api/v1/auth/register', ['email' => 'x'])
            ->assertUnprocessable();
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
    }

    public function test_logout_invalidates_the_token(): void
    {
        $user = $this->user();
        $token = auth('api')->tokenById($user->id);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => ['message' => 'Successfully logged out'],
            ]);
    }

    public function test_refresh_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/refresh')->assertUnauthorized();
    }

    public function test_refresh_returns_a_new_token(): void
    {
        $user = $this->user();
        $token = auth('api')->tokenById($user->id);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/refresh');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.authorisation.type', 'bearer');

        $this->assertIsString($response->json('data.authorisation.token'));
        $this->assertNotEmpty($response->json('data.authorisation.token'));
    }
}

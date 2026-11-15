<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_log_in_and_receive_a_device_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reader@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => '  READER@EXAMPLE.COM  ',
            'password' => 'password',
            'device_name' => '  iPhone  ',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'reader@example.com')
            ->assertJsonMissingPath('data.user.password');

        $plainTextToken = $response->json('data.token');
        $storedToken = $user->tokens()->sole();
        [, $tokenSecret] = explode('|', $plainTextToken, 2);

        $this->assertSame('iPhone', $storedToken->name);
        $this->assertSame(hash('sha256', $tokenSecret), $storedToken->token);
        $this->assertNotSame($plainTextToken, $storedToken->token);
    }

    public function test_invalid_credentials_use_the_same_generic_response(): void
    {
        User::factory()->create([
            'email' => 'reader@example.com',
        ]);

        $wrongPassword = $this->postJson('/api/v1/auth/login', [
            'email' => 'reader@example.com',
            'password' => 'wrong-password',
            'device_name' => 'Browser',
        ]);

        $unknownEmail = $this->postJson('/api/v1/auth/login', [
            'email' => 'missing@example.com',
            'password' => 'wrong-password',
            'device_name' => 'Browser',
        ]);

        foreach ([$wrongPassword, $unknownEmail] as $response) {
            $response
                ->assertUnauthorized()
                ->assertJsonPath('error.code', 'INVALID_CREDENTIALS')
                ->assertJsonPath('error.message', 'The provided credentials are invalid.')
                ->assertJsonStructure(['error' => ['code', 'message', 'request_id']]);
        }

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_requires_valid_input(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => '',
            'device_name' => str_repeat('a', 101),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password', 'device_name'], 'error.fields');
    }

    public function test_login_is_rate_limited_after_repeated_authentication_failures(): void
    {
        User::factory()->create([
            'email' => 'reader@example.com',
        ]);

        $payload = [
            'email' => 'reader@example.com',
            'password' => 'wrong-password',
            'device_name' => 'Browser',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', $payload)->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', $payload)
            ->assertTooManyRequests()
            ->assertJsonPath('error.code', 'TOO_MANY_REQUESTS');
    }

    public function test_successful_logins_do_not_consume_the_failure_limit(): void
    {
        User::factory()->create([
            'email' => 'reader@example.com',
        ]);

        $payload = [
            'email' => 'reader@example.com',
            'password' => 'password',
            'device_name' => 'Browser',
        ];

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->postJson('/api/v1/auth/login', $payload)->assertOk();
        }
    }
}

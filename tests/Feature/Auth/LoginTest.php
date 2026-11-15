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

        $response->assertOk();
        $plainTextToken = $response->json('data.token');
        $this->assertIsString($plainTextToken);
        $response->assertExactJson([
            'data' => [
                'token' => $plainTextToken,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'username' => $user->username,
                    'birth_date' => $user->birth_date->toDateString(),
                    'phone_number' => $user->phone_number,
                    'email' => 'reader@example.com',
                    'role' => $user->role->value,
                ],
            ],
        ]);

        $storedToken = $user->tokens()->sole();
        [, $tokenSecret] = explode('|', $plainTextToken, 2);

        $this->assertSame('iPhone', $storedToken->name);
        $this->assertSame(hash('sha256', $tokenSecret), $storedToken->token);
        $this->assertNotSame($plainTextToken, $storedToken->token);
    }

    public function test_returns_401_with_generic_error_for_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'reader@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'reader@example.com',
            'password' => 'wrong-password',
            'device_name' => 'Browser',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS')
            ->assertJsonPath('error.message', 'The provided credentials are invalid.')
            ->assertJsonStructure(['error' => ['code', 'message', 'request_id']]);
        $this->assertStringNotContainsString('wrong-password', $response->getContent());
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_returns_401_with_same_generic_error_for_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'missing@example.com',
            'password' => 'wrong-password',
            'device_name' => 'Browser',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS')
            ->assertJsonPath('error.message', 'The provided credentials are invalid.')
            ->assertJsonStructure(['error' => ['code', 'message', 'request_id']]);
        $this->assertStringNotContainsString('wrong-password', $response->getContent());
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_returns_422_for_invalid_login_fields(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => '',
            'device_name' => str_repeat('a', 101),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath('error.fields.email.0', 'The email field must be a valid email address.')
            ->assertJsonPath('error.fields.password.0', 'The password field is required.')
            ->assertJsonPath('error.fields.device_name.0', 'The device name field must not be greater than 100 characters.');
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

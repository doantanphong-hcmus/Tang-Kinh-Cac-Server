<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_payload_creates_normalized_reader_and_returns_201_without_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validPayload([
            'full_name' => '  Nguyen Van An  ',
            'username' => '  Reader.One  ',
            'phone_number' => '(+84) 901-234-567',
            'email' => '  READER@EXAMPLE.COM  ',
        ]));

        $user = User::query()->sole();

        $response
            ->assertCreated()
            ->assertExactJson([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'full_name' => 'Nguyen Van An',
                        'username' => 'Reader.One',
                        'birth_date' => '1990-06-15',
                        'phone_number' => '+84901234567',
                        'email' => 'reader@example.com',
                        'role' => 'reader',
                    ],
                ],
            ]);

        $this->assertSame('reader.one', $user->username_normalized);
        $this->assertSame(UserRole::Reader, $user->role);
        $this->assertTrue(Hash::check('correct-horse-battery-staple', $user->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertGuest();
    }

    public function test_server_ignores_client_controlled_identity_and_role_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validPayload([
            'username_normalized' => 'attacker',
            'role' => 'admin',
        ]));

        $user = User::query()->sole();

        $response->assertCreated();
        $this->assertSame('reader.one', $user->username_normalized);
        $this->assertSame(UserRole::Reader, $user->role);
    }

    public function test_returns_409_when_normalized_email_is_already_used(): void
    {
        User::factory()->create(['email' => 'reader@example.com']);

        $response = $this->postJson('/api/v1/auth/register', $this->validPayload([
            'username' => 'another.reader',
            'email' => ' READER@EXAMPLE.COM ',
        ]));

        $response
            ->assertConflict()
            ->assertJsonPath('error.code', 'EMAIL_ALREADY_USED')
            ->assertJsonPath('error.message', 'The email address has already been used.');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_returns_409_when_normalized_username_is_already_used(): void
    {
        User::factory()->create(['username_normalized' => 'reader.one']);

        $response = $this->postJson('/api/v1/auth/register', $this->validPayload([
            'username' => ' READER.ONE ',
            'email' => 'another@example.com',
        ]));

        $response
            ->assertConflict()
            ->assertJsonPath('error.code', 'USERNAME_ALREADY_USED')
            ->assertJsonPath('error.message', 'The username has already been used.');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_returns_422_with_required_field_errors_for_empty_payload(): void
    {
        $response = $this->postJson('/api/v1/auth/register');

        $response
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors([
                'full_name',
                'username',
                'birth_date',
                'phone_number',
                'email',
                'password',
                'password_confirmation',
            ], 'error.fields');
        $this->assertDatabaseCount('users', 0);
    }

    #[DataProvider('invalidRegistrationFields')]
    public function test_returns_422_for_invalid_registration_field(
        string $field,
        mixed $value,
        string $errorField,
        string $message,
    ): void {
        $response = $this->postJson('/api/v1/auth/register', $this->validPayload([
            $field => $value,
        ]));

        $response
            ->assertUnprocessable()
            ->assertJsonPath("error.fields.{$errorField}.0", $message);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_returns_429_after_five_registration_attempts_for_the_same_identity_and_ip(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/register')->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/register')
            ->assertTooManyRequests()
            ->assertJsonPath('error.code', 'TOO_MANY_REQUESTS');
    }

    /**
     * @return array<string, array{string, mixed, string, string}>
     */
    public static function invalidRegistrationFields(): array
    {
        return [
            'short full name' => ['full_name', 'A', 'full_name', 'The full name field must be at least 2 characters.'],
            'invalid username characters' => ['username', 'reader-name', 'username', 'The username field format is invalid.'],
            'future birth date' => ['birth_date', '2999-01-01', 'birth_date', 'The birth date field must be a date before or equal to today.'],
            'invalid phone number' => ['phone_number', '1234', 'phone_number', 'The phone number field format is invalid.'],
            'invalid email' => ['email', 'not-an-email', 'email', 'The email field must be a valid email address.'],
            'short password' => ['password', 'short', 'password', 'The password field must be at least 8 characters.'],
            'mismatched confirmation' => ['password_confirmation', 'different-password', 'password', 'The password field confirmation does not match.'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_replace([
            'full_name' => 'Nguyen Van An',
            'username' => 'reader.one',
            'birth_date' => '1990-06-15',
            'phone_number' => '+84901234567',
            'email' => 'reader@example.com',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ], $overrides);
    }
}

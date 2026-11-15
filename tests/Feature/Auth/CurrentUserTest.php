<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CurrentUserTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('Android')->plainTextToken;

        $this->withToken($plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'username' => $user->username,
                    'birth_date' => $user->birth_date->toDateString(),
                    'phone_number' => $user->phone_number,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ],
            ]);
    }

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_returns_401_when_token_is_invalid(): void
    {
        $this->withToken('invalid-token')
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}

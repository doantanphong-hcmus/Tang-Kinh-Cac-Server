<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_logout_revokes_only_the_current_access_token(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('Current device');
        $otherToken = $user->createToken('Other device');

        $this->withToken($currentToken->plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $currentToken->accessToken->id,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $otherToken->accessToken->id,
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Still active');

        $this->postJson('/api/v1/auth/logout')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }
}

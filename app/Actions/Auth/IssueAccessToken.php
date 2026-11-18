<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Exceptions\InvalidCredentials;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class IssueAccessToken
{
    /**
     * @return array{user: User, token: string}
     */
    public function handle(string $email, #[\SensitiveParameter] string $password, string $deviceName): array
    {
        if (! Auth::validate(['email' => $email, 'password' => $password])) {
            throw new InvalidCredentials;
        }

        $user = User::query()->where('email', $email)->first() ?? throw new InvalidCredentials;

        return [
            'user' => $user,
            'token' => $user->createToken($deviceName)->plainTextToken,
        ];
    }
}

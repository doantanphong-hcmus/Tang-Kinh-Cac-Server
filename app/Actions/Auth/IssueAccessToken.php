<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Exceptions\InvalidCredentials;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class IssueAccessToken
{
    /**
     * @return array{user: User, token: string}
     */
    public function handle(string $email, string $password, string $deviceName): array
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw new InvalidCredentials;
        }

        return [
            'user' => $user,
            'token' => $user->createToken($deviceName)->plainTextToken,
        ];
    }
}

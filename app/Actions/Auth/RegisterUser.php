<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Exceptions\RegistrationConflict;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class RegisterUser
{
    /**
     * @param  array{
     *     full_name: string,
     *     username: string,
     *     username_normalized: string,
     *     birth_date: string,
     *     phone_number: string,
     *     email: string,
     *     password: string
     * }  $attributes
     */
    public function handle(array $attributes): User
    {
        try {
            return DB::transaction(function () use ($attributes): User {
                if (User::query()->where('email', $attributes['email'])->exists()) {
                    throw RegistrationConflict::emailAlreadyUsed();
                }

                if (User::query()->where('username_normalized', $attributes['username_normalized'])->exists()) {
                    throw RegistrationConflict::usernameAlreadyUsed();
                }

                $user = new User($attributes);
                $user->role = UserRole::Reader;
                $user->save();

                return $user;
            });
        } catch (UniqueConstraintViolationException $exception) {
            // Constraint mapping closes the race between the friendly pre-check and the insert.
            throw match ($exception->index) {
                'users_email_unique' => RegistrationConflict::emailAlreadyUsed(),
                'users_username_normalized_unique' => RegistrationConflict::usernameAlreadyUsed(),
                default => $exception,
            };
        }
    }
}

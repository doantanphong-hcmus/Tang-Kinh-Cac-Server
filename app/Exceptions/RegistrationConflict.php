<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

final class RegistrationConflict extends RuntimeException implements ShouldntReport
{
    private function __construct(
        public readonly string $apiCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function emailAlreadyUsed(): self
    {
        return new self('EMAIL_ALREADY_USED', 'The email address has already been used.');
    }

    public static function usernameAlreadyUsed(): self
    {
        return new self('USERNAME_ALREADY_USED', 'The username has already been used.');
    }
}

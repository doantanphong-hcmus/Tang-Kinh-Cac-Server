<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

final class ContentAccessDenied extends RuntimeException implements ShouldntReport
{
    public function __construct()
    {
        parent::__construct('Your account cannot access this content.');
    }
}

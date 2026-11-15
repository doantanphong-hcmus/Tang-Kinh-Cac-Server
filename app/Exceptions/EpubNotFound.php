<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

final class EpubNotFound extends RuntimeException implements ShouldntReport
{
    public function __construct()
    {
        parent::__construct('No EPUB is available for this book.');
    }
}

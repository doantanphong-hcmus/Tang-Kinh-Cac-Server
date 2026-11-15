<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

final class MediaDownloadController extends Controller
{
    public function __invoke(): never
    {
        // shortcut: E03 reserves the signed route; E04 replaces this with bounded file streaming.
        abort(501);
    }
}

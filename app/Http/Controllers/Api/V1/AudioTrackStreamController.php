<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

final class AudioTrackStreamController extends Controller
{
    public function __invoke(): never
    {
        // shortcut: AU02 reserves the signed route; AU03 replaces this with byte-range streaming.
        abort(501);
    }
}

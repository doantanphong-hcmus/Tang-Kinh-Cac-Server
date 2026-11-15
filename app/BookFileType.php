<?php

declare(strict_types=1);

namespace App;

enum BookFileType: string
{
    case Cover = 'cover';
    case Epub = 'epub';
    case Audio = 'audio';
}

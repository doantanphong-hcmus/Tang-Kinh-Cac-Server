<?php

declare(strict_types=1);

namespace App;

enum BookStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}

<?php

declare(strict_types=1);

namespace App;

enum UserRole: string
{
    case Reader = 'reader';
    case Admin = 'admin';
}

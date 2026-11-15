<?php

declare(strict_types=1);

namespace App;

enum BookAccessLevel: string
{
    case Free = 'free';
    case Vip = 'vip';
}

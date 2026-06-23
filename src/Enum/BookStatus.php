<?php

declare(strict_types=1);

namespace App\Enum;

enum BookStatus: string
{
    case Available = 'available';
    case Borrowed = 'borrowed';
}

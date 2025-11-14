<?php

namespace App\Enums;

enum ItemStatus: string
{
    case OPEN = 'open';
    case CLAIMED = 'claimed';
    case CLOSED = 'closed';
}

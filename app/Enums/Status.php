<?php

namespace App\Enums;

enum Status: string
{
    case Unconfirmed = 'unconfirmed';
    case Confirmed = 'confirmed';
}

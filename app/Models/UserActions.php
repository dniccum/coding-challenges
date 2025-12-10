<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActions extends Model
{
    /** @use HasFactory<\Database\Factories\UserActionsFactory> */
    use HasFactory;

    public $casts = [
        'rules' => 'array',
    ];
}

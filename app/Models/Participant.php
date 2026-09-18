<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $fillable = [
        'name',
        'document',
        'email',
        'phone',
        'license',
        'team',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participante extends Model
{
    protected $table = 'participantes';

    protected $fillable = [
        'name',
        'document',
        'email',
        'phone',
        'license',
        'team',
    ];

    public function registrations(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'participant_id');
    }
}

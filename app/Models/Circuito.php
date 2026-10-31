<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Circuito extends Model
{
    protected $table = 'circuitos';

    protected $fillable = [
        'organizer_id',
        'name',
        'image_path',
    ];

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function events()
    {
        return $this->hasMany(Evento::class, 'circuit_id');
    }
}
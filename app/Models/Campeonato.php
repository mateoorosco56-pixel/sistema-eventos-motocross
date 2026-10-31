<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campeonato extends Model
{
    use HasFactory;

    protected $table = 'campeonatos';

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'total_rounds',
        'status',
        'organizer_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'total_rounds' => 'integer',
    ];

    public function events()
    {
        return $this->hasMany(Evento::class, 'championship_id')->orderBy('round_number');
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function scoringRules()
    {
        return $this->hasMany(PuntuacionCampeonato::class, 'championship_id');
    }
}

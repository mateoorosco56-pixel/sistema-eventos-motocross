<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PuntuacionCampeonato extends Model
{
    protected $table = 'puntuaciones_campeonato';

    protected $fillable = [
        'championship_id',
        'category_id',
        'round_number',
        'position',
        'points',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'round_number' => 'integer',
        'position' => 'integer',
        'points' => 'integer',
    ];

    public function championship(): BelongsTo
    {
        return $this->belongsTo(Campeonato::class, 'championship_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'category_id');
    }
}

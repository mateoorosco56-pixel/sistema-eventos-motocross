<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PremioEvento extends Model
{
    protected $table = 'premios_evento';

    protected $fillable = [
        'event_id',
        'category_id',
        'position',
        'award_type',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Evento::class, 'event_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'category_id');
    }
}

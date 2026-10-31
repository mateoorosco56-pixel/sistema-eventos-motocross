<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenSalidaEvento extends Model
{
    protected $table = 'ordenes_salida_evento';

    protected $fillable = [
        'event_id',
        'category_id',
        'position',
        'scheduled_time',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
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

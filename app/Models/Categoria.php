<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = [
        'name',
        'description',
    ];

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Evento::class, 'categoria_evento', 'category_id', 'event_id');
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(PremioEvento::class, 'category_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'category_id');
    }

    public function startingOrders(): HasMany
    {
        return $this->hasMany(OrdenSalidaEvento::class, 'category_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    public const ACTIVE_STATUSES = ['pendiente', 'confirmada'];

    protected $table = 'inscripciones';

    protected $fillable = [
        'event_id',
        'user_id',
        'participant_id',
        'category_id',
        'dorsal',
        'status',
        'cancellation_reason',
        'payment_proof_path',
        'payment_status',
        'payment_submitted_at',
        'participated',
        'result_position',
        'result_points',
    ];

    protected $casts = [
        'participated' => 'boolean',
        'payment_submitted_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function event()
    {
        return $this->belongsTo(Evento::class, 'event_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participante::class, 'participant_id');
    }

    public function category()
    {
        return $this->belongsTo(Categoria::class, 'category_id');
    }
}

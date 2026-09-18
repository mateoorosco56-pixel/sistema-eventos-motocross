<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    public const ACTIVE_STATUSES = ['pendiente', 'confirmada'];

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

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

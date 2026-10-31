<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudOrganizador extends Model
{
    protected $table = 'solicitudes_organizador';

    protected $fillable = [
        'user_id',
        'status',
        'reason',
        'admin_note',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
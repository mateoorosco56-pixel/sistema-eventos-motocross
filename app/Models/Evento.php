<?php

namespace App\Models;

use App\Models\Categoria;
use App\Models\Circuito;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    use HasFactory;

    protected $table = 'eventos';

    protected $fillable = [
        'title',
        'description',
        'event_date',
        'start_time',
        'location',
        'circuit_id',
        'organizer_whatsapp',
        'latitude',
        'longitude',
        'status',
        'results_published',
        'category_id',
        'entry_fee',
        'payment_qr_path',
        'event_background_path',
        'payment_instructions',
        'max_participants',
        'registration_deadline',
        'championship_id',
        'round_number',
    ];

    protected $casts = [
        'event_date' => 'date',
        'registration_deadline' => 'date',
        'results_published' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function category()
    {
        return $this->belongsTo(Categoria::class, 'category_id');
    }

    public function circuit()
    {
        return $this->belongsTo(Circuito::class, 'circuit_id');
    }

    public function championship()
    {
        return $this->belongsTo(Campeonato::class, 'championship_id');
    }

    public function getCoverPathAttribute(): ?string
    {
        return $this->circuit?->image_path ?? $this->event_background_path;
    }

    public function categories()
    {
        return $this->belongsToMany(Categoria::class, 'categoria_evento', 'event_id', 'category_id')
            ->withPivot('entry_fee');
    }

    public function registrations()
    {
        return $this->hasMany(Inscripcion::class, 'event_id');
    }

    public function prizes()
    {
        return $this->hasMany(PremioEvento::class, 'event_id')->orderBy('category_id')->orderBy('position');
    }

    public function startingOrders()
    {
        return $this->hasMany(OrdenSalidaEvento::class, 'event_id')->orderBy('position');
    }

    public function syncStatusByDate(): void
    {
        if (! $this->event_date || ! in_array($this->status, ['programado', 'en_curso'], true)) {
            return;
        }

        $eventDate = Carbon::parse($this->event_date)->startOfDay();

        if ($eventDate->isBefore(Carbon::today())) {
            $this->status = 'finalizado';
            $this->save();
        }
    }
}

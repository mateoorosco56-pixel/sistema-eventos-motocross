<?php

namespace App\Models;

use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'title',
        'description',
        'event_date',
        'location',
        'latitude',
        'longitude',
        'status',
        'results_published',
        'category_id',
        'entry_fee',
        'payment_qr_path',
        'payment_instructions',
        'max_participants',
        'registration_deadline',
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
        return $this->belongsTo(Category::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'event_category');
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
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

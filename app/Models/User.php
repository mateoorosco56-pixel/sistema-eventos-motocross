<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
    'name',
    'first_name',
    'last_name',
    'email',
    'password',
    'role',
    'identity_document_number',
    'identity_processing_consent_at',
    'verification_status',
    'document_path',
    'document_back_path',
    'selfie_path',
    'verification_result',
    'verified_at',
    'verification_method',
    'verification_reviewed_by',
    'verification_reviewed_at',
    'verification_rejection_reason',
    'profile_photo_path',
];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verified_at' => 'datetime',
            'verification_reviewed_at' => 'datetime',
            'identity_processing_consent_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function verificationReviewer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'verification_reviewed_by');
    }
}

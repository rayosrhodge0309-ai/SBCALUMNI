<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

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
        'email',
        'password',
        'role',
        'account_status',
        'approved_at',
        'portal_otp_verified_at',
        'alumni_id',
        'profile_photo_path',
        'fcm_token',
        'registration_details',
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
            'password' => 'hashed',
            'approved_at' => 'datetime',
            'portal_otp_verified_at' => 'datetime',
            'fcm_token' => 'string',
            'registration_details' => 'array',
        ];
    }

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class);
    }

    public function getRegistrationProfileAttribute(): Alumni
    {
        if ($this->registration_details !== null) {
            $details = $this->registration_details;

            // School records and imports store given and middle names together in first_name.
            if (filled($details['middle_name'] ?? null)) {
                $details['first_name'] = trim(($details['first_name'] ?? '').' '.$details['middle_name']);
            }
            unset($details['middle_name']);

            return new Alumni($details + ['email' => $this->email]);
        }

        return $this->alumni ?? new Alumni([
            'first_name' => $this->name,
            'last_name' => '',
            'email' => $this->email,
        ]);
    }

    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function eventRegistrationReplies(): HasMany
    {
        return $this->hasMany(EventRegistration::class, 'replied_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAlumni(): bool
    {
        return $this->role === 'alumni';
    }

    public function isApproved(): bool
    {
        return $this->account_status === 'approved';
    }

    public function isPendingApproval(): bool
    {
        return $this->account_status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->account_status === 'rejected';
    }

    public function hasCompletedPortalOtp(): bool
    {
        return $this->portal_otp_verified_at !== null;
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (! $this->profile_photo_path) {
            return null;
        }

        return route('profile.photo', [
            'user' => $this,
            'v' => $this->updated_at?->timestamp ?? time(),
        ]);
    }

    public function getInitialsAttribute(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }
}

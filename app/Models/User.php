<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $casts = [
       'is_banned' => 'boolean',
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'group_id',
        'office_id',
        'is_banned',
        'image_url',
        'face_embedding',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    // Implementasi metode canAccessPanel() dari FilamentUser contract
    public function canAccessPanel(Panel $panel): bool
    {
        // Mengizinkan semua pengguna untuk mengakses panel
        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->image_url
            ? asset('storage/' . $this->image_url) // Gambar avatar pengguna
            : asset('images/default_image.png'); // Gambar default jika avatar tidak ada
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Set email_verified_at to current datetime if it's not already set.
            if (is_null($user->email_verified_at)) {
                $user->email_verified_at = now();
            }
        });

        static::deleting(function ($user) {
            if ($user->image_url) {
                // Hapus file dari storage
                Storage::disk('public')->delete($user->image_url);
            }
        });
    }
}

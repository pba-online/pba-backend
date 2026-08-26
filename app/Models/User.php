<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
    public $timestamps = false;
    protected $table = 'accounts';
    
    protected $fillable = [
        'name',
        'full_name',
        'email',
        'password_hash',
        'role',
        'team_id',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_hash' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTeam(): bool
    {
        return $this->role === 'team';
    }

    public function toAuthArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'team_id' => $this->team_id,
        ];
    }
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}

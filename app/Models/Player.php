<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Player extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'photo_url',
        'age',
        'height_inches',
        'weight_lbs',
        'position',
    ];

    protected $appends = ['name', 'height', 'weight'];

    public function getNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
    }

    public function getHeightAttribute(): ?string
    {
        if ($this->height_inches === null) {
            return null;
        }
        $feet = intdiv((int) $this->height_inches, 12);
        $inches = (int) $this->height_inches % 12;

        return "{$feet}'{$inches}\"";
    }

    public function getWeightAttribute(): ?int
    {
        return $this->weight_lbs;
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(PlayerRating::class);
    }

    public function ratingForSeason(?int $seasonId = null): HasOne
    {
        $relation = $this->hasOne(PlayerRating::class);
        if ($seasonId) {
            $relation->where('season_id', $seasonId);
        }

        return $relation;
    }

    public function rosters(): HasMany
    {
        return $this->hasMany(TeamRoster::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}

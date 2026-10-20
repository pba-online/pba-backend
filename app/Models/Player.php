<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property \Illuminate\Support\Carbon|null $birth_date
 * @property string|null $height
 * @property int|null $weight_lbs
 * @property string|null $position
 * @property string|null $photo_url
 * @property string $status
 * @property int|null $jersey_number
 * @property string|null $nationality
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $name
 * @property-read int|null $age
 * @property-read int|null $height_inches
 * @property-read int|null $weight
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PlayerRating> $ratings
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TeamRoster> $rosters
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Contract> $contracts
 */
class Player extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'age',
        'height_inches',
        'birth_date',
        'height',
        'photo_url',
        'weight_lbs',
        'position',
        'jersey_number',
        'nationality',
        'status',
    ];

    protected $appends = ['name', 'height', 'weight'];

    public function getNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
    }

    public function getHeightAttribute(): ?string
    {
        if (isset($this->attributes['height'])) {
            return $this->attributes['height'];
        }

        $heightInches = $this->attributes['height_inches'] ?? null;
        if ($heightInches === null) {
            return null;
        }

        return intdiv((int) $heightInches, 12)."'".((int) $heightInches % 12).'"';
    }

    public function getWeightAttribute(): ?int
    {
        return $this->weight_lbs;
    }

    public function getAgeAttribute(): ?int
    {
        if (isset($this->attributes['age'])) {
            return (int) $this->attributes['age'];
        }

        return $this->birth_date
            ? \Illuminate\Support\Carbon::parse($this->birth_date)->age
            : null;
    }

    public function getHeightInchesAttribute(): ?int
    {
        if (isset($this->attributes['height_inches'])) {
            return (int) $this->attributes['height_inches'];
        }

        if (! preg_match('/^(\d+)[\'\x{2019}](\d+)(?:"|in)?$/u', trim((string) $this->height), $matches)) {
            return null;
        }

        return ((int) $matches[1] * 12) + (int) $matches[2];
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

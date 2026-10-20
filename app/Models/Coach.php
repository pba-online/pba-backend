<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $first_name
 * @property string|null $last_name
 * @property \Illuminate\Support\Carbon|null $birth_date
 * @property string|null $role
 * @property string|null $photo_url
 * @property string|null $status
 * @property-read string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TeamCoach> $teamAssignments
 */
class Coach extends Model
{
    protected $fillable = ['name', 'first_name', 'last_name', 'photo_url', 'role'];

    protected $appends = ['name'];

    public function getNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
    }

    public function setNameAttribute(string $name): void
    {
        [$firstName, $lastName] = array_pad(preg_split('/\s+/', trim($name), 2), 2, '');
        $this->attributes['first_name'] = $firstName;
        $this->attributes['last_name'] = $lastName;
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(CoachRating::class);
    }

    public function latestRating(): HasOne
    {
        return $this->hasOne(CoachRating::class)->latestOfMany();
    }

    public function teamAssignments(): HasMany
    {
        return $this->hasMany(TeamCoach::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}

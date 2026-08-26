<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Team extends Model
{
    protected $fillable = [
        'name',
        'abbreviation',
        'city',
        'division_id',
        'venue_id',
        'primary_color',
        'secondary_color',
        'logo_url',
        'founded_year',
        'status',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(TeamOwner::class);
    }

    public function owners(): HasMany
    {
        return $this->hasMany(TeamOwner::class);
    }

    public function rosters(): HasMany
    {
        return $this->hasMany(TeamRoster::class);
    }

    public function coaches(): HasMany
    {
        return $this->hasMany(TeamCoach::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function standings(): HasMany
    {
        return $this->hasMany(Standing::class);
    }

    public function draftPicks(): HasMany
    {
        return $this->hasMany(DraftPick::class);
    }
}
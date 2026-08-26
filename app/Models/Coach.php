<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Coach extends Model
{
    protected $fillable = ['name', 'photo_url', 'role'];

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

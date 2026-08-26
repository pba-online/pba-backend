<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    protected $fillable = [
        'season_year',
        'conference_season',
        'conference_id',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    public static function current(): ?self
    {
        return static::where('is_current', true)->first();
    }
}
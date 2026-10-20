<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $season_year
 * @property bool $is_current
 * @property int|null $conference_id
 * @property string|null $conference_season
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $season_id
 * @property string $name
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Playoff extends Model
{
    protected $fillable = ['season_id', 'name'];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function series(): HasMany
    {
        return $this->hasMany(PlayoffSeries::class);
    }
}

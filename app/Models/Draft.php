<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $season_id
 * @property int $draft_year
 * @property string|null $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DraftPick> $picks
 */
class Draft extends Model
{
    protected $fillable = ['year', 'season_id', 'name'];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function picks(): HasMany
    {
        return $this->hasMany(DraftPick::class);
    }
}

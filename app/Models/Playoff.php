<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

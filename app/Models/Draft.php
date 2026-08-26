<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

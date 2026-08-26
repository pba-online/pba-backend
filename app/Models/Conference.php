<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conference extends Model
{
    protected $fillable = ['name', 'description'];

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class);
    }
}
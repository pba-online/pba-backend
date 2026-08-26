<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Venue extends Model
{
    protected $fillable = ['homecourt', 'city', 'capacity', 'team_id'];

    public function team(): HasOne
    {
        return $this->hasOne(Team::class);
    }
}

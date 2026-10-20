<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string|null $homecourt
 * @property string|null $city
 * @property int|null $capacity
 * @property string|null $image_url
 * @property int|null $team_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Team|null $team
 */
class Venue extends Model
{
    protected $fillable = ['homecourt', 'city', 'capacity', 'team_id'];

    public function team(): HasOne
    {
        return $this->hasOne(Team::class);
    }
}

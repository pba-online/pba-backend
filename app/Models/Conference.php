<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Season> $seasons
 */
class Conference extends Model
{
    protected $table = 'conference';

    protected $fillable = ['name', 'description'];

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class);
    }
}
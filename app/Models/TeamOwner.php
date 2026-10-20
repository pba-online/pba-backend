<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $team_id
 * @property string $owner_name
 * @property string|null $company
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Team|null $team
 */
class TeamOwner extends Model
{
    protected $table = 'team_owner';

    protected $fillable = ['team_id', 'owner_name', 'company'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
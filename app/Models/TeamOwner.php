<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamOwner extends Model
{
    protected $table = 'team_owner';

    protected $fillable = ['team_id', 'owner_name', 'company'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $contract_id
 * @property int $season_id
 * @property int $salary
 * @property bool|null $player_option
 * @property bool|null $team_option
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Contract|null $contract
 * @property-read Season|null $season
 */
class ContractYear extends Model
{
    protected $fillable = [
        'contract_id',
        'season_id',
        'salary',
        'year_number',
    ];

    protected function casts(): array
    {
        return [
            'salary' => 'integer',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}

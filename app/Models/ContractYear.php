<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

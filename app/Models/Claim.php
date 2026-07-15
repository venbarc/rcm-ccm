<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_type', 'external_id', 'patient_name', 'date_of_service', 'payer',
        'provider', 'cpt_code', 'billed_amount', 'balance', 'status', 'priority',
        'assigned_to', 'notes', 'last_import_id',
    ];

    protected function casts(): array
    {
        return [
            'date_of_service' => 'date:Y-m-d',
            'billed_amount' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ClaimActivity::class);
    }
}

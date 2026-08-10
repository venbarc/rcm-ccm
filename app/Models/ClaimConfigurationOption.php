<?php

namespace App\Models;

use App\Models\Concerns\UsesAccountScopedTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClaimConfigurationOption extends Model
{
    use SoftDeletes, UsesAccountScopedTable;

    protected $fillable = [
        'account_type',
        'option_type',
        'system_key',
        'value',
        'label',
        'color',
        'sort_order',
        'added_by',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\UsesAccountScopedTable;
use Illuminate\Database\Eloquent\Model;

class ClaimConfigurationSystemDefault extends Model
{
    use UsesAccountScopedTable;

    protected $fillable = [
        'account_type',
        'option_type',
        'system_key',
        'value',
        'label',
        'color',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimConfigurationSystemDefault extends Model
{
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

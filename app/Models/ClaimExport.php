<?php

namespace App\Models;

use App\Models\Concerns\UsesAccountScopedTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimExport extends Model
{
    use UsesAccountScopedTable;

    protected $fillable = [
        'account_type', 'user_id', 'file_name', 'file_path', 'status',
        'total_rows', 'processed_rows', 'total_chunks', 'processed_chunks',
        'filters', 'error_message', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

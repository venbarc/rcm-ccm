<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimImport extends Model
{
    protected $fillable = [
        'account_type', 'file_name', 'stored_path', 'status', 'created_count',
        'updated_count', 'skipped_count', 'failed_count', 'error_message', 'imported_by',
        'total_rows', 'processed_rows', 'total_chunks', 'processed_chunks', 'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}

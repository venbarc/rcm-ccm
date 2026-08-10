<?php

namespace App\Models;

use App\Models\Concerns\UsesAccountScopedTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimRawRow extends Model
{
    use UsesAccountScopedTable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['raw_payload' => 'array'];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(ClaimImport::class, 'claim_import_id');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\UsesAccountScopedTable;
use Illuminate\Database\Eloquent\Model;

class ClaimImportSnapshot extends Model
{
    use UsesAccountScopedTable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['snapshot_data' => 'array'];
    }
}

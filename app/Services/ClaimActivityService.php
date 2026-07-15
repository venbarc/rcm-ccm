<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\ClaimActivity;
use App\Models\User;

class ClaimActivityService
{
    /** @param array<string, mixed>|null $before @param array<string, mixed>|null $after */
    public function record(string $account, string $action, string $description, ?User $user, ?Claim $claim = null, ?array $before = null, ?array $after = null): ClaimActivity
    {
        return ClaimActivity::create([
            'account_type' => $account,
            'claim_id' => $claim?->id,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'before' => $before,
            'after' => $after,
        ]);
    }
}

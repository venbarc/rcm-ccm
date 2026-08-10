<?php

namespace App\Support;

use App\Enums\AccountType;
use Illuminate\Http\Request;

class CurrentAccount
{
    public static function resolve(Request $request): AccountType
    {
        $account = AccountContext::activeAccount($request);

        abort_unless($account->isReady(), 403, 'Select an available CCM account in One Access.');
        abort_unless($request->user()?->canAccessAccount($account), 403, 'You do not have access to this CCM account.');

        return $account;
    }
}

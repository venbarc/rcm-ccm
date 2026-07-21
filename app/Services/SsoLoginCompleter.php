<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SsoLoginCompleter
{
    public function complete(Request $request, User $user, ?AccountType $account): RedirectResponse
    {
        if ($account && ! $account->isReady()) {
            abort(403, 'This CCM account is not available yet.');
        }

        $updates = [];

        if ($account && ! $user->is_admin && ! $user->canAccessAccount($account)) {
            $updates['account_types'] = array_values(array_unique([
                ...($user->account_types ?? []),
                $account->value,
            ]));
        }

        if ($account) {
            $request->session()->put('account_type', $account->value);
        }

        // OneAccess is the source of truth for identity and account authorization.
        $updates['email_verified_at'] = $user->email_verified_at ?? now();
        $user->forceFill($updates)->save();

        Auth::login($user, remember: false);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}

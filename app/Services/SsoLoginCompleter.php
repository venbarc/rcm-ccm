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

        if ($account && ! $user->canAccessAccount($account)) {
            abort(403, 'You do not have access to this CCM account.');
        }

        if ($account) {
            $request->session()->put('account_type', $account->value);
        }

        // OneAccess has already authenticated the identity and authorized the account.
        $user->forceFill([
            'email_verified_at' => $user->email_verified_at ?? now(),
            'is_approved' => true,
        ])->save();

        Auth::login($user, remember: false);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}

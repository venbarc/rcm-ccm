<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrentAccountController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_type' => ['required', Rule::enum(AccountType::class)],
        ]);

        $account = AccountType::from($validated['account_type']);
        $user = $request->user();

        if (! $account->isReady()) {
            return back()->with('error', 'This CCM account is not available yet.');
        }

        if (! $user->canAccessAccount($account)) {
            return back()->with('error', 'You do not have access to this CCM account.');
        }

        $request->session()->put('account_type', $account->value);

        return back()->with('success', "Switched account to {$account->label()}.");
    }
}

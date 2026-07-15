<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SsoLoginCompleter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SsoChooseAccountController extends Controller
{
    public function __construct(private readonly SsoLoginCompleter $loginCompleter) {}

    public function create(Request $request): Response|RedirectResponse
    {
        $pending = $request->session()->get('sso_choose_account');
        if (! is_array($pending)) {
            return redirect()->route('login');
        }

        return Inertia::render('auth/sso-choose-account', [
            'accounts' => User::query()->whereIn('id', $pending['user_ids'])->get(['id', 'email']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('sso_choose_account');
        if (! is_array($pending)) {
            return redirect()->route('login');
        }

        $validated = $request->validate(['user_id' => ['required', 'integer']]);
        if (! in_array($validated['user_id'], $pending['user_ids'], true)) {
            throw ValidationException::withMessages(['user_id' => 'Choose one of the accounts shown.']);
        }

        $request->session()->forget('sso_choose_account');

        return $this->loginCompleter->complete(
            $request,
            User::findOrFail($validated['user_id']),
            AccountType::tryFrom((string) $pending['account_type']),
        );
    }
}

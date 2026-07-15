<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\User;
use App\Services\ClaimActivityService;
use App\Support\CurrentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function __construct(private readonly ClaimActivityService $activities) {}

    public function index(Request $request): Response
    {
        CurrentAccount::resolve($request);

        return Inertia::render('users/index', [
            'users' => User::query()->orderBy('is_approved')->orderBy('name')->paginate(25),
            'accountTypes' => AccountType::options(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $account = CurrentAccount::resolve($request);
        $validated = $request->validate([
            'is_approved' => ['required', 'boolean'],
            'is_admin' => ['required', 'boolean'],
            'can_assign_claims' => ['required', 'boolean'],
            'account_types' => ['required', 'array'],
            'account_types.*' => ['string', 'in:'.implode(',', AccountType::values())],
        ]);
        abort_if($user->is($request->user()) && ! $validated['is_admin'], 422, 'You cannot remove your own admin access.');

        $before = $user->only(array_keys($validated));
        $user->update($validated);
        $this->activities->record($account->value, 'user_updated', "Updated access for {$user->email}", $request->user(), before: $before, after: $validated);

        return back()->with('success', 'User access updated.');
    }
}

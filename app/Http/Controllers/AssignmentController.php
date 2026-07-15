<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\User;
use App\Services\ClaimActivityService;
use App\Support\CurrentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    public function __construct(private readonly ClaimActivityService $activities) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->canAssignClaims(), 403);
        $account = CurrentAccount::resolve($request);

        return Inertia::render('assignments/index', [
            'claims' => Claim::query()->with('assignee:id,name')->where('account_type', $account->value)->whereNull('assigned_to')->latest('date_of_service')->paginate(30),
            'assignees' => User::query()->where('is_approved', true)->where(function ($query) use ($account): void {
                $query->where('is_admin', true)->orWhereJsonContains('account_types', $account->value);
            })->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canAssignClaims(), 403);
        $account = CurrentAccount::resolve($request);
        $validated = $request->validate([
            'claim_ids' => ['required', 'array', 'min:1'],
            'claim_ids.*' => ['integer'],
            'user_id' => ['required', 'exists:users,id'],
        ]);
        $assignee = User::findOrFail($validated['user_id']);
        abort_unless($assignee->is_approved && $assignee->canAccessAccount($account), 422, 'Choose an approved Tricity user.');

        $claims = Claim::query()->where('account_type', $account->value)->whereIn('id', $validated['claim_ids'])->get();
        DB::transaction(function () use ($claims, $validated, $account, $request): void {
            foreach ($claims as $claim) {
                $before = ['assigned_to' => $claim->assigned_to];
                $claim->update(['assigned_to' => $validated['user_id'], 'status' => $claim->status === 'new' ? 'in_progress' : $claim->status]);
                $this->activities->record($account->value, 'assigned', "Assigned claim {$claim->external_id}", $request->user(), $claim, $before, ['assigned_to' => $validated['user_id']]);
            }
        });

        return back()->with('success', "Assigned {$claims->count()} claims.");
    }

    public function distribute(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canAssignClaims(), 403);
        $account = CurrentAccount::resolve($request);
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);
        $validAssigneeCount = User::query()
            ->whereIn('id', $validated['user_ids'])
            ->where('is_approved', true)
            ->where(function ($query) use ($account): void {
                $query->where('is_admin', true)->orWhereJsonContains('account_types', $account->value);
            })
            ->count();
        abort_unless($validAssigneeCount === count(array_unique($validated['user_ids'])), 422, 'Choose only approved Tricity users.');

        $claims = Claim::query()->where('account_type', $account->value)->whereNull('assigned_to')->orderByDesc('balance')->get();

        DB::transaction(function () use ($claims, $validated): void {
            foreach ($claims as $index => $claim) {
                $claim->update([
                    'assigned_to' => $validated['user_ids'][$index % count($validated['user_ids'])],
                    'status' => $claim->status === 'new' ? 'in_progress' : $claim->status,
                ]);
            }
        });
        $this->activities->record($account->value, 'auto_assigned', "Distributed {$claims->count()} unassigned claims", $request->user(), after: ['user_ids' => $validated['user_ids']]);

        return back()->with('success', "Distributed {$claims->count()} claims.");
    }
}

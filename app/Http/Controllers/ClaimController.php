<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\User;
use App\Services\ClaimActivityService;
use App\Support\CurrentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClaimController extends Controller
{
    public function __construct(private readonly ClaimActivityService $activities) {}

    public function index(Request $request): Response
    {
        $account = CurrentAccount::resolve($request);
        $query = Claim::query()->with('assignee:id,name,email')->where('account_type', $account->value);

        $query->when($request->string('search')->trim()->toString(), function ($query, string $search): void {
            $query->where(function ($nested) use ($search): void {
                $nested->where('external_id', 'like', "%{$search}%")
                    ->orWhere('patient_name', 'like', "%{$search}%")
                    ->orWhere('payer', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%");
            });
        });
        $query->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')));
        $query->when($request->input('assigned_to') === 'unassigned', fn ($query) => $query->whereNull('assigned_to'));
        $query->when(is_numeric($request->input('assigned_to')), fn ($query) => $query->where('assigned_to', $request->integer('assigned_to')));

        return Inertia::render('claims/index', [
            'claims' => $query->latest('date_of_service')->latest('id')->paginate(25)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'assigned_to']),
            'assignees' => User::query()->where('is_approved', true)->orderBy('name')->get(['id', 'name', 'email']),
            'statuses' => ['new', 'in_progress', 'pending', 'denied', 'appealed', 'paid', 'closed'],
        ]);
    }

    public function update(Request $request, Claim $claim): RedirectResponse
    {
        $account = CurrentAccount::resolve($request);
        abort_unless($claim->account_type === $account->value, 404);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['new', 'in_progress', 'pending', 'denied', 'appealed', 'paid', 'closed'])],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);
        if (array_key_exists('assigned_to', $validated)) {
            abort_unless($request->user()->canAssignClaims(), 403);
            if ($validated['assigned_to'] !== null) {
                $assignee = User::findOrFail($validated['assigned_to']);
                abort_unless($assignee->is_approved && $assignee->canAccessAccount($account), 422, 'Choose an approved Tricity user.');
            }
        }

        $before = $claim->only(array_keys($validated));
        $claim->update($validated);
        $this->activities->record($account->value, 'claim_updated', "Updated claim {$claim->external_id}", $request->user(), $claim, $before, $claim->only(array_keys($validated)));

        return back()->with('success', 'Claim updated.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\User;
use App\Services\ClaimActivityService;
use App\Services\ClaimAssignmentService;
use App\Services\TeamService;
use App\Support\CurrentAccount;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly ClaimActivityService $activities,
        private readonly ClaimAssignmentService $assignments,
        private readonly TeamService $teams,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorizeAssignment($request);
        $account = CurrentAccount::resolve($request);
        $assignees = $this->availableAssignees($request->user(), $account->value);
        $snapshot = $this->assignments->assignmentSnapshot($account->value, $assignees);

        return Inertia::render('assignments/index', [
            'summary' => $snapshot['summary'],
            'assignmentWorkloads' => $snapshot['workloads'],
            'groupDefinitions' => $this->assignments->groupDefinitions($account->value),
            'assignees' => $assignees,
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorizeAssignment($request);
        $account = CurrentAccount::resolve($request);
        $validated = $request->validate([
            'group_by' => ['required', 'string'],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:200'],
        ]);
        $groupBy = trim($validated['group_by']);
        abort_unless($this->assignments->isValidGroup($groupBy) && $groupBy !== 'all', 422, 'Choose a valid distribution group.');

        return response()->json(
            $this->assignments->options(
                $account->value,
                $groupBy,
                $validated['search'] ?? null,
                (int) ($validated['page'] ?? 1),
                (int) ($validated['per_page'] ?? 10),
            )
        );
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorizeAssignment($request);
        $account = CurrentAccount::resolve($request);
        $validated = $request->validate([
            'group_by' => ['required', 'string'],
            'group_values' => ['nullable', 'array', 'max:250'],
            'group_values.*' => ['string', 'max:255'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $groupBy = (string) $validated['group_by'];
        $groupValues = $validated['group_values'] ?? [];
        $this->validateGroupSelection($groupBy, $groupValues);
        $assignees = $this->eligibleAssignees($request->user(), $validated['user_ids'], $account->value);

        return response()->json($this->assignments->preview($account->value, $groupBy, $groupValues, $assignees));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAssignment($request);
        $account = CurrentAccount::resolve($request);
        $validated = $request->validate([
            'claim_ids' => ['required', 'array', 'min:1'],
            'claim_ids.*' => ['integer'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);
        $assignee = $this->eligibleAssignees($request->user(), [(int) $validated['user_id']], $account->value)->firstOrFail();
        $billIds = Claim::query()
            ->where('account_type', $account->value)
            ->whereIn('id', $validated['claim_ids'])
            ->pluck('bill_id')
            ->unique()
            ->values();

        abort_if($billIds->isEmpty(), 422, 'Choose at least one Bill ID.');

        $lineCount = DB::transaction(function () use ($billIds, $assignee, $account, $request): int {
            $claims = Claim::query()
                ->where('account_type', $account->value)
                ->whereIn('bill_id', $billIds)
                ->lockForUpdate()
                ->get();

            foreach ($claims as $claim) {
                $before = ['assigned_to' => $claim->assigned_to];
                $claim->update([
                    'assigned_to' => $assignee->id,
                    'status' => $claim->status === 'new' ? 'in_progress' : $claim->status,
                ]);
                $this->activities->record(
                    $account->value,
                    'assigned',
                    "Assigned Bill ID {$claim->bill_id}",
                    $request->user(),
                    $claim,
                    $before,
                    ['assigned_to' => $assignee->id],
                );
            }

            return $claims->count();
        });

        return back()->with('success', "Assigned {$billIds->count()} Bill IDs ({$lineCount} CPT lines) to {$assignee->name}.");
    }

    public function distribute(Request $request): RedirectResponse
    {
        $this->authorizeAssignment($request);
        $account = CurrentAccount::resolve($request);
        $validated = $request->validate([
            'group_by' => ['required', 'string'],
            'group_values' => ['nullable', 'array', 'max:250'],
            'group_values.*' => ['string', 'max:255'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $groupBy = (string) $validated['group_by'];
        $groupValues = $validated['group_values'] ?? [];
        $this->validateGroupSelection($groupBy, $groupValues);
        $assignees = $this->eligibleAssignees($request->user(), $validated['user_ids'], $account->value);
        $result = $this->assignments->distribute($account->value, $groupBy, $groupValues, $assignees);

        if ($result['total_claims'] === 0) {
            return back()->with('error', 'No unassigned claim groups match that distribution scope.');
        }

        $this->activities->record(
            $account->value,
            'auto_assigned',
            "Distributed {$result['total_claims']} claim groups ({$result['total_lines']} CPT lines)",
            $request->user(),
            after: [
                'group_by' => $groupBy,
                'group_values' => $groupValues,
                'user_ids' => $assignees->pluck('id')->all(),
                'target_true_balance' => $result['target_balance'],
            ],
        );

        return back()->with(
            'success',
            "Distributed {$result['total_claims']} Bill IDs ({$result['total_lines']} CPT lines) without splitting Bill IDs.",
        );
    }

    private function authorizeAssignment(Request $request): void
    {
        abort_unless($request->user()?->canAssignClaims(), 403);
    }

    /** @return Collection<int, User> */
    private function availableAssignees(User $manager, string $account): Collection
    {
        return $this->teams->assignmentCandidates($manager, $account);
    }

    /** @param array<int, int|string> $userIds @return Collection<int, User> */
    private function eligibleAssignees(User $manager, array $userIds, string $account): Collection
    {
        $ids = array_values(array_unique(array_map('intval', $userIds)));
        $assignees = $this->availableAssignees($manager, $account)->whereIn('id', $ids)->values();
        abort_unless($assignees->count() === count($ids), 422, 'Choose only users from your active-account team.');

        return $assignees;
    }

    /** @param array<int, string> $groupValues */
    private function validateGroupSelection(string $groupBy, array $groupValues): void
    {
        abort_unless($this->assignments->isValidGroup($groupBy), 422, 'Choose a valid distribution group.');
        abort_if($groupBy !== 'all' && $groupValues === [], 422, 'Choose at least one distribution value.');
    }
}

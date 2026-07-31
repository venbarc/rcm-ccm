<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\User;
use App\Services\ClaimConfigurationService;
use App\Services\TeamService;
use App\Support\CurrentAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    private const BALANCE_EXPRESSION = 'COALESCE(true_balance, balance, 0)';

    public function __construct(
        private readonly ClaimConfigurationService $configurations,
        private readonly TeamService $teams,
    ) {}

    public function __invoke(Request $request): Response
    {
        $account = CurrentAccount::resolve($request);
        $users = $this->filteredVisibleUsers($request, $account->value);
        $userIds = (clone $users)->pluck('id')->all();
        $claims = $this->metricClaims($request, $account->value);
        $draftStatusId = $this->configurations->idForValue($account->value, ClaimConfigurationService::WORK_STATUS, 'draft');
        $paidStatusId = $this->configurations->idForValue($account->value, ClaimConfigurationService::WORK_STATUS, 'paid');

        $metrics = (clone $users)
            ->select(['users.id', 'users.name', 'users.email', 'users.is_admin'])
            ->selectSub($this->metricSubquery($claims, 'COUNT(*)'), 'total_lines')
            ->selectSub($this->metricSubquery((clone $claims)->whereNotNull('work_status_id')->when($draftStatusId, fn (Builder $query) => $query->where('work_status_id', '!=', $draftStatusId)), 'COUNT(*)'), 'worked_lines')
            ->selectSub($this->metricSubquery((clone $claims)->when($paidStatusId, fn (Builder $query) => $query->where('work_status_id', $paidStatusId), fn (Builder $query) => $query->whereRaw('1 = 0')), 'COUNT(*)'), 'closed_lines')
            ->selectSub($this->metricSubquery($claims, 'COALESCE(SUM('.self::BALANCE_EXPRESSION.'), 0)'), 'total_balance')
            ->selectSub($this->metricSubquery((clone $claims)->when($paidStatusId, fn (Builder $query) => $query->where('work_status_id', $paidStatusId), fn (Builder $query) => $query->whereRaw('1 = 0')), 'COALESCE(SUM('.self::BALANCE_EXPRESSION.'), 0)'), 'closed_balance')
            ->orderByDesc('total_balance')
            ->orderBy('users.name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => (bool) $user->is_admin,
                'total_lines' => (int) $user->total_lines,
                'worked_lines' => (int) $user->worked_lines,
                'closed_lines' => (int) $user->closed_lines,
                'total_balance' => (float) $user->total_balance,
                'closed_balance' => (float) $user->closed_balance,
            ]);

        return Inertia::render('activity-logs/index', [
            'metrics' => $metrics,
            'statusSummary' => $this->statusSummary($claims, $userIds, $account->value),
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'role' => (string) $request->input('role', 'all'),
                'worked_from' => (string) $request->input('worked_from', ''),
                'worked_to' => (string) $request->input('worked_to', ''),
            ],
            'roleOptions' => [
                ['value' => 'admin', 'label' => 'Admin'],
                ['value' => 'user', 'label' => 'User'],
            ],
            'isAdmin' => (bool) $request->user()->is_admin,
        ]);
    }

    public function statusDetails(Request $request): JsonResponse
    {
        $account = CurrentAccount::resolve($request);
        $status = (string) $request->input('status');
        $statusOption = $this->configurations->optionForValue($account->value, ClaimConfigurationService::WORK_STATUS, $status);
        abort_unless($statusOption, 422, 'Choose a valid status.');
        $statusLabels = $this->configurations->labelMapById($account->value, ClaimConfigurationService::WORK_STATUS);
        $statusColors = $this->configurations->colorMapById($account->value, ClaimConfigurationService::WORK_STATUS);
        $denialReasonLabels = $this->configurations->labelMapById($account->value, ClaimConfigurationService::DENIAL_REASON);

        $userIds = $this->filteredVisibleUsers($request, $account->value)->pluck('id')->all();
        $lines = $this->metricClaims($request, $account->value)
            ->with(['assignee:id,name,email', 'workStatusOption:id,value,label,color', 'denialReasonOption:id,value,label'])
            ->whereIn('assigned_to', $userIds)
            ->where('work_status_id', $statusOption->id)
            ->latest('updated_at')
            ->paginate(20);

        return response()->json([
            'data' => $lines->getCollection()
                ->map(fn (Claim $line): array => $this->workedLinePayload($line, $statusLabels, $statusColors, $denialReasonLabels))
                ->all(),
            'current_page' => $lines->currentPage(),
            'has_more' => $lines->hasMorePages(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $account = CurrentAccount::resolve($request);
        $statusLabels = $this->configurations->labelMapById($account->value, ClaimConfigurationService::WORK_STATUS);
        $draftStatusId = $this->configurations->idForValue($account->value, ClaimConfigurationService::WORK_STATUS, 'draft');
        $userIds = $this->filteredVisibleUsers($request, $account->value)->pluck('id')->all();

        if ($request->filled('user_id')) {
            $requestedUserId = (int) $request->integer('user_id');
            abort_unless(in_array($requestedUserId, $userIds, true), 404);
            $userIds = [$requestedUserId];
        }

        $claims = $this->metricClaims($request, $account->value)
            ->with(['assignee:id,name,email', 'workStatusOption:id,value,label,color'])
            ->whereIn('assigned_to', $userIds)
            ->whereNotNull('work_status_id')
            ->when($draftStatusId, fn (Builder $query) => $query->where('work_status_id', '!=', $draftStatusId));
        if ($request->filled('user_id')) {
            $this->applyWorkedDateFilters($claims, $request);
        }
        $this->applyWorkedLineFilters($claims, $request, $account->value);

        return response()->streamDownload(function () use ($claims, $statusLabels): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }

            fputcsv($stream, ['User', 'Email', 'Bill ID', 'Patient', 'CPT Code', 'Status', 'True Charge', 'Payments', 'True Balance', 'Worked At']);
            foreach ($claims->orderBy('id')->lazyById(500) as $line) {
                fputcsv($stream, [
                    $line->assignee?->name ?? 'Unassigned',
                    $line->assignee?->email ?? '',
                    $line->bill_id,
                    $line->patient_name,
                    $line->procedure_code ?: $line->cpt_code,
                    $line->workStatusOption?->label ?? $statusLabels[$line->work_status_id] ?? ($line->work_status ?: 'draft'),
                    (float) ($line->true_charge ?? $line->billed_amount ?? 0),
                    (float) ($line->payments ?? 0),
                    (float) ($line->true_balance ?? $line->balance ?? 0),
                    $line->updated_at?->toIso8601String(),
                ]);
            }
            fclose($stream);
        }, 'activity-logs-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function workedClaimLines(Request $request, User $user): Response
    {
        $account = CurrentAccount::resolve($request);
        $this->authorizeVisibleUser($request, $user, $account->value);
        $draftStatusId = $this->configurations->idForValue($account->value, ClaimConfigurationService::WORK_STATUS, 'draft');
        $claims = Claim::query()
            ->with(['assignee:id,name,email', 'workStatusOption:id,value,label,color', 'denialReasonOption:id,value,label'])
            ->where('account_type', $account->value)
            ->where('assigned_to', $user->id)
            ->whereNotNull('work_status_id')
            ->when($draftStatusId, fn (Builder $query) => $query->where('work_status_id', '!=', $draftStatusId));

        $this->applyWorkedDateFilters($claims, $request);
        $this->applyWorkedLineFilters($claims, $request, $account->value);
        $statusLabels = $this->configurations->labelMapById($account->value, ClaimConfigurationService::WORK_STATUS);
        $statusColors = $this->configurations->colorMapById($account->value, ClaimConfigurationService::WORK_STATUS);
        $denialReasonLabels = $this->configurations->labelMapById($account->value, ClaimConfigurationService::DENIAL_REASON);

        return Inertia::render('activity-logs/worked-claim-lines', [
            'user' => $user->only(['id', 'name', 'email', 'is_admin']),
            'workedStatusSummary' => $this->statusSummary($claims, [$user->id], $account->value, false),
            'statusOptions' => $this->statusOptions($account->value),
            'workedLines' => (clone $claims)
                ->latest('updated_at')
                ->paginate(30)
                ->withQueryString()
                ->through(fn (Claim $line): array => $this->workedLinePayload($line, $statusLabels, $statusColors, $denialReasonLabels)),
            'filters' => [
                'claim_number' => (string) $request->input('claim_number', ''),
                'cpt_code' => (string) $request->input('cpt_code', ''),
                'status' => (string) $request->input('status', 'all'),
                'date_filter_type' => (string) $request->input('date_filter_type', 'date_of_service'),
                'date_from' => (string) $request->input('date_from', ''),
                'date_to' => (string) $request->input('date_to', ''),
            ],
            'returnTo' => $this->safeActivityReturnUrl($request->query('return_to')),
        ]);
    }

    private function filteredVisibleUsers(Request $request, string $account): Builder
    {
        $visibleIds = $this->visibleUserIds($request->user(), $account);

        return User::query()
            ->whereIn('id', $visibleIds)
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim($request->string('search')->toString());
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->input('role') === 'admin', fn (Builder $query) => $query->where('is_admin', true))
            ->when($request->input('role') === 'user', fn (Builder $query) => $query->where('is_admin', false));
    }

    /** @return array<int, int> */
    private function visibleUserIds(User $viewer, string $account): array
    {
        return $this->teams
            ->assignmentCandidates($viewer, $account)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function metricClaims(Request $request, string $account): Builder
    {
        $query = Claim::query()
            ->where('account_type', $account)
            ->whereNotNull('assigned_to');
        $this->applyWorkedDateFilters($query, $request, 'worked_from', 'worked_to');

        return $query;
    }

    private function metricSubquery(Builder $claims, string $aggregate): Builder
    {
        return (clone $claims)
            ->whereColumn('assigned_to', 'users.id')
            ->selectRaw($aggregate);
    }

    /** @param array<int, int> $userIds */
    private function statusSummary(Builder $claims, array $userIds, string $account, bool $applyAssignedScope = true): array
    {
        $rows = (clone $claims)
            ->when($applyAssignedScope, fn (Builder $query) => $query->whereIn('assigned_to', $userIds))
            ->whereNotNull('work_status_id')
            ->selectRaw('work_status_id as status_key')
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('SUM('.self::BALANCE_EXPRESSION.') as amount')
            ->groupBy('work_status_id')
            ->get()
            ->keyBy('status_key');

        return collect($this->statusOptions($account))
            ->reject(fn (array $status): bool => $status['value'] === 'draft')
            ->map(function (array $status) use ($rows): array {
                $row = $rows->get($status['id']);

                return [
                    'status' => $status['value'],
                    'label' => $status['label'],
                    'color' => $status['color'],
                    'count' => (int) ($row->line_count ?? 0),
                    'amount' => (float) ($row->amount ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<int, array{id: int, value: string, label: string, color: string|null}> */
    private function statusOptions(string $account): array
    {
        return $this->configurations->selectOptions($account, ClaimConfigurationService::WORK_STATUS);
    }

    private function applyWorkedDateFilters(Builder $query, Request $request, string $fromKey = 'date_from', string $toKey = 'date_to'): void
    {
        $dateColumn = $request->input('date_filter_type') === 'date_of_service'
            ? 'service_date_start'
            : 'updated_at';

        if ($request->filled($fromKey)) {
            $query->whereDate($dateColumn, '>=', (string) $request->input($fromKey));
        }
        if ($request->filled($toKey)) {
            $query->whereDate($dateColumn, '<=', (string) $request->input($toKey));
        }
    }

    private function applyWorkedLineFilters(Builder $query, Request $request, string $account): void
    {
        if ($request->filled('claim_number')) {
            $query->where('bill_id', 'like', '%'.trim((string) $request->input('claim_number')).'%');
        }
        if ($request->filled('cpt_code')) {
            $cpt = trim((string) $request->input('cpt_code'));
            $query->where(function (Builder $nested) use ($cpt): void {
                $nested->where('procedure_code', 'like', "%{$cpt}%")
                    ->orWhere('cpt_code', 'like', "%{$cpt}%");
            });
        }
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $statusId = $this->configurations->idForValue(
                $account,
                ClaimConfigurationService::WORK_STATUS,
                $request->input('status'),
            );
            $query->when(
                $statusId,
                fn (Builder $nested) => $nested->where('work_status_id', $statusId),
                fn (Builder $nested) => $nested->whereRaw('1 = 0'),
            );
        }
    }

    /**
     * @param  array<string, string>  $statusLabels
     * @param  array<string, string>  $statusColors
     * @param  array<string, string>  $denialReasonLabels
     */
    private function workedLinePayload(Claim $line, array $statusLabels, array $statusColors, array $denialReasonLabels): array
    {
        return [
            'id' => $line->id,
            'claim_id' => $line->id,
            'claim_number' => $line->bill_id,
            'patient_name' => $line->patient_name,
            'cpt_code' => $line->procedure_code ?: $line->cpt_code,
            'status' => $line->workStatusOption?->value ?? ($line->work_status ?: 'draft'),
            'status_label' => $line->workStatusOption?->label
                ?? $statusLabels[$line->work_status_id]
                ?? str($line->work_status ?: 'draft')->replace('_', ' ')->title()->toString(),
            'status_color' => $line->workStatusOption?->color ?? $statusColors[$line->work_status_id] ?? null,
            'date_of_service' => ($line->service_date_start ?? $line->date_of_service)?->toDateString(),
            'worked_at' => $line->updated_at?->toIso8601String(),
            'charges' => (float) ($line->true_charge ?? $line->billed_amount ?? 0),
            'paid' => (float) ($line->payments ?? 0),
            'balance' => (float) ($line->true_balance ?? $line->balance ?? 0),
            'denial_reason' => $line->denialReasonOption?->label
                ?? $denialReasonLabels[$line->denial_reason_id]
                ?? $line->denial_reason,
            'assigned_to' => $line->assignee?->only(['id', 'name', 'email']),
        ];
    }

    private function authorizeVisibleUser(Request $request, User $target, string $account): void
    {
        abort_unless(in_array($target->id, $this->visibleUserIds($request->user(), $account), true), 404);
    }

    private function safeActivityReturnUrl(mixed $returnTo): string
    {
        return is_string($returnTo) && ($returnTo === '/activity-logs' || str_starts_with($returnTo, '/activity-logs?'))
            ? $returnTo
            : route('activity-logs.index', absolute: false);
    }
}

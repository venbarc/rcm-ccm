<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\ClaimActivity;
use App\Models\ClaimImport;
use App\Services\ClaimActivityService;
use App\Services\TeamService;
use App\Support\CurrentAccount;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClaimController extends Controller
{
    private const FILTERABLE_OPTION_COLUMNS = [
        'modmed_claim_status',
        'payer_name',
        'primary_provider',
        'denial_reason',
        'procedure_code',
        'service_month',
        'invoiced_status_date',
    ];

    private const WORK_STATUSES = [
        'draft', 'paid', 'rebilled', 'appeal',
        'pending', 'void', 'corrected', 'patient_balance',
    ];

    private const INVOICED_STATUSES = [
        'invoiced' => 'Invoiced',
    ];

    private const CREDIT_REASONS = [
        'inactive_insurance' => 'Inactive Insurance',
        'not_covered_by_insurance' => 'Not Covered by the Insurance',
    ];

    private const SORTABLE_COLUMNS = [
        'bill_id', 'patient_name', 'payer_name', 'location', 'service_date_start', 'line_count',
        'true_charge', 'true_balance', 'payments', 'updated_at',
    ];

    public function __construct(
        private readonly ClaimActivityService $activities,
        private readonly TeamService $teams,
    ) {}

    public function index(Request $request): Response
    {
        $account = CurrentAccount::resolve($request);
        $accountValue = $account->value;
        $matchedClaims = $this->buildMatchedClaimGroupQuery($request, $accountValue);
        $matchedBillIds = (clone $matchedClaims)->select('bill_id')->distinct();

        $search = trim($request->string('search')->toString());
        $serviceMonth = trim((string) $request->input('service_month', ''));
        $assignedTo = $request->input('assigned_to');
        $sortBy = in_array($request->input('sort_by'), self::SORTABLE_COLUMNS, true)
            ? $request->input('sort_by')
            : 'updated_at';
        $sortDirection = $request->input('sort_direction') === 'asc' ? 'asc' : 'desc';

        $claimGroups = Claim::query()
            ->where('account_type', $accountValue)
            ->whereIn('bill_id', $matchedBillIds)
            ->selectRaw('bill_id')
            ->selectRaw('MAX(NULLIF(patient_name, \'\')) as patient_name')
            ->selectRaw('MAX(NULLIF(first_name, \'\')) as first_name')
            ->selectRaw('MAX(NULLIF(last_name, \'\')) as last_name')
            ->selectRaw('MIN(service_date_start) as service_date_start')
            ->selectRaw('MAX(service_date_end) as service_date_end')
            ->selectRaw('MIN(cf_invoice_date) as cf_invoice_date')
            ->selectRaw('SUM(COALESCE(payments, 0)) as payments')
            ->selectRaw('SUM(COALESCE(true_charge, 0)) as true_charge')
            ->selectRaw('SUM(COALESCE(true_balance, 0)) as true_balance')
            ->selectRaw('MAX(updated_at) as updated_at')
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('MAX(CASE WHEN work_status IS NULL OR work_status = \'\' THEN \'draft\' ELSE work_status END) as work_status')
            ->selectRaw('MAX(NULLIF(modmed_claim_status, \'\')) as modmed_claim_status')
            ->selectRaw('MAX(NULLIF(invoiced_status, \'\')) as invoiced_status')
            ->selectRaw('MAX(invoiced_status_date) as invoiced_status_date')
            ->selectRaw('MAX(credit_status) as credit_status')
            ->selectRaw('MAX(credit_status_date) as credit_status_date')
            ->selectRaw('MAX(NULLIF(credit_reason, \'\')) as credit_reason')
            ->selectRaw('MAX(NULLIF(denial_reason, \'\')) as denial_reason')
            ->selectRaw('MAX(NULLIF(notes, \'\')) as notes')
            ->selectRaw('MAX(NULLIF(activity_type, \'\')) as activity_type')
            ->selectRaw('MAX(NULLIF(batch_name, \'\')) as batch_name')
            ->selectRaw('MAX(NULLIF(location, \'\')) as location')
            ->selectRaw('MAX(NULLIF(primary_provider, \'\')) as primary_provider')
            ->selectRaw('MAX(NULLIF(payer_name, \'\')) as payer_name')
            ->selectRaw('MAX(NULLIF(place_of_service_code, \'\')) as place_of_service_code')
            ->selectRaw('MAX(CASE WHEN work_status_manually_set = 1 OR (notes IS NOT NULL AND TRIM(notes) != \'\') OR (denial_reason IS NOT NULL AND TRIM(denial_reason) != \'\') OR credit_status IS NOT NULL OR (credit_reason IS NOT NULL AND TRIM(credit_reason) != \'\') THEN 1 ELSE 0 END) as is_modified')
            ->groupBy('bill_id')
            ->orderBy($sortBy, $sortDirection)
            ->orderBy('bill_id')
            ->paginate(50)
            ->withQueryString();

        $claimLines = Claim::query()
            ->with('assignee:id,name,email')
            ->where('account_type', $accountValue)
            ->whereIn('bill_id', $claimGroups->getCollection()->pluck('bill_id')->all())
            ->orderBy('service_date_start')
            ->orderBy('id')
            ->get()
            ->groupBy('bill_id');

        $lineIds = $claimLines->flatten(1)->pluck('id')->all();
        $latestActivityIds = ClaimActivity::query()
            ->whereIn('claim_id', $lineIds)
            ->selectRaw('MAX(id)')
            ->groupBy('claim_id');
        $latestActivities = ClaimActivity::query()
            ->with('user:id,name,email')
            ->whereIn('id', $latestActivityIds)
            ->get()
            ->keyBy('claim_id');

        $claimGroups->setCollection($claimGroups->getCollection()->map(function ($group) use ($claimLines, $latestActivities) {
            $lines = $claimLines->get($group->bill_id, collect())->values();
            /** @var Claim|null $representative */
            $representative = $lines->sortByDesc(fn (Claim $claim): int => $claim->updated_at?->getTimestamp() ?? 0)->first()
                ?? $lines->first();

            $cptCodes = $lines
                ->map(fn (Claim $claim): ?string => $claim->procedure_code ?: $claim->cpt_code)
                ->filter()
                ->unique()
                ->values()
                ->all();
            $latestActivity = $lines
                ->map(fn (Claim $claim) => $latestActivities->get($claim->id))
                ->filter()
                ->sortByDesc('id')
                ->first();

            return [
                'id' => (int) ($lines->min('id') ?? 0),
                'bill_id' => (string) $group->bill_id,
                'patient_name' => $representative?->patient_name ?: ($group->patient_name ?: 'Unknown patient'),
                'first_name' => $representative?->first_name,
                'last_name' => $representative?->last_name,
                'patient_dob' => $representative?->patient_dob?->toDateString(),
                'patient_id' => $representative?->patient_id,
                'payer_name' => $representative?->payer_name ?: $representative?->payer ?: $group->payer_name,
                'primary_provider' => $representative?->primary_provider ?: $representative?->provider ?: $group->primary_provider,
                'facility' => $representative?->practice_location ?: $representative?->location,
                'modified_by' => $latestActivity?->user?->only(['id', 'name', 'email']),
                'service_date_start' => $group->service_date_start ? Carbon::parse($group->service_date_start)->toDateString() : null,
                'service_date_end' => $group->service_date_end ? Carbon::parse($group->service_date_end)->toDateString() : null,
                'payments' => (float) ($group->payments ?? 0),
                'true_charge' => (float) ($group->true_charge ?? 0),
                'true_balance' => (float) ($group->true_balance ?? 0),
                'modmed_claim_status' => $representative?->modmed_claim_status ?: $group->modmed_claim_status,
                'cf_invoice_date' => $representative?->cf_invoice_date?->toDateString()
                    ?? ($group->cf_invoice_date ? Carbon::parse($group->cf_invoice_date)->toDateString() : null),
                'invoiced_status' => 'invoiced',
                'invoiced_status_date' => $representative?->cf_invoice_date?->toDateString()
                    ?? ($group->cf_invoice_date ? Carbon::parse($group->cf_invoice_date)->toDateString() : null),
                'credit_status' => $group->credit_status === null ? null : (bool) $group->credit_status,
                'credit_status_date' => $group->credit_status_date ? Carbon::parse($group->credit_status_date)->toDateString() : null,
                'credit_reason' => $representative?->credit_reason ?: $group->credit_reason,
                'work_status' => $representative?->work_status ?: ($group->work_status ?: 'draft'),
                'denial_reason' => $representative?->denial_reason ?: $group->denial_reason,
                'notes' => $representative?->notes ?: $group->notes,
                'activity_type' => $representative?->activity_type ?: $group->activity_type,
                'batch_name' => $representative?->batch_name ?: $group->batch_name,
                'location' => $representative?->location ?: $group->location,
                'place_of_service_code' => $representative?->place_of_service_code ?: $group->place_of_service_code,
                'assigned_to' => $representative?->assigned_to,
                'assignee' => $representative?->assignee?->only(['id', 'name', 'email']),
                'updated_at' => ($representative?->updated_at ?? Carbon::parse($group->updated_at))->toIso8601String(),
                'line_count' => (int) $group->line_count,
                'cpt_codes' => $cptCodes,
                'is_modified' => (bool) $group->is_modified,
                'lines' => $lines->map(fn (Claim $claim): array => [
                    'id' => $claim->id,
                    'bill_id' => $claim->bill_id,
                    'procedure_code' => $claim->procedure_code,
                    'cpt_code' => $claim->cpt_code,
                    'service_date_start' => $claim->service_date_start?->toDateString(),
                    'service_date_end' => $claim->service_date_end?->toDateString(),
                    'true_charge' => $claim->true_charge !== null ? (float) $claim->true_charge : null,
                    'true_balance' => $claim->true_balance !== null ? (float) $claim->true_balance : null,
                    'primary_provider' => $claim->primary_provider ?: $claim->provider,
                    'payer_name' => $claim->payer_name ?: $claim->payer,
                    'patient_id' => $claim->patient_id,
                    'modmed_claim_status' => $claim->modmed_claim_status,
                    'cf_invoice_date' => $claim->cf_invoice_date?->toDateString(),
                    'invoiced_status' => 'invoiced',
                    'invoiced_status_date' => $claim->cf_invoice_date?->toDateString(),
                    'credit_status' => $claim->credit_status,
                    'credit_status_date' => $claim->credit_status_date?->toDateString(),
                    'credit_reason' => $claim->credit_reason,
                    'work_status' => $claim->work_status ?: 'draft',
                    'denial_reason' => $claim->denial_reason,
                    'notes' => $claim->notes,
                    'source_notes' => $claim->source_notes,
                    'activity_type' => $claim->activity_type,
                    'batch_name' => $claim->batch_name,
                    'location' => $claim->location,
                    'place_of_service_code' => $claim->place_of_service_code,
                    'assigned_to' => $claim->assigned_to,
                    'assignee' => $claim->assignee?->only(['id', 'name', 'email']),
                    'is_modified' => (bool) ($claim->work_status_manually_set
                        || filled($claim->notes)
                        || filled($claim->denial_reason)
                        || $claim->credit_status !== null
                        || filled($claim->credit_reason)),
                    'updated_at' => $claim->updated_at->toIso8601String(),
                ])->all(),
            ];
        }));

        $summaryQuery = Claim::query()
            ->where('account_type', $accountValue)
            ->whereIn('bill_id', $matchedBillIds);

        return Inertia::render('claims/index', [
            'claims' => $claimGroups,
            'filters' => [
                'search' => $search,
                'modmed_claim_status' => (string) $request->input('modmed_claim_status', ''),
                'invoiced_status' => (string) $request->input('invoiced_status', ''),
                'payer_name' => (string) $request->input('payer_name', ''),
                'primary_provider' => (string) $request->input('primary_provider', ''),
                'denial_reason' => (string) $request->input('denial_reason', ''),
                'work_status' => (string) $request->input('work_status', ''),
                'assigned_to' => is_scalar($assignedTo) ? (string) $assignedTo : '',
                'worked_from' => (string) $request->input('worked_from', ''),
                'worked_to' => (string) $request->input('worked_to', ''),
                'service_month' => $serviceMonth,
                'cf_invoice_from' => (string) $request->input('cf_invoice_from', ''),
                'cf_invoice_to' => (string) $request->input('cf_invoice_to', ''),
                'invoiced_status_date' => (string) $request->input('invoiced_status_date', ''),
                'procedure_code' => (string) $request->input('procedure_code', ''),
                'expanded' => (string) $request->input('expanded', ''),
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
            'summary' => [
                'totalCount' => (clone $summaryQuery)->count(),
                'totalTrueBalance' => (float) ((clone $summaryQuery)->where('true_balance', '>', 0)->sum('true_balance') ?? 0),
                'totalTrueCharge' => (float) ((clone $summaryQuery)->sum('true_charge') ?? 0),
                'totalPayments' => (float) ((clone $summaryQuery)->where('payments', '>', 0)->sum('payments') ?? 0),
            ],
            'workStatuses' => fn (): array => array_map(fn (string $value): array => [
                'value' => $value,
                'label' => str($value)->replace('_', ' ')->title()->toString(),
            ], self::WORK_STATUSES),
            'invoicedStatuses' => $this->selectOptions(self::INVOICED_STATUSES),
            'creditReasons' => $this->selectOptions(self::CREDIT_REASONS),
            'assignees' => fn () => $request->user()->canAssignClaims()
                ? $this->teams->assignmentCandidates($request->user(), $accountValue)
                : collect(),
            'hasActiveImport' => fn (): bool => ClaimImport::query()
                ->where('account_type', $accountValue)
                ->whereIn('status', ['queued', 'processing'])
                ->exists(),
            'canEditClaims' => $this->canEditClaims($request, $accountValue),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $account = CurrentAccount::resolve($request);
        $validated = $request->validate([
            'filter' => ['required', 'string'],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:200'],
        ]);

        $filter = trim($validated['filter']);
        abort_unless(in_array($filter, self::FILTERABLE_OPTION_COLUMNS, true), 422, 'Choose a valid claims filter.');

        $search = trim((string) ($validated['search'] ?? ''));
        $page = max((int) ($validated['page'] ?? 1), 1);
        $perPage = min(max((int) ($validated['per_page'] ?? 10), 5), 200);

        if ($filter === 'service_month') {
            $months = Claim::query()
                ->where('account_type', $account->value)
                ->whereNotNull('service_date_start')
                ->pluck('service_date_start')
                ->map(fn ($date): string => Carbon::parse($date)->format('Y-m'))
                ->filter(fn (string $value): bool => $search === '' || str_contains($value, $search) || str_contains(strtolower(Carbon::createFromFormat('Y-m', $value)->format('F Y')), strtolower($search)))
                ->unique()
                ->sortDesc()
                ->values();

            $total = $months->count();
            $lastPage = max((int) ceil(max($total, 1) / $perPage), 1);

            return response()->json([
                'data' => $months->forPage($page, $perPage)->map(fn (string $value): array => [
                    'id' => $value,
                    'name' => Carbon::createFromFormat('Y-m', $value)->format('F Y'),
                ])->values()->all(),
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'has_more' => $page < $lastPage,
            ]);
        }

        if ($filter === 'invoiced_status_date') {
            $dates = Claim::query()
                ->where('account_type', $account->value)
                ->whereRaw($this->filterExpression('invoiced_status_date').' IS NOT NULL')
                ->selectRaw($this->filterExpression('invoiced_status_date').' as option_value')
                ->distinct()
                ->pluck('option_value')
                ->map(fn ($value): string => Carbon::parse($value)->toDateString())
                ->filter(function (string $value) use ($search): bool {
                    if ($search === '') {
                        return true;
                    }

                    $normalizedSearch = strtolower($search);
                    $formattedDate = strtolower(Carbon::parse($value)->format('F j, Y'));

                    return str_contains($value, $normalizedSearch)
                        || str_contains($formattedDate, $normalizedSearch);
                })
                ->unique()
                ->sortDesc()
                ->values();

            $total = $dates->count();
            $lastPage = max((int) ceil(max($total, 1) / $perPage), 1);

            return response()->json([
                'data' => $dates->forPage($page, $perPage)->map(fn (string $value): array => [
                    'id' => $value,
                    'name' => Carbon::parse($value)->format('F j, Y'),
                ])->values()->all(),
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'has_more' => $page < $lastPage,
            ]);
        }

        $expression = $this->filterExpression($filter);
        abort_if($expression === null, 422, 'Choose a valid claims filter.');

        $query = Claim::query()
            ->where('account_type', $account->value)
            ->whereRaw("{$expression} IS NOT NULL")
            ->whereRaw("{$expression} != ''")
            ->when($search !== '', fn (Builder $query) => $query->whereRaw("{$expression} LIKE ?", ['%'.$search.'%']))
            ->selectRaw("{$expression} as option_value")
            ->distinct();

        $total = DB::query()->fromSub(clone $query, 'claim_filter_options')->count();
        $lastPage = max((int) ceil(max($total, 1) / $perPage), 1);
        $options = (clone $query)
            ->orderBy('option_value', $filter === 'invoiced_status_date' ? 'desc' : 'asc')
            ->forPage($page, $perPage)
            ->pluck('option_value');

        return response()->json([
            'data' => $options->map(fn ($value): array => [
                'id' => (string) $value,
                'name' => (string) $value,
            ])->values()->all(),
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'has_more' => $page < $lastPage,
        ]);
    }

    public function show(Request $request, Claim $claim): Response
    {
        $account = CurrentAccount::resolve($request);
        abort_unless($claim->account_type === $account->value, 404);

        $lines = Claim::query()
            ->with('assignee:id,name,email')
            ->where('account_type', $account->value)
            ->where('bill_id', $claim->bill_id)
            ->orderBy('service_date_start')
            ->orderBy('id')
            ->get();
        $representative = $lines->firstWhere('id', $claim->id) ?? $lines->firstOrFail();
        $activities = $this->claimActivitiesPage($account->value, $lines, 1);
        $returnTo = $this->safeClaimsReturnUrl($request->query('return_to'));
        $serviceStart = $lines
            ->map(fn (Claim $line) => $line->service_date_start ?? $line->date_of_service)
            ->filter()
            ->min();
        $serviceEnd = $lines
            ->map(fn (Claim $line) => $line->service_date_end ?? $line->service_date_start ?? $line->date_of_service)
            ->filter()
            ->max();

        $diagnosisCodes = $lines
            ->pluck('diagnosis_code')
            ->filter()
            ->flatMap(fn (string $codes) => preg_split('/[,;|\s]+/', $codes) ?: [])
            ->map(fn (string $code) => trim($code))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return Inertia::render('claims/show', [
            'claim' => [
                'id' => (int) $lines->min('id'),
                'bill_id' => (string) $claim->bill_id,
                'patient_name' => $representative->patient_name ?: trim("{$representative->first_name} {$representative->last_name}"),
                'patient_id' => $representative->patient_id,
                'patient_dob' => $representative->patient_dob?->toDateString(),
                'facility' => $representative->practice_location ?: $representative->location,
                'service_date_start' => $serviceStart?->toDateString(),
                'service_date_end' => $serviceEnd?->toDateString(),
                'service_type' => $representative->service_type,
                'diagnosis_codes' => $diagnosisCodes,
                'line_count' => $lines->count(),
                'total_true_charge' => (float) $lines->sum(fn (Claim $line): float => (float) ($line->true_charge ?? $line->billed_amount ?? 0)),
                'total_payments' => (float) $lines->sum(fn (Claim $line): float => (float) ($line->payments ?? 0)),
                'total_true_balance' => (float) $lines->sum(fn (Claim $line): float => (float) ($line->true_balance ?? $line->balance ?? 0)),
                'lines' => $lines->map(fn (Claim $line): array => [
                    'id' => $line->id,
                    'cpt_code' => $line->procedure_code ?: $line->cpt_code,
                    'modifier' => $line->modifiers ?: $line->primary_modifier,
                    'units' => $line->units !== null ? (float) $line->units : null,
                    'true_charge' => (float) ($line->true_charge ?? $line->billed_amount ?? 0),
                    'true_balance' => (float) ($line->true_balance ?? $line->balance ?? 0),
                    'work_status' => $line->work_status ?: 'draft',
                    'denial_reason' => $line->denial_reason,
                    'payer_name' => $line->payer_name ?: $line->payer,
                    'primary_provider' => $line->primary_provider ?: $line->provider,
                    'modmed_claim_status' => $line->modmed_claim_status,
                    'cf_invoice_date' => $line->cf_invoice_date?->toDateString(),
                    'invoiced_status' => 'invoiced',
                    'invoiced_status_date' => $line->cf_invoice_date?->toDateString(),
                    'credit_status' => $line->credit_status,
                    'credit_status_date' => $line->credit_status_date?->toDateString(),
                    'credit_reason' => $line->credit_reason,
                    'patient_id' => $line->patient_id,
                    'notes' => $line->notes,
                    'assigned_to' => $line->assignee?->only(['id', 'name', 'email']),
                ])->all(),
            ],
            'activities' => $activities['data'],
            'activitiesPage' => $activities['current_page'],
            'activitiesHasMore' => $activities['has_more'],
            'returnTo' => $returnTo,
        ]);
    }

    public function activities(Request $request, Claim $claim): JsonResponse
    {
        $account = CurrentAccount::resolve($request);
        abort_unless($claim->account_type === $account->value, 404);

        $lines = Claim::query()
            ->where('account_type', $account->value)
            ->where('bill_id', $claim->bill_id)
            ->get(['id', 'procedure_code', 'cpt_code']);
        $payload = $this->claimActivitiesPage(
            $account->value,
            $lines,
            max((int) $request->integer('page', 1), 1),
        );

        return response()->json($payload);
    }

    public function update(Request $request, Claim $claim): RedirectResponse
    {
        $account = CurrentAccount::resolve($request);
        abort_unless($claim->account_type === $account->value, 404);

        if (! $this->canEditClaims($request, $account->value)) {
            throw ValidationException::withMessages([
                'claim' => 'You are not assigned to an administrator. Ask an administrator to add you as a member before editing claims.',
            ]);
        }

        $validated = $request->validate([
            'work_status' => ['sometimes', Rule::in(self::WORK_STATUSES)],
            'denial_reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'credit_status' => ['sometimes', 'nullable', 'boolean'],
            'credit_status_date' => [
                Rule::requiredIf(fn (): bool => $request->boolean('credit_status')),
                'nullable',
                'date_format:Y-m-d',
            ],
            'credit_reason' => [
                Rule::requiredIf(fn (): bool => $request->boolean('credit_status')),
                'nullable',
                Rule::in(array_keys(self::CREDIT_REASONS)),
            ],
        ], [
            'credit_status_date.required' => 'Credit Status Date is required when Credit Status is Yes.',
            'credit_reason.required' => 'Credit Reason is required when Credit Status is Yes.',
        ]);

        foreach (['denial_reason', 'notes', 'credit_reason'] as $field) {
            if (array_key_exists($field, $validated)) {
                $validated[$field] = trim((string) $validated[$field]) ?: null;
            }
        }

        if (array_key_exists('credit_status', $validated)) {
            $validated['credit_status'] = $validated['credit_status'] === null
                ? null
                : (bool) $validated['credit_status'];

            if ($validated['credit_status'] !== true) {
                $validated['credit_status_date'] = null;
                $validated['credit_reason'] = null;
            }
        }

        if (array_key_exists('work_status', $validated)) {
            $validated['work_status_manually_set'] = $validated['work_status'] !== 'draft';
        }

        $validated['assigned_to'] = $request->user()->id;
        if ($claim->status === 'new') {
            $validated['status'] = 'in_progress';
        }

        DB::transaction(function () use ($claim, $validated, $account, $request): void {
            $before = $claim->only(array_keys($validated));
            $claim->update($validated);
            $after = $claim->only(array_keys($validated));
            if ($before !== $after) {
                $this->activities->record(
                    $account->value,
                    'claim_updated',
                    "Updated Bill ID {$claim->bill_id} CPT ".($claim->procedure_code ?: $claim->cpt_code ?: $claim->id),
                    $request->user(),
                    $claim,
                    $before,
                    $after,
                );
            }
        });

        $query = $request->query();
        $query['expanded'] = (string) Claim::query()
            ->where('account_type', $account->value)
            ->where('bill_id', $claim->bill_id)
            ->min('id');

        return redirect()
            ->route('claims.index', $query)
            ->with('success', 'Claim line updated and assigned to you.');
    }

    private function claimActivitiesPage(string $account, Collection $lines, int $page): array
    {
        $linesById = $lines->keyBy('id');
        $activities = ClaimActivity::query()
            ->with('user:id,name,email')
            ->where('account_type', $account)
            ->whereIn('claim_id', $linesById->keys()->all())
            ->latest('id')
            ->paginate(20, ['*'], 'page', $page);

        return [
            'data' => $activities->getCollection()->map(function (ClaimActivity $activity) use ($linesById): array {
                $line = $linesById->get($activity->claim_id);

                return [
                    'id' => $activity->id,
                    'claim_line_id' => $activity->claim_id,
                    'cpt_code' => $line?->procedure_code ?: $line?->cpt_code,
                    'action' => $activity->action,
                    'description' => $activity->description,
                    'before' => $activity->before,
                    'after' => $activity->after,
                    'created_at' => $activity->created_at?->toIso8601String(),
                    'user' => $activity->user?->only(['id', 'name', 'email']),
                ];
            })->all(),
            'current_page' => $activities->currentPage(),
            'has_more' => $activities->hasMorePages(),
        ];
    }

    private function safeClaimsReturnUrl(mixed $returnTo): string
    {
        if (is_string($returnTo) && (
            $returnTo === '/dashboard'
            || $returnTo === '/claims'
            || str_starts_with($returnTo, '/claims?')
            || $returnTo === '/activity-logs'
            || str_starts_with($returnTo, '/activity-logs?')
            || str_starts_with($returnTo, '/activity-logs/users/')
        )) {
            return $returnTo;
        }

        return route('claims.index', absolute: false);
    }

    private function buildMatchedClaimGroupQuery(Request $request, string $account): Builder
    {
        $query = Claim::query()->where('account_type', $account);
        $search = trim($request->string('search')->toString());

        if ($search !== '') {
            $query->where(function (Builder $nested) use ($search): void {
                $nested->where('bill_id', 'like', "%{$search}%")
                    ->orWhere('patient_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('patient_id', 'like', "%{$search}%")
                    ->orWhereRaw($this->filterExpression('payer_name').' LIKE ?', ["%{$search}%"])
                    ->orWhereRaw($this->filterExpression('primary_provider').' LIKE ?', ["%{$search}%"])
                    ->orWhereRaw($this->filterExpression('procedure_code').' LIKE ?', ["%{$search}%"]);
            });
        }

        $this->applyExactFilter($query, 'modmed_claim_status', $request->input('modmed_claim_status'));
        $this->applyExactFilter($query, 'invoiced_status', $request->input('invoiced_status'));
        $this->applyExpressionExactFilter($query, $this->filterExpression('payer_name'), $request->input('payer_name'));
        $this->applyExpressionExactFilter($query, $this->filterExpression('primary_provider'), $request->input('primary_provider'));
        $this->applyExactFilter($query, 'denial_reason', $request->input('denial_reason'));
        $this->applyExactFilter($query, 'work_status', $request->input('work_status'));
        $this->applyExpressionExactFilter($query, $this->filterExpression('procedure_code'), $request->input('procedure_code'));

        $assignedTo = $request->input('assigned_to');
        if ($assignedTo === 'unassigned') {
            $query->whereNull('assigned_to');
        } elseif ($assignedTo === 'me') {
            $query->where('assigned_to', $request->user()->id);
        } elseif (is_numeric($assignedTo)) {
            $query->where('assigned_to', (int) $assignedTo);
        }

        $this->applyDateFilter($query, 'updated_at', '>=', $request->input('worked_from'));
        $this->applyDateFilter($query, 'updated_at', '<=', $request->input('worked_to'));
        $this->applyDateFilter($query, 'cf_invoice_date', '>=', $request->input('cf_invoice_from'));
        $this->applyDateFilter($query, 'cf_invoice_date', '<=', $request->input('cf_invoice_to'));
        $this->applyExpressionExactFilter(
            $query,
            $this->filterExpression('invoiced_status_date'),
            $request->input('invoiced_status_date'),
        );

        $serviceMonth = trim((string) $request->input('service_month', ''));
        if (preg_match('/^\d{4}-\d{2}$/', $serviceMonth) === 1) {
            $month = Carbon::createFromFormat('!Y-m', $serviceMonth);
            $query->whereBetween('service_date_start', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ]);
        }

        return $query;
    }

    private function applyExactFilter(Builder $query, string $column, mixed $value): void
    {
        $value = trim((string) ($value ?? ''));
        if ($value !== '' && $value !== 'all') {
            $query->where($column, $value);
        }
    }

    private function applyExpressionExactFilter(Builder $query, ?string $expression, mixed $value): void
    {
        $value = trim((string) ($value ?? ''));
        if ($expression === null || $value === '' || $value === 'all') {
            return;
        }

        $query->whereRaw("{$expression} = ?", [$value]);
    }

    private function applyDateFilter(Builder $query, string $column, string $operator, mixed $value): void
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return;
        }

        try {
            $query->whereDate($column, $operator, Carbon::parse($value)->toDateString());
        } catch (\Throwable) {
            // Ignore malformed query-string dates and keep the claims page usable.
        }
    }

    private function filterExpression(string $filter): ?string
    {
        return match ($filter) {
            'modmed_claim_status' => "NULLIF(modmed_claim_status, '')",
            'payer_name' => "COALESCE(NULLIF(payer_name, ''), NULLIF(payer, ''))",
            'primary_provider' => "COALESCE(NULLIF(primary_provider, ''), NULLIF(provider, ''))",
            'denial_reason' => "NULLIF(denial_reason, '')",
            'procedure_code' => "COALESCE(NULLIF(procedure_code, ''), NULLIF(cpt_code, ''))",
            'invoiced_status_date' => 'COALESCE(invoiced_status_date, cf_invoice_date)',
            default => null,
        };
    }

    private function canEditClaims(Request $request, string $account): bool
    {
        $user = $request->user();

        return $user->is_admin
            || $user->groupMembershipsAsMember()
                ->where('account_type', $account)
                ->exists();
    }

    /** @param array<string, string> $options @return array<int, array{value: string, label: string}> */
    private function selectOptions(array $options): array
    {
        return collect($options)
            ->map(fn (string $label, string $value): array => compact('value', 'label'))
            ->values()
            ->all();
    }
}

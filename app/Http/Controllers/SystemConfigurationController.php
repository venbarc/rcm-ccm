<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\ClaimConfigurationOption;
use App\Services\ClaimConfigurationService;
use App\Support\ClaimWorkspace;
use App\Support\CurrentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SystemConfigurationController extends Controller
{
    public function __construct(private readonly ClaimConfigurationService $configurations) {}

    public function index(Request $request): Response
    {
        $account = CurrentAccount::resolve($request);
        $typeLabels = $this->availableTypeLabels($account->value);
        $options = ClaimConfigurationOption::query()
            ->with('addedBy:id,name,email')
            ->where('account_type', $account->value)
            ->whereIn('option_type', array_keys($typeLabels))
            ->orderBy('option_type')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->map(fn (ClaimConfigurationOption $option): array => $this->payload($option))
            ->groupBy('option_type');

        return Inertia::render('system-configuration/index', [
            'sections' => collect($typeLabels)
                ->map(fn (string $label, string $type): array => [
                    'type' => $type,
                    'label' => $label,
                    'can_restore_defaults' => $this->configurations->canRestoreDefaults($type),
                    'options' => $options->get($type, collect())->values()->all(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $account = CurrentAccount::resolve($request);
        $requestedType = (string) $request->input('option_type');
        $this->normalizeConfigurationColor($request, $requestedType);
        $validated = $request->validate([
            'option_type' => ['required', 'string', Rule::in(array_keys($this->availableTypeLabels($account->value)))],
            'label' => ['required', 'string', 'max:255'],
            'color' => $this->colorRules($requestedType, $account->value),
        ], $this->colorValidationMessages($requestedType));
        $type = $validated['option_type'];
        $this->ensureTypeCanBeCreated($type);
        $label = trim($validated['label']);
        $this->ensureSystemLabelIsNotReserved($account->value, $type, $label);
        $this->ensureUniqueLabel($account->value, $type, $label);

        ClaimConfigurationOption::query()->create([
            'account_type' => $account->value,
            'option_type' => $type,
            'value' => $this->uniqueValue($account->value, $type, $label),
            'label' => $label,
            'color' => $this->configurations->usesColor($type) ? $validated['color'] : null,
            'sort_order' => ((int) $this->configurations->query($account->value, $type)->max('sort_order')) + 1,
            'added_by' => $request->user()->id,
        ]);

        return back()->with('success', "{$this->configurations->typeLabels()[$type]} added.");
    }

    public function update(Request $request, ClaimConfigurationOption $configurationOption): RedirectResponse
    {
        $account = CurrentAccount::resolve($request);
        abort_unless($configurationOption->account_type === $account->value, 404);
        $this->ensureTypeIsAvailable($account->value, $configurationOption->option_type);
        $this->ensureOptionIsEditable($configurationOption);
        $this->normalizeConfigurationColor($request, $configurationOption->option_type);
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'color' => $this->colorRules(
                $configurationOption->option_type,
                $account->value,
                $configurationOption->id,
            ),
        ], $this->colorValidationMessages($configurationOption->option_type));
        $label = trim($validated['label']);
        if (! $this->configurations->isSystemOption($configurationOption)) {
            $this->ensureSystemLabelIsNotReserved($account->value, $configurationOption->option_type, $label);
        }
        $this->ensureUniqueLabel($account->value, $configurationOption->option_type, $label, $configurationOption->id);
        $configurationOption->update([
            'label' => $label,
            'color' => $this->configurations->usesColor($configurationOption->option_type)
                ? $validated['color']
                : null,
        ]);

        return back()->with('success', "{$this->configurations->typeLabels()[$configurationOption->option_type]} updated.");
    }

    public function destroy(Request $request, ClaimConfigurationOption $configurationOption): RedirectResponse
    {
        $account = CurrentAccount::resolve($request);
        abort_unless($configurationOption->account_type === $account->value, 404);
        $this->ensureTypeIsAvailable($account->value, $configurationOption->option_type);
        $this->ensureOptionIsDeletable($configurationOption);
        $request->validate([
            'confirmation' => ['required', Rule::in(['confirm'])],
        ], [
            'confirmation.in' => 'Type confirm exactly to delete this option.',
        ]);
        $typeLabel = $this->configurations->typeLabels()[$configurationOption->option_type];
        $isRecoverable = DB::transaction(function () use ($configurationOption): bool {
            $lockedOption = ClaimConfigurationOption::query()
                ->whereKey($configurationOption->id)
                ->lockForUpdate()
                ->firstOrFail();
            $referenceColumn = $this->configurations->claimReferenceColumn($lockedOption->option_type);
            $isReferenced = $referenceColumn !== null
                && Claim::query()->where($referenceColumn, $lockedOption->id)->exists();
            $isRecoverable = $this->configurations->isSystemOption($lockedOption) || $isReferenced;

            $isRecoverable
                ? $lockedOption->delete()
                : $lockedOption->forceDelete();

            return $isRecoverable;
        });

        return back()->with(
            'success',
            $isRecoverable
                ? "{$typeLabel} removed from active options. Historical claim references were retained."
                : "{$typeLabel} permanently deleted.",
        );
    }

    public function restoreDefaults(Request $request, string $type): RedirectResponse
    {
        $account = CurrentAccount::resolve($request);
        $this->ensureTypeIsAvailable($account->value, $type);
        abort_unless($this->configurations->canRestoreDefaults($type), 404);
        $request->validate([
            'confirmation' => ['required', Rule::in(['restore'])],
        ]);
        $this->configurations->restoreSystemDefaults($account->value, $type);
        $typeLabel = $this->configurations->typeLabels()[$type];

        return back()->with('success', "System {$typeLabel} defaults restored. Administrator-created options were not changed.");
    }

    /** @return array<string, string> */
    private function availableTypeLabels(string $account): array
    {
        return collect($this->configurations->typeLabels())
            ->reject(fn (string $label, string $type): bool => $type === ClaimConfigurationService::MODMED_CLAIM_STATUS
                && ! ClaimWorkspace::supports($account, 'modmed_status'))
            ->all();
    }

    private function ensureTypeIsAvailable(string $account, string $type): void
    {
        abort_unless(array_key_exists($type, $this->availableTypeLabels($account)), 404);
    }

    private function ensureUniqueLabel(string $account, string $type, string $label, ?int $ignoreId = null): void
    {
        $exists = $this->configurations->queryIncludingDeleted($account, $type)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereRaw('LOWER(label) = ?', [mb_strtolower($label)])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'label' => 'An option with this name already exists.',
            ]);
        }
    }

    private function ensureTypeCanBeCreated(string $type): void
    {
        if ($type === ClaimConfigurationService::CREDIT_STATUS) {
            throw ValidationException::withMessages([
                'option_type' => 'Credit Status uses the required Yes and No options. Their display names can be edited.',
            ]);
        }
    }

    private function ensureSystemLabelIsNotReserved(string $account, string $type, string $label): void
    {
        $reserved = collect($this->configurations->systemDefaultLabels($account, $type))
            ->contains(fn (string $defaultLabel): bool => mb_strtolower($defaultLabel) === mb_strtolower($label));
        if ($reserved) {
            throw ValidationException::withMessages([
                'label' => 'That name is reserved for a system default. Restore this configuration instead.',
            ]);
        }
    }

    private function ensureOptionIsEditable(ClaimConfigurationOption $option): void
    {
        if ($option->option_type === ClaimConfigurationService::WORK_STATUS && $option->value === 'draft') {
            throw ValidationException::withMessages([
                'option' => 'Draft is the required default Work Status and cannot be edited or deleted.',
            ]);
        }
    }

    private function ensureOptionIsDeletable(ClaimConfigurationOption $option): void
    {
        $this->ensureOptionIsEditable($option);

        if ($option->option_type === ClaimConfigurationService::CREDIT_STATUS) {
            throw ValidationException::withMessages([
                'option' => 'Yes and No are required Credit Status options and cannot be deleted.',
            ]);
        }

    }

    /** @return array<int, mixed> */
    private function colorRules(string $type, string $account, ?int $ignoreId = null): array
    {
        if (! $this->configurations->usesColor($type)) {
            return ['nullable'];
        }

        $rules = [
            'required',
            'string',
            'regex:/^#[0-9A-F]{6}$/',
            Rule::unique((new ClaimConfigurationOption)->getTable(), 'color')
                ->where(fn ($query) => $query
                    ->where('account_type', $account)
                    ->where('option_type', $type))
                ->ignore($ignoreId),
        ];

        $editingSystemDefault = $ignoreId !== null
            && ($option = ClaimConfigurationOption::query()->find($ignoreId))
            && $option->account_type === $account
            && $this->configurations->isSystemOption($option);
        if (! $editingSystemDefault) {
            $rules[] = Rule::notIn($this->configurations->systemDefaultColors($account, $type));
        }

        return $rules;
    }

    /** @return array<string, string> */
    private function colorValidationMessages(string $type): array
    {
        $typeLabel = $this->configurations->typeLabels()[$type] ?? 'configuration option';

        return [
            'color.required' => "Choose a background color for the {$typeLabel}.",
            'color.regex' => 'Enter a valid six-digit hex color such as #DCEEFF.',
            'color.unique' => "That background color is already assigned to another {$typeLabel}.",
            'color.not_in' => 'That color is reserved for a system configuration default.',
        ];
    }

    private function normalizeConfigurationColor(Request $request, string $type): void
    {
        if ($this->configurations->usesColor($type) && is_string($request->input('color'))) {
            $request->merge([
                'color' => strtoupper(trim((string) $request->input('color'))),
            ]);
        }
    }

    private function uniqueValue(string $account, string $type, string $label): string
    {
        if ($type === ClaimConfigurationService::DENIAL_REASON) {
            return $label;
        }

        $base = Str::slug($label, '_') ?: 'option';
        $usesReservedSystemValue = in_array($base, $this->configurations->systemDefaultValues($account, $type), true);
        $value = $usesReservedSystemValue ? "{$base}_2" : $base;
        $suffix = $usesReservedSystemValue ? 3 : 2;

        while ($this->configurations->queryIncludingDeleted($account, $type)->where('value', $value)->exists()) {
            $value = "{$base}_{$suffix}";
            $suffix++;
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private function payload(ClaimConfigurationOption $option): array
    {
        return [
            'id' => $option->id,
            'option_type' => $option->option_type,
            'value' => $option->value,
            'label' => $option->label,
            'color' => $option->color,
            'added_by' => $option->addedBy?->only(['id', 'name', 'email']),
            'created_at' => $option->created_at?->toIso8601String(),
            'updated_at' => $option->updated_at?->toIso8601String(),
        ];
    }
}

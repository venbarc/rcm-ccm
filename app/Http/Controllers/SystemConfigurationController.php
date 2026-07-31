<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\ClaimConfigurationOption;
use App\Services\ClaimConfigurationService;
use App\Support\CurrentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $options = ClaimConfigurationOption::query()
            ->with('addedBy:id,name,email')
            ->where('account_type', $account->value)
            ->orderBy('option_type')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->map(fn (ClaimConfigurationOption $option): array => $this->payload($option))
            ->groupBy('option_type');

        return Inertia::render('system-configuration/index', [
            'sections' => collect($this->configurations->typeLabels())
                ->map(fn (string $label, string $type): array => [
                    'type' => $type,
                    'label' => $label,
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
            'option_type' => ['required', 'string', Rule::in(array_keys($this->configurations->typeLabels()))],
            'label' => ['required', 'string', 'max:255'],
            'color' => $this->colorRules($requestedType, $account->value),
        ], $this->colorValidationMessages($requestedType));
        $type = $validated['option_type'];
        $this->ensureTypeCanBeCreated($type);
        $label = trim($validated['label']);
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
        $this->ensureOptionIsDeletable($configurationOption);
        $request->validate([
            'confirmation' => ['required', Rule::in(['confirm'])],
        ], [
            'confirmation.in' => 'Type confirm exactly to delete this option.',
        ]);
        $typeLabel = $this->configurations->typeLabels()[$configurationOption->option_type];
        $configurationOption->delete();

        return back()->with('success', "{$typeLabel} deleted.");
    }

    private function ensureUniqueLabel(string $account, string $type, string $label, ?int $ignoreId = null): void
    {
        $exists = $this->configurations->query($account, $type)
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

        $referenceColumn = $this->configurations->claimReferenceColumn($option->option_type);
        if ($referenceColumn !== null && Claim::query()->where($referenceColumn, $option->id)->exists()) {
            throw ValidationException::withMessages([
                'option' => 'This option is assigned to one or more claim lines. Reassign those claim lines before deleting it.',
            ]);
        }
    }

    /** @return array<int, mixed> */
    private function colorRules(string $type, string $account, ?int $ignoreId = null): array
    {
        if (! $this->configurations->usesColor($type)) {
            return ['nullable'];
        }

        return [
            'required',
            'string',
            'regex:/^#[0-9A-F]{6}$/',
            Rule::unique('claim_configuration_options', 'color')
                ->where(fn ($query) => $query
                    ->where('account_type', $account)
                    ->where('option_type', $type))
                ->ignore($ignoreId),
        ];
    }

    /** @return array<string, string> */
    private function colorValidationMessages(string $type): array
    {
        $typeLabel = $this->configurations->typeLabels()[$type] ?? 'configuration option';

        return [
            'color.required' => "Choose a background color for the {$typeLabel}.",
            'color.regex' => 'Enter a valid six-digit hex color such as #DCEEFF.',
            'color.unique' => "That background color is already assigned to another {$typeLabel}.",
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
        $value = $base;
        $suffix = 2;

        while ($this->configurations->query($account, $type)->where('value', $value)->exists()) {
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

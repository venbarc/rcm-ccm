<?php

namespace App\Http\Controllers;

use App\Models\ClaimImport;
use App\Services\ClaimImportService;
use App\Support\CurrentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClaimImportController extends Controller
{
    public function __construct(private readonly ClaimImportService $imports) {}

    public function index(Request $request): Response
    {
        $account = CurrentAccount::resolve($request);

        return Inertia::render('claims/import', [
            'imports' => ClaimImport::query()->with('importer:id,name')->where('account_type', $account->value)->latest()->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->is_admin, 403);
        $account = CurrentAccount::resolve($request);
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480'],
        ]);

        $import = $this->imports->import($validated['file'], $account->value, $request->user());

        return back()->with('success', "Import completed: {$import->created_count} created, {$import->updated_count} updated, {$import->skipped_count} skipped.");
    }
}

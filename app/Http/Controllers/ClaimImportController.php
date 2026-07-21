<?php

namespace App\Http\Controllers;

use App\Models\ClaimImport;
use App\Services\ClaimImportService;
use App\Support\CurrentAccount;
use Illuminate\Http\JsonResponse;
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
            'activeImportId' => ClaimImport::query()
                ->where('account_type', $account->value)
                ->whereIn('status', ['queued', 'processing'])
                ->latest('id')
                ->value('id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->is_admin, 403);
        $account = CurrentAccount::resolve($request);
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480'],
        ]);

        $hasActiveImport = ClaimImport::query()
            ->where('account_type', $account->value)
            ->whereIn('status', ['queued', 'processing'])
            ->exists();
        if ($hasActiveImport) {
            return back()->withErrors(['file' => 'A Tricity import is already in progress.']);
        }

        $import = $this->imports->queue($validated['file'], $account->value, $request->user());

        return back()->with('success', "{$import->file_name} was queued for import.");
    }

    public function progress(Request $request, ClaimImport $claimImport): JsonResponse
    {
        $account = CurrentAccount::resolve($request);
        abort_unless($claimImport->account_type === $account->value, 404);

        $claimImport->refresh();
        $percentage = $claimImport->total_rows > 0
            ? min(100, (int) round(($claimImport->processed_rows / $claimImport->total_rows) * 100))
            : ($claimImport->status === 'completed' ? 100 : 0);

        return response()->json([
            'id' => $claimImport->id,
            'status' => $claimImport->status,
            'total_rows' => $claimImport->total_rows,
            'processed_rows' => $claimImport->processed_rows,
            'created_count' => $claimImport->created_count,
            'updated_count' => $claimImport->updated_count,
            'skipped_count' => $claimImport->skipped_count,
            'failed_count' => $claimImport->failed_count,
            'error_message' => $claimImport->error_message,
            'progress_percentage' => $percentage,
        ]);
    }
}

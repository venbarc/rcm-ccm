<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\ClaimImport;
use App\Support\CurrentAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $account = CurrentAccount::resolve($request);
        $claims = Claim::query()->where('account_type', $account->value);

        return Inertia::render('dashboard', [
            'summary' => [
                'total' => (clone $claims)->count(),
                'unassigned' => (clone $claims)->whereNull('assigned_to')->count(),
                'open_balance' => (clone $claims)->whereNotIn('status', ['paid', 'closed'])->sum('balance'),
                'worked_today' => (clone $claims)->whereDate('updated_at', today())->count(),
            ],
            'recentImports' => ClaimImport::query()
                ->with('importer:id,name')
                ->where('account_type', $account->value)
                ->latest()->limit(5)->get(),
        ]);
    }
}

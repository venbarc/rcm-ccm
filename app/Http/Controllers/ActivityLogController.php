<?php

namespace App\Http\Controllers;

use App\Models\ClaimActivity;
use App\Models\User;
use App\Support\CurrentAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $account = CurrentAccount::resolve($request);
        $query = ClaimActivity::query()->with(['user:id,name,email', 'claim:id,external_id,patient_name'])->where('account_type', $account->value);
        $query->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')));
        $query->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')));

        return Inertia::render('activity-logs/index', [
            'activities' => $query->latest()->paginate(30)->withQueryString(),
            'filters' => $request->only(['action', 'user_id']),
            'actions' => ClaimActivity::query()->where('account_type', $account->value)->distinct()->orderBy('action')->pluck('action'),
            'users' => User::query()->where('is_approved', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}

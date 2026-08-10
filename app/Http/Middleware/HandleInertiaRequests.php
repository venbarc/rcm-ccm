<?php

namespace App\Http\Middleware;

use App\Enums\AccountType;
use App\Support\AccountContext;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
        $user = $request->user();
        $activeAccount = $request->session()->get('account_type', $user?->defaultAccountType());

        if ($user && $activeAccount && ! $user->canAccessAccount($activeAccount)) {
            $activeAccount = $user->defaultAccountType();

            if ($activeAccount) {
                $request->session()->put('account_type', $activeAccount);
            } else {
                $request->session()->forget('account_type');
            }
        }

        $activeAccount = $user ? AccountContext::activeAccountType($request) : null;

        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'app' => [
                'timezone' => config('app.timezone'),
            ],
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user ? [
                    ...$user->only(['id', 'name', 'email', 'email_verified_at', 'is_admin']),
                    'account_types' => $user->allowedAccountTypes(),
                ] : null,
            ],
            'activeAccount' => $activeAccount,
            'adminMembership' => function () use ($activeAccount, $user): ?array {
                if (! $user || $user->is_admin || ! $activeAccount) {
                    return null;
                }

                $administrator = $user->adminsForAccount($activeAccount)
                    ->where('users.is_admin', true)
                    ->select(['users.id', 'users.name'])
                    ->first();

                return $administrator ? [
                    'id' => $administrator->id,
                    'name' => $administrator->name,
                ] : null;
            },
            'accountTypes' => AccountType::options(),
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}

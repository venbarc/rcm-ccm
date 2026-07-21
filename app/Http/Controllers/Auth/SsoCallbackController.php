<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SsoLoginCompleter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SsoCallbackController extends Controller
{
    public function __construct(private readonly SsoLoginCompleter $loginCompleter) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $token = (string) $request->input('token', '');
        $state = (string) $request->input('state', '');

        abort_unless(preg_match('/^[0-9a-f]{64}$/', $token) === 1, 403, 'Invalid SSO token.');

        $oneAccessUrl = (string) config('sso.oneaccess_url');
        $apiKey = (string) config('sso.api_key');
        abort_if($oneAccessUrl === '' || $apiKey === '', 500, 'One Access SSO is not configured.');
        abort_if(app()->isProduction() && ! str_starts_with($oneAccessUrl, 'https://'), 500, 'One Access must use HTTPS.');

        $timestamp = time();

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'X-SSO-Signature' => hash_hmac('sha256', "{$token}:{$timestamp}", $apiKey),
                    'X-SSO-Timestamp' => $timestamp,
                ])
                ->post(rtrim($oneAccessUrl, '/').'/api/sso/introspect', [
                    'token' => $token,
                    'project' => 'rcm_ccm',
                ]);
        } catch (\Throwable) {
            abort(503, 'One Access is currently unavailable.');
        }

        abort_unless($response->successful(), 403, 'SSO token is invalid or expired.');

        $email = strtolower(trim((string) $response->json('email')));
        $stateFromHub = (string) $response->json('state');
        abort_unless($stateFromHub !== '' && hash_equals($stateFromHub, $state), 403, 'SSO state mismatch.');
        abort_unless(filter_var($email, FILTER_VALIDATE_EMAIL), 403, 'Invalid SSO identity.');

        $accountRaw = (string) $response->json('account_type');
        $account = AccountType::tryFrom($accountRaw);
        abort_unless($account?->isReady(), 403, 'This CCM account is not available yet.');

        $additionalEmails = array_filter(
            (array) $response->json('additional_emails', []),
            fn (mixed $candidate): bool => is_string($candidate) && filter_var($candidate, FILTER_VALIDATE_EMAIL) !== false,
        );
        $candidateEmails = array_values(array_unique(array_map(
            fn (string $candidate): string => strtolower(trim($candidate)),
            [$email, ...$additionalEmails],
        )));
        $matches = User::query()->whereIn('email', $candidateEmails)->get();
        $isBootstrapAdmin = in_array($email, config('sso.bootstrap_admin_emails', []), true);

        if ($matches->isEmpty()) {
            $user = User::create([
                'name' => $response->json('name') ?: Str::headline(Str::before($email, '@')),
                'email' => $email,
                'password' => Str::random(64),
                'is_admin' => $isBootstrapAdmin,
                'can_assign_claims' => $isBootstrapAdmin,
                'account_types' => [$account->value],
            ]);

            return $this->loginCompleter->complete($request, $user, $account);
        }

        if ($matches->count() === 1) {
            $user = $matches->first();
            if ($isBootstrapAdmin && ! $user->is_admin) {
                $user->update(['is_admin' => true, 'can_assign_claims' => true]);
            }

            return $this->loginCompleter->complete($request, $user, $account);
        }

        $request->session()->put('sso_choose_account', [
            'user_ids' => $matches->pluck('id')->all(),
            'account_type' => $account->value,
        ]);

        return redirect()->route('sso.choose-account');
    }
}

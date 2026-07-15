<?php

namespace Tests\Feature\Auth;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_access_creates_an_approved_tricity_user_and_redirects_to_dashboard(): void
    {
        config()->set('sso.api_key', 'test-key');
        config()->set('sso.bootstrap_admin_emails', []);
        Http::fake(['*/api/sso/introspect' => Http::response([
            'email' => 'agent@example.com',
            'additional_emails' => [],
            'name' => 'Claims Agent',
            'account_type' => AccountType::Tricity->value,
            'state' => 'expected-state',
        ])]);

        $response = $this->post('/sso/callback', [
            'token' => str_repeat('a', 64),
            'state' => 'expected-state',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        Http::assertSent(fn ($request): bool => $request['project'] === 'rcm_ccm');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'agent@example.com',
            'is_approved' => true,
        ]);
        $this->assertNotNull(User::where('email', 'agent@example.com')->value('email_verified_at'));
    }

    public function test_verified_additional_email_matches_an_existing_local_user(): void
    {
        config()->set('sso.api_key', 'test-key');
        $user = User::factory()->unverified()->create([
            'email' => 'local@example.com',
            'is_approved' => false,
        ]);
        Http::fake(['*/api/sso/introspect' => Http::response([
            'email' => 'main@example.com',
            'additional_emails' => ['local@example.com'],
            'name' => 'Existing Agent',
            'account_type' => AccountType::Tricity->value,
            'state' => 'expected-state',
        ])]);

        $response = $this->post('/sso/callback', [
            'token' => str_repeat('b', 64),
            'state' => 'expected-state',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
        $this->assertTrue($user->refresh()->is_approved);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_pending_accounts_are_rejected_by_the_spoke(): void
    {
        config()->set('sso.api_key', 'test-key');
        Http::fake(['*/api/sso/introspect' => Http::response([
            'email' => 'agent@example.com',
            'additional_emails' => [],
            'name' => 'Claims Agent',
            'account_type' => AccountType::Principle->value,
            'state' => 'expected-state',
        ])]);

        $this->post('/sso/callback', [
            'token' => str_repeat('c', 64),
            'state' => 'expected-state',
        ])->assertForbidden();
    }
}

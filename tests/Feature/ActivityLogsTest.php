<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_logs_match_rcm_member_metrics_and_status_summary(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Team Admin']);
        $agent = User::factory()->create([
            'name' => 'Worked Agent',
            'account_types' => [AccountType::Tricity->value],
        ]);
        GroupMember::create([
            'admin_id' => $admin->id,
            'user_id' => $agent->id,
            'account_type' => AccountType::Tricity->value,
        ]);

        Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'ACT-100',
            'patient_name' => 'Activity Patient',
            'procedure_code' => '99490',
            'assigned_to' => $agent->id,
            'work_status' => 'appeal',
            'true_balance' => 75,
        ]);
        Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'ACT-100',
            'patient_name' => 'Activity Patient',
            'procedure_code' => '99439',
            'assigned_to' => $agent->id,
            'work_status' => 'paid',
            'true_balance' => 25,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/activity-logs');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('activity-logs/index')
            ->where('metrics.data', function ($metrics) use ($agent): bool {
                $metric = collect($metrics)->firstWhere('user_id', $agent->id);

                return $metric !== null
                    && $metric['total_lines'] === 2
                    && $metric['worked_lines'] === 2
                    && $metric['closed_lines'] === 1
                    && $metric['total_balance'] === 100.0
                    && $metric['closed_balance'] === 25.0;
            })
            ->where('statusSummary', function ($statuses): bool {
                $appeal = collect($statuses)->firstWhere('status', 'appeal');
                $paid = collect($statuses)->firstWhere('status', 'paid');

                return $appeal['count'] === 1
                    && $appeal['amount'] === 75.0
                    && $paid['count'] === 1
                    && $paid['amount'] === 25.0;
            }));

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get("/activity-logs/users/{$agent->id}/worked-claim-lines")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('activity-logs/worked-claim-lines')
                ->where('user.id', $agent->id)
                ->has('workedLines.data', 2));

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->getJson('/activity-logs/status-details?status=appeal')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.cpt_code', '99490');
    }
}

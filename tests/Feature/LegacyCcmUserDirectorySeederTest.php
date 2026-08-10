<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\GroupMember;
use App\Models\User;
use Database\Seeders\LegacyCcmUserDirectorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LegacyCcmUserDirectorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_users_and_account_specific_managers_are_imported_idempotently(): void
    {
        $benedict = User::factory()->create([
            'name' => 'Benedict Barcebal',
            'email' => 'benedict.barcebal@cfoutsourcing.com',
            'is_admin' => true,
        ]);
        $paul = User::factory()->create([
            'name' => 'Paul Librea',
            'email' => 'paul.librea@cfrevenuecycle.com',
            'is_admin' => true,
        ]);

        $this->seed(LegacyCcmUserDirectorySeeder::class);
        $this->seed(LegacyCcmUserDirectorySeeder::class);

        $abby = User::query()->where('email', 'abyssel.gobris@cfoutsourcing.com')->firstOrFail();

        $this->assertFalse($abby->is_admin);
        $this->assertContains(AccountType::Tricity->value, $abby->account_types);
        $this->assertContains(AccountType::Principle->value, $abby->account_types);
        $this->assertSame(1, User::query()->where('email', $abby->email)->count());
        $this->assertSame(2, GroupMember::query()->where('user_id', $abby->id)->count());
        $this->assertDatabaseHas('group_members', [
            'admin_id' => $paul->id,
            'user_id' => $abby->id,
            'account_type' => AccountType::Tricity->value,
        ]);
        $this->assertDatabaseHas('group_members', [
            'admin_id' => $benedict->id,
            'user_id' => $abby->id,
            'account_type' => AccountType::Principle->value,
        ]);

        $this->actingAs($benedict)
            ->withSession(['account_type' => AccountType::Principle->value])
            ->get('/user-management')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('users.data', function ($users) use ($benedict): bool {
                    $abby = collect($users)->firstWhere('email', 'abyssel.gobris@cfoutsourcing.com');

                    return $abby !== null
                        && $abby['admins'][0]['id'] === $benedict->id
                        && $abby['can_manage'] === true;
                }));

        $this->actingAs($paul)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/user-management')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('users.data', function ($users) use ($paul): bool {
                    $abby = collect($users)->firstWhere('email', 'abyssel.gobris@cfoutsourcing.com');

                    return $abby !== null
                        && $abby['admins'][0]['id'] === $paul->id
                        && $abby['can_manage'] === true;
                }));
    }
}

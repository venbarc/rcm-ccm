<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LegacyCcmUserDirectorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->users() as $directoryUser) {
            $email = strtolower($directoryUser['email']);
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

            if ($user) {
                if (! $user->is_admin) {
                    $user->update([
                        'account_types' => array_values(array_unique([
                            ...($user->account_types ?? []),
                            ...$directoryUser['account_types'],
                        ])),
                    ]);
                }

                continue;
            }

            User::query()->create([
                'name' => $directoryUser['name'],
                'email' => $email,
                'password' => Str::random(64),
                'is_admin' => false,
                'account_types' => $directoryUser['account_types'],
            ]);
        }

        $this->assignAccountManager(
            'paul.librea@cfrevenuecycle.com',
            'abyssel.gobris@cfoutsourcing.com',
            AccountType::Tricity->value,
        );
        $this->assignAccountManager(
            'benedict.barcebal@cfoutsourcing.com',
            'abyssel.gobris@cfoutsourcing.com',
            AccountType::Principle->value,
        );
    }

    private function assignAccountManager(string $adminEmail, string $memberEmail, string $account): void
    {
        $admin = User::query()->whereRaw('LOWER(email) = ?', [strtolower($adminEmail)])->first();
        $member = User::query()->whereRaw('LOWER(email) = ?', [strtolower($memberEmail)])->first();

        if (! $admin?->is_admin || ! $member || ! $member->canAccessAccount($account)) {
            return;
        }

        GroupMember::query()->firstOrCreate(
            ['user_id' => $member->id, 'account_type' => $account],
            ['admin_id' => $admin->id],
        );
    }

    /**
     * User identities and account access migrated from the existing CCM directory.
     * Passwords and administrator roles are intentionally not copied.
     *
     * @return array<int, array{name: string, email: string, account_types: array<int, string>}>
     */
    private function users(): array
    {
        return [
            ['name' => 'Abyssel Gobris', 'email' => 'abyssel.gobris@cfoutsourcing.com', 'account_types' => ['wc_health', 'tricity_pain_associates', 'principle_spine_and_pain']],
            ['name' => 'Aira Peteza', 'email' => 'aira.peteza@cfoutsourcing.com', 'account_types' => ['principle_spine_and_pain', 'tricity_pain_associates', 'wc_health']],
            ['name' => 'Andrea', 'email' => 'andrea.montes@wc-health.com', 'account_types' => ['wc_health', 'tricity_pain_associates']],
            ['name' => 'Claire Escaner', 'email' => 'claire.escaner@cfoutsourcing.com', 'account_types' => ['tricity_pain_associates']],
            ['name' => 'Cyra Mae Olitoquit', 'email' => 'cyra.olitoquit@cfoutsourcing.com', 'account_types' => ['wc_health']],
            ['name' => 'Desiree Binuya', 'email' => 'desiree.binuya@cfoutsourcing.com', 'account_types' => ['wc_health', 'tricity_pain_associates', 'principle_spine_and_pain']],
            ['name' => 'Esmeralda De Leon', 'email' => 'e.deleon@wc-health.com', 'account_types' => ['tricity_pain_associates', 'wc_health']],
            ['name' => 'Febe Alfonso', 'email' => 'febe.alfonso@cfcareconnect.com', 'account_types' => ['wc_health', 'tricity_pain_associates', 'principle_spine_and_pain']],
            ['name' => 'Gemroe Romero', 'email' => 'gemroe.romero@cfrevenuecycle.com', 'account_types' => ['wc_health', 'tricity_pain_associates', 'principle_spine_and_pain']],
            ['name' => 'James Son Jamandron', 'email' => 'james.jamandron@cfcareconnect.com', 'account_types' => ['principle_spine_and_pain', 'wc_health', 'tricity_pain_associates']],
            ['name' => 'Jayvee Totanes', 'email' => 'jayvee.totanes@cfcareconnect.com', 'account_types' => ['wc_health', 'tricity_pain_associates', 'principle_spine_and_pain']],
            ['name' => 'Kate Dilao', 'email' => 'kate.dilao@cfcareconnect.com', 'account_types' => ['wc_health', 'tricity_pain_associates', 'principle_spine_and_pain']],
            ['name' => 'Katrina Ocampo', 'email' => 'katrina.ocampo@cfoutsourcing.com', 'account_types' => ['wc_health', 'principle_spine_and_pain']],
            ['name' => 'Kenny Fukumoto', 'email' => 'kenny@cfoutsourcing.com', 'account_types' => ['wc_health', 'tricity_pain_associates', 'principle_spine_and_pain']],
            ['name' => 'Kimberly Sambere', 'email' => 'kimberly.sambere@cfoutsourcing.com', 'account_types' => ['wc_health']],
            ['name' => 'Kristine Thompson', 'email' => 'kristine@wc-health.com', 'account_types' => ['wc_health', 'tricity_pain_associates', 'principle_spine_and_pain']],
            ['name' => 'LC Castro', 'email' => 'louise.castro@cfoutsourcing.com', 'account_types' => ['tricity_pain_associates', 'wc_health']],
            ['name' => 'Maria Edaine Pinuela', 'email' => 'maria.pinuela@cfoutsourcing.com', 'account_types' => ['wc_health']],
            ['name' => 'Maria Solis', 'email' => 'maria.solis@cfcareconnect.com', 'account_types' => ['wc_health']],
            ['name' => 'Mariella Calderon', 'email' => 'mariella.calderon@cfoutsourcing.com', 'account_types' => ['principle_spine_and_pain', 'tricity_pain_associates', 'wc_health']],
            ['name' => 'Nelly Ochoa', 'email' => 'nelly.ochoa@cfoutsourcing.com', 'account_types' => ['tricity_pain_associates', 'wc_health', 'principle_spine_and_pain']],
            ['name' => 'Paul Librea', 'email' => 'paul.librea@cfrevenuecycle.com', 'account_types' => ['wc_health', 'tricity_pain_associates', 'principle_spine_and_pain']],
            ['name' => 'Renzel Quebite', 'email' => 'renzel.quebite@cfoutsourcing.com', 'account_types' => ['wc_health', 'tricity_pain_associates', 'principle_spine_and_pain']],
            ['name' => 'Stephany Guerrero', 'email' => 'stephany.guerrero@cfstaffingsolutions.com', 'account_types' => ['wc_health', 'tricity_pain_associates', 'principle_spine_and_pain']],
            ['name' => 'Vermark Catimbang', 'email' => 'vermark.catimbang@cfoutsourcing.com', 'account_types' => ['wc_health', 'tricity_pain_associates', 'principle_spine_and_pain']],
            ['name' => 'Wendy Ocampo', 'email' => 'rwendelyn.ocampo@cfoutsourcing.com', 'account_types' => ['wc_health']],
            ['name' => 'Whin Janda', 'email' => 'w.janda@wc-health.com', 'account_types' => ['wc_health']],
            ['name' => 'Yoninje', 'email' => 'y.eason@wc-health.com', 'account_types' => ['wc_health']],
            ['name' => 'Yuann Rocas', 'email' => 'yuann.rocas@cfoutsourcing.com', 'account_types' => ['wc_health', 'tricity_pain_associates', 'principle_spine_and_pain']],
        ];
    }
}

<?php

namespace App\Models;

use App\Enums\AccountType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'is_approved',
        'can_assign_claims',
        'account_types',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_approved' => 'boolean',
            'can_assign_claims' => 'boolean',
            'account_types' => 'array',
        ];
    }

    /** @return array<int, string> */
    public function allowedAccountTypes(): array
    {
        return $this->is_admin
            ? AccountType::values()
            : array_values(array_intersect($this->account_types ?? [], AccountType::values()));
    }

    public function canAccessAccount(AccountType|string $account): bool
    {
        $value = $account instanceof AccountType ? $account->value : $account;

        return in_array($value, $this->allowedAccountTypes(), true);
    }

    public function canAssignClaims(): bool
    {
        return $this->is_admin || $this->can_assign_claims;
    }
}

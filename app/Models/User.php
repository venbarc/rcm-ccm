<?php

namespace App\Models;

use App\Enums\AccountType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        return $this->is_admin;
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'group_members', 'admin_id', 'user_id')
            ->withPivot('account_type')
            ->withTimestamps();
    }

    public function membersForAccount(string $account): BelongsToMany
    {
        return $this->members()->wherePivot('account_type', $account);
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'group_members', 'user_id', 'admin_id')
            ->withPivot('account_type')
            ->withTimestamps();
    }

    public function adminsForAccount(string $account): BelongsToMany
    {
        return $this->admins()->wherePivot('account_type', $account);
    }

    public function groupMembershipsAsAdmin(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'admin_id');
    }

    public function groupMembershipsAsMember(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'user_id');
    }
}

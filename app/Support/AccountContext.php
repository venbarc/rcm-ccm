<?php

namespace App\Support;

use App\Enums\AccountType;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AccountContext
{
    private static ?string $forcedAccountType = null;

    public static function activeAccountType(?Request $request = null): string
    {
        if (in_array(self::$forcedAccountType, AccountType::values(), true)) {
            return self::$forcedAccountType;
        }

        $request ??= app()->bound('request') ? app(Request::class) : null;
        $activeAccountType = null;

        if ($request instanceof Request) {
            try {
                if ($request->hasSession()) {
                    $activeAccountType = $request->session()->get('account_type');
                }
            } catch (\Throwable) {
                $activeAccountType = null;
            }

            $activeAccountType ??= $request->user()?->defaultAccountType();
        }

        return in_array($activeAccountType, AccountType::values(), true)
            ? $activeAccountType
            : AccountType::Tricity->value;
    }

    public static function activeAccount(?Request $request = null): AccountType
    {
        return AccountType::tryFrom(self::activeAccountType($request)) ?? AccountType::Tricity;
    }

    public static function scopedTable(string $defaultTable, ?string $accountType = null): string
    {
        $account = AccountType::tryFrom($accountType ?? self::activeAccountType()) ?? AccountType::Tricity;
        $prefix = $account->tablePrefix();

        return $prefix !== '' && ! str_starts_with($defaultTable, $prefix)
            ? $prefix.$defaultTable
            : $defaultTable;
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function runWith(string $accountType, callable $callback): mixed
    {
        if (! in_array($accountType, AccountType::values(), true)) {
            throw new InvalidArgumentException("Unsupported account context [{$accountType}].");
        }

        $previous = self::$forcedAccountType;
        self::$forcedAccountType = $accountType;

        try {
            return $callback();
        } finally {
            self::$forcedAccountType = $previous;
        }
    }
}

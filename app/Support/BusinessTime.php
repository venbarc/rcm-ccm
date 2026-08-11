<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class BusinessTime
{
    public static function timezone(): string
    {
        return (string) config('app.business_timezone', 'America/Los_Angeles');
    }

    public static function today(): CarbonInterface
    {
        return Carbon::today(self::timezone());
    }

    public static function now(): CarbonInterface
    {
        return Carbon::now(self::timezone());
    }

    public static function dayStart(mixed $value): ?CarbonInterface
    {
        $value = trim((string) ($value ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value, self::timezone());

            if ($date === false || $date->format('Y-m-d') !== $value) {
                return null;
            }

            return $date->startOfDay()->setTimezone((string) config('app.timezone', 'UTC'));
        } catch (\Throwable) {
            return null;
        }
    }

    public static function display(CarbonInterface $value): CarbonInterface
    {
        return $value->copy()->setTimezone(self::timezone());
    }
}

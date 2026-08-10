<?php

namespace App\Enums;

enum AccountType: string
{
    case Tricity = 'tricity_pain_associates';
    case Principle = 'principle_spine_and_pain';
    case WcHealth = 'wc_health';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $account): string => $account->value, self::cases());
    }

    /** @return array<int, array{value: string, label: string, ready: bool}> */
    public static function options(): array
    {
        return array_map(fn (self $account): array => [
            'value' => $account->value,
            'label' => $account->label(),
            'ready' => $account->isReady(),
        ], self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Tricity => 'Tricity Pain Associates',
            self::Principle => 'Principle Spine and Pain',
            self::WcHealth => 'WC Health',
        };
    }

    public function isReady(): bool
    {
        return in_array($this, [self::Tricity, self::Principle], true);
    }

    public function tablePrefix(): string
    {
        return match ($this) {
            self::Principle => 'principle_',
            self::Tricity, self::WcHealth => '',
        };
    }
}

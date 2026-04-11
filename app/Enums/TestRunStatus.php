<?php

namespace App\Enums;

enum TestRunStatus: string
{
    case Pass    = 'pass';
    case Fail    = 'fail';
    case Blocked = 'blocked';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match($this) {
            self::Pass    => 'Pass',
            self::Fail    => 'Fail',
            self::Blocked => 'Blocked',
            self::Skipped => 'Skipped',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pass    => 'emerald',
            self::Fail    => 'red',
            self::Blocked => 'amber',
            self::Skipped => 'slate',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

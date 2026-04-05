<?php

namespace App\Enums;

enum TestCaseType: string
{
    case Manual    = 'manual';
    case Automated = 'automated';

    public function label(): string
    {
        return match($this) {
            self::Manual    => 'Manual',
            self::Automated => 'Automated',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

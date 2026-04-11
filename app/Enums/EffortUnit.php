<?php

namespace App\Enums;

enum EffortUnit: string
{
    case Points = 'points';
    case Hours  = 'hours';

    public function label(): string
    {
        return match ($this) {
            self::Points => 'Story Points',
            self::Hours  => 'Hours',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Points => 'pts',
            self::Hours  => 'hrs',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

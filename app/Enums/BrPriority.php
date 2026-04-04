<?php

namespace App\Enums;

enum BrPriority: string
{
    case Critical = 'critical';
    case High     = 'high';
    case Medium   = 'medium';
    case Low      = 'low';

    public function label(): string
    {
        return match($this) {
            self::Critical => 'Critical',
            self::High     => 'High',
            self::Medium   => 'Medium',
            self::Low      => 'Low',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Critical => 'red',
            self::High     => 'orange',
            self::Medium   => 'amber',
            self::Low      => 'slate',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

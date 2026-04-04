<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Active   = 'active';
    case OnHold   = 'on_hold';
    case Archived = 'archived';

    public function label(): string
    {
        return match($this) {
            self::Active   => 'Active',
            self::OnHold   => 'On Hold',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Active   => 'emerald',
            self::OnHold   => 'amber',
            self::Archived => 'slate',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

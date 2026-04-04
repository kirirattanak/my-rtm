<?php

namespace App\Enums;

enum RequirementStatus: string
{
    case Draft       = 'draft';
    case Review      = 'review';
    case Approved    = 'approved';
    case Implemented = 'implemented';
    case Deprecated  = 'deprecated';

    public function label(): string
    {
        return match($this) {
            self::Draft       => 'Draft',
            self::Review      => 'In Review',
            self::Approved    => 'Approved',
            self::Implemented => 'Implemented',
            self::Deprecated  => 'Deprecated',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft       => 'slate',
            self::Review      => 'blue',
            self::Approved    => 'emerald',
            self::Implemented => 'violet',
            self::Deprecated  => 'red',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

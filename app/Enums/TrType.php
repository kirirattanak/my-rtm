<?php

namespace App\Enums;

enum TrType: string
{
    case Functional    = 'functional';
    case NonFunctional = 'non_functional';
    case Constraint    = 'constraint';

    public function label(): string
    {
        return match($this) {
            self::Functional    => 'Functional',
            self::NonFunctional => 'Non-Functional',
            self::Constraint    => 'Constraint',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

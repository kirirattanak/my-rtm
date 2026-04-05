<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo        = 'todo';
    case InProgress  = 'in_progress';
    case Done        = 'done';
    case Cancelled   = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Todo       => 'To Do',
            self::InProgress => 'In Progress',
            self::Done       => 'Done',
            self::Cancelled  => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Todo       => 'slate',
            self::InProgress => 'blue',
            self::Done       => 'emerald',
            self::Cancelled  => 'red',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

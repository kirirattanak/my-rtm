<?php

namespace App\Enums;

enum TaskCategory: string
{
    case Development   = 'development';
    case Documentation = 'documentation';
    case Testing       = 'testing';
    case Design        = 'design';
    case DevOps        = 'devops';
    case BugFix        = 'bug_fix';
    case Research      = 'research';
    case Other         = 'other';

    public function label(): string
    {
        return match($this) {
            self::Development   => 'Development',
            self::Documentation => 'Documentation',
            self::Testing       => 'Testing',
            self::Design        => 'Design',
            self::DevOps        => 'DevOps',
            self::BugFix        => 'Bug Fix',
            self::Research      => 'Research',
            self::Other         => 'Other',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

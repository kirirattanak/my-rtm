<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin            = 'admin';
    case ProjectManager   = 'project_manager';
    case BusinessAnalyst  = 'business_analyst';
    case Developer        = 'developer';
    case Tester           = 'tester';
    case Viewer           = 'viewer';

    public function label(): string
    {
        return match($this) {
            self::Admin           => 'Admin',
            self::ProjectManager  => 'Project Manager',
            self::BusinessAnalyst => 'Business Analyst',
            self::Developer       => 'Developer',
            self::Tester          => 'Tester',
            self::Viewer          => 'Viewer',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

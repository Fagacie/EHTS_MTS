<?php

namespace App\Enums;

enum RoleSlug: string
{
    case Administrator = 'administrator';
    case Instructor = 'instructor';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Instructor => 'Instructor',
            self::Customer => 'Trainee / Customer',
        };
    }

    public function defaultPortalRoute(): string
    {
        return match ($this) {
            self::Administrator => 'admin-vue.dashboard',
            self::Instructor => 'instructor.dashboard',
            self::Customer => 'portal.dashboard',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Administrator => 'info',
            self::Instructor => 'teal',
            self::Customer => 'success',
        };
    }
}

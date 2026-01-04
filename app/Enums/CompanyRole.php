<?php

declare(strict_types=1);

namespace App\Enums;

enum CompanyRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Manager = 'manager';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner => __('team.roles.owner'),
            self::Admin => __('team.roles.admin'),
            self::Manager => __('team.roles.manager'),
            self::Member => __('team.roles.member'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Owner => 'danger',
            self::Admin => 'warning',
            self::Manager => 'info',
            self::Member => 'zinc',
        };
    }

    public function canManageTeam(): bool
    {
        return in_array($this, [self::Owner, self::Admin]);
    }

    public function canInvite(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Manager]);
    }
}

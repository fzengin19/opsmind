<?php

declare(strict_types=1);

namespace App\Enums;

enum CalendarVisibility: string
{
    case CompanyWide = 'company_wide';
    case MembersOnly = 'members_only';
    case Private = 'private';

    public function label(): string
    {
        return match ($this) {
            self::CompanyWide => __('enums.calendar_visibility.company_wide'),
            self::MembersOnly => __('enums.calendar_visibility.members_only'),
            self::Private => __('enums.calendar_visibility.private'),
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum CalendarType: string
{
    case Default = 'default';
    case Team = 'team';
    case Resource = 'resource';
    case Personal = 'personal';

    public function label(): string
    {
        return match ($this) {
            self::Default => __('enums.calendar_type.default'),
            self::Team => __('enums.calendar_type.team'),
            self::Resource => __('enums.calendar_type.resource'),
            self::Personal => __('enums.calendar_type.personal'),
        };
    }
}

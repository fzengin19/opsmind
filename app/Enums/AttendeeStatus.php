<?php

declare(strict_types=1);

namespace App\Enums;

enum AttendeeStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Bekliyor',
            self::Accepted => 'Kabul Edildi',
            self::Declined => 'Reddedildi',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Accepted => 'success',
            self::Declined => 'danger',
        };
    }
}

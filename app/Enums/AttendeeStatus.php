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
            self::Pending => __('enums.attendee_status.pending'),
            self::Accepted => __('enums.attendee_status.accepted'),
            self::Declined => __('enums.attendee_status.declined'),
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

<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentType: string
{
    case Meeting = 'meeting';
    case Call = 'call';
    case Focus = 'focus';
    case Break = 'break';

    public function label(): string
    {
        return match ($this) {
            self::Meeting => 'Toplantı',
            self::Call => 'Telefon',
            self::Focus => 'Odaklanma',
            self::Break => 'Mola',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Meeting => '#3b82f6',
            self::Call => '#10b981',
            self::Focus => '#8b5cf6',
            self::Break => '#f59e0b',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactType: string
{
    case Customer = 'customer';
    case Vendor = 'vendor';
    case Partner = 'partner';
    case Lead = 'lead';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Müşteri',
            self::Vendor => 'Tedarikçi',
            self::Partner => 'İş Ortağı',
            self::Lead => 'Aday',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Customer => 'success',
            self::Vendor => 'info',
            self::Partner => 'accent',
            self::Lead => 'warning',
        };
    }
}

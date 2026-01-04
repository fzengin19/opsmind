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
            self::Customer => __('enums.contact_type.customer'),
            self::Vendor => __('enums.contact_type.vendor'),
            self::Partner => __('enums.contact_type.partner'),
            self::Lead => __('enums.contact_type.lead'),
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

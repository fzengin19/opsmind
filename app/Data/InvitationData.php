<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CompanyRole;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class InvitationData extends Data
{
    public function __construct(
        #[Required, Email]
        public string $email,
        #[Required]
        public CompanyRole $role,
    ) {}
}

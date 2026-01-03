<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AppointmentType;
use Carbon\Carbon;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class AppointmentData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public readonly ?int $id,
        public readonly int $company_id,
        public readonly string $title,
        public readonly AppointmentType $type,
        public readonly Carbon $start_at,
        public readonly Carbon $end_at,
        public readonly ?string $description = null,
        public readonly bool $all_day = false,
        public readonly ?string $location = null,
        public readonly ?string $color = null,
        public readonly ?string $google_calendar_id = null,
        public readonly ?int $created_by = null,
        /** @var array<int>|null */
        public readonly ?array $attendee_user_ids = null,
        /** @var array<int>|null */
        public readonly ?array $attendee_contact_ids = null,
    ) {}
}

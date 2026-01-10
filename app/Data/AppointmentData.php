<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AppointmentType;
use Carbon\Carbon;
use Livewire\Wireable;
use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class AppointmentData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public readonly ?int $id,

        #[Exists('companies', 'id')]
        public readonly int $company_id,

        #[Exists('calendars', 'id')]
        public readonly int $calendar_id,

        #[Max(100)]
        public readonly string $title,

        public readonly AppointmentType $type,

        public readonly Carbon $start_at,

        #[AfterOrEqual('start_at')]
        public readonly Carbon $end_at,

        public readonly bool $all_day = false,

        #[Max(255)]
        public readonly ?string $location = null,

        public readonly ?string $description = null,

        public readonly ?int $created_by = null,

        /** @var array<int>|null */
        public readonly ?array $attendee_user_ids = null,
    ) {}
}

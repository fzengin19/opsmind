<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CalendarType;
use App\Enums\CalendarVisibility;
use Livewire\Wireable;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class CalendarData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public readonly ?int $id,

        #[Max(100)]
        public readonly string $name,

        #[Regex('/^#[0-9A-Fa-f]{6}$/')]
        public readonly string $color,

        public readonly CalendarType $type,

        public readonly CalendarVisibility $visibility,
    ) {}
}

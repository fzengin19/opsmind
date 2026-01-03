<?php

declare(strict_types=1);

namespace App\Data;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class CompanyData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly ?string $slug,
        public readonly ?string $logo,
        public readonly string $timezone = 'Europe/Istanbul',
        /** @var array<string, mixed>|null */
        public readonly ?array $settings = null,
    ) {}
}

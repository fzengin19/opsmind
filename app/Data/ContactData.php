<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ContactType;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class ContactData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public readonly ?int $id,
        public readonly int $company_id,
        public readonly ContactType $type,
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $company_name = null,
        public readonly ?string $job_title = null,
        public readonly ?string $notes = null,
        /** @var array<string>|null */
        public readonly ?array $tags = null,
        public readonly ?int $created_by = null,
    ) {}

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}

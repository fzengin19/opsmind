<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Carbon\Carbon;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class TaskData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public readonly ?int $id,
        public readonly int $company_id,
        public readonly string $title,
        public readonly TaskStatus $status = TaskStatus::Backlog,
        public readonly TaskPriority $priority = TaskPriority::Medium,
        public readonly ?string $description = null,
        public readonly ?Carbon $due_date = null,
        public readonly ?float $estimated_hours = null,
        public readonly ?int $assignee_id = null,
        public readonly ?int $contact_id = null,
        public readonly ?int $appointment_id = null,
        public readonly int $position = 0,
        /** @var array<array{text: string, done: bool}>|null */
        public readonly ?array $checklist = null,
        public readonly ?int $created_by = null,
    ) {}
}

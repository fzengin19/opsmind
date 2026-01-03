<?php

declare(strict_types=1);

namespace App\Data;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class TaskCommentData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public readonly ?int $id,
        public readonly int $task_id,
        public readonly int $user_id,
        public readonly string $body,
    ) {}
}

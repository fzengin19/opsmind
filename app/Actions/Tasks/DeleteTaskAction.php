<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Models\Task;

class DeleteTaskAction
{
    public function execute(Task $task): void
    {
        $task->delete();
    }
}

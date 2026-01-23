<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Data\TaskData;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class UpdateTaskAction
{
    public function execute(Task $task, TaskData $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            $task->update([
                'title' => $data->title,
                'description' => $data->description,
                'status' => $data->status,
                'priority' => $data->priority,
                'due_date' => $data->due_date,
                'estimated_hours' => $data->estimated_hours,
                'assignee_id' => $data->assignee_id,
                'contact_id' => $data->contact_id,
                'appointment_id' => $data->appointment_id,
                'checklist' => $data->checklist,
            ]);

            return $task->fresh();
        });
    }
}

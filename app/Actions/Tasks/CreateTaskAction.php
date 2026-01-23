<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Data\TaskData;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTaskAction
{
    public function execute(TaskData $data, User $user): Task
    {
        return DB::transaction(function () use ($data, $user) {
            return Task::create([
                'company_id' => $data->company_id,
                'title' => $data->title,
                'description' => $data->description,
                'status' => $data->status,
                'priority' => $data->priority,
                'due_date' => $data->due_date,
                'estimated_hours' => $data->estimated_hours,
                'assignee_id' => $data->assignee_id,
                'contact_id' => $data->contact_id,
                'appointment_id' => $data->appointment_id,
                'position' => $data->position,
                'checklist' => $data->checklist,
                'created_by' => $user->id,
            ]);
        });
    }
}

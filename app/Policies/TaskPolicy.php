<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view any tasks.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentCompany() !== null;
    }

    /**
     * Determine whether the user can view the task.
     */
    public function view(User $user, Task $task): bool
    {
        if ($task->company_id !== $user->currentCompany()?->id) {
            return false;
        }

        return $task->created_by === $user->id || $task->assignee_id === $user->id;
    }

    /**
     * Determine whether the user can create tasks.
     */
    public function create(User $user): bool
    {
        return $user->currentCompany() !== null;
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, Task $task): bool
    {
        if ($task->company_id !== $user->currentCompany()?->id) {
            return false;
        }

        setPermissionsTeamId($user->currentCompany()->id);
        if ($user->hasAnyRole(['admin'])) {
            return true;
        }

        return $task->created_by === $user->id || $task->assignee_id === $user->id;
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $user, Task $task): bool
    {
        if ($task->company_id !== $user->currentCompany()?->id) {
            return false;
        }

        setPermissionsTeamId($user->currentCompany()->id);
        if ($user->hasAnyRole(['admin'])) {
            return true;
        }

        return $task->created_by === $user->id;
    }
}

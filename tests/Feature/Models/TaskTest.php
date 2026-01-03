<?php

declare(strict_types=1);

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Company;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Task Model', function () {
    it('can be created with factory', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect($task)
            ->toBeInstanceOf(Task::class)
            ->id->toBeInt()
            ->title->toBeString();
    });

    it('casts status to TaskStatus enum', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $task = Task::factory()->inProgress()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect($task->status)
            ->toBeInstanceOf(TaskStatus::class)
            ->toBe(TaskStatus::InProgress);
    });

    it('casts priority to TaskPriority enum', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $task = Task::factory()->urgent()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect($task->priority)
            ->toBeInstanceOf(TaskPriority::class)
            ->toBe(TaskPriority::Urgent);
    });

    it('casts checklist to array', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $task = Task::factory()->withChecklist()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect($task->checklist)
            ->toBeArray()
            ->toHaveCount(3)
            ->each->toHaveKeys(['text', 'done']);
    });

    it('has comments relationship', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        TaskComment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        expect($task->fresh()->comments)->toHaveCount(1);
    });

    it('belongs to assignee', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $task = Task::factory()->assignedTo($user)->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect($task->assignee->id)->toBe($user->id);
    });
});

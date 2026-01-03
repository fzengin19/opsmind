<?php

declare(strict_types=1);

use App\Enums\CompanyRole;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Company;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Task Model', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->company->addUser($this->user, CompanyRole::Owner);
    });

    it('can be created with factory', function () {
        $task = Task::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($task)
            ->toBeInstanceOf(Task::class)
            ->id->toBeInt()
            ->title->toBeString();
    });

    it('casts status to TaskStatus enum', function () {
        $task = Task::factory()->inProgress()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($task->status)
            ->toBeInstanceOf(TaskStatus::class)
            ->toBe(TaskStatus::InProgress);
    });

    it('casts priority to TaskPriority enum', function () {
        $task = Task::factory()->urgent()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($task->priority)
            ->toBeInstanceOf(TaskPriority::class)
            ->toBe(TaskPriority::Urgent);
    });

    it('casts checklist to array', function () {
        $task = Task::factory()->withChecklist()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($task->checklist)
            ->toBeArray()
            ->toHaveCount(3)
            ->each->toHaveKeys(['text', 'done']);
    });

    it('has comments relationship', function () {
        $task = Task::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        TaskComment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $this->user->id,
        ]);

        expect($task->fresh()->comments)->toHaveCount(1);
    });

    it('belongs to assignee', function () {
        $task = Task::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'assignee_id' => $this->user->id,
        ]);

        expect($task->assignee->id)->toBe($this->user->id);
    });
});

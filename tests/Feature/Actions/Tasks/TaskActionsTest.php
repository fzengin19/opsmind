<?php

declare(strict_types=1);

use App\Actions\Tasks\CreateTaskAction;
use App\Actions\Tasks\DeleteTaskAction;
use App\Actions\Tasks\UpdateTaskAction;
use App\Data\TaskData;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Company;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

describe('CreateTaskAction', function () {
    it('creates task', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->users()->attach($user->id);
        setPermissionsTeamId($company->id);
        $user->assignRole('member');

        $data = new TaskData(
            id: null,
            company_id: $company->id,
            title: 'Test Task',
            status: TaskStatus::Todo,
            priority: TaskPriority::Medium,
            description: 'Test description',
            due_date: now()->addDays(7),
            estimated_hours: 4.5,
            assignee_id: $user->id,
            contact_id: null,
            appointment_id: null,
            position: 0,
            checklist: null,
            created_by: null,
        );

        $action = app(CreateTaskAction::class);
        $task = $action->execute($data, $user);

        expect($task)->toBeInstanceOf(Task::class);
        expect($task->title)->toBe('Test Task');
        expect($task->status)->toBe(TaskStatus::Todo);
        expect($task->created_by)->toBe($user->id);
    });

    it('creates task with checklist', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->users()->attach($user->id);
        setPermissionsTeamId($company->id);
        $user->assignRole('member');

        $checklist = [
            ['text' => 'First step', 'done' => false],
            ['text' => 'Second step', 'done' => true],
        ];

        $data = new TaskData(
            id: null,
            company_id: $company->id,
            title: 'Task with Checklist',
            status: TaskStatus::Todo,
            priority: TaskPriority::Medium,
            checklist: $checklist,
            created_by: null,
        );

        $action = app(CreateTaskAction::class);
        $task = $action->execute($data, $user);

        expect($task->checklist)->toBe($checklist);
    });
});

describe('UpdateTaskAction', function () {
    it('updates task', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->users()->attach($user->id);
        setPermissionsTeamId($company->id);
        $user->assignRole('member');

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        $data = new TaskData(
            id: $task->id,
            company_id: $company->id,
            title: 'Updated Title',
            status: TaskStatus::InProgress,
            priority: TaskPriority::High,
            created_by: null,
        );

        $action = app(UpdateTaskAction::class);
        $updatedTask = $action->execute($task, $data);

        expect($updatedTask->title)->toBe('Updated Title');
        expect($updatedTask->status)->toBe(TaskStatus::InProgress);
        expect($updatedTask->priority)->toBe(TaskPriority::High);
    });

    it('wraps operations in database transaction', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->users()->attach($user->id);
        setPermissionsTeamId($company->id);
        $user->assignRole('member');

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        $originalTitle = $task->title;

        // Force failure by using non-existent assignee_id (FK constraint violation)
        $data = new TaskData(
            id: $task->id,
            company_id: $company->id,
            title: 'Updated Title',
            status: TaskStatus::Todo,
            created_by: null,
            assignee_id: 999999, // Non-existent user - will cause FK violation
        );

        $action = app(UpdateTaskAction::class);

        expect(fn () => $action->execute($task, $data))
            ->toThrow(\Exception::class);

        // Task should remain unchanged if transaction rolled back
        expect($task->fresh()->title)->toBe($originalTitle);
    });
});

describe('DeleteTaskAction', function () {
    it('deletes task', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->users()->attach($user->id);
        setPermissionsTeamId($company->id);
        $user->assignRole('member');

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect(Task::where('id', $task->id)->exists())->toBeTrue();

        $action = app(DeleteTaskAction::class);
        $action->execute($task);

        expect(Task::where('id', $task->id)->exists())->toBeFalse();
    });
});

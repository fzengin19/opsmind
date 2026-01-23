<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

describe('TaskPolicy', function () {
    it('allows any company user to view tasks', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('viewAny', Task::class))->toBeTrue();
    });

    it('denies user without company to view tasks', function () {
        $user = User::factory()->create();

        $this->actingAs($user);

        expect($user->can('viewAny', Task::class))->toBeFalse();
    });

    it('allows creator to view task', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('view', $task))->toBeTrue();
    });

    it('allows assignee to view task', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $company->addUser($creator, 'member');
        $company->addUser($assignee, 'member');

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $creator->id,
            'assignee_id' => $assignee->id,
        ]);

        $this->actingAs($assignee);
        setPermissionsTeamId($company->id);

        expect($assignee->can('view', $task))->toBeTrue();
    });

    it('denies non-related user from viewing task', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $company->addUser($creator, 'member');
        $company->addUser($otherUser, 'member');

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $creator->id,
        ]);

        $this->actingAs($otherUser);
        setPermissionsTeamId($company->id);

        expect($otherUser->can('view', $task))->toBeFalse();
    });

    it('allows any company user to create tasks', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('create', Task::class))->toBeTrue();
    });

    it('allows creator to update task', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('update', $task))->toBeTrue();
    });

    it('allows assignee to update task', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $company->addUser($creator, 'member');
        $company->addUser($assignee, 'member');

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $creator->id,
            'assignee_id' => $assignee->id,
        ]);

        $this->actingAs($assignee);
        setPermissionsTeamId($company->id);

        expect($assignee->can('update', $task))->toBeTrue();
    });

    it('allows admin to update any task', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $creator = User::factory()->create();
        $admin = User::factory()->create();
        $company->addUser($creator, 'member');
        $company->addUser($admin, 'admin');

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $creator->id,
        ]);

        $this->actingAs($admin);
        setPermissionsTeamId($company->id);

        expect($admin->can('update', $task))->toBeTrue();
    });

    it('denies non-related user from updating task', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $company->addUser($creator, 'member');
        $company->addUser($otherUser, 'member');

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $creator->id,
        ]);

        $this->actingAs($otherUser);
        setPermissionsTeamId($company->id);

        expect($otherUser->can('update', $task))->toBeFalse();
    });

    it('allows creator to delete task', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('delete', $task))->toBeTrue();
    });

    it('allows admin to delete any task', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $creator = User::factory()->create();
        $admin = User::factory()->create();
        $company->addUser($creator, 'member');
        $company->addUser($admin, 'admin');

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $creator->id,
        ]);

        $this->actingAs($admin);
        setPermissionsTeamId($company->id);

        expect($admin->can('delete', $task))->toBeTrue();
    });

    it('denies assignee from deleting task they did not create', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $company->addUser($creator, 'member');
        $company->addUser($assignee, 'member');

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $creator->id,
            'assignee_id' => $assignee->id,
        ]);

        $this->actingAs($assignee);
        setPermissionsTeamId($company->id);

        expect($assignee->can('delete', $task))->toBeFalse();
    });

    it('denies non-related user from deleting task', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $company->addUser($creator, 'member');
        $company->addUser($otherUser, 'member');

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $creator->id,
        ]);

        $this->actingAs($otherUser);
        setPermissionsTeamId($company->id);

        expect($otherUser->can('delete', $task))->toBeFalse();
    });
});

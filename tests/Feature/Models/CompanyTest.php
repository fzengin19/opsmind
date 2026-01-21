<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;

describe('Company Model', function () {
    beforeEach(function () {
        $this->seed(\Database\Seeders\RoleSeeder::class);
    });

    it('can be created with factory', function () {
        $company = Company::factory()->create();

        expect($company)
            ->toBeInstanceOf(Company::class)
            ->id->toBeInt()
            ->name->toBeString()
            ->slug->toBeString();
    });

    it('has users relationship via pivot', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $company->addUser($user, 'member');

        expect($company->users)
            ->toHaveCount(1)
            ->first()->id->toBe($user->id);
    });

    it('can add user with role', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $company->addUser($user, 'admin', null, 'CTO');

        $pivot = $company->users()->first()->pivot;

        expect($pivot->job_title)->toBe('CTO');

        setPermissionsTeamId($company->id);
        expect($user->hasRole('admin'))->toBeTrue();
    });

    it('can remove user', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $company->addUser($user, 'member');
        expect($company->users)->toHaveCount(1);

        $company->removeUser($user);
        expect($company->fresh()->users)->toHaveCount(0);
    });

    it('deletes invitations when removing user', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        // Create an invitation for this user
        \App\Models\Invitation::create([
            'company_id' => $company->id,
            'email' => $user->email,
            'role_name' => 'member',
            'token' => \App\Models\Invitation::generateToken(),
            'expires_at' => now()->addHours(48),
            'invited_by' => $user->id,
            'accepted_at' => now(),
        ]);

        expect($company->invitations)->toHaveCount(1);

        // Remove user should also delete invitations
        $company->addUser($user, 'member');
        $company->removeUser($user);

        expect($company->fresh()->invitations)->toHaveCount(0);
    });

    it('can get owners', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $company->addUser($owner, 'owner');
        $company->addUser($member, 'member');

        expect($company->owners()->get())->toHaveCount(1);
        expect($company->owners()->first()->id)->toBe($owner->id);
    });

    it('has departments relationship', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);

        expect($company->departments)
            ->toHaveCount(1)
            ->first()->id->toBe($department->id);
    });

    it('has contacts relationship', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'owner');

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect($company->contacts)
            ->toHaveCount(1)
            ->first()->id->toBe($contact->id);
    });

    it('has appointments relationship', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'owner');

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect($company->appointments)
            ->toHaveCount(1)
            ->first()->id->toBe($appointment->id);
    });

    it('has tasks relationship', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'owner');

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect($company->tasks)
            ->toHaveCount(1)
            ->first()->id->toBe($task->id);
    });

    it('casts settings to array', function () {
        $company = Company::factory()->create([
            'settings' => ['theme' => 'dark', 'language' => 'tr'],
        ]);

        expect($company->settings)
            ->toBeArray()
            ->toHaveKey('theme', 'dark')
            ->toHaveKey('language', 'tr');
    });
});

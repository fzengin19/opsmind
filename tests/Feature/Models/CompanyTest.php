<?php

declare(strict_types=1);

use App\Enums\CompanyRole;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Company Model', function () {
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
        $user = User::factory()->create();

        $company->addUser($user, CompanyRole::Member);

        expect($company->users)
            ->toHaveCount(1)
            ->first()->id->toBe($user->id);
    });

    it('can add user with role', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $company->addUser($user, CompanyRole::Admin, null, 'CTO');

        $pivot = $company->users()->first()->pivot;

        expect($pivot->role)->toBe('admin');
        expect($pivot->job_title)->toBe('CTO');
    });

    it('can remove user', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $company->addUser($user, CompanyRole::Member);
        expect($company->users)->toHaveCount(1);

        $company->removeUser($user);
        expect($company->fresh()->users)->toHaveCount(0);
    });

    it('can get owners', function () {
        $company = Company::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $company->addUser($owner, CompanyRole::Owner);
        $company->addUser($member, CompanyRole::Member);

        expect($company->owners)->toHaveCount(1);
        expect($company->owners->first()->id)->toBe($owner->id);
    });

    it('can get admins (owners + admins)', function () {
        $company = Company::factory()->create();
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $company->addUser($owner, CompanyRole::Owner);
        $company->addUser($admin, CompanyRole::Admin);
        $company->addUser($member, CompanyRole::Member);

        expect($company->admins)->toHaveCount(2);
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
        $user = User::factory()->create();
        $company->addUser($user, CompanyRole::Owner);

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
        $user = User::factory()->create();
        $company->addUser($user, CompanyRole::Owner);

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
        $user = User::factory()->create();
        $company->addUser($user, CompanyRole::Owner);

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

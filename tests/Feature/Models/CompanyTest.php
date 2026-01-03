<?php

declare(strict_types=1);

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

    it('has users relationship', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        expect($company->users)
            ->toHaveCount(1)
            ->first()->id->toBe($user->id);
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
        $user = User::factory()->create(['company_id' => $company->id]);
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
        $user = User::factory()->create(['company_id' => $company->id]);
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
        $user = User::factory()->create(['company_id' => $company->id]);
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

<?php

declare(strict_types=1);

use App\Enums\CalendarType;
use App\Enums\CalendarVisibility;
use App\Models\Appointment;
use App\Models\Calendar;
use App\Models\Company;
use App\Models\User;

describe('Calendar Model', function () {
    beforeEach(function () {
        $this->seed(\Database\Seeders\RoleSeeder::class);
    });

    it('can be created with factory', function () {
        $calendar = Calendar::factory()->create();

        expect($calendar)
            ->toBeInstanceOf(Calendar::class)
            ->id->toBeInt()
            ->name->toBeString();
    });

    it('casts type to CalendarType enum', function () {
        $calendar = Calendar::factory()->create(['type' => 'personal']);

        expect($calendar->type)->toBe(CalendarType::Personal);
    });

    it('casts visibility to CalendarVisibility enum', function () {
        $calendar = Calendar::factory()->create(['visibility' => 'private']);

        expect($calendar->visibility)->toBe(CalendarVisibility::Private);
    });

    it('belongs to company', function () {
        $company = Company::factory()->create();
        $calendar = Calendar::factory()->create(['company_id' => $company->id]);

        expect($calendar->company->id)->toBe($company->id);
    });

    it('has many appointments', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'owner');

        $calendar = $company->defaultCalendar();

        Appointment::factory()->create([
            'company_id' => $company->id,
            'calendar_id' => $calendar->id,
            'created_by' => $user->id,
        ]);

        expect($calendar->fresh()->appointments)->toHaveCount(1);
    });

    it('has users through pivot with role', function () {
        $calendar = Calendar::factory()->create();
        $user = User::factory()->create();

        $calendar->users()->attach($user->id, ['role' => 'editor']);

        expect($calendar->users)->toHaveCount(1);
        expect($calendar->users->first()->pivot->role)->toBe('editor');
    });
});

describe('Company Calendar Integration', function () {
    beforeEach(function () {
        $this->seed(\Database\Seeders\RoleSeeder::class);
    });

    it('creates default calendar on company creation', function () {
        $company = Company::factory()->create();

        expect($company->calendars)->toHaveCount(1);
        expect($company->defaultCalendar())
            ->not->toBeNull()
            ->is_default->toBeTrue()
            ->type->toBe(CalendarType::Default);
    });

    it('creates personal calendar when user is added', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $company->addUser($user, 'member');

        // 1 default + 1 personal
        expect($company->fresh()->calendars)->toHaveCount(2);

        $personalCalendar = $company->calendars()
            ->where('type', CalendarType::Personal->value)
            ->first();

        expect($personalCalendar)
            ->not->toBeNull()
            ->visibility->toBe(CalendarVisibility::Private);

        expect($personalCalendar->users->first()->id)->toBe($user->id);
    });

    it('deletes personal calendar when user is removed', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $company->addUser($user, 'member');
        expect($company->calendars)->toHaveCount(2); // 1 default + 1 personal

        $company->removeUser($user);

        // Should only have default calendar left
        expect($company->fresh()->calendars)->toHaveCount(1);
        expect($company->fresh()->calendars->first()->is_default)->toBeTrue();
    });

    it('has calendars relationship', function () {
        $company = Company::factory()->create();

        expect($company->calendars)->toHaveCount(1);
    });

    it('can get default calendar', function () {
        $company = Company::factory()->create();

        $defaultCalendar = $company->defaultCalendar();

        expect($defaultCalendar)->not->toBeNull();
        expect($defaultCalendar->is_default)->toBeTrue();
    });
});

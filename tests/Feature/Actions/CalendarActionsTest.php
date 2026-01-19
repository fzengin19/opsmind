<?php

declare(strict_types=1);

use App\Actions\Calendars\CreateCalendarAction;
use App\Actions\Calendars\DeleteCalendarAction;
use App\Actions\Calendars\UpdateCalendarAction;
use App\Data\CalendarData;
use App\Enums\CalendarType;
use App\Enums\CalendarVisibility;
use App\Models\Appointment;
use App\Models\Calendar;
use App\Models\Company;
use App\Models\User;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

describe('CreateCalendarAction', function () {
    it('creates calendar and attaches user as owner', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'owner');

        $this->actingAs($user);

        $data = new CalendarData(
            id: null,
            name: 'Sales Team',
            color: '#ef4444',
            type: CalendarType::Team,
            visibility: CalendarVisibility::CompanyWide,
        );

        $action = new CreateCalendarAction;
        $calendar = $action->execute($data, $user);

        expect($calendar)
            ->name->toBe('Sales Team')
            ->color->toBe('#ef4444')
            ->type->toBe(CalendarType::Team)
            ->visibility->toBe(CalendarVisibility::CompanyWide)
            ->is_default->toBeFalse();

        expect($calendar->users()->where('role', 'owner')->first()->id)->toBe($user->id);
    });

    it('creates resource calendar', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'owner');

        $this->actingAs($user);

        $data = new CalendarData(
            id: null,
            name: 'Meeting Room A',
            color: '#22c55e',
            type: CalendarType::Resource,
            visibility: CalendarVisibility::CompanyWide,
        );

        $action = new CreateCalendarAction;
        $calendar = $action->execute($data, $user);

        expect($calendar)
            ->name->toBe('Meeting Room A')
            ->type->toBe(CalendarType::Resource);
    });
});

describe('UpdateCalendarAction', function () {
    it('updates name and color', function () {
        $calendar = Calendar::factory()->create([
            'name' => 'Old Name',
            'color' => '#000000',
            'type' => CalendarType::Team,
        ]);

        $data = new CalendarData(
            id: $calendar->id,
            name: 'New Name',
            color: '#ffffff',
            type: CalendarType::Team,
            visibility: CalendarVisibility::CompanyWide,
        );

        $action = new UpdateCalendarAction;
        $updated = $action->execute($calendar, $data);

        expect($updated)
            ->name->toBe('New Name')
            ->color->toBe('#ffffff');
    });

    it('updates visibility for team calendar', function () {
        $calendar = Calendar::factory()->create([
            'type' => CalendarType::Team,
            'visibility' => CalendarVisibility::CompanyWide,
        ]);

        $data = new CalendarData(
            id: $calendar->id,
            name: $calendar->name,
            color: $calendar->color,
            type: CalendarType::Team,
            visibility: CalendarVisibility::MembersOnly,
        );

        $action = new UpdateCalendarAction;
        $updated = $action->execute($calendar, $data);

        expect($updated->visibility)->toBe(CalendarVisibility::MembersOnly);
    });

    it('does not change visibility for personal calendar', function () {
        $calendar = Calendar::factory()->create([
            'type' => CalendarType::Personal,
            'visibility' => CalendarVisibility::Private,
        ]);

        $data = new CalendarData(
            id: $calendar->id,
            name: 'Updated Name',
            color: '#ffffff',
            type: CalendarType::Personal,
            visibility: CalendarVisibility::CompanyWide, // Bu değişmemeli
        );

        $action = new UpdateCalendarAction;
        $updated = $action->execute($calendar, $data);

        expect($updated->visibility)->toBe(CalendarVisibility::Private);
    });
});

describe('DeleteCalendarAction', function () {
    it('moves appointments to default calendar', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'owner');

        $teamCalendar = Calendar::factory()->create([
            'company_id' => $company->id,
            'type' => CalendarType::Team,
        ]);

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'calendar_id' => $teamCalendar->id,
            'created_by' => $user->id,
        ]);

        $action = new DeleteCalendarAction;
        $action->execute($teamCalendar);

        expect(Calendar::find($teamCalendar->id))->toBeNull();
        expect($appointment->fresh()->calendar_id)->toBe($company->defaultCalendar()->id);
    });

    it('throws exception for default calendar', function () {
        $company = Company::factory()->create();
        $defaultCalendar = $company->defaultCalendar();

        $action = new DeleteCalendarAction;

        expect(fn () => $action->execute($defaultCalendar))
            ->toThrow(\DomainException::class);
    });

    it('throws exception for personal calendar', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'owner');

        $personalCalendar = $company->calendars()
            ->where('type', CalendarType::Personal)
            ->first();

        $action = new DeleteCalendarAction;

        expect(fn () => $action->execute($personalCalendar))
            ->toThrow(\DomainException::class);
    });

    it('deletes calendar without appointments', function () {
        $company = Company::factory()->create();
        $calendar = Calendar::factory()->create([
            'company_id' => $company->id,
            'type' => CalendarType::Team,
        ]);

        $action = new DeleteCalendarAction;
        $action->execute($calendar);

        expect(Calendar::find($calendar->id))->toBeNull();
    });
});

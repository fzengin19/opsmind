<?php

declare(strict_types=1);

use App\Actions\Appointments\CreateAppointmentAction;
use App\Actions\Appointments\DeleteAppointmentAction;
use App\Actions\Appointments\RescheduleAppointmentAction;
use App\Actions\Appointments\UpdateAppointmentAction;
use App\Data\AppointmentData;
use App\Enums\AppointmentType;
use App\Enums\AttendeeStatus;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    $this->company = Company::factory()->create();
    createDefaultRolesForCompany($this->company);
    $this->user = User::factory()->create();
    $this->company->addUser($this->user, 'owner');
    $this->calendar = $this->company->defaultCalendar();
});

describe('CreateAppointmentAction', function () {
    it('creates an appointment with attendees', function () {
        $otherUser = User::factory()->create();
        $this->company->addUser($otherUser, 'member');

        $data = new AppointmentData(
            id: null,
            company_id: $this->company->id,
            calendar_id: $this->calendar->id,
            title: 'Test Meeting',
            type: AppointmentType::Meeting,
            start_at: Carbon::now()->addDay(),
            end_at: Carbon::now()->addDay()->addHour(),
            all_day: false,
            location: 'Zoom',
            description: 'Test description',
            attendee_user_ids: [$otherUser->id],
        );

        $action = new CreateAppointmentAction();
        $appointment = $action->execute($data, $this->user);

        expect($appointment)
            ->title->toBe('Test Meeting')
            ->type->toBe(AppointmentType::Meeting)
            ->calendar_id->toBe($this->calendar->id)
            ->created_by->toBe($this->user->id);

        // Creator is auto-added as attendee
        expect($appointment->attendees)->toHaveCount(2);

        $creatorAttendee = $appointment->attendees->firstWhere('user_id', $this->user->id);
        expect($creatorAttendee->status)->toBe(AttendeeStatus::Accepted);
    });

    it('does not duplicate creator in attendees', function () {
        $data = new AppointmentData(
            id: null,
            company_id: $this->company->id,
            calendar_id: $this->calendar->id,
            title: 'Solo Meeting',
            type: AppointmentType::Meeting,
            start_at: Carbon::now()->addDay(),
            end_at: Carbon::now()->addDay()->addHour(),
            attendee_user_ids: [$this->user->id], // Creator also in list
        );

        $action = new CreateAppointmentAction();
        $appointment = $action->execute($data, $this->user);

        // Should only have 1 attendee (creator), not duplicated
        expect($appointment->attendees)->toHaveCount(1);
    });
});

describe('UpdateAppointmentAction', function () {
    it('updates appointment and syncs attendees', function () {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $this->calendar->id,
            'title' => 'Original Title',
            'created_by' => $this->user->id,
        ]);

        $newCalendar = $this->company->calendars()->where('id', '!=', $this->calendar->id)->first()
            ?? $this->calendar;

        $data = new AppointmentData(
            id: $appointment->id,
            company_id: $this->company->id,
            calendar_id: $newCalendar->id,
            title: 'Updated Title',
            type: AppointmentType::Call,
            start_at: Carbon::now()->addDays(2),
            end_at: Carbon::now()->addDays(2)->addHour(),
        );

        $action = new UpdateAppointmentAction();
        $updated = $action->execute($appointment, $data);

        expect($updated)
            ->title->toBe('Updated Title')
            ->type->toBe(AppointmentType::Call);
    });
});

describe('DeleteAppointmentAction', function () {
    it('deletes appointment and attendees cascade', function () {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $this->calendar->id,
            'created_by' => $this->user->id,
        ]);

        $appointment->attendees()->create([
            'user_id' => $this->user->id,
            'status' => AttendeeStatus::Accepted,
        ]);

        $appointmentId = $appointment->id;

        $action = new DeleteAppointmentAction();
        $action->execute($appointment);

        expect(Appointment::find($appointmentId))->toBeNull();
    });
});

describe('RescheduleAppointmentAction', function () {
    it('reschedules appointment to new times', function () {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'calendar_id' => $this->calendar->id,
            'start_at' => Carbon::now(),
            'end_at' => Carbon::now()->addHour(),
        ]);

        $newStart = Carbon::now()->addWeek();
        $newEnd = $newStart->copy()->addHours(2);

        $action = new RescheduleAppointmentAction();
        $rescheduled = $action->execute($appointment, $newStart, $newEnd);

        expect($rescheduled->start_at->toDateTimeString())->toBe($newStart->toDateTimeString());
        expect($rescheduled->end_at->toDateTimeString())->toBe($newEnd->toDateTimeString());
    });
});

<?php

declare(strict_types=1);

use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\AppointmentAttendee;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;

describe('Appointment Model', function () {
    beforeEach(function () {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->company = Company::factory()->create();
        createDefaultRolesForCompany($this->company);
        $this->user = User::factory()->create();
        $this->company->addUser($this->user, 'owner');
    });

    it('can be created with factory', function () {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($appointment)
            ->toBeInstanceOf(Appointment::class)
            ->id->toBeInt()
            ->title->toBeString();
    });

    it('casts type to AppointmentType enum', function () {
        $appointment = Appointment::factory()->meeting()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($appointment->type)
            ->toBeInstanceOf(AppointmentType::class)
            ->toBe(AppointmentType::Meeting);
    });

    it('casts start_at and end_at to Carbon', function () {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($appointment->start_at)->toBeInstanceOf(Carbon::class);
        expect($appointment->end_at)->toBeInstanceOf(Carbon::class);
    });

    it('casts all_day to boolean', function () {
        $appointment = Appointment::factory()->allDay()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        expect($appointment->all_day)->toBeTrue();
    });

    it('has attendees relationship', function () {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        AppointmentAttendee::create([
            'appointment_id' => $appointment->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        expect($appointment->fresh()->attendees)->toHaveCount(1);
    });
});
